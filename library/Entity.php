<?php
declare(strict_types=1);

namespace esp\weiPay\library;

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
    public bool $isService;//是否服务商
    public string $privatePath;
    public string $publicPath;

    public $certEncrypt;

    /**
     * 可以自行引用此类并实现此类的相关方法
     *
     * Entity constructor.
     * @param array $conf
     */
    public function __construct(array $conf)
    {
        $this->isService = boolval($conf['service'] ?? 1);
        $this->reConfig($conf);
    }

    /**
     * @param array $svConf
     * @return $this
     */
    public function reConfig(array $svConf): Entity
    {
        if (!isset($svConf['mchID'])) throw new Error("传入数据需要含有微信支付商户基本数据结构");

        $this->mchID = $svConf['mchID'] ?? ($svConf['mchid'] ?? '');
        foreach (['appID', 'appid', 'miniAppID', 'mppAppID', 'appId'] as $ak) {
            if (isset($svConf[$ak])) {
                $this->appID = $svConf[$ak];
                break;
            }
        }

        $this->apiV3Key = $svConf['v3Key'] ?? '';
        $this->certSerial = $svConf['certSerial'] ?? '';

        if (isset($svConf['cert'])) {
            $this->privatePath = $svConf['cert']['private'] ?? '';
            $this->publicPath = $svConf['cert']['public'] ?? '';
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
         * 这里用到的密钥是在微信支付后台申请的
         */
        $cert = "{$this->privatePath}/{$this->certSerial}/apiclient_key.pem";
        if (!is_readable($cert)) {
            $cert = "{$this->privatePath}/{$this->mchID}/apiclient_key.pem";
            if (!is_readable($cert)) throw new Error("商户证书文件不存在，请检查");
        }
        $this->certEncrypt = \openssl_get_privatekey(\file_get_contents($cert));
        return $this;
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