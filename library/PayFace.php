<?php

namespace laocc\weiPay\library;

interface PayFace
{
    public function notify(string $json): array|string;

    public function app(array $params): array|string;

    public function jsapi(array $params): array|string;

    public function h5(array $params): array|string;

    public function query(array $params): array|string;


    /**
     * native，也就是二维码支付
     *
     * @param array $params
     * @return array|string
     */
    public function native(array $params): array|string;


    /**
     * 关闭订单
     *
     * @param array $params
     * @return array|string
     */
    public function close(array $params): array|string;

}