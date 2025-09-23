<?php

namespace laocc\weiPay\library;

use esp\error\Error;
use OpenSSLAsymmetricKey;

class WxCert
{
    private string $serial;//微信公钥证书号
    private OpenSSLAsymmetricKey $cert;


    public function __construct(string $path, string $serial)
    {
        if (!is_readable("{$path}/{$serial}/cert.pem")) return;
        $cert = openssl_get_publickey(file_get_contents("{$path}/{$serial}/cert.pem"));
        if (!$cert) throw new Error("微信公钥错误");
        $this->cert = $cert;
        $this->serial = $serial;
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