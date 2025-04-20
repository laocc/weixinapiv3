<?php
declare(strict_types=1);

namespace laocc\weiPay\ecommerce;

use laocc\weiPay\ApiV3Base;
use laocc\weiPay\library\PayFace;
use function esp\helper\str_rand;

class Pay extends ApiV3Base implements PayFace
{
    public function app(array $params)
    {
        // TODO: Implement app() method.
    }

    public function h5(array $params)
    {
        // TODO: Implement h5() method.
    }

    /**
     * 发起公众号、小程序支付
     * @param array $params
     * @return array|string
     */
    public function jsapi(array $params)
    {
        $time = time();
        $data = [];
        $data['sp_appid'] = $this->entity->appID;
        $data['sp_mchid'] = $this->entity->mchID;

//        $data['sub_appid'] = $params['appID'];
        $data['sub_mchid'] = $params['mchid'];

        $data['description'] = $params['description'];
        $data['out_trade_no'] = strval($params['number']);
        $data['time_expire'] = date(DATE_RFC3339, $time + ($params['ttl'] ?? 7200));
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
        $values['appId'] = $this->entity->appID;
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
     * @param array $params
     * @return array|string
     *
     * https://pay.weixin.qq.com/wiki/doc/apiv3_partner/apis/chapter7_2_5.shtml
     */
    public function query(array $params)
    {
        $param = [];
        $param['sp_mchid'] = $this->entity->mchID;
        $param['sub_mchid'] = $params['mchID'];

        $data = $this->get("/v3/pay/partner/transactions/out-trade-no/{$params['number']}", $param);
        if (is_string($data)) return $data;

        return [
            'mchid' => $data['sub_mchid'],
            'number' => $data['out_trade_no'],
            'state' => $data['trade_state'],
            'desc' => $data['trade_state_desc'],
            'waybill' => $data['transaction_id'] ?? '',
            'time' => strtotime($data['success_time'] ?? ''),
            'data' => $data,
        ];
    }

    public function refund(array $params)
    {
        // TODO: Implement refund() method.
    }

}