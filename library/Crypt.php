<?php
declare(strict_types=1);

namespace esp\weixinapiv3\library;


class Crypt
{
    private $wxPubCert;

    public function __construct(array $wxPubCert)
    {
        $this->wxPubCert = [
            'index' => $wxPubCert['certIndex'],
            'encrypt' => $wxPubCert['certEncrypt'],
            'algorithm' => $wxPubCert['certAlgorithm'],
        ];
    }

    /**
     * 加密数组中值第一位是*的数据
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
            } else if (is_string($value) && $value[0] === '@') {
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
        $rsa = ($this->wxPubCert['encrypt']);
        $rsa = chunk_split($rsa, 64, "\n");
        $rsa = "-----BEGIN PUBLIC KEY-----\n{$rsa}-----END PUBLIC KEY-----";
        $rsa3 = <<<PUB
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC0bIp1HvBiYXqOOHFwQObud1VQ
XwobDwo7p1Dv9L8Vuw7lwTURfJGnZE8z22YQK68Q3UM16BHmLK17IMfgPWz1xMJx
Qd9kQBI7JMUhNxkynVKRyl9KBGj8+qrhbQeviGrnENktMlrF6P/Y6xC7rLsezXCo
dU8Br3wTauq1PJs+9wIDAQAB
-----END PUBLIC KEY-----
PUB;
        var_dump($rsa);

//        $rsa = file_get_contents(_RUNTIME . '/rsa.pem');

        openssl_public_encrypt($str, $encrypted, $rsa, OPENSSL_PKCS1_OAEP_PADDING);
        return base64_encode($encrypted);
    }

}