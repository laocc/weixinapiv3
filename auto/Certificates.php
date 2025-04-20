<?php
declare(strict_types=1);

namespace laocc\weiPay\auto;

use laocc\weiPay\ApiV3Base;

final class Certificates extends ApiV3Base
{
    /**
     * 下载平台证书
     * @return array|string
     *
     * 服务商
     * https://pay.weixin.qq.com/wiki/doc/apiv3_partner/apis/wechatpay5_1.shtml
     * https://pay.weixin.qq.com/doc/v3/partner/4012715700
     *
     * 直连商户：
     * https://pay.weixin.qq.com/wiki/doc/apiv3/apis/wechatpay5_1.shtml
     * https://pay.weixin.qq.com/doc/v3/merchant/4012551764
     */
    public function downloadPlatCert()
    {
        $Certificates = $this->signCheck(false)->get("/v3/certificates");
        if (is_string($Certificates)) return $Certificates;

        foreach ($Certificates['data'] as &$cert) {
            $cert['cert'] = $this->decryptToString($this->entity->certKey,
                $cert['encrypt_certificate']['associated_data'],
                $cert['encrypt_certificate']['nonce'],
                $cert['encrypt_certificate']['ciphertext']);
        }

        return $Certificates;
    }


}