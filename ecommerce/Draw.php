<?php

namespace laocc\weiPay\ecommerce;


use laocc\weiPay\ApiV3Base;

class Draw extends ApiV3Base
{
    public function create(array $param)
    {
        $data = [];
        $data['sub_mchid'] = $param['mchid'];
        $data['out_request_no'] = $param['number'];
        $data['amount'] = $param['amount'];
        if ($param['remark'] ?? '') $data['remark'] = $param['remark'];
        if ($param['memo'] ?? '') $data['bank_memo'] = $param['memo'];
        $request = $this->post("/v3/ecommerce/fund/withdraw", $data);
        if (is_string($request)) return $request;

        return $request;
    }


    public function query(array $param)
    {
        $data = [];
        $data['sub_mchid'] = $param['mchid'];

        if (isset($param['transaction']) and !empty($param['transaction'])) {
            $rest = $this->get("/v3/ecommerce/fund/withdraw/{$param['transaction']}", $data);
        } else {
            $rest = $this->get("/v3/ecommerce/fund/withdraw/out-request-no/{$param['number']}", $data);
        }
        if (is_string($rest)) return $rest;
        return $rest;
    }


}