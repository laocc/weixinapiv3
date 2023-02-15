<?php
declare(strict_types=1);

namespace esp\weiPay\service;

use esp\weiPay\ApiV3Base;


class Certificates extends ApiV3Base
{
    /**
     * 下载平台证书
     * @param string $apiV3Key
     * @return array|string
     *
     * 服务商
     * https://pay.weixin.qq.com/wiki/doc/apiv3_partner/apis/wechatpay5_1.shtml
     * 直连商户：
     * https://pay.weixin.qq.com/wiki/doc/apiv3/apis/wechatpay5_1.shtml
     */
    public function downloadPlatCert(string $apiV3Key)
    {
        $Certificates = $this->get("/v3/certificates");
        if (is_string($Certificates)) return $Certificates;
        foreach ($Certificates['data'] as &$cert) {
            $cert['cert'] = $this->decryptToString($apiV3Key,
                $cert['encrypt_certificate']['associated_data'],
                $cert['encrypt_certificate']['nonce'],
                $cert['encrypt_certificate']['ciphertext']);
        }
        return $Certificates;
    }


}