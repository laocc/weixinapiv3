<?php

namespace laocc\weiPay\auto;

use laocc\weiPay\library\Entity;
use laocc\weiPay\service\Complaint as sComplaint;
use laocc\weiPay\merchant\Complaint as mComplaint;

class Complaint
{
    protected Entity $entity;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    private function createComplaint(): sComplaint|mComplaint
    {
        if ($this->entity->service) {
            return new sComplaint($this->entity);
        } else {
            return new mComplaint($this->entity);
        }
    }


    public function notify(): array|string
    {
        return $this->createComplaint()->notify();
    }

    public function notifyUrl(string $method, string $url = null)
    {
        return $this->createComplaint()->notifyUrl($method, $url);
    }



}