<?php

namespace laocc\weiPay\auto;

use esp\error\Error;
use laocc\weiPay\library\Entity;
use laocc\weiPay\library\RefundFace;

use laocc\weiPay\ecommerce\Refund as eRefund;
use laocc\weiPay\service\Refund as sRefund;
use laocc\weiPay\merchant\Refund as mRefund;

class Refund implements RefundFace
{
    protected Entity $entity;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    private function createRefund(): sRefund|mRefund|eRefund
    {
        //服务商类型，1直连商户，2普通服务商，4电商服务商，32自建支付中心
        return match ($this->entity->service) {
            1 => new mRefund($this->entity),
            2 => new sRefund($this->entity),
            4 => new eRefund($this->entity),
            default => throw new Error("未知商户类型{$this->entity->service}"),
        };
    }

    public function notify(): array|string
    {
        return $this->createRefund()->notify();
    }


    /**
     * 解密
     *
     * @param string $ciphertext
     * @return string
     * @throws Error
     */
    public function decryptedCipher(string $ciphertext): string
    {
        return $this->entity->decryptedCipher($ciphertext);
    }

    public function query(array $params): array|string
    {
        return $this->createRefund()->query($params);
    }


    public function abnormal(array $refund): array|string
    {
        return $this->createRefund()->abnormal($refund);
    }


    /**
     * 请求退款
     * @param array $refund
     * @return array|string
     *
     * https://pay.weixin.qq.com/wiki/doc/apiv3_partner/apis/chapter4_5_9.shtml
     */
    public function send(array $refund): array|string
    {
        return $this->createRefund()->send($refund);
    }
}