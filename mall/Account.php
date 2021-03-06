<?php


namespace esp\weiPay\mall;

use esp\weiPay\ApiV3Base;

class Account extends ApiV3Base
{

    /**
     * 查询二级商户账户
     * @param $mchID
     * @return array|string
     */
    public function query(string $mchID)
    {
        $param = [];
        $param['account_type'] = 'BASIC';

        $data = $this->get("/v3/ecommerce/fund/balance/{$mchID}", $param);
        if (is_string($data)) return $data;
        return $data;
    }

}