<?php

namespace laocc\weiPay\library;

use esp\error\Error;
use OpenSSLAsymmetricKey;

class WxCert
{
    private string $serial;//微信公钥证书号
    private OpenSSLAsymmetricKey $cert;


    public function __construct(string $certSerial, string $certPath, string $wxSerial)
    {
        $wxPub = openssl_get_publickey(file_get_contents("{$certPath}/{$certSerial}/wxpub.pem"));
        if (!$wxPub) throw new Error("微信公钥错误");
        $this->cert = $wxPub;
        $this->serial = $wxSerial;
    }


    public function serial()
    {
        return $this->serial;
    }

    public function public()
    {
        return $this->cert;
    }


}