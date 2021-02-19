<?php
declare(strict_types=1);

namespace esp\weixinapiv3\src;


use esp\library\request\Post;
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

    public function notify($data)
    {
        $serial = getenv('HTTP_WECHATPAY_SERIAL');
        $time = getenv('HTTP_WECHATPAY_TIMESTAMP');
        $nonce = getenv('HTTP_WECHATPAY_NONCE');
        $sign = getenv('HTTP_WECHATPAY_SIGNATURE');
        $json = file_get_contents('php://input');

        $message = "{$time}\n{$nonce}\n{$json}\n";
        if (!is_null($this->crypt)) {
            $certEncrypt = $this->crypt->public();
        } else {
            $cert = _ROOT . "/common/cert/{$serial}/public.pem";
            $certEncrypt = \openssl_get_publickey(file_get_contents($cert));
        }
        $signature = \base64_decode($sign);
        $chk = \openssl_verify($message, $signature, $certEncrypt, 'sha256WithRSAEncryption');
        if ($chk !== 1) return "wxAPIv3 Sign Error";

        $resource = $data['resource'];

        $value = $this->decryptToString($this->service->apiV3Key,
            $resource['associated_data'],
            $resource['nonce'],
            $resource['ciphertext']);

        return $value;
    }


}