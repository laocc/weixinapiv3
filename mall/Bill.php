<?php
declare(strict_types=1);

namespace esp\weiPay\mall;

use esp\weiPay\ApiV3Base;

class Bill extends ApiV3Base
{

    /**
     * @param array $billOrder
     * @return array
     *
     * 分账回退通知的url，在服务商后台【交易中心】【分账】【分账接收设置】中配置
     */
    public function create(array $billOrder)
    {
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