<?php
declare(strict_types=1);

namespace laocc\weiPay\service;

use laocc\weiPay\ApiV3Base;
use laocc\weiPay\library\RefundFace;

class Refund extends ApiV3Base implements RefundFace
{


    public function notify(array $post): array|string
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

    public function query(array $params): array|string
    {
        $data = [];
        $data['sub_mchid'] = $params['mchid'];

        if (isset($params['transaction']) and !empty($params['transaction'])) {
            $rest = $this->get("/v3/ecommerce/refunds/id/{$params['transaction']}", $data);
        } else {
            $rest = $this->get("/v3/ecommerce/refunds/out-refund-no/{$params['number']}", $data);
        }
        if (is_string($rest)) return $rest;
        return $rest;
    }


    /**
     * 请求退款
     * @param array $refund
     * @return array|string
     *
     * https://pay.weixin.qq.com/wiki/doc/apiv3_partner/apis/chapter4_5_9.shtml
     */
    public function send(array $refund): array|string
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

        return [
            'waybill' => $data['refund_id'],
            'number' => $data['out_refund_no'],
            'time' => strtotime($data['create_time']),
            'amount' => intval($data['amount']['payer_refund']),
        ];
    }

    public function abnormal(array $refund): array|string
    {
        $param = [];
        $param['sub_mchid'] = $refund['mchid'];
        $param['out_trade_no'] = $refund['out_trade_no'];
        $param['type'] = 'MERCHANT_BANK_CARD';//USER_BANK_CARD: 退款到用户银行卡;MERCHANT_BANK_CARD: 退款至交易商户银行账户

        if (($refund['to'] ?? '') === 'user') $param['type'] = 'USER_BANK_CARD';//USER_BANK_CARD: 退款到用户银行卡;MERCHANT_BANK_CARD: 退款至交易商户银行账户

        /**
         * 【开户银行】 银行类型，采用字符串类型的银行标识，值列表详见银行类型。仅支持招行、交通银行、农行、建行、工商、中行、平安、浦发、中信、光大、民生、兴业、广发、邮储、宁波银行的借记卡。
         * 若退款至用户此字段必填。
         */
        if ($param['type'] === 'USER_BANK_CARD') {
            $param['bank_type'] = 'ICBC_DEBIT';
            $param['bank_account'] = '';
            $param['real_name'] = '';
        }


        $data = $this->post("/v3/refund/domestic/refunds/{$refund['waybill']}/apply-abnormal-refund", $param);
        if (is_string($data)) return $data;

        return [
            'waybill' => $data['refund_id'],
            'number' => $data['out_refund_no'],
            'time' => strtotime($data['create_time']),
            'amount' => intval($data['amount']['payer_refund']),
        ];
    }


}