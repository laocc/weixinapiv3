<?php
declare(strict_types=1);

namespace esp\weiPay\mall;

use esp\weiPay\ApiV3Base;

class Refund extends ApiV3Base
{
    public function apply(array $refDataAll)
    {
        $value = [];

        foreach ($refDataAll as $ref) {
            $data = [];
            $data['sp_mchid'] = $this->entity->mchID;
            $data['sp_appid'] = $this->entity->mppAppID;
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
                $value[$ref['mchID']] = $unified;

            } else {
                $value[$ref['mchID']] = [
                    'refund_id' => $unified['refund_id'],
                    'number' => $unified['out_refund_no'],
                    'time' => strtotime($unified['create_time']),
                    'amount' => intval($unified['amount']['payer_refund']),
                ];
            }
        }

        return $value;
    }
}