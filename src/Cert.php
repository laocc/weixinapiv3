<?php
declare(strict_types=1);

namespace esp\weixinapiv3\src;


class Cert extends Base
{
    /**
     * 下载平台证书
     * @return \esp\http\Result
     * https://pay.weixin.qq.com/wiki/doc/apiv3/wechatpay/wechatpay5_1.shtml
     */
    public function downloadPlatCert()
    {
        $json = $this->get("/v3/certificates");

        return $json;
    }
}