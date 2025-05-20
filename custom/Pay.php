<?php
declare(strict_types=1);

namespace laocc\weiPay\custom;

use laocc\weiPay\library\buildPay;
use laocc\weiPay\library\PayFace;
use function esp\helper\str_rand;

class Pay extends Base implements PayFace
{

    use buildPay;

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

        $data['type'] = 'jsapi';
        $data['appid'] = $this->entity->appID;

        $data['subject'] = $params['subject'] ?? $params['description'];
        $data['number'] = strval($params['number']);
        $data['ttl'] = $params['ttl'];
        $data['notify'] = $params['notify'];
        $data['sharing'] = boolval($params['sharing'] ?? 0);//分账
        $data['total'] = $params['total'];
        $data['openid'] = $params['openid'];
        $data['order'] = [...$params['pay']];

        $unified = $this->post("/gateway/order/", $data);
        if (is_string($unified)) return $unified;

        return $this->PayCodeJsAPI($unified['prepay_id'], $time);
    }


    public function notify(): array|string
    {
        $value = $this->notifyDecrypt();
        if (is_string($value)) return $value;

        $params = [];
        $params['mchid'] = $value['mchid'];//商户号
        $params['success'] = $value['trade_state'] === 'SUCCESS';
        $params['waybill'] = $value['transaction_id'];
        $params['number'] = $value['out_trade_no'];
        $params['time'] = strtotime($value['success_time'] ?? '');
        $params['state'] = strtolower(substr($value['trade_state'], -20));
        $params['amount'] = intval($value['amount']['total']);
        return $params;
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
        $time = time();
        $data = [];

        $data['appid'] = $this->entity->appID;
        $data['mchid'] = $this->entity->mchID;

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

        $unified = $this->post("/v3/pay/transactions/app", $data);
        if (is_string($unified)) return $unified;

        return $this->PayCodeForApp($unified['prepay_id'], $time);
    }


    public function h5(array $params): array|string
    {
        return [];
    }

    /**
     * @param array $params
     * @return array|string
     */
    public function query(array $params): array|string
    {
        $post = [];
        $post['waybill'] = $params['waybill'];
        $data = $this->post("/gateway/query/", $post);
        if (is_string($data)) return $data;

        return [
            'mchid' => $data['mchid'],
            'number' => $data['out_trade_no'],
            'success' => $data['trade_state'] === 'SUCCESS',
            'state' => $data['trade_state'],
            'desc' => $data['trade_state_desc'],
            'type' => $data['trade_type'] ?? '',//支付方式，jsapi,app等
            'waybill' => $data['transaction_id'] ?? '',
            'time' => strtotime($data['success_time'] ?? ''),
            'data' => $data,
        ];
    }


}