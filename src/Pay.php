<?php
declare(strict_types=1);

namespace esp\weixinapiv3\src;

use function esp\helper\str_rand;

class Pay extends ApiV3Base
{
    /**
     * 发起公众号、小程序支付
     * @param array $params
     * @return array|string
     */
    public function jsapi(array $params)
    {
        $time = time();
        $data = [];
        $data['sp_appid'] = $this->service->miniAppID;
        $data['sp_mchid'] = $this->service->mchID;

//        $data['sub_appid'] = $params['appID'];
        $data['sub_mchid'] = $params['mchID'];

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

    /**
     * 合单支付
     * @param array $order
     * @return array|string
     */
    /**
     * @param array $order
     * @param string $notify
     * @return array|string
     */
    public function jsapi_combine(array $order, string $notify)
    {
        $time = time();
        $data = [];
        $data['combine_appid'] = $this->service->miniAppID;
        $data['combine_mchid'] = $this->service->mchID;
        $data['combine_out_trade_no'] = $order['orderPlatNumber'];
        $data['combine_payer_info'] = ['openid' => $order['orderOpenID']];
        $data['time_start'] = date(DATE_RFC3339, $time);
        $data['time_expire'] = date(DATE_RFC3339, $time + 86400);
        $data['notify_url'] = $notify;

        $data['sub_orders'] = [];
        foreach ($order['sub_order'] as $sub) {
            $ord = [];
            $ord['mchid'] = $this->service->mchID;
            $ord['sub_mchid'] = $sub['subMchID'];
            $ord['attach'] = str_rand();
            $ord['out_trade_no'] = $sub['subNumber'];
            $ord['description'] = $sub['subDescription'];
            $ord['amount'] = [];
            $ord['amount']['currency'] = 'CNY';
            $ord['amount']['total_amount'] = $sub['subAmount'];
            $ord['settle_info'] = [];
            $ord['settle_info']['profit_sharing'] = true;
            $ord['settle_info']['subsidy_amount'] = 0;
            $data['sub_orders'][] = $ord;
        }

        $unified = $this->post("/v3/combine-transactions/jsapi", $data);
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

    /**
     * 受理通知数据，验签，并解密
     * @param $data
     * @return mixed|string
     */
    public function notifyDecrypt($data)
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
            $cert = _CERT . "/{$serial}/public.pem";
            $certEncrypt = \openssl_get_publickey(file_get_contents($cert));
        }
        $chk = \openssl_verify($message, \base64_decode($sign), $certEncrypt, 'sha256WithRSAEncryption');
        if ($chk !== 1) return "wxAPIv3 Sign Error";

        $resource = $data['resource'];

        $value = $this->decryptToString($this->service->apiV3Key,
            $resource['associated_data'],
            $resource['nonce'],
            $resource['ciphertext']);
        if ($value === false) return "数据解密失败";

        return json_decode($value, true);
    }


}