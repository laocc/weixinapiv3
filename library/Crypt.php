<?php
declare(strict_types=1);

namespace laocc\weiPay\library;

use esp\error\Error;
use OpenSSLAsymmetricKey;

/**
 * 数据加解密
 *
 * Class Crypt
 * @package laocc\weiPay\library
 */
class Crypt
{
    private string $serial;//证书号
    private OpenSSLAsymmetricKey $cert;
    private OpenSSLAsymmetricKey $public;

    public function __construct(string $certSerial, string $certPath = null)
    {
        if (is_null($certPath)) {
            $certPath = '';
            if (defined('_CERT')) {
                if (is_string(_CERT)) {
                    $certPath = _CERT;
                } else {
                    $certPath = _CERT['public'] ?? '';
                }
            }
        }
        if (!$certPath) throw new Error('未指定证书目录');
        $certPath = rtrim($certPath, '/');
        $this->serial = $certSerial;//证书序列号
        if (!is_readable("{$certPath}/{$certSerial}")) throw new Error("证书目录{$certPath}/{$certSerial}不存在或不可读");
        $cert = openssl_get_privatekey(file_get_contents("{$certPath}/{$certSerial}/cert.pem"));
        $public = openssl_get_publickey(file_get_contents("{$certPath}/{$certSerial}/public.pem"));
        if (!$cert) throw new Error("商户私钥错误");
        if (!$public) throw new Error("商户公钥错误");
        $this->cert = $cert;
        $this->public = $public;
    }

    public function serial()
    {
        return $this->serial;
    }

    public function public()
    {
        return $this->public;
    }

    public function cert()
    {
        return $this->cert;
    }


    /**
     * 用腾讯的公钥加密敏感信息
     * 加密数组中值第一位是@的数据
     * 支持三维数组
     * @param array $data
     * @return array
     */
    public function encryptArray(array $data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $val) {
                    if (is_array($val)) {
                        foreach ($val as $a => $b) {
                            if (is_string($b) && $b[0] === '@') {
                                $data[$key][$k][$a] = $this->encrypt(substr($b, 1));
                            }
                        }
                    } else if (is_string($val) && $val[0] === '@') {
                        $data[$key][$k] = $this->encrypt(substr($val, 1));
                    }
                }
            } else if (is_string($value) && !empty($value) && $value[0] === '@') {
                $data[$key] = $this->encrypt(substr($value, 1));
            }
        }
        return $data;
    }

    /**
     * 用腾讯的公钥加密
     * @param string $str
     * @return string
     */
    private function encrypt(string $str)
    {
        openssl_public_encrypt($str, $encrypted, $this->public, OPENSSL_PKCS1_OAEP_PADDING);
        return base64_encode($encrypted);
    }

}