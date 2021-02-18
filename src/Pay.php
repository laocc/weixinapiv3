<?php
declare(strict_types=1);

namespace esp\weixinapiv3\src;


use function esp\helper\str_rand;

class Pay extends ApiV3Base
{
    public function jsapi(array $params)
    {
        $data = [];
        $data['sp_appid'] = $this->service->appID;
        $data['sp_mchid'] = $this->service->mchID;

//        $data['sub_appid'] = $this->merchant->appID;
        $data['sub_mchid'] = $this->merchant->mchID;

        $data['description'] = $params['subject'];
        $data['out_trade_no'] = $params['id'];
        $data['time_expire'] = date('YmdTHis+08:00');
        $data['attach'] = $params['attach'];
        $data['notify_url'] = $params['notify'];

        $data['settle_info'] = [];
        $data['settle_info']['profit_sharing'] = true;

        $data['amount'] = [];
        $data['amount']['total'] = $params['fee'];
        $data['amount']['currency'] = 'CNY';

        $data['payer'] = [];
        $data['payer']['sp_openid'] = $params['openid'];
        $data['payer']['sub_openid'] = 'CNY';

        return $this->post("/v3/pay/partner/transactions/jsapi", $data);
    }
}