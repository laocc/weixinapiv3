<?php
declare(strict_types=1);

namespace esp\weiPay\mall;

use esp\weiPay\ApiV3Base;
use function esp\helper\str_rand;

class Pay extends ApiV3Base
{
    /**
     * 发起公众号、小程序支付
     * @param array $params
     * @return array|string
     */
    public function jsapi(array $params)
    {
        $time = time();
        $data = [];
        $data['sp_appid'] = $this->service->miniAppID;
        $data['sp_mchid'] = $this->service->mchID;

//        $data['sub_appid'] = $params['appID'];
        $data['sub_mchid'] = $params['mchID'];

        $data['description'] = $params['subject'];
        $data['out_trade_no'] = $params['id'];
        $data['time_expire'] = date(DATE_RFC3339, $time);
        $data['attach'] = $params['attach'];
        $data['notify_url'] = $params['notify'];

        $data['settle_info'] = [];
        $data['settle_info']['profit_sharing'] = true;

        $data['amount'] = [];
        $data['amount']['total'] = $params['fee'];
        $data['amount']['currency'] = 'CNY';

        $data['payer'] = [];
        $data['payer']['sp_openid'] = $params['openid'];
//        $data['payer']['sub_openid'] = 'CNY';

        $unified = $this->post("/v3/pay/partner/transactions/jsapi", $data);
        if (is_string($unified)) return $unified;

        $values = array();
        $values['timeStamp'] = strval($time);//这timeStamp中间的S必须是大写
        $values['nonceStr'] = str_rand(30);//随机字符串，不长于32位。推荐随机数生成算法
        $values['package'] = "prepay_id={$unified['prepay_id']}";
        $values['signType'] = 'RSA';

        $message = "{$this->service->miniAppID}\n{$values['timeStamp']}\n{$values['nonceStr']}\n{$values['package']}\n";
        openssl_sign($message, $sign, $this->service->certEncrypt, 'sha256WithRSAEncryption');
        $values['paySign'] = base64_encode($sign);//生成签名

        return $values;
    }

    /**
     * 合单支付
     * @param array $order
     * @return array|string
     */
    /**
     * @param array $order
     * @param string $notify
     * @return array|string
     */
    public function jsapi_combine(array $order, string $notify)
    {
        $time = time();
        $data = [];
        $data['combine_appid'] = $this->service->miniAppID;
        $data['combine_mchid'] = $this->service->mchID;
        $data['combine_out_trade_no'] = $order['orderPlatNumber'];
        $data['combine_payer_info'] = ['openid' => $order['orderOpenID']];
        $data['time_start'] = date(DATE_RFC3339, $time);
        $data['time_expire'] = date(DATE_RFC3339, $time + 86400);
        $data['notify_url'] = $notify;

        $data['sub_orders'] = [];
        foreach ($order['sub_order'] as $sub) {
            $ord = [];
            $ord['mchid'] = $this->service->mchID;
            $ord['sub_mchid'] = $sub['subWxMchID'];
            $ord['attach'] = str_rand();
            $ord['out_trade_no'] = $sub['subNumber'];
            $ord['description'] = $sub['subDescription'];
            $ord['amount'] = [];
            $ord['amount']['currency'] = 'CNY';
            $ord['amount']['total_amount'] = $sub['subAmount'];
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

        $message = "{$this->service->miniAppID}\n{$values['timeStamp']}\n{$values['nonceStr']}\n{$values['package']}\n";
        openssl_sign($message, $sign, $this->service->certEncrypt, 'sha256WithRSAEncryption');
        $values['paySign'] = base64_encode($sign);//生成签名

        return $values;
    }


    public function query(string $ordNumber)
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