<?php

namespace laocc\weiPay\library;

use function esp\helper\str_rand;

trait buildPay
{

    private function PayCodeJsAPI(string $payPreID, int $time, string $appID = null): array
    {
        if (!$appID) $appID = $this->entity->appID;

        $value = array();
        $value['appId'] = $appID;
        $value['timeStamp'] = strval($time);//这timeStamp中间的S必须是大写
        $value['nonceStr'] = str_rand(30);//随机字符串，不长于32位。推荐随机数生成算法
        $value['package'] = "prepay_id={$payPreID}";
        $value['signType'] = 'RSA';

        $message = "{$appID}\n{$value['timeStamp']}\n{$value['nonceStr']}\n{$value['package']}\n";
        openssl_sign($message, $sign, $this->entity->certEncrypt, 'sha256WithRSAEncryption');
        $value['paySign'] = base64_encode($sign);//生成签名

        return $value;
    }

    private function PayCodeForApp(string $payPreID, int $time, string $appID = null)
    {
        if (!$appID) $appID = $this->entity->appID;

        $value = array();
        $value['appId'] = $appID;
        $value['partnerId'] = $this->entity->mchID;
        $value['prepayId'] = $payPreID;
        $value['packageValue'] = "Sign=WXPay";
        $value['nonceStr'] = str_rand(30);//随机字符串，不长于32位。推荐随机数生成算法
        $value['timeStamp'] = strval($time);//这timeStamp中间的S必须是大写

        $message = "{$appID}\n{$value['timeStamp']}\n{$value['nonceStr']}\n{$value['partnerId']}\n";
        openssl_sign($message, $sign, $this->entity->certEncrypt, 'sha256WithRSAEncryption');
        $value['sign'] = base64_encode($sign);//生成签名

        return $value;
    }

}