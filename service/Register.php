<?php

namespace esp\weiPay\service;

use esp\weiPay\ApiV3Base;

class Register extends ApiV3Base
{
    public function register(array $data)
    {
//        return $this->post("/v3/ecommerce/applyments/", $data);
        return $this->post("/v3/applyment4sub/applyment/", $data);
    }
    
}