<?php

namespace esp\weixinapiv3\src;


class Refund extends ApiV3Base
{
    public function apply(array $params)
    {
        $data = [];
        $data['sp_mchid'] = $this->service->mchID;
        $data['sub_mchid'] = $params['mchID'];
        $data['transaction_id'] = $params['transaction'];
        $data['out_trade_no'] = $params['number'];
        $data['reason'] = $params['reason'];
        $data['notify_url'] = $params['notify'];
        $data['amount'] = [];
        $data['amount']['refund'] = $params['fee'];
        $data['amount']['total'] = $params['total'];
        $data['amount']['currency'] = 'CNY';

        $unified = $this->post("/v3/ecommerce/refunds/apply", $data);
        if (is_string($unified)) return $unified;

        return [
            'refund_id' => $unified['refund_id'],
            'number' => $unified['out_refund_no'],
            'time' => strtotime($unified['create_time']),
            'fee' => intval($unified['amount']['payer_refund']),
        ];
    }
}