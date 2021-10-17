<?php
declare(strict_types=1);

namespace esp\weiPay\merchant;

use esp\weiPay\ApiV3Base;
use function esp\helper\str_rand;

class Pay extends ApiV3Base
{
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

        $data['description'] = $params['description'];
        $data['out_trade_no'] = strval($params['number']);
        $data['time_expire'] = date(DATE_RFC3339, $time);
        $data['attach'] = $params['attach'];
        $data['notify_url'] = $params['notify'];

        $data['settle_info'] = [];
        $data['settle_info']['profit_sharing'] = boolval($params['sharing'] ?? 1);//分账

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
        $param['sp_mchid'] = $this->entity->mchID;
        $param['sub_mchid'] = $option['mchID'];

        $data = $this->get("/v3/pay/partner/transactions/id/{$option['transaction_id']}", $param);
        if (is_string($data)) return $data;

        return $data;
    }


}