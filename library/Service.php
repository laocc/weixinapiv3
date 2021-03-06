<?php
declare(strict_types=1);

namespace esp\weiPay\library;

use esp\error\EspError;

class Service
{
    public $mchID;
    public $appID;
    public $miniAppID;
    public $mppAppID;
    public $apiKey;
    public $apiV3Key;
    public $certSerial;

    public $certEncrypt;

    /**
     * 可以自行引用此类并实现此类的相关方法
     * Service constructor.
     * @param array $service
     * @throws EspError
     */
    public function __construct(array $service)
    {
        $this->reService($service);
    }

    /**
     * @param array $svConf
     * @return $this
     * @throws EspError
     */
    public function reService(array $svConf)
    {
        if (!isset($svConf['mchID'])) throw new EspError("传入数据需要含有serviceModel的数据结构");

        $this->mchID = $svConf['mchID'];
        $this->miniAppID = $svConf['miniAppID'];
        $this->mppAppID = $svConf['mppAppID'];
        $this->apiKey = $svConf['apiKey'];
        $this->apiV3Key = $svConf['v3Key'];
        $this->certSerial = $svConf['certSerial'];

        $cert = _CERT . "/{$this->certSerial}/apiclient_key.pem";
        $this->certEncrypt = \openssl_get_privatekey(\file_get_contents($cert));
        return $this;
    }

    public function __toString()
    {
        return json_encode([
            'mchID' => $this->mchID,
            'miniAppID' => $this->miniAppID,
            'mppAppID' => $this->mppAppID,
            'certSerial' => $this->certSerial,
        ], 256 | 64 | 128);
    }


}