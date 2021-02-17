<?php
declare(strict_types=1);

namespace esp\weixinapiv3\src;

use esp\http\Http;

class Pay extends Base
{
    public function jsapi(array $params)
    {
        $api = "/v3/pay/partner/transactions/jsapi";

        $data = [];
        $data['sp_appid'] = $this->service->appID;
        $data['sp_mchid'] = $this->service->mchID;

        $data['sub_appid'] = $this->merchant->appID;
        $data['sub_mchid'] = $this->merchant->mchID;

        $data['description'] = $params['description'];
        $data['out_trade_no'] = $params['number'];
        $data['time_expire'] = date('YmdTHis+08:00');
        $data['attach'] = mt_rand();
        $data['notify_url'] = $params['notify'];

        $data['settle_info'] = [];
        $data['settle_info']['profit_sharing'] = true;

        $data['amount'] = [];
        $data['amount']['total'] = $params['amount'];
        $data['amount']['currency'] = 'CNY';

        $data['payer'] = [];
        $data['payer']['sp_openid'] = $params['amount'];
        $data['payer']['sub_openid'] = 'CNY';


        $data = json_encode($data, 256 | 64);

        $option = [];
        $option['encode'] = 'json';
        $option['headers'][] = "Content-Type: application/json";
        $option['headers'][] = $this->sign('POST', $api, $data);

        $http = new Http($option);
        return $http->data($data)->post($this->api . $api);
    }
}