<?php
declare(strict_types=1);

namespace esp\weiPay\ecommerce;

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
        $data['sp_appid'] = $this->entity->miniAppID;
        $data['sp_mchid'] = $this->entity->mchID;

//        $data['sub_appid'] = $params['appID'];
        $data['sub_mchid'] = $params['mchid'];

        $data['description'] = $params['description'];
        $data['out_trade_no'] = $params['number'];
        $data['time_expire'] = date(DATE_RFC3339, $time);
        $data['attach'] = $params['attach'];
        $data['notify_url'] = $params['notify'];

        $data['settle_info'] = [];
        $data['settle_info']['profit_sharing'] = $params['sharing'] ?? true;

        $data['amount'] = [];
        $data['amount']['total'] = $params['amount'];
        $data['amount']['currency'] = 'CNY';

        $data['payer'] = [];
        $data['payer']['sp_openid'] = $params['openid'];

        $unified = $this->post("/v3/pay/partner/transactions/jsapi", $data);
        if (is_string($unified)) return $unified;

        $values = array();
        $values['timeStamp'] = strval($time);//这timeStamp中间的S必须是大写
        $values['nonceStr'] = str_rand(30);//随机字符串，不长于32位。推荐随机数生成算法
        $values['package'] = "prepay_id={$unified['prepay_id']}";
        $values['signType'] = 'RSA';

        $message = "{$this->entity->miniAppID}\n{$values['timeStamp']}\n{$values['nonceStr']}\n{$values['package']}\n";
        openssl_sign($message, $sign, $this->entity->certEncrypt, 'sha256WithRSAEncryption');
        $values['paySign'] = base64_encode($sign);//生成签名

        return $values;
    }


    /**
     * @param string $ordNumber
     * @param string $mchID
     * @return array|mixed|null|string
     *
     * https://pay.weixin.qq.com/wiki/doc/apiv3_partner/apis/chapter7_2_5.shtml
     */
    public function query(string $ordNumber, string $mchID)
    {
        $param = [];
        $param['sp_mchid'] = $this->entity->mchID;
        $param['sub_mchid'] = $mchID;

        $data = $this->get("/v3/pay/partner/transactions/out-trade-no/{$ordNumber}", $param);
        if (is_string($data)) return $data;
        $values = [];

        $values[] = [
            'number' => $data['out_trade_no'],
            'state' => $data['trade_state'],
            'desc' => $data['trade_state_desc'],
            'transaction' => $data['transaction_id'] ?? '',
            'time' => strtotime($data['success_time'] ?? ''),
        ];

        return $values;
    }


}