<?php
declare(strict_types=1);

namespace esp\weiPay\library;

use esp\error\Error;

/**
 * 数据加解密
 *
 * Class Crypt
 * @package esp\weiPay\library
 */
class Crypt
{
    private string $serial;
    private $cert;
    private $public;

    public function __construct(string $certSerial, string $certPath = null)
    {
        if (is_null($certPath)) $certPath = defined('_CERT') ? _CERT : null;
        if (!$certPath) throw new Error('未指定证书目录');
        $certPath = rtrim($certPath, '/');

        $this->serial = $certSerial;
        $cert = $certPath . "/{$certSerial}/cert.pem";
        $pub = $certPath . "/{$certSerial}/public.pem";
        $this->cert = openssl_get_privatekey(file_get_contents($cert));
        $this->public = openssl_get_publickey(file_get_contents($pub));
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
    public function encrypt(string $str)
    {
        openssl_public_encrypt($str, $encrypted, $this->public, OPENSSL_PKCS1_OAEP_PADDING);
        return base64_encode($encrypted);
    }

}