<?php
declare(strict_types=1);

namespace laocc\weiPay\ecommerce;

use laocc\weiPay\ApiV3Base;
use laocc\weiPay\library\buildPay;
use laocc\weiPay\library\PayFace;

class Pay extends ApiV3Base implements PayFace
{
    use buildPay;

    public function notify(): array|string
    {
        $value = $this->notifyDecrypt();
        if (is_string($value)) return $value;

        $params = [];
        $params['mchid'] = $value['sub_mchid'];//商户号
        $params['success'] = $value['trade_state'] === 'SUCCESS';
        $params['waybill'] = $value['transaction_id'];
        $params['number'] = $value['out_trade_no'];
        $params['time'] = strtotime($value['success_time']);
        $params['state'] = strtolower(substr($value['trade_state'], -20));
        $params['amount'] = intval($value['amount']['total']);
        return $params;
    }


    /**
     * 发起公众号、小程序支付
     * @param array $params
     * @return array|string
     */
    public function jsapi(array $params): array|string
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
        return $this->PayCodeJsAPI($unified['prepay_id'], $time);
    }


    /**
     * @param array $params
     * @return array|string
     *
     * https://pay.weixin.qq.com/wiki/doc/apiv3_partner/apis/chapter7_2_5.shtml
     */
    public function query(array $params): array|string
    {
        $param = [];
        $param['sp_mchid'] = $this->entity->mchID;
        $param['sub_mchid'] = $params['mchID'];

        $data = $this->get("/v3/pay/partner/transactions/out-trade-no/{$params['number']}", $param);
        if (is_string($data)) return $data;

        return [
            'mchid' => $data['sub_mchid'],
            'number' => $data['out_trade_no'],
            'success' => $data['trade_state'] === 'SUCCESS',
            'state' => $data['trade_state'],
            'desc' => $data['trade_state_desc'],
            'type' => $data['trade_type'] ?? '',
            'waybill' => $data['transaction_id'] ?? '',
            'time' => strtotime($data['success_time'] ?? ''),
            'data' => $data,
        ];
    }

    public function app(array $params): array|string
    {
        return '';
    }

    public function h5(array $params): array|string
    {
        return '';
    }

    public function native(array $params): array|string
    {
        return '未开发';
    }

    public function close(array $params): array|string
    {
        return '未开发';
    }
}