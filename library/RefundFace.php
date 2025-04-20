<?php

namespace laocc\weiPay\library;

interface RefundFace
{
    public function send(array $refund): array|string;

    public function abnormal(array $refund): array|string;

    public function query(array $params): array|string;

    public function notify(string $json): array|string;

}