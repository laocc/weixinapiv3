<?php

namespace laocc\weiPay\custom;

use laocc\weiPay\library\RefundFace;

class Refund extends Base implements RefundFace
{


    public function notify(): array|string
    {
        $value = $this->notifyDecrypt();
        if (is_string($value)) return $value;

        if (!isset($value['refund_id'])) return json_encode($value, 320);
        $params = [];
        $params['success'] = ($value['refund_status'] ?? $value['status']) === 'SUCCESS';
        $params['waybill'] = $value['refund_id'];
        $params['pay_waybill'] = $value['transaction_id'];
        $params['number'] = $value['out_refund_no'];
        $params['time'] = strtotime($value['success_time']);
        $params['state'] = strtolower(substr($value['refund_status'], -20));
        $params['amount'] = intval($value['amount']['refund']);
        $params['total'] = intval($value['amount']['total']);
        return $params;
    }

    public function query(array $params): array|string
    {
        if (isset($params['waybill']) and !empty($params['waybill'])) {
            $rest = $this->get("/v3/ecommerce/refunds/id/{$params['waybill']}");
        } else {
            $rest = $this->get("/v3/refund/domestic/refunds/{$params['number']}");
        }
        if (is_string($rest)) return $rest;
        return $rest;
    }


    /**
     * https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_1_9.shtml
     *
     * @param array $refund
     * @return array|string
     */
    public function send(array $refund): array|string
    {
        $param = [];
        $param['transaction_id'] = $refund['transaction_id'];
        if (empty($param['transaction_id'])) $param['out_trade_no'] = $refund['out_trade_no'];
        $param['out_refund_no'] = $refund['out_refund_no'];
        $param['reason'] = $refund['reason'] ?? '用户要求退款';
        $param['notify_url'] = $refund['notify'];
//        $param['funds_account'] = 'AVAILABLE';
        $param['amount'] = [
            'refund' => $refund['amount'],
            'total' => $refund['total'],
            'currency' => 'CNY',
        ];

        $data = $this->post("/v3/refund/domestic/refunds", $param);
        if (is_string($data)) return $data;

        return [
            'waybill' => $data['refund_id'],
            'number' => $data['out_refund_no'],
            'time' => strtotime($data['create_time']),
            'amount' => intval($data['amount']['payer_refund']),
        ];
    }

    /**
     * 异常退款处理
     *
     * @param array $refund
     * @return array|string
     *
     * https://pay.weixin.qq.com/doc/v3/merchant/4013071193
     */
    public function abnormal(array $refund): array|string
    {
        $param = [];
        $param['out_refund_no'] = $refund['out_refund_no'];
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

        $data = $this->post("/v3/refund/domestic/refunds/{$refund['refund_id']}/apply-abnormal-refund", $param);
        if (is_string($data)) return $data;

        return $data;
    }


}