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

    private function createComplaint()
    {
        if ($this->entity->service) {
            return new \laocc\weiPay\service\Complaint($this->entity);
        } else {
            return new \laocc\weiPay\merchant\Complaint($this->entity);
        }
    }


    public function notifyUrl(string $method, string $url = null)
    {
        return $this->createComplaint()->notifyUrl($method, $url);
    }


    public function notify(string $json): bool|string
    {
        return $this->createComplaint()->notify($json);
    }


}