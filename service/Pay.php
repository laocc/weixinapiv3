<?php
declare(strict_types=1);

namespace esp\weiPay\service;

use esp\weiPay\ApiV3Base;
use function esp\helper\str_rand;

class Pay extends ApiV3Base
{
    /**
     * 发起公众号、小程序支付
     * @param array $params
     * @return array|string
     *
     * https://pay.weixin.qq.com/wiki/doc/apiv3_partner/apis/chapter4_5_1.shtml
     */
    public function jsapi(array $params)
    {
        $time = time();
        $data = [];
        $data['sp_appid'] = $this->entity->appID;
        $data['sp_mchid'] = $this->entity->mchID;

//        $data['sub_appid'] = $params['appID'];
        $data['sub_mchid'] = $params['mchID'];

        $data['description'] = $params['subject'];
        $data['out_trade_no'] = $params['id'];
        $data['time_expire'] = date(DATE_RFC3339, $time);
        $data['attach'] = $params['attach'];
        $data['notify_url'] = $params['notify'];

        $data['settle_info'] = [];//是否分账
        $data['settle_info']['profit_sharing'] = $params['sharing'] ?? false;

        $data['amount'] = [];
        $data['amount']['total'] = $params['fee'];
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

        $message = "{$this->entity->appID}\n{$values['timeStamp']}\n{$values['nonceStr']}\n{$values['package']}\n";
        openssl_sign($message, $sign, $this->entity->certEncrypt, 'sha256WithRSAEncryption');
        $values['paySign'] = base64_encode($sign);//生成签名

        return $values;
    }


    /**
     * @param array $option
     * @return array|string
     *
     * https://pay.weixin.qq.com/wiki/doc/apiv3_partner/apis/chapter4_5_2.shtml
     */
    public function query(array $option)
    {
        $param = [];
        $param['sp_mchid'] = $this->entity->mchID;
        $param['sub_mchid'] = $option['mchID'];
        if (empty($option['number'] ?? '') and empty($option['transaction_id'] ?? '')) {
            return '商户订单号或微信订单号至少要提供一个';
        }

        if (empty($option['transaction_id'])) {
            $data = $this->get("/v3/pay/partner/transactions/out-trade-no/{$option['number']}", $param);
        } else {
            $data = $this->get("/v3/pay/partner/transactions/id/{$option['transaction_id']}", $param);
        }
        if (is_string($data)) return $data;

        $value = [
            'mchid' => $data['sub_mchid'],
            'number' => $data['out_trade_no'],
            'state' => $data['trade_state'],
            'transaction' => $data['transaction_id'] ?? '',
            'time' => strtotime($data['success_time'] ?? ''),
        ];

        return $value;
    }


}