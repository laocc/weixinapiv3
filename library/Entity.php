<?php
declare(strict_types=1);

namespace laocc\weiPay\library;

use esp\error\Error;
use Exception;
use OpenSSLAsymmetricKey;

/**
 * 服务器，或直连商户，身份构造
 */
class Entity
{
    public string $mchID;//微信商户号
    public string $appID;//APPID

    public string $certKey;//私钥Key和证书串号
    public string $certSerial;

    public string $publicPath;
    public string $publicSerial;

    public array $merchant;//子商户

    public int $service = 1;//服务商类型，1直连商户，2普通服务商，4电商服务商，32自建支付中心
    public bool $improve = false;//商户性质提升，从二级子商户提升为直接商户
    public WxCert $wxCert;


    /**
     * 如何从平台证书切换成微信支付公钥
     * https://pay.weixin.qq.com/doc/v3/merchant/4012154180
     *
     * 微信支付公钥产品简介及使用说明
     * https://pay.weixin.qq.com/doc/v3/merchant/4012153196
     */

    public OpenSSLAsymmetricKey $certEncrypt;

    /**
     * Entity constructor.
     * @param array $conf
     */
    public function __construct(array $conf)
    {
        $this->mchID = $conf['mchid'] ?? ($conf['mchID'] ?? ($conf['mchId'] ?? ''));
        if (!$this->mchID) throw new Error("传入数据需要含有微信支付商户mchID");

        $this->appID = $conf['appid'] ?? ($conf['appID'] ?? ($conf['appId'] ?? ''));
        if (!$this->appID) throw new Error("传入数据需要含有微信支付商户appID");
        $this->improve = boolval($conf['improve'] ?? false);//商户性质提升

        if (is_array($conf['merchant'] ?? '')) {
            $this->service = ($conf['ecommerce'] ?? 0) ? 4 : 2;
            $this->merchant = [
                'mchid' => $conf['merchant']['mchid'] ?? '',
                'appid' => $conf['merchant']['appid'] ?? '',
            ];
        } else {
            $this->service = 1;
        }

        $this->certKey = $conf['certKey'] ?? ($conf['key'] ?? ($conf['v3Key'] ?? ''));
        $this->certSerial = $conf['certSerial'] ?? ($conf['serial'] ?? '');
        $this->publicSerial = $conf['publicSerial'] ?? ($conf['public'] ?? '');

        if (isset($conf['cert'])) {
            $certPath = $conf['cert'];
        } else if (defined('_CERT')) {
            $certPath = _CERT;
        } else {
            throw new Error('未指定证书目录');
        }

        $certPath = rtrim($certPath, '/');
        $this->publicPath = $certPath;

        /**
         * 这里用到的密钥是在微信支付后台申请的商户私钥，或服务商私钥
         */
        $certFile = "{$certPath}/{$this->certSerial}/apiclient_key.pem";
        if (!is_readable($certFile)) {
            throw new Error("商户证书文件{$certFile}不存在，请检查");
        }

        $this->certEncrypt = \openssl_get_privatekey(\file_get_contents($certFile));

        if (!($conf['pubKey'] ?? 0)) return;
        $this->wxCert = new WxCert($certPath, $conf['wxPubKey']);
    }


    public function setWxCert(WxCert $crypt)
    {
        $this->wxCert = $crypt;
        return $this;
    }


    public function __toString()
    {
        $value = [
            'service' => $this->service,
            'mchID' => $this->mchID,
            'appID' => $this->appID,
            'private' => $this->certSerial,
            'public' => $this->publicSerial,
        ];
        if (isset($this->merchant)) $value['merchant'] = $this->merchant;
        return json_encode($value, 256 | 64 | 128);
    }

    /**
     * 解密
     *
     * @param string $ciphertext
     * @return string
     * @throws Error
     */
    public function decryptedCipher(string $ciphertext): string
    {
        $data = base64_decode($ciphertext, true);
        if ($data === false) {
            throw new Error("无效的Base64编码字符串");
        }

        // 执行解密操作
        $decrypted = '';
        $success = openssl_private_decrypt(
            $data,
            $decrypted,
            $this->certEncrypt,
            OPENSSL_PKCS1_OAEP_PADDING // 使用OAEP填充模式
        );

        // 检查解密结果
        if (!$success) {
            $error = openssl_error_string();
            throw new Error("解密失败: " . $error);
        }

        // 返回UTF-8编码的明文
        return $decrypted;
    }

}