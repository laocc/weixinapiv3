<?php

namespace laocc\weiPay\service;

use laocc\weiPay\ApiV3Base;
use laocc\weiPay\library\buildPay;
use laocc\weiPay\library\PayFace;

class Combine extends ApiV3Base implements PayFace
{

    use buildPay;

    public function notify(): array|string
    {
        $value = $this->notifyDecrypt();
        if (is_string($value)) return $value;

        $params = [];
        $params['appid'] = $value['combine_appid'];//商户号
        $params['mchid'] = $value['combine_mchid'];//商户号
        $params['number'] = $value['combine_out_trade_no'];
        $params['openid'] = $value['combine_payer_info']['openid'];
        $params['time'] = strtotime($value['success_time'] ?? '');
        $params['data'] = $value;
        $params['order'] = [];
        foreach ($value['sub_orders'] as $order) {
            $params['order'][] = [
                'mchid' => $order['sub_mchid'],
                'appid' => $order['sub_appid'],
                'openid' => $order['sub_openid'],
                'waybill' => $order['transaction_id'] ?? '',
                'number' => $order['out_trade_no'] ?? '',
                'type' => $order['trade_type'],
                'state' => $order['trade_state'],
                'attach' => $order['attach'],
                'time' => strtotime($order['success_time'] ?? ''),
                'amount' => intval($order['amount']['total_amount']),
            ];
        }
        return $params;
    }

    /**
     * 发起公众号、小程序支付
     *
     * @param array $params
     * @return array|string
     */
    public function jsapi(array $params): array|string
    {
        $time = time();
        $data = [];

        $data['combine_appid'] = $this->entity->appID;
        $data['combine_mchid'] = $this->entity->mchID;
        $data['combine_out_trade_no'] = strval($params['pay'][0]['number']);
        $data['sub_orders'] = [];

        foreach ($params['pay'] as $pay) {
            $order = [];
            $order['mchid'] = $this->entity->mchID;
            $order['sub_mchid'] = $this->entity->merchant['mchid'];
            $order['sub_appid'] = $this->entity->merchant['appid'];
            $order['attach'] = $pay['attach'];
            $order['description'] = $pay['subject'] ?? $pay['description'];
            $order['out_trade_no'] = strval($pay['number']);

            $order['settle_info'] = [];
            $order['settle_info']['profit_sharing'] = boolval($pay['sharing'] ?? 0);//分账

            $order['amount'] = [];
            $order['amount']['total_amount'] = $pay['fee'];
            $order['amount']['currency'] = 'CNY';

            $data['sub_orders'][] = $order;
        }

        $data['time_expire'] = date(DATE_RFC3339, $time + ($params['ttl'] ?? 7200));
        $data['notify_url'] = $params['notify'];

        $data['combine_payer_info'] = [];
        $data['combine_payer_info']['openid'] = $params['openid'];
        $unified = $this->post("/v3/combine-transactions/jsapi", $data);
        if (is_string($unified)) return $unified;

        return $this->PayCodeJsAPI($unified['prepay_id'], $time);
    }


    /**
     * @param array $params
     * @return array|string
     */
    public function query(array $params): array|string
    {
        $data = $this->get("/v3/combine-transactions/out-trade-no/{$params['number']}");

        if (is_string($data)) return $data;
        $value = [
            'appid' => $data['combine_appid'],
            'mchid' => $data['combine_mchid'],
            'openid' => $data['combine_payer_info']['openid'],
            'number' => $data['combine_out_trade_no'],
            'order' => [],
        ];

        foreach ($data['sub_orders'] as $order) {
            $value['order'][] = [
                'mchid' => $order['sub_mchid'],
                'appid' => $order['sub_appid'],
                'openid' => $order['sub_openid'],
                'waybill' => $order['transaction_id'] ?? '',
                'number' => $order['out_trade_no'] ?? '',
                'type' => $order['trade_type'],
                'state' => $order['trade_state'],
                'attach' => $order['attach'],
                'amount' => intval($order['amount']['total_amount']),
                'time' => strtotime($order['success_time'] ?? ''),
            ];
        }

        return $value;
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


    /**
     * app支付
     *
     * @param array $params
     * @return array|string
     */
    public function app(array $params): array|string
    {

        return [];
    }


    public function h5(array $params): array|string
    {
        return [];
    }

}