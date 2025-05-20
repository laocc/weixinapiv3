<?php
declare(strict_types=1);

namespace laocc\weiPay\merchant;

use laocc\weiPay\ApiV3Base;
use laocc\weiPay\library\buildPay;
use laocc\weiPay\library\PayFace;
use function esp\helper\str_rand;

class Pay extends ApiV3Base implements PayFace
{
    use buildPay;

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
     * 发起公众号、小程序支付
     *
     * @param array $params
     * @return array|string
     */
    public function jsapi(array $params): array|string
    {
        $time = time();
        $data = [];

        $data['appid'] = $this->entity->appID;
        $data['mchid'] = $this->entity->mchID;

        $data['description'] = $params['subject'] ?? $params['description'];
        $data['out_trade_no'] = strval($params['number']);
        $data['time_expire'] = date(DATE_RFC3339, $time + ($params['ttl'] ?? 7200));
        $data['attach'] = $params['attach'];
        $data['notify_url'] = $params['notify'];

        $data['settle_info'] = [];
        $data['settle_info']['profit_sharing'] = boolval($params['sharing'] ?? 0);//分账

        $data['amount'] = [];
        $data['amount']['total'] = $params['total'] ?? $params['fee'];
        $data['amount']['currency'] = 'CNY';

        $data['payer'] = [];
        $data['payer']['openid'] = $params['openid'];
        $unified = $this->post("/v3/pay/transactions/jsapi", $data);
        if (is_string($unified)) return $unified;

        return $this->PayCodeJsAPI($unified['prepay_id'], $time);
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
        $data['amount']['total'] = $params['total'] ?? ($params['amount'] ?? $params['fee']);
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
        $param = [];
        $param['mchid'] = $this->entity->mchID;

        if ($params['waybill'] ?? '') {
            $data = $this->get("/v3/pay/transactions/id/{$params['waybill']}", $param);

        } else {
            $data = $this->get("/v3/pay/transactions/out-trade-no/{$params['number']}", $param);
        }

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