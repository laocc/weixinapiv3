<?php

namespace laocc\weiPay\custom;


class Complaint extends Base
{

    public function notify()
    {
        return $this->notifyDecrypt();
    }

    public function reply(array $data)
    {
        return '无此接口';
    }

    /**
     * 投诉回调
     *
     * 文档：
     *
     * https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter10_2_11.shtml
     *
     * https://pay.weixin.qq.com/doc/v3/merchant/4012459282
     */

    public function notifyUrl(string $action, string $url = null)
    {
        return '无此接口';
    }


}