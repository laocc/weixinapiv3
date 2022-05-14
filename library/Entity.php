<?php
declare(strict_types=1);

namespace esp\weiPay\library;

/**
 * 服务器，或直连商户，身份构造
 */
class Entity
{
    public $mchID;
    public $appID;
    public $apiKey;
    public $apiV3Key;
    public $certSerial;
    public $isService;//是否服务商

    public $certEncrypt;
    public $certPath;

    /**
     * 可以自行引用此类并实现此类的相关方法
     *
     * Entity constructor.
     * @param array $conf
     * @param string|null $certPath
     */
    public function __construct(array $conf, string $certPath = null)
    {
        if ($certPath) $conf['certPath'] = $certPath;
        $this->isService = boolval($conf['service'] ?? 1);
        $this->reConfig($conf);
    }

    /**
     * @param array $svConf
     * @return $this
     */
    public function reConfig(array $svConf): Entity
    {
        if (!isset($svConf['mchID'])) throw new \Error("传入数据需要含有微信支付商户基本数据结构");

        $this->mchID = $svConf['mchID'] ?? ($svConf['mchid'] ?? '');
        foreach (['appID', 'appid', 'miniAppID', 'mppAppID'] as $ak) {
            if (isset($svConf[$ak])) {
                $this->appID = $svConf[$ak];
                break;
            }
        }

        $this->apiKey = $svConf['apiKey'];
        $this->apiV3Key = $svConf['v3Key'];
        $this->certSerial = $svConf['certSerial'];
        $this->certPath = $svConf['certPath'] ?? null;

        if (is_null($this->certPath)) {
            $this->certPath = defined('_CERT') ? _CERT : (_ROOT . '/cert');
        }
        if (!$this->certPath) throw new \Error('未指定证书目录');
        $this->certPath = rtrim($this->certPath, '/');

        /**
         * 这里用到的密钥是在微信支付后台申请的
         */
        $cert = $this->certPath . "/{$this->certSerial}/apiclient_key.pem";
        if (!is_readable($cert)) {
            $cert = $this->certPath . "/{$this->mchID}/apiclient_key.pem";
            if (!is_readable($cert)) throw new \Error("商户证书文件不存在，请检查");
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