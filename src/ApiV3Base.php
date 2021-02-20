<?php
declare(strict_types=1);

namespace esp\weixinapiv3\src;

use esp\core\Debug;
use esp\http\Http;
use esp\weixinapiv3\library\Crypt;
use esp\weixinapiv3\library\Merchant;
use esp\weixinapiv3\library\Service;


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

    public function __construct(Service $service, Merchant $merchant = null)
    {
        $this->service = $service;
        $this->merchant = $merchant;
        $this->_debug = Debug::class();
    }

    protected function debug($val)
    {
        $prev = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        return $this->_debug->relay($val, $prev);
    }

    public function setService(Service $service)
    {
        $this->service = $service;
        return $this;
    }

    public function setMerchant(Merchant $merchant)
    {
        $this->merchant = $merchant;
        return $this;
    }

    public function setCrypt(Crypt $crypt)
    {
        $this->crypt = $crypt;
        return $this;
    }

    /**
     * @param $uri
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

    protected function get(string $api)
    {
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
     * 数据解密
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

        return \openssl_decrypt(substr($ciphertext, 0, -16),
            'aes-256-gcm',
            $aesKey,
            \OPENSSL_RAW_DATA,
            $nonceStr,
            substr($ciphertext, -16),
            $associatedData);
    }


}