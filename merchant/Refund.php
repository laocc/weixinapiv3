<?php

namespace laocc\weiPay\merchant;

use laocc\weiPay\ApiV3Base;

class Refund extends ApiV3Base
{


    public function query(array $param)
    {
        $data = [];
        $data['sub_mchid'] = $param['mchid'];

        if (isset($param['transaction']) and !empty($param['transaction'])) {
            $rest = $this->get("/v3/ecommerce/refunds/id/{$param['transaction']}", $data);
        } else {
            $rest = $this->get("/v3/ecommerce/refunds/out-refund-no/{$param['number']}", $data);
        }
        if (is_string($rest)) return $rest;
        return $rest;
    }


    /**
     * https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_1_9.shtml
     *
     * @param array $params
     * @return array|\esp\http\HttpResult|mixed|string|null
     *
     */
    public function send(array $params)
    {
        $param = [];
        $param['transaction_id'] = $params['transaction_id'];
        $param['out_trade_no'] = $params['out_trade_no'];
        $param['out_refund_no'] = $params['out_refund_no'];
        $param['reason'] = $params['out_refund_no'] ?? '用户要求退款';
        $param['notify_url'] = $params['notify'];
//        $param['funds_account'] = 'AVAILABLE';
        $param['amount'] = [
            'refund' => $params['refund'] ?? $params['total'],
            'total' => $params['total'],
            'currency' => 'CNY',
        ];

        $data = $this->post("/v3/refund/domestic/refunds", $param);
        if (is_string($data)) return $data;

        return $data;
    }


}