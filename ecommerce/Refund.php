<?php
declare(strict_types=1);

namespace laocc\weiPay\ecommerce;

use laocc\weiPay\ApiV3Base;

class Refund extends ApiV3Base
{
    public function send(array $refDataAll)
    {
        $value = [];

        foreach ($refDataAll as $ref) {
            $data = [];
            $data['sp_mchid'] = $this->entity->mchID;
            $data['sp_appid'] = $this->entity->appID;
            $data['sub_mchid'] = $ref['mchID'];
            $data['transaction_id'] = $ref['transaction'];
            $data['out_refund_no'] = $ref['number'];
            $data['reason'] = $ref['reason'];
            $data['notify_url'] = $ref['notify'];
            $data['amount'] = [];
            $data['amount']['refund'] = $ref['amount'];
            $data['amount']['total'] = $ref['total'];
            $data['amount']['currency'] = 'CNY';

            $unified = $this->post("/v3/ecommerce/refunds/apply", $data);
            if (is_string($unified)) {
                $value[$ref['refID']] = $unified;

            } else {
                $value[$ref['refID']] = [
                    'refund_id' => $unified['refund_id'],//微信退款单号
                    'number' => $unified['out_refund_no'],//商户退款单号
                    'time' => strtotime($unified['create_time']),
                    'amount' => intval($unified['amount']['payer_refund']),
                ];
            }
        }

        return $value;
    }


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


}