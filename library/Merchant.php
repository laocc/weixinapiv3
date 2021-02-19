<?php
declare(strict_types=1);

namespace esp\weixinapiv3\library;


use esp\error\EspError;

class Merchant
{
    public $servID;
    public $mchID;
    public $appID;

    /**
     * 可以自行引用此类并实现此类的相关方法
     * Service constructor.
     * @param array $mchOrShop
     * @throws EspError
     */
    public function __construct(array $mchOrShop)
    {
        $this->reMerchant($mchOrShop);
    }

    /**
     * @param array $service
     * @return $this
     * @throws EspError
     */
    public function reMerchant(array $mchOrShop)
    {
        if (!isset($mchOrShop['shopID'])) throw new EspError("传入数据需要含有shopModel的数据结构");

        $this->servID = $mchOrShop['shopServID'];
        $this->mchID = $mchOrShop['shopWxMchID'];
        $this->appID = $mchOrShop['shopAppID'];
        return $this;
    }

    public function __toString()
    {
        return json_encode([
            'servID' => $this->servID,
            'mchID' => $this->mchID,
            'appID' => $this->appID,
        ], 256 | 64 | 128);
    }

}