<?php
declare(strict_types=1);

namespace esp\weixinapiv3\src;

use function esp\helper\rnd;

class Register extends ApiV3Base
{
    /**
     * 提交进件
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
     * @return array
     */
    public function query($regID)
    {
        $data = $this->get("/v3/ecommerce/applyments/{$regID}");

        $upAPY = [];
        if ($data['applyment_state'] ?? '') $upAPY['state'] = $data['applyment_state'];
        if ($data['sign_url'] ?? '') $upAPY['sign_url'] = $data['sign_url'];
        if ($data['legal_validation_url'] ?? '') $upAPY['legal_url'] = $data['legal_validation_url'];
        if ($data['account_validation'] ?? '') {
            $upAPY['validation'] = [];
            $upAPY['validation'][] = "汇款账户：" . $this->decryptString($upAPY['validation']['account_name']);
            if ($upAPY['validation']['account_no']) {
                $upAPY['validation'][] = "汇款账号：" . $this->decryptString($upAPY['validation']['account_no']);
            }
            $upAPY['validation'][] = "汇款金额：" . rnd($upAPY['validation']['pay_amount'] / 100) . '元';
            $upAPY['validation'][] = "汇款留言：{$upAPY['validation']['remark']}";

            $upAPY['validation'][] = "收款银行：{$upAPY['validation']['destination_account_bank']}";
            $upAPY['validation'][] = "收款城市：{$upAPY['validation']['city']}";
            $upAPY['validation'][] = "收款账户：{$upAPY['validation']['destination_account_name']}";
            $upAPY['validation'][] = "收款账号：{$upAPY['validation']['destination_account_number']}";
            $upAPY['validation'][] = "截止时间：{$upAPY['validation']['deadline']}";
            $upAPY['validation'] = implode("；\n", $upAPY['validation']);
        }

        if ($data['audit_detail'] ?? '') $upAPY['audit'] = $data['audit_detail'];

        if ($data['sub_mchid'] ?? '') $upAPY['mchid'] = $data['sub_mchid'];

        return $upAPY;
    }

}