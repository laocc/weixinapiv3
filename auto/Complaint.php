<?php

namespace laocc\weiPay\auto;

use esp\error\Error;
use laocc\weiPay\library\Entity;
use laocc\weiPay\service\Complaint as sComplaint;
use laocc\weiPay\merchant\Complaint as mComplaint;

/**
 * 投诉管理
 */
class Complaint
{
    protected Entity $entity;
    private sComplaint|mComplaint $complaint;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    private function createComplaint(): sComplaint|mComplaint
    {
        if (isset($this->complaint)) return $this->complaint;

        if ($this->entity->service) {
            $this->complaint = new sComplaint($this->entity);
        } else {
            $this->complaint = new mComplaint($this->entity);
        }

        return $this->complaint;
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

    public function notify(): array|string
    {
        return $this->createComplaint()->notify();
    }


    public function download(array $data): array|string
    {
        return $this->createComplaint()->download($data);
    }

    public function image(array $data): array|string
    {
        return $this->createComplaint()->image($data);
    }

    public function history(array $data): array|string
    {
        return $this->createComplaint()->history($data);
    }

    public function read(array $data): array|string
    {
        return $this->createComplaint()->read($data);
    }

    public function reply(array $data): array|string
    {
        return $this->createComplaint()->reply($data);
    }

    public function notifyUrl(string $method, string $url = null)
    {
        return $this->createComplaint()->notifyUrl($method, $url);
    }


}