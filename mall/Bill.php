<?php
declare(strict_types=1);

namespace esp\weiPay\mall;

use esp\weiPay\ApiV3Base;

class Bill extends ApiV3Base
{

    public function bind($param)
    {
        $data = [];
        $data['appid'] = $this->service->miniAppID;
        $data['type'] = 'PERSONAL_OPENID';
        $data['account'] = $param['openid'];
        $data['relation_type'] = 'DISTRIBUTOR';
        $unified = $this->post("/v3/ecommerce/profitsharing/receivers/add", $data);
        if (is_string($unified)) return $unified;
        return true;
    }


    /**
     * @param array $billOrder
     * @return array
     *
     * 分账回退通知的url，在服务商后台【交易中心】【分账】【分账接收设置】中配置
     */
    public function create(array $billOrder)
    {
        /**
         * (
         * [order_id] => 30002102162021022708255143656
         * [out_order_no] => 2102271613399447597400
         * [sub_mchid] => 1606823507
         * [transaction_id] => 4313500652202102274472653205
         * )
         */
        $value = [];
        foreach ($billOrder as $i => $bill) {
            $data = [];
            $data['appid'] = $this->service->miniAppID;
            $data['sub_mchid'] = $bill['mchID'];
            $data['transaction_id'] = $bill['transaction'];
            $data['out_order_no'] = $bill['number'];
            $data['finish'] = $bill['finish'];
            $data['receivers'] = $bill['receivers'];
            $unified = $this->post("/v3/ecommerce/profitsharing/orders", $data);
            $value[$bill['number']] = $unified;
        }
        return $value;
    }

}