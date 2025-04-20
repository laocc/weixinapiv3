<?php

namespace laocc\weiPay\auto;

use laocc\weiPay\library\Entity;
use laocc\weiPay\library\RefundFace;

class Refund implements RefundFace
{
    protected Entity $entity;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    public function notify(array $post): array|string
    {
        if ($this->entity->service) {
            $pay = new \laocc\weiPay\service\Refund($this->entity);
        } else {
            $pay = new \laocc\weiPay\merchant\Refund($this->entity);
        }

        return $pay->notify($post);
    }


    public function query(array $params): array|string
    {
        if ($this->entity->service) {
            $pay = new \laocc\weiPay\service\Refund($this->entity);
        } else {
            $pay = new \laocc\weiPay\merchant\Refund($this->entity);
        }
        return $pay->query($params);
    }


    public function abnormal(array $refund): array|string
    {
        if ($this->entity->service) {
            $pay = new \laocc\weiPay\service\Refund($this->entity);
        } else {
            $pay = new \laocc\weiPay\merchant\Refund($this->entity);
        }
        return $pay->abnormal($refund);
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
        if ($this->entity->service) {
            $pay = new \laocc\weiPay\service\Refund($this->entity);
        } else {
            $pay = new \laocc\weiPay\merchant\Refund($this->entity);
        }
        return $pay->send($refund);
    }
}