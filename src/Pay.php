<?php
declare(strict_types=1);

namespace esp\weixinapiv3\src;


use function esp\helper\str_rand;

class Pay extends ApiV3Base
{
    public function jsapi(array $params)
    {
        $time = time();
        $data = [];
        $data['sp_appid'] = $this->service->miniAppID;
        $data['sp_mchid'] = $this->service->mchID;

//        $data['sub_appid'] = $this->merchant->appID;
        $data['sub_mchid'] = $this->merchant->mchID;

        $data['description'] = $params['subject'];
        $data['out_trade_no'] = $params['id'];
        $data['time_expire'] = date(DATE_RFC3339, $time);
        $data['attach'] = $params['attach'];
        $data['notify_url'] = $params['notify'];

        $data['settle_info'] = [];
        $data['settle_info']['profit_sharing'] = true;

        $data['amount'] = [];
        $data['amount']['total'] = $params['fee'];
        $data['amount']['currency'] = 'CNY';

        $data['payer'] = [];
        $data['payer']['sp_openid'] = $params['openid'];
//        $data['payer']['sub_openid'] = 'CNY';

        $unified = $this->post("/v3/pay/partner/transactions/jsapi", $data);
        if (is_string($unified)) return $unified;

        $values = array();
        $values['timeStamp'] = strval($time);//这timeStamp中间的S必须是大写
        $values['nonceStr'] = str_rand(30);//随机字符串，不长于32位。推荐随机数生成算法
        $values['package'] = "prepay_id={$unified['prepay_id']}";
        $values['signType'] = 'RSA';

        $message = "{$this->service->miniAppID}\n{$values['timeStamp']}\n{$values['nonceStr']}\n{$values['package']}\n";
        openssl_sign($message, $sign, $this->service->certEncrypt, 'sha256WithRSAEncryption');
        $values['paySign'] = base64_encode($sign);//生成签名

        return $values;
    }


    private function paySign(string $method, string $uri, string $body = '')
    {
        $method = strtoupper($method);
        $mchID = $this->service->mchID;
        $nonce = sha1(uniqid('', true));
        $time = time();
        $message = "{$this->service->miniAppID}\n{$uri}\n{$time}\n{$nonce}\n{$body}\n";
        openssl_sign($message, $sign, $this->service->certEncrypt, 'sha256WithRSAEncryption');
        $ts = 'WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"';
        return sprintf($ts, $mchID, $nonce, $time, $this->service->certSerial, base64_encode($sign));
    }
}