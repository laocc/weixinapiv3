<?php
declare(strict_types=1);

namespace laocc\weiPay\library;

use esp\error\Error;

/**
 * 服务器，或直连商户，身份构造
 */
class Entity
{
    public string $mchID;
    public string $appID;
    public string $apiV3Key;
    public string $certSerial;
    public string $privatePath = '';
    public string $publicPath = '';

    public string $shopMchID = '';//子商户
    public string $shopAppID = '';

    public int $service;//服务商类型，1直连商户，2普通服务商，4电商服务商

    public $certEncrypt;

    /**
     * 可以自行引用此类并实现此类的相关方法
     *
     * Entity constructor.
     * @param array $conf
     */
    public function __construct(array $conf)
    {
        $this->service = intval($conf['service'] ?? 0);

        if (!isset($conf['mchID'])) throw new Error("传入数据需要含有微信支付商户基本数据结构");

        $this->mchID = $conf['mchID'] ?? ($conf['mchid'] ?? '');
        foreach (['appID', 'appid', 'miniAppID', 'mppAppID', 'appId'] as $ak) {
            if (isset($conf[$ak])) {
                $this->appID = $conf[$ak];
                break;
            }
        }

        if (isset($conf['shopID'])) $this->shopMchID = $conf['shopID'];
        if (isset($conf['shopAppID'])) $this->shopAppID = $conf['shopAppID'];

        $this->apiV3Key = $conf['v3Key'] ?? '';
        $this->certSerial = $conf['certSerial'] ?? '';

        if (isset($conf['cert'])) {
            $this->privatePath = $conf['cert']['private'] ?? '';
            $this->publicPath = $conf['cert']['public'] ?? '';
        } else {
            if (defined('_CERT')) {
                if (is_string(_CERT)) {
                    $this->privatePath = _CERT;
                    $this->publicPath = _CERT;
                } else {
                    $this->privatePath = _CERT['private'] ?? '';
                    $this->publicPath = _CERT['public'] ?? '';
                }
            }
        }
        if (!$this->privatePath) throw new Error('未指定商户私钥证书目录');
        if (!$this->publicPath) throw new Error('未指定微信公钥证书目录');

        $this->privatePath = rtrim($this->privatePath, '/');
        $this->publicPath = rtrim($this->publicPath, '/');

        /**
         * 这里用到的密钥是在微信支付后台申请的商户私钥，或服务商私钥
         */
        $cert = "{$this->privatePath}/{$this->certSerial}/apiclient_key.pem";
        if (!is_readable($cert)) {
            $cert = "{$this->privatePath}/{$this->mchID}/apiclient_key.pem";
            if (!is_readable($cert)) throw new Error("商户证书文件不存在，请检查");
        }
        $this->certEncrypt = \openssl_get_privatekey(\file_get_contents($cert));
    }

    public function __toString()
    {
        return json_encode([
            'mchID' => $this->mchID,
            'appID' => $this->appID,
            'certSerial' => $this->certSerial,
        ], 256 | 64 | 128);
    }


}