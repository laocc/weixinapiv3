<?php
declare(strict_types=1);

namespace esp\weixinapiv3\src;

use esp\http\Http;
use esp\weixinapiv3\library\Crypt;
use esp\weixinapiv3\library\Merchant;
use esp\weixinapiv3\library\Service;


abstract class Base
{
    protected $api = 'https://api.mch.weixin.qq.com';
    protected $service;
    protected $merchant;
    protected $crypt;

    public function __construct(Service $service, Merchant $merchant = null)
    {
        $this->service = $service;
        $this->merchant = $merchant;
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
        $certIndex = $this->service->certKey;
        $message = "{$method}\n{$uri}\n{$time}\n{$nonce}\n{$body}\n";
        $schema = 'Authorization: WECHATPAY2-SHA256-RSA2048 ';
        openssl_sign($message, $sign, $this->service->certEncrypt, 'sha256WithRSAEncryption');
        $ts = 'mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"';
        return $schema . sprintf($ts, $mchID, $nonce, $time, $certIndex, base64_encode($sign));
    }

    public function get(string $api)
    {
        $option = [];
        $option['encode'] = 'json';
        $option['transfer'] = true;
        $option['agent'] = 'EspHttpClient/cURL';
        $option['headers'][] = "Accept: application/json";
        $option['headers'][] = "Content-Type: application/json";
        $option['headers'][] = $this->sign('GET', $api);

        $http = new Http($option);
        $json = $http->get($this->api . $api);

        return $json;
    }


    public function post(string $api, array $data)
    {
        if (!is_null($this->crypt)) $data = $this->crypt->encryptArray($data);
        $data = json_encode($data, 256 | 64);

        $option = [];
        $option['encode'] = 'json';
        $option['transfer'] = true;
        $option['agent'] = 'EspHttpClient/cURL';
        $option['headers'][] = "Accept: application/json";
        $option['headers'][] = "Content-Type: application/json";
        $option['headers'][] = $this->sign('POST', $api, $data);

        $http = new Http($option);
        $json = $http->data($data)->post($this->api . $api);

        return $json;
    }


}