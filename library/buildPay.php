<?php

namespace laocc\weiPay\library;

use function esp\helper\str_rand;

trait buildPay
{

    private function jsApiPayID(string $payPreID, string $appid, int $time = null): array
    {
        if (is_null($time)) $time = time();

        $values = array();
        $values['appId'] = $appid;
        $values['timeStamp'] = strval($time);//这timeStamp中间的S必须是大写
        $values['nonceStr'] = str_rand(30);//随机字符串，不长于32位。推荐随机数生成算法
        $values['package'] = "prepay_id={$payPreID}";
        $values['signType'] = 'RSA';

        $message = "{$appid}\n{$values['timeStamp']}\n{$values['nonceStr']}\n{$values['package']}\n";
        openssl_sign($message, $sign, $this->entity->certEncrypt, 'sha256WithRSAEncryption');
        $values['paySign'] = base64_encode($sign);//生成签名

        return $values;
    }

}