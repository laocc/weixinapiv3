<?php

namespace laocc\weiPay\auto;

use laocc\weiPay\library\Entity;

class Complaint
{
    protected Entity $entity;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }


    public function notifyUrl(string $method, string $url = null)
    {
        if ($this->entity->service) {
            $pay = new \laocc\weiPay\service\Complaint($this->entity);
        } else {
            $pay = new \laocc\weiPay\merchant\Complaint($this->entity);
        }
        return $pay->notifyUrl($method, $url);
    }


    public function reply(array $params): bool|string
    {
        if ($this->entity->service) {
            $pay = new \laocc\weiPay\service\Complaint($this->entity);
        } else {
            $pay = new \laocc\weiPay\merchant\Complaint($this->entity);
        }
        return $pay->reply($params);
    }


}