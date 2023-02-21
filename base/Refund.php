<?php

namespace laocc\weiPay\base;

use laocc\weiPay\ApiV3Base;

class Refund extends ApiV3Base
{

    public function notify(array $post)
    {
        if (!isset($post['refund_id'])) return json_encode($post, 320);
        $params = [];
        $params['success'] = ($post['refund_status'] ?? $post['status']) === 'SUCCESS';
        $params['waybill'] = $post['refund_id'];
        $params['number'] = $post['out_refund_no'];
        $params['time'] = strtotime($post['success_time']);
        $params['state'] = strtolower(substr($post['refund_status'], -20));
        $params['amount'] = intval($post['amount']['refund']);
        return $params;
    }


    public function query(array $param)
    {
        $data = [];

        if ($this->entity->service > 1) {
            $data['sub_mchid'] = $this->entity->shopMchID;
        }

        $rest = $this->get("/v3/refund/domestic/refunds/{$param['number']}", $data);
        if (is_string($rest)) return $rest;

        return [
            'success' => ($data['status'] === 'SUCCESS'),
            'status' => $data['status'],
            'waybill' => $data['refund_id'],
            'number' => $data['out_refund_no'],
            'time' => strtotime($data['success_time']),
            'amount' => intval($data['amount']['payer_refund']),
        ];
    }


    /**
     * 请求退款
     * @param array $refund
     * @return array|string
     *
     * https://pay.weixin.qq.com/wiki/doc/apiv3_partner/apis/chapter4_5_9.shtml
     */
    public function send(array $refund)
    {
        $param = [];
        if ($this->entity->service > 1) {
            $data['sub_mchid'] = $this->entity->shopMchID;
        }

        $param['transaction_id'] = $refund['waybill'];//微信订单号
        $param['out_trade_no'] = $refund['number'];//商户支付单号
        $param['out_refund_no'] = $refund['refund'];//退款订单号
        $param['reason'] = $refund['reason'];
        $param['notify_url'] = $refund['notify'];
        $param['amount'] = [];
        $param['amount']['refund'] = $refund['amount'];
        $param['amount']['total'] = $refund['total'];
        $param['amount']['currency'] = 'CNY';

        $data = $this->post("/v3/refund/domestic/refunds", $param);
        if (is_string($data)) return $data;

        return [
            'waybill' => $data['refund_id'],
            'number' => $data['out_refund_no'],
            'time' => strtotime($data['create_time']),
            'amount' => intval($data['amount']['payer_refund']),
        ];
    }
}