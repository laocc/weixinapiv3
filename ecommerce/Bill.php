<?php
declare(strict_types=1);

namespace laocc\weiPay\ecommerce;

use laocc\weiPay\ApiV3Base;

class Bill extends ApiV3Base
{

    /**
     * 绑定分销账号
     * @param array $param
     * @return bool|string
     */
    public function bind(array $param)
    {
        $data = [];
        $data['appid'] = $this->entity->appID;
        if (isset($param['openid'])) {
            $data['type'] = 'PERSONAL_OPENID';
            $data['account'] = $param['openid'];
            $data['relation_type'] = 'DISTRIBUTOR';
        } else {
            $data['type'] = 'MERCHANT_ID';
            $data['name'] = $param['name'];
            $data['account'] = $param['mchid'];
            $data['relation_type'] = 'PLATFORM';
        }
        $unified = $this->post("/v3/ecommerce/profitsharing/receivers/add", $data);
        if (is_string($unified)) return $unified;
        return true;
    }


    /**
     * 创建分账
     * @param array $billOrder
     * @return array
     *
     * 分账回退通知的url，在服务商后台【交易中心】【分账】【分账接收设置】中配置
     */
    public function create(array $billOrder)
    {
        $value = [];
        foreach ($billOrder as $i => $bill) {
            if (!is_array($bill)) {
                $value[$bill['number']] = ['result' => $bill, 'attach' => $bill['attach'] ?? ''];
                continue;
            }

            //无分账方，且未指令结单
            if (empty($bill['receivers']) and !$bill['finish']) continue;

            $data = [];
            $data['sub_mchid'] = $bill['mchID'];
            $data['transaction_id'] = $bill['transaction'];
            $data['out_order_no'] = $bill['number'];

            if (empty($bill['receivers'])) {
                //无分账方，结束分账
                $data['description'] = $bill['desc'] ?? ($bill['description'] ?? '结束分账');
                if (empty($data['description'])) $data['description'] = '结束分账';
                $unified = $this->post("/v3/ecommerce/profitsharing/finish-order", $data);

            } else {
                $data['appid'] = $this->entity->appID;
                $data['finish'] = $bill['finish'];
                $data['receivers'] = $bill['receivers'];
                $unified = $this->post("/v3/ecommerce/profitsharing/orders", $data);
            }

            $value[$bill['number']] = ['result' => $unified, 'attach' => $bill['attach'] ?? ''];
        }
        return $value;
    }

    /**
     * 直接完结订单
     * @param array $billOrder
     * @return array
     */
    public function finish(array $billOrder)
    {
        $value = [];
        foreach ($billOrder as $bill) {
            if (empty($bill)) continue;
            $data = [];
            $data['sub_mchid'] = $bill['mchID'];
            $data['transaction_id'] = $bill['transaction'];
            $data['out_order_no'] = $bill['number'];
            $data['description'] = $bill['description'];
            $value[$bill['number']] = $this->post("/v3/ecommerce/profitsharing/finish-order", $data);
        }
        return $value;
    }

    /**
     * 查询分销状态
     * @param array $param
     * @return mixed|string|null
     */
    public function query(array $param)
    {
        $data = [];
        $data['sub_mchid'] = $param['mchid'];
        $data['transaction_id'] = $param['transaction'];
        $data['out_order_no'] = $param['number'];

        $rest = $this->get("/v3/ecommerce/profitsharing/orders", $data);
        if (is_string($rest)) return $rest;
        return $rest;
    }


    /**
     * 查询订单剩余可分账余额
     * @param string $transaction_id
     * @return int|string
     */
    public function balance(string $transaction_id)
    {
        $rest = $this->get("/v3/ecommerce/profitsharing/orders/{$transaction_id}/amounts");
        if (is_string($rest)) return $rest;
        if (!isset($rest['unsplit_amount'])) return json_encode($rest, 256);
        return intval($rest['unsplit_amount']);
    }


}