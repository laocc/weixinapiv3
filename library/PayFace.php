<?php

namespace laocc\weiPay\library;

interface PayFace
{
    public function app(array $params): array|string;

    public function jsapi(array $params): array|string;

    public function h5(array $params): array|string;

    public function query(array $params): array|string;

//    public function refund(array $params);
}