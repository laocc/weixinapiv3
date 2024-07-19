<?php
declare(strict_types=1);

namespace laocc\weiPay\service;

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
     *
     * https://pay.weixin.qq.com/wiki/doc/apiv3_partner/apis/chapter4_5_1.shtml
     */
    public function jsapi(array $params)
    {
        $time = time();
        $data = [];
        $data['sp_appid'] = $this->entity->appID;//若是服务商+子商户模式，此值后面会更改
        $data['sp_mchid'] = $this->entity->mchID;//服务商商户号

        $data['sub_appid'] = $this->entity->appID;
        $data['sub_mchid'] = $params['mchID'];//子商户号

        $data['description'] = $params['subject'] ?? ($params['description'] ?? '');
        $data['out_trade_no'] = strval($params['number']);
        $data['time_expire'] = date(DATE_RFC3339, $time);
        $data['attach'] = $params['attach'] ?? '';
        $data['notify_url'] = $params['notify'];

        $data['settle_info'] = [];//是否分账
        $data['settle_info']['profit_sharing'] = $params['sharing'] ?? false;

        $data['amount'] = [];
        $data['amount']['total'] = $params['fee'];
        $data['amount']['currency'] = 'CNY';

        $data['payer'] = [];
        $data['payer']['sp_openid'] = $params['openid'];

        /**
         * 非服务商直接商户模式，需要单独传入sp_sub信息：
         * ['sp_sub']['appid']      服务商应用APPID
         * ['sp_sub']['openid']     用户在此APPID下的OPENID
         */
        if (isset($params['sp_sub'])) {
            $data['sp_appid'] = $params['sp_sub']['appid'];

            $data['payer']['sub_openid'] = $params['openid'];
            $data['payer']['sp_openid'] = $params['sp_sub']['openid'];
        }

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
     * @param array $params
     * @return array|string
     *
     * https://pay.weixin.qq.com/wiki/doc/apiv3_partner/apis/chapter4_5_2.shtml
     */
    public function query(array $params)
    {
        $param = [];
        $param['sp_mchid'] = $this->entity->mchID;
        $param['sub_mchid'] = $params['mchID'];
        if (empty($params['number'] ?? '') and empty($params['waybill'] ?? '')) {
            return '商户订单号或微信订单号至少要提供一个';
        }

        if (empty($params['waybill'])) {
            $data = $this->get("/v3/pay/partner/transactions/out-trade-no/{$params['number']}", $param);
        } else {
            $data = $this->get("/v3/pay/partner/transactions/id/{$params['waybill']}", $param);
        }
        if (is_string($data)) return $data;

        return [
            'mchid' => $data['sub_mchid'],
            'number' => $data['out_trade_no'],
            'state' => $data['trade_state'],
            'waybill' => $data['transaction_id'] ?? '',
            'time' => strtotime($data['success_time'] ?? ''),
            'data' => $data,
        ];
    }


}