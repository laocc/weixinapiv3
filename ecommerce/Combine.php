<?php

namespace laocc\weiPay\ecommerce;


use function esp\helper\str_rand;
use laocc\weiPay\ApiV3Base;

class Combine extends ApiV3Base
{

    /**
     * 合单支付
     * @param array $order
     * @return array|string
     */
    public function jsapi(array $order)
    {
        $time = time();
        $data = [];
        $data['combine_appid'] = $this->entity->appID;
        $data['combine_mchid'] = $this->entity->mchID;
        $data['combine_out_trade_no'] = $order['number'];
        $data['combine_payer_info'] = ['openid' => $order['openid']];
        $data['time_start'] = date(DATE_RFC3339, $time);
        $data['time_expire'] = date(DATE_RFC3339, $time + ($params['ttl'] ?? 7200));
        $data['notify_url'] = $order['notify'];

        $data['sub_orders'] = [];
        foreach ($order['sub_order'] as $sub) {
            $ord = [];
            $ord['mchid'] = $this->entity->mchID;
            $ord['sub_mchid'] = $sub['mchid'];
            $ord['attach'] = str_rand();
            $ord['out_trade_no'] = $sub['number'];
            $ord['description'] = $sub['description'];
            $ord['amount'] = [];
            $ord['amount']['currency'] = 'CNY';
            $ord['amount']['total_amount'] = $sub['amount'];
            $ord['settle_info'] = [];
            $ord['settle_info']['profit_sharing'] = true;
            $ord['settle_info']['subsidy_amount'] = 0;
            $data['sub_orders'][] = $ord;
        }

        $unified = $this->post("/v3/combine-transactions/jsapi", $data);
        if (is_string($unified)) return $unified;

        $values = array();
        $values['timeStamp'] = strval($time);//这timeStamp中间的S必须是大写
        $values['nonceStr'] = str_rand(30);//随机字符串，不长于32位。推荐随机数生成算法
        $values['package'] = "prepay_id={$unified['prepay_id']}";
        $values['signType'] = 'RSA';

        $message = "{$this->entity->appID}\n{$values['timeStamp']}\n{$values['nonceStr']}\n{$values['package']}\n";
        openssl_sign($message, $sign, $this->entity->certEncrypt, 'sha256WithRSAEncryption');
        $values['paySign'] = base64_encode($sign);//生成签名

        return $values;
    }


    public function query(string $ordNumber, string $mchID = null)
    {
        $data = $this->get("/v3/combine-transactions/out-trade-no/{$ordNumber}");
        if (is_string($data)) return $data;
        $values = [];

        foreach ($data['sub_orders'] as $ord) {
            $values[] = [
                'number' => $ord['out_trade_no'],
                'state' => $ord['trade_state'],
                'transaction' => $ord['transaction_id'] ?? '',
                'time' => strtotime($ord['success_time'] ?? ''),
            ];
        }

        return $values;
    }


}