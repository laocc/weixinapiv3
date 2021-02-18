<?php
declare(strict_types=1);

namespace esp\weixinapiv3\src;

class Register extends ApiV3Base
{
    /**
     * 提交进件
     * @param $regID
     * @return \esp\http\Result
     * https://pay.weixin.qq.com/wiki/doc/apiv3/wxpay/ecommerce/applyments/chapter3_1.shtml
     * https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter7_1_1.shtml
     */
    public function register(array $data)
    {
        return $this->post("/v3/ecommerce/applyments/", $data);
    }

    /**
     * 查询进件审核状态
     * @param $regID
     * @return \esp\http\Result
     */
    public function query($regID)
    {
        return $this->get("/v3/ecommerce/applyments/{$regID}");
    }

}