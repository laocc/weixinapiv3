<?php
declare(strict_types=1);

namespace laocc\weiPay\service;

use laocc\weiPay\ApiV3Base;
use laocc\weiPay\library\PayFace;
use function esp\helper\str_rand;

class Pay extends ApiV3Base implements PayFace
{

    public function notify(string $json): array|string
    {
        $value = $this->notifyDecrypt($json);
        if (is_string($value)) return $value;

        $params = [];
        $params['mchid'] = $value['sub_mchid'];//商户号
        $params['success'] = $value['trade_state'] === 'SUCCESS';
        $params['number'] = $value['out_trade_no'];
        $params['waybill'] = $value['transaction_id'];
        $params['time'] = strtotime($value['success_time'] ?? '');
        $params['state'] = strtolower(substr($value['trade_state'], -20));
        $params['amount'] = intval($value['amount']['total']);
        return $params;
    }


    /**
     * 发起公众号、小程序支付
     * @param array $params
     * @return array|string
     *
     * https://pay.weixin.qq.com/wiki/doc/apiv3_partner/apis/chapter4_5_1.shtml
     */
    public function jsapi(array $params): array|string
    {
        $time = time();
        $data = [];
        $data['sp_appid'] = $this->entity->appID;//若是服务商+子商户模式，此值后面会更改
        $data['sp_mchid'] = $this->entity->mchID;//服务商商户号

        $data['sub_appid'] = $this->entity->merchant['appid'];
        $data['sub_mchid'] = $this->entity->merchant['mchid'];//子商户号

        $data['description'] = $params['subject'] ?? ($params['description'] ?? '');
        $data['out_trade_no'] = strval($params['number']);
        $data['time_expire'] = date(DATE_RFC3339, $time + ($params['ttl'] ?? 7200));
        $data['attach'] = $params['attach'] ?? '';
        $data['notify_url'] = $params['notify'];

        $data['settle_info'] = [];//是否分账
        $data['settle_info']['profit_sharing'] = $params['sharing'] ?? false;

        $data['amount'] = [];
        $data['amount']['total'] = $params['fee'];
        $data['amount']['currency'] = 'CNY';

        $signAppID = $this->entity->appID;

        $data['payer'] = [];
        if (isset($params['sp_openid'])) {
            $data['payer']['sp_openid'] = $params['sp_openid'];//在服务端应用下的OpenID
        } else {
            $data['payer']['sub_openid'] = $params['openid'];//子商户的App中的OpenID
            $signAppID = $this->entity->merchant['appid'];
        }

        $unified = $this->post("/v3/pay/partner/transactions/jsapi", $data);
        if (is_string($unified)) return $unified;

        return $this->paySign($unified['prepay_id'], $signAppID, $time);
    }


    /**
     * native，也就是二维码支付
     *
     * @param array $params
     * @return array|string
     */
    public function native(array $params): array|string
    {
        return [];
    }


    /**
     * 关闭订单
     *
     * @param array $params
     * @return array|string
     */
    public function close(array $params): array|string
    {
        return [];
    }

    public function app(array $params): array|string
    {
        $time = time();
        $data = [];

        $data['sp_appid'] = $this->entity->appID;
        $data['sp_mchid'] = $this->entity->mchID;
        $data['sub_appid'] = $this->entity->merchant['appid'];//子商户的 appid
        $data['sub_mchid'] = $this->entity->merchant['mchid'];

        $data['description'] = $params['description'];
        $data['out_trade_no'] = strval($params['number']);
        $data['time_expire'] = date(DATE_RFC3339, $time + ($params['ttl'] ?? 7200));
        $data['attach'] = $params['attach'];
        $data['notify_url'] = $params['notify'];

        $data['settle_info'] = [];
        $data['settle_info']['profit_sharing'] = boolval($params['sharing'] ?? 0);//分账

        $data['amount'] = [];
        $data['amount']['total'] = $params['fee'];
        $data['amount']['currency'] = 'CNY';

        $unified = $this->post("/v3/pay/partner/transactions/app", $data);

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
     * @param string $payPreID
     * @param string $appid
     * @param int|null $time
     * @return array
     */
    public function paySign(string $payPreID, string $appid, int $time = null): array
    {
        if (is_null($time)) $time = time();

        $values = array();
        $values['appId'] = $appid;
        $values['timeStamp'] = strval($time);//这timeStamp中间的S必须是大写
        $values['nonceStr'] = str_rand(30);//随机字符串，不长于32位。推荐随机数生成算法
        $values['package'] = "prepay_id={$payPreID}";
        $values['signType'] = 'RSA';

        $message = "{$appid}\n{$values['timeStamp']}\n{$values['nonceStr']}\n{$values['package']}\n";
        openssl_sign($message, $sign, $this->entity->certEncrypt, 'sha256WithRSAEncryption');
        $values['paySign'] = base64_encode($sign);//生成签名

        return $values;
    }

    public function h5(array $params): array|string
    {
        // TODO: Implement h5() method.
        return [];
    }

    /**
     * @param array $params
     * @return array|string
     *
     * https://pay.weixin.qq.com/wiki/doc/apiv3_partner/apis/chapter4_5_2.shtml
     */
    public function query(array $params): array|string
    {
        $param = [];
        $param['sp_mchid'] = $this->entity->mchID;
        $param['sub_mchid'] = $this->entity->merchant['mchid'];
        if (empty($params['number'] ?? '') and empty($params['waybill'] ?? '')) {
            return '商户订单号或微信订单号至少要提供一个';
        }

        if ($params['waybill'] ?? '') {
            $data = $this->get("/v3/pay/partner/transactions/id/{$params['waybill']}", $param);
        } else {
            $data = $this->get("/v3/pay/partner/transactions/out-trade-no/{$params['number']}", $param);
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