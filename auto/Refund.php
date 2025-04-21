<?php

namespace laocc\weiPay\auto;

use laocc\weiPay\library\Entity;
use laocc\weiPay\library\RefundFace;
use laocc\weiPay\service\Refund as sRefund;
use laocc\weiPay\merchant\Refund as mRefund;

class Refund implements RefundFace
{
    protected Entity $entity;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    private function createRefund(): sRefund|mRefund
    {
        if ($this->entity->service) {
            return new sRefund($this->entity);
        } else {
            return new mRefund($this->entity);
        }
    }

    public function notify(): array|string
    {
        return $this->createRefund()->notify();
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