<?php
declare(strict_types=1);

namespace esp\weiPay\ecommerce;

use esp\weiPay\ApiV3Base;

class Bill extends ApiV3Base
{

    public function bind(array $param)
    {
        $data = [];
        $data['appid'] = $this->entity->miniAppID;
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
        $value = [];
        foreach ($billOrder as $i => $bill) {
            if (empty($bill['receivers'])) {
                //无分账方，结束分账
                $data = [];
                $data['sub_mchid'] = $bill['mchID'];
                $data['transaction_id'] = $bill['transaction'];
                $data['out_order_no'] = $bill['number'];
                $data['description'] = $bill['description'];
                $unified = $this->post("/v3/ecommerce/profitsharing/finish-order", $data);
                $value[$bill['number']] = $unified;

            } else {
                $data = [];
                $data['appid'] = $this->entity->miniAppID;
                $data['sub_mchid'] = $bill['mchID'];
                $data['transaction_id'] = $bill['transaction'];
                $data['out_order_no'] = $bill['number'];
                $data['finish'] = $bill['finish'];
                $data['receivers'] = $bill['receivers'];
                $unified = $this->post("/v3/ecommerce/profitsharing/orders", $data);
                $value[$bill['number']] = $unified;
            }
        }
        return $value;
    }


    public function query(array $param)
    {
        $data = [];
        $data['sub_mchid'] = $param['mchid'];
        $data['transaction_id'] = $param['transaction'];
        $data['out_order_no'] = $param['number'];

        $unified = $this->get("/v3/ecommerce/profitsharing/orders", $data);
        return $unified;
    }


}