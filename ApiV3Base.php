<?php
declare(strict_types=1);

namespace esp\weiPay;

use esp\core\Debug;
use esp\http\Http;
use esp\weiPay\library\Crypt;
use esp\weiPay\library\Entity;


abstract class ApiV3Base
{
    protected $api = 'https://api.mch.weixin.qq.com';
    protected $service;
    protected $merchant;
    private $_debug;
    /**
     * @var $crypt Crypt
     */
    protected $crypt;

    private $signCheck = true;

    public function __construct(Entity $service)
    {
        $this->service = $service;
        $this->_debug = Debug::class();
    }

    protected function debug($val)
    {
        $prev = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        return $this->_debug->relay($val, $prev);
    }

    public function setService(Entity $service)
    {
        $this->service = $service;
        return $this;
    }

    /**
     * 在接口中需要加密的时候，要设置密钥
     *
     * 参数例如：
     * $data['account_info']['account_number'] = '@' . $bank['bankNumber'];
     * 在值前加@表示此字段需要加密
     *
     * @param Crypt $crypt
     * @return $this
     */
    public function setCrypt(Crypt $crypt)
    {
        $this->crypt = $crypt;
        return $this;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param string $body
     * @return string
     * https://pay.weixin.qq.com/wiki/doc/apiv3/wechatpay/wechatpay4_0.shtml
     */
    protected function sign(string $method, string $uri, string $body = '')
    {
        $method = strtoupper($method);
        $mchID = $this->service->mchID;
        $nonce = sha1(uniqid('', true));
        $time = time();
        $message = "{$method}\n{$uri}\n{$time}\n{$nonce}\n{$body}\n";
        openssl_sign($message, $sign, $this->service->certEncrypt, 'sha256WithRSAEncryption');
        $ts = 'WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"';
        return sprintf($ts, $mchID, $nonce, $time, $this->service->certSerial, base64_encode($sign));
    }

    public function signCheck(bool $check)
    {
        $this->signCheck = $check;
        return $this;
    }

    protected function get(string $api, array $params = null)
    {
        if ($params) $api = $api . '?' . http_build_query($params);
        $option = [];
        $option['type'] = 'get';
        $option['headers'] = [];
        $option['headers']['Authorization'] = $this->sign('GET', $api);

        return $this->requestWx($option, $api,);
    }


    protected function post(string $api, array $data)
    {
        $option = [];
        $option['type'] = 'post';
        $option['headers'] = [];

        if (!is_null($this->crypt)) {
            $data = $this->crypt->encryptArray($data);
            $option['headers']['Wechatpay-Serial'] = $this->crypt->serial();
        }
        $this->debug($data);
        $data = json_encode($data, 256 | 64);

        $option['headers']['Authorization'] = $this->sign('POST', $api, $data);

        return $this->requestWx($option, $api, $data);
    }

    protected function requestWx(array $option, string $api, string $data = null)
    {
        if (!isset($option['type'])) $option['type'] = 'post';
        $option['agent'] = 'laocc/esp HttpClient/cURL';
        $option['encode'] = 'json';
        $option['header'] = true;
        $option['headers']['Accept'] = "application/json";
        $option['headers']['Accept-Language'] = 'zh-CN';
        if ($option['type'] === 'upload') {
            $option['type'] = 'post';
        } else {
            $option['headers']['Content-Type'] = "application/json";
        }

        $http = new Http($option);
        if (!is_null($data)) $http->data($data);

        $request = $http->request($this->api . $api);

        $this->debug($request);

        if ($err = $request->error()) return "wxAPIv3 Error:{$err}";

        //不签名验证
        if (!$this->signCheck) return $request->data();

        $header = $request->header();
        $json = $request->html();
//        print_r($request);

        $cert = _CERT . "/{$header['WECHATPAY-SERIAL']}/public.pem";
        $message = "{$header['WECHATPAY-TIMESTAMP']}\n{$header['WECHATPAY-NONCE']}\n{$json}\n";
        if (!is_null($this->crypt)) {
            $certEncrypt = $this->crypt->public();
        } else {
            $certEncrypt = \openssl_get_publickey(file_get_contents($cert));
        }
        $signature = \base64_decode($header['WECHATPAY-SIGNATURE']);
        $chk = \openssl_verify($message, $signature, $certEncrypt, 'sha256WithRSAEncryption');
        if ($chk !== 1) return "wxAPIv3 Sign Error";

        return $request->data();
    }

    /**
     * 数据解密，
     *
     * 如果是获取证书，解密后，要再次导出公钥：
     * openssl x509 -in cert.pem -pubkey -noout > public.pem
     *
     * @param $aesKey
     * @param $associatedData
     * @param $nonceStr
     * @param $ciphertext
     * @return false|string
     *
     */
    protected function decryptToString($aesKey, $associatedData, $nonceStr, $ciphertext)
    {
        $ciphertext = \base64_decode($ciphertext);

        /**
         * 微信第1版Api中是这样解密的：
         * $xml = openssl_decrypt(base64_decode($code), "AES-256-ECB", md5($key), OPENSSL_RAW_DATA);
         */


        return \openssl_decrypt(substr($ciphertext, 0, -16),
            'aes-256-gcm',
            $aesKey,
            \OPENSSL_RAW_DATA,
            $nonceStr,
            substr($ciphertext, -16),
            $associatedData);
    }

    /**
     * 用服务商私钥解密
     * @param string $input
     * @return mixed
     */
    public function decryptString(string $input)
    {
        openssl_private_decrypt(base64_decode($input), $out, $this->service->certEncrypt, \OPENSSL_PKCS1_OAEP_PADDING);
        return $out;
    }


    /**
     * 受理通知数据，验签，并解密
     * @param $data
     * @return mixed|string
     */
    public function notifyDecrypt($data)
    {
        $serial = getenv('HTTP_WECHATPAY_SERIAL');
        $time = getenv('HTTP_WECHATPAY_TIMESTAMP');
        $nonce = getenv('HTTP_WECHATPAY_NONCE');
        $sign = getenv('HTTP_WECHATPAY_SIGNATURE');
        $json = file_get_contents('php://input');

        $message = "{$time}\n{$nonce}\n{$json}\n";
        if (!is_null($this->crypt)) {
            $certEncrypt = $this->crypt->public();
        } else {
            $cert = _CERT . "/{$serial}/public.pem";
            $certEncrypt = \openssl_get_publickey(file_get_contents($cert));
        }
        $chk = \openssl_verify($message, \base64_decode($sign), $certEncrypt, 'sha256WithRSAEncryption');
        if ($chk !== 1) return "wxAPIv3 Sign Error";

        $value = $this->decryptToString($this->service->apiV3Key,
            $data['resource']['associated_data'],
            $data['resource']['nonce'],
            $data['resource']['ciphertext']);
        if ($value === false) return "数据解密失败";

        return json_decode($value, true);
    }


}