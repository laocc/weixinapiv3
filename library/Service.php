<?php
declare(strict_types=1);

namespace esp\weiPay\library;

use esp\error\EspError;

class Service
{
    public $servID;
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
     * @param array $service
     * @return $this
     * @throws EspError
     */
    public function reService(array $service)
    {
        if (!isset($service['servID'])) throw new EspError("传入数据需要含有serviceModel的数据结构");

        $this->servID = $service['servID'];
        $this->mchID = $service['servMchID'];
        $this->appID = $service['servAppID'];
        $this->miniAppID = $service['servMiniAppID'];
        $this->mppAppID = $service['servMppAppID'];
        $this->apiKey = $service['servKey'];
        $this->apiV3Key = $service['servApiV3Key'];
        $this->certSerial = $service['servCertSerial'];

        $cert = _CERT . "/{$this->certSerial}/apiclient_key.pem";
        $this->certEncrypt = \openssl_get_privatekey(\file_get_contents($cert));
        return $this;
    }

    public function __toString()
    {
        return json_encode([
            'servID' => $this->servID,
            'mchID' => $this->mchID,
            'appID' => $this->appID,
            'certSerial' => $this->certSerial,
        ], 256 | 64 | 128);
    }


}