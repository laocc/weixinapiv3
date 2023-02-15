<?php
declare(strict_types=1);

namespace esp\weiPay\merchant;

use esp\weiPay\ApiV3Base;
use function esp\helper\str_rand;

class Pay extends ApiV3Base
{

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

        if ($this->entity->isService) {
            $data['sp_appid'] = $this->entity->appID;
            $data['sp_mchid'] = $this->entity->mchID;

            if (isset($params['appID'])) {
                //用子商户的appid
                $data['sub_appid'] = $params['appID'];
            }
            $data['sub_mchid'] = $params['mchID'];
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

        if ($this->entity->isService) {
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
     * 服务商和直连都可用，取决于 $this->entity->isService
     *
     * @param array $params
     * @return array|string
     */
    public function jsapi(array $params)
    {
        $time = time();
        $data = [];

        if ($this->entity->isService) {
            $data['sp_appid'] = $this->entity->appID;
            $data['sp_mchid'] = $this->entity->mchID;

            if (isset($params['appID'])) {
                //用子商户的appid
                $data['sub_appid'] = $params['appID'];
            }
            $data['sub_mchid'] = $params['mchID'];
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
        if ($this->entity->isService) {
            if (isset($params['appID'])) {
                $data['payer']['sub_openid'] = $params['openid'];
            } else {
                $data['payer']['sp_openid'] = $params['openid'];
            }
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


    public function query(array $option)
    {
        $param = [];
        $param['mchid'] = $this->entity->mchID;

        if ($option['transaction_id'] ?? '') {
            $data = $this->get("/v3/pay/transactions/id/{$option['transaction_id']}", $param);
        } else if ($option['out_trade_no'] ?? '') {
            $data = $this->get("/v3/pay/transactions/out-trade-no/{$option['out_trade_no']}", $param);
        } else {
            return "商户订单号或通道订单号至少要填1项";
        }
        if (is_string($data)) return $data;

        return $data;
    }


    public function refund(array $option)
    {
        $param = [];
        $param['transaction_id'] = $option['transaction_id'];
        $param['out_trade_no'] = $option['out_trade_no'];
        $param['out_refund_no'] = $option['out_refund_no'];
        $param['reason'] = $option['out_refund_no'] ?? '用户要求退款';
        $param['notify_url'] = $option['notify'];
//        $param['funds_account'] = 'AVAILABLE';
        $param['amount'] = [
            'refund' => $option['refund'] ?? $option['total'],
            'total' => $option['total'],
            'currency' => 'CNY',
        ];

        $data = $this->post("/v3/refund/domestic/refunds", $param);
        if (is_string($data)) return $data;

        return $data;
    }


}