<?php
declare(strict_types=1);

namespace esp\weiPay\ecommerce;

use function esp\helper\rnd;
use esp\weiPay\ApiV3Base;

class Register extends ApiV3Base
{

    /**
     * 更换结算账户
     * @return array|string
     */
    public function bank(array $param)
    {
        $post = [];
        $post['account_type'] = 'ACCOUNT_TYPE_BUSINESS';//账户类型，对公
        if ($param['private'] ?? 0) $post['account_type'] = 'ACCOUNT_TYPE_PRIVATE';//账户类型，对私
        $post['account_bank'] = $param['bank'];//开户银行
        $post['bank_address_code'] = $param['area'];//开户银行省市编码
        if ($param['bank'] === '其他银行') {
            $post['bank_name'] = $param['name'];//开户银行全称 （含支行）
            if (strpos($param['name'], $param['bank']) !== 0) {
                $post['bank_name'] = $param['bank'] . $param['name'];//开户银行全称 （含支行）
            }
        }
        if ($param['index'] ?? '') $post['bank_branch_id'] = $param['index'];//开户银行联行号
        $post['account_number'] = '@' . $param['number'];//银行帐号

        $data = $this->returnHttp(true)->post("/v3/apply4sub/sub_merchants/{$param['mchid']}/modify-settlement", $post);
        return $data;
    }


    /**
     * 提交进件
     *
     * @param array $data
     * @return array|\esp\http\HttpResult|mixed|string|null
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
     * @return array|string
     */
    public function query($regID)
    {
        $data = $this->get("/v3/ecommerce/applyments/{$regID}");
        if (is_string($data)) return $data;

        $upAPY = [];
        if ($data['applyment_state'] ?? '') $upAPY['state'] = $data['applyment_state'];
        if ($data['sign_url'] ?? '') $upAPY['sign_url'] = $data['sign_url'];
        if ($data['legal_validation_url'] ?? '') $upAPY['legal_url'] = $data['legal_validation_url'];
        if ($data['account_validation'] ?? '') {
            $av = $data['account_validation'];
            $upAPY['validation'] = [];
            $upAPY['validation'][] = "汇款账户：" . $this->decryptString($av['account_name']);
            if ($av['account_no']) {
                $upAPY['validation'][] = "汇款账号：" . $this->decryptString($av['account_no']);
            }
            $upAPY['validation'][] = "汇款金额：" . rnd($av['pay_amount'] / 100) . '元';
            $upAPY['validation'][] = "汇款留言：{$av['remark']}";

            $upAPY['validation'][] = "收款银行：{$av['destination_account_bank']}";
            $upAPY['validation'][] = "收款城市：{$av['city']}";
            $upAPY['validation'][] = "收款账户：{$av['destination_account_name']}";
            $upAPY['validation'][] = "收款账号：{$av['destination_account_number']}";
            $upAPY['validation'][] = "截止时间：{$av['deadline']}";
            $upAPY['validation'] = implode("\n", $upAPY['validation']);
        }

        if ($data['audit_detail'] ?? '') $upAPY['audit'] = $data['audit_detail'];

        if ($data['sub_mchid'] ?? '') $upAPY['mchid'] = $data['sub_mchid'];
        $upAPY['desc'] = $data['applyment_state_desc'];
        return $upAPY;
    }

}