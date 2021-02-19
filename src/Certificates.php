<?php
declare(strict_types=1);

namespace esp\weixinapiv3\src;


class Certificates extends ApiV3Base
{
    /**
     * 下载平台证书
     * @return array|string
     * https://pay.weixin.qq.com/wiki/doc/apiv3/wechatpay/wechatpay5_1.shtml
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