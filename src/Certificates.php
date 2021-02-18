<?php
declare(strict_types=1);

namespace esp\weixinapiv3\src;


class Certificates extends ApiV3Base
{
    /**
     * 下载平台证书
     * @return array|string
     * https://pay.weixin.qq.com/wiki/doc/apiv3/wechatpay/wechatpay5_1.shtml
     */
    public function downloadPlatCert(string $apiV3Key)
    {
        $Certificates = $this->get("/v3/certificates");
        if (is_string($Certificates)) return $Certificates;
        foreach ($Certificates['data'] as &$cert) {
            $cert['cert'] = $this->decryptToString($apiV3Key,
                $cert['encrypt_certificate']['associated_data'],
                $cert['encrypt_certificate']['nonce'],
                $cert['encrypt_certificate']['ciphertext']);
        }
        return $Certificates;
    }


    /**
     * 证书解密，
     *
     * 解密后，要再次导出公钥：
     * openssl x509 -in cert.pem -pubkey -noout > public.pem
     *
     * @param $aesKey
     * @param $associatedData
     * @param $nonceStr
     * @param $ciphertext
     * @return false|string
     * @throws \SodiumException
     *
     */
    private function decryptToString($aesKey, $associatedData, $nonceStr, $ciphertext)
    {
        $ciphertext = \base64_decode($ciphertext);

        // ext-sodium (default installed on >= PHP 7.2)
        if (function_exists('\sodium_crypto_aead_aes256gcm_is_available') &&
            \sodium_crypto_aead_aes256gcm_is_available()) {
            return \sodium_crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $aesKey);
        }

        // ext-libsodium (need install libsodium-php 1.x via pecl)
        if (function_exists('\Sodium\crypto_aead_aes256gcm_is_available') &&
            \Sodium\crypto_aead_aes256gcm_is_available()) {
            return \Sodium\crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $aesKey);
        }

        // openssl (PHP >= 7.1 support AEAD)
        if (PHP_VERSION_ID >= 70100 && in_array('aes-256-gcm', \openssl_get_cipher_methods())) {
            $ctext = substr($ciphertext, 0, -16);
            $authTag = substr($ciphertext, -16);

            return \openssl_decrypt($ctext, 'aes-256-gcm', $aesKey, \OPENSSL_RAW_DATA, $nonceStr,
                $authTag, $associatedData);
        }

        throw new \RuntimeException('AEAD_AES_256_GCM需要PHP 7.1以上或者安装libsodium-php');
    }


}