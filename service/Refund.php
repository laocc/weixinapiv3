<?php
declare(strict_types=1);

namespace esp\weiPay\service;


use esp\weiPay\ApiV3Base;

class Refund extends ApiV3Base
{

    /**
     * 请求退款
     * @param array $refund
     * @return array|mixed|null|string
     *
     * https://pay.weixin.qq.com/wiki/doc/apiv3_partner/apis/chapter4_5_9.shtml
     */
    public function apply(array $refund)
    {
        $param = [];
        $param['sub_mchid'] = $refund['mchid'];

        $param['transaction_id'] = $refund['transaction_id'];
        $param['out_trade_no'] = $refund['out_trade_no'];
        $param['out_refund_no'] = $refund['out_refund_no'];
        $param['reason'] = $refund['reason'];
        $param['notify_url'] = $refund['notify'];
        $param['amount'] = [];
        $param['amount']['refund'] = $refund['amount'];
        $param['amount']['total'] = $refund['total'];
        $param['amount']['currency'] = 'CNY';

        $data = $this->post("/v3/refund/domestic/refunds", $param);
        if (is_string($data)) return $data;

        $values = [
            'refund_id' => $data['refund_id'],
            'number' => $data['out_refund_no'],
            'time' => strtotime($data['create_time']),
            'amount' => intval($data['amount']['payer_refund']),
        ];
        return $values;
    }


}