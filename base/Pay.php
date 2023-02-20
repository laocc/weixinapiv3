<?php

namespace laocc\weiPay\base;

use laocc\weiPay\ApiV3Base;
use function esp\helper\str_rand;

class Pay extends ApiV3Base
{

    public function notify(array $value): array
    {
        $params = [];
        $params['success'] = $value['trade_state'] === 'SUCCESS';
        $params['waybill'] = $value['transaction_id'];
        $params['time'] = strtotime($value['success_time']);
        $params['state'] = strtolower(substr($value['trade_state'], -20));
        $params['fee'] = intval($value['amount']['total']);
        return $params;
    }


    /**
     * app支付
     *
     * @param array $params
     * @return array|string
     */
    public function app(array $params)
    {
        $time = time();
        $data = [];

        if ($this->entity->service > 1) {
            $data['sp_appid'] = $this->entity->appID;
            $data['sp_mchid'] = $this->entity->mchID;
            $data['sub_appid'] = $this->entity->shopAppID;
            $data['sub_mchid'] = $this->entity->shopMchID;
        } else {
            $data['appid'] = $this->entity->appID;
            $data['mchid'] = $this->entity->mchID;
        }

        $data['description'] = $params['description'];
        $data['out_trade_no'] = strval($params['number']);
        $data['time_expire'] = date(DATE_RFC3339, $time + ($params['ttl'] ?? 60));
        $data['attach'] = $params['attach'];
        $data['notify_url'] = $params['notify'];

        $data['settle_info'] = [];
        $data['settle_info']['profit_sharing'] = boolval($params['sharing'] ?? 0);//分账

        $data['amount'] = [];
        $data['amount']['total'] = $params['fee'];
        $data['amount']['currency'] = 'CNY';

        if ($this->entity->service > 1) {
            $unified = $this->post("/v3/pay/partner/transactions/app", $data);
        } else {
            $unified = $this->post("/v3/pay/transactions/app", $data);
        }

        if (is_string($unified)) return $unified;

        /**
         * 这里组合的是送给前端用的 orderInfo部分内容
         */
        $value = array();
        $value['appid'] = $this->entity->appID;
        $value['partnerid'] = $this->entity->mchID;
        $value['prepayid'] = $unified['prepay_id'];
        $value['package'] = "Sign=WXPay";
        $value['noncestr'] = str_rand(30);//随机字符串，不长于32位。推荐随机数生成算法
        $value['timestamp'] = strval($time);//这timeStamp中间的S必须是大写

        $message = "{$this->entity->appID}\n{$value['timestamp']}\n{$value['noncestr']}\n{$value['prepayid']}\n";
        openssl_sign($message, $sign, $this->entity->certEncrypt, 'sha256WithRSAEncryption');
        $value['sign'] = base64_encode($sign);//生成签名

        return $value;
    }

    /**
     * 发起公众号、小程序支付
     * 服务商和直连都可用，取决于 $this->entity->service
     *
     * @param array $params
     * @return array|string
     */
    public function jsapi(array $params)
    {
        $time = time();
        $data = [];

        if ($this->entity->service > 1) {
            $data['sp_appid'] = $this->entity->appID;
            $data['sp_mchid'] = $this->entity->mchID;
            $data['sub_appid'] = $this->entity->shopAppID;
            $data['sub_mchid'] = $this->entity->shopMchID;
        } else {
            $data['appid'] = $this->entity->appID;
            $data['mchid'] = $this->entity->mchID;
        }

        $data['description'] = $params['subject'] ?? $params['description'];
        $data['out_trade_no'] = strval($params['number']);
        $data['time_expire'] = date(DATE_RFC3339, $time + ($params['ttl'] ?? 60));
        $data['attach'] = $params['attach'];
        $data['notify_url'] = $params['notify'];

        $data['settle_info'] = [];
        $data['settle_info']['profit_sharing'] = boolval($params['sharing'] ?? 0);//分账

        $data['amount'] = [];
        $data['amount']['total'] = $params['fee'];
        $data['amount']['currency'] = 'CNY';

        $data['payer'] = [];
        if ($this->entity->service > 1) {//服务商
            $data['payer']['sub_openid'] = $params['openid'];
//            $data['payer']['sp_openid'] = $params['openid'];//与sub_openid二选一
            $unified = $this->post("/v3/pay/partner/transactions/jsapi", $data);
        } else {
            $data['payer']['openid'] = $params['openid'];
            $unified = $this->post("/v3/pay/transactions/jsapi", $data);
        }

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

    public function h5(array $params)
    {
        // TODO: Implement h5() method.
    }


    /**
     * @param array $params
     * @return array|string
     */
    public function query(array $params)
    {
        $param = [];
        if ($this->entity->service > 1) {
            $param['sp_mchid'] = $this->entity->mchID;
            $param['sub_mchid'] = $this->entity->shopMchID;
            $api = '/partner';
        } else {
            $param['mchid'] = $this->entity->mchID;
            $api = '';
        }

        if ($params['waybill'] ?? '') {
            $data = $this->get("/v3/pay{$api}/transactions/id/{$params['waybill']}", $param);
        } else if ($params['number'] ?? '') {
            $data = $this->get("/v3/pay{$api}/transactions/out-trade-no/{$params['number']}", $param);

        } else {
            return "商户订单号或通道订单号至少要填1项";
        }
        if (is_string($data)) return $data;

        return [
            'mchid' => $data['mchid'],
            'number' => $data['out_trade_no'],
            'state' => $data['trade_state'],
            'waybill' => $data['transaction_id'] ?? '',
            'time' => strtotime($data['success_time'] ?? ''),
            'data' => $data,
        ];
    }

}