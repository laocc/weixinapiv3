<?php
declare(strict_types=1);

namespace esp\weiPay\mall;


use esp\weiPay\ApiV3Base;

class Bill extends ApiV3Base
{
    public function create(array $param)
    {
        $time = time();
        $data = [];
        $data['appid'] = $this->service->miniAppID;
        $data['sub_mchid'] = $param['mchID'];
        $data['transaction_id'] = $param['subTransactionID'];
        $data['out_order_no'] = $param['billNumber'];
        $data['finish'] = $param['finish'];

        $data['receivers'] = [];

        $data['receivers']['type'] = 'MERCHANT_ID';
        $data['receivers']['receiver_account'] = $param['mchID'];
        $data['receivers']['amount'] = 3333;
        $data['receivers']['description'] = 3333;


        $unified = $this->post("/v3/ecommerce/profitsharing/orders", $data);
        if (is_string($unified)) return $unified;


        return $values;
    }

}