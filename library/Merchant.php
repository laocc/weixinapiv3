<?php
declare(strict_types=1);

namespace esp\weiPay\library;


use esp\error\EspError;

class Merchant
{
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
     * @param array $mch
     * @return $this
     * @throws EspError
     */
    public function reMerchant(array $mch)
    {
        $this->mchID = $mch['mchID'];
        $this->appID = $mch['appID'];
        return $this;
    }

    public function __toString()
    {
        return json_encode([
            'mchID' => $this->mchID,
            'appID' => $this->appID,
        ], 256 | 64 | 128);
    }

}