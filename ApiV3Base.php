<?php
declare(strict_types=1);

namespace esp\weiPay;

use esp\core\Library;
use esp\http\Http;
use esp\weiPay\library\Crypt;
use esp\weiPay\library\Entity;

abstract class ApiV3Base extends Library
{
    protected string $api = 'https://api.mch.weixin.qq.com';
    protected Entity $entity;
    protected Crypt $crypt;
    private bool $signCheck = true;
    private bool $returnHttp = false;

    public function _init(Entity $entity)
    {
        $this->entity = $entity;
    }

    public function setService(Entity $entity)
    {
        $this->entity = $entity;
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
    protected function sign(string $method, string $uri, string $body = ''): string
    {
        $method = strtoupper($method);
        $mchID = $this->entity->mchID;
        $nonce = sha1(uniqid('', true));
        $time = time();
        $message = "{$method}\n{$uri}\n{$time}\n{$nonce}\n{$body}\n";
        openssl_sign($message, $sign, $this->entity->certEncrypt, 'sha256WithRSAEncryption');
        $ts = 'WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"';
        return sprintf($ts, $mchID, $nonce, $time, $this->entity->certSerial, base64_encode($sign));
    }

    public function signCheck(bool $check)
    {
        $this->signCheck = $check;
        return $this;
    }

    public function returnHttp(bool $check)
    {
        $this->returnHttp = $check;
        return $this;
    }

    protected function get(string $api, array $params = null, array $option = [])
    {
        if ($params) $api = $api . '?' . http_build_query($params);
        if (!isset($option['type'])) $option['type'] = 'get';
        $option['headers'] = [];
        $option['headers']['Authorization'] = $this->sign(strtoupper($option['type']), $api);

        return $this->requestWx($option, $api);
    }


    protected function post(string $api, array $data, array $option = [])
    {
        if (!isset($option['type'])) $option['type'] = 'post';
        $option['headers'] = [];

        if (isset($this->crypt)) {
            $data = $this->crypt->encryptArray($data);
            $option['headers']['Wechatpay-Serial'] = $this->crypt->serial();
        }
        $this->debug($data);
        $data = json_encode($data, 256 | 64);

        $option['headers']['Authorization'] = $this->sign(strtoupper($option['type']), $api, $data);

        return $this->requestWx($option, $api, $data);
    }

    protected function requestWx(array $option, string $api, string $data = null)
    {
        if (!isset($option['type'])) $option['type'] = 'post';
        if (!isset($option['encode'])) $option['encode'] = 'json';
        if (!isset($option['decode'])) $option['decode'] = 'json';
        $option['agent'] = 'laocc/esp HttpClient/cURL';
        $option['header'] = true;
        $option['allow'] = [200, 204];
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

        if ($err = $request->error()) return "Error:{$err}";

        //不签名验证
        if (!$this->signCheck) return $request->data();

        //只要求返回对方响应状态码
        if ($option['returnCode'] ?? 0) return $request->info('code');

        $header = $request->header();
        $json = $request->html();

        $message = "{$header['WECHATPAY-TIMESTAMP']}\n{$header['WECHATPAY-NONCE']}\n{$json}\n";
        if (isset($this->crypt)) {
            $certEncrypt = $this->crypt->public();
        } else {
            $cert = "{$this->entity->publicPath}/{$header['WECHATPAY-SERIAL']}/public.pem";
            $certEncrypt = \openssl_get_publickey(file_get_contents($cert));
        }
        $signature = \base64_decode($header['WECHATPAY-SIGNATURE']);
        $chk = \openssl_verify($message, $signature, $certEncrypt, 'sha256WithRSAEncryption');
        if ($chk !== 1) return "Error:请求收到的结果签名验证失败";

        if ($this->returnHttp) return $request;

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
        openssl_private_decrypt(base64_decode($input), $out, $this->entity->certEncrypt, \OPENSSL_PKCS1_OAEP_PADDING);
        return $out;
    }


    /**
     * 受理通知数据，验签，并解密
     * @param array &$data
     * @return mixed|string
     */
    public function notifyDecrypt(array &$data)
    {
        $serial = getenv('HTTP_WECHATPAY_SERIAL');
        $time = getenv('HTTP_WECHATPAY_TIMESTAMP');
        $nonce = getenv('HTTP_WECHATPAY_NONCE');
        $sign = getenv('HTTP_WECHATPAY_SIGNATURE');
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if (empty($data)) return '未接收到数据';

        $message = "{$time}\n{$nonce}\n{$json}\n";
        if (isset($this->crypt)) {
            $certEncrypt = $this->crypt->public();
        } else {
            $cert = "{$this->entity->publicPath}/{$serial}/public.pem";
            $certEncrypt = \openssl_get_publickey(file_get_contents($cert));
        }
        $chk = \openssl_verify($message, \base64_decode($sign), $certEncrypt, 'sha256WithRSAEncryption');
        if ($chk !== 1) return "wxAPIv3 Sign Error";

        $value = $this->decryptToString($this->entity->apiV3Key,
            $data['resource']['associated_data'],
            $data['resource']['nonce'],
            $data['resource']['ciphertext']);
        if ($value === false) return "数据解密失败";

        return json_decode($value, true);
    }


}