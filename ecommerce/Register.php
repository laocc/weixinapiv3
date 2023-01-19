<?php
declare(strict_types=1);

namespace esp\weiPay\ecommerce;

use esp\http\HttpResult;
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
     * @param array $apy
     * @param array $shop
     * @param array $license
     * @param array $Legal
     * @param array $bank
     * @param array $admin
     * @return array|HttpResult|mixed|string|null
     * https://pay.weixin.qq.com/wiki/doc/apiv3/wxpay/ecommerce/applyments/chapter3_1.shtml
     * https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter7_1_1.shtml
     */
    public function register(array $apy,
                             array $shop,
                             array $license,
                             array $Legal,
                             array $bank,
                             array $admin)
    {

        $data = [];
        $data['out_request_no'] = $apy['apyNumber'];//业务申请编号，用本平台商户号

        $data['need_account_info'] = true;//是否填写结算银行账户

        if ($shop['shopLicID']) {
            $data['organization_type'] = $license['licType'];//主体类型
            $data['business_license_info'] = [];
            $data['business_license_info']['business_license_copy'] = $license['uploadMediaID'];//证件扫描件
            $data['business_license_info']['business_license_number'] = $license['licNumber'];//证件注册号
            $data['business_license_info']['merchant_name'] = $license['licName'];//商户名称
            $data['business_license_info']['legal_person'] = $Legal['cardName'];//经营者/法定代表人姓名
            $data['business_license_info']['company_address'] = $license['licAddress'];//注册地址
            $data['business_license_info']['business_time'] = json_encode($license['licExpire'], 256 | 64);//营业期限
        } else {
            $data['organization_type'] = '2401';//主体类型:小微商户
        }

        if ($Legal['cardType'] === 'idcard') {
            $data['id_doc_type'] = 'IDENTIFICATION_TYPE_MAINLAND_IDCARD';//法人代表，证件类型
            $data['id_card_info'] = [];//经营者/法人身份证信息
            $data['id_card_info']['id_card_copy'] = $Legal['uploadMediaIDA'];//身份证人像面照片
            $data['id_card_info']['id_card_national'] = $Legal['uploadMediaIDB'];//身份证国徽面照片
            $data['id_card_info']['id_card_name'] = '@' . $Legal['cardName'];//身份证姓名
            $data['id_card_info']['id_card_number'] = '@' . $Legal['cardNumber'];//身份证号码
            $data['id_card_info']['id_card_valid_time'] = $Legal['cardExpire'][1];//身份证有效期限
        } else {
            $type = [
                'hongkong' => 'IDENTIFICATION_TYPE_HONGKONG',
                'macao' => 'IDENTIFICATION_TYPE_MACAO',
                'taiwan' => 'IDENTIFICATION_TYPE_TAIWAN',
                'passport' => 'IDENTIFICATION_TYPE_OVERSEA_PASSPORT',
            ];
            $data['id_doc_type'] = $type[$Legal['cardType']];//法人代表，证件类型
            $data['id_doc_info'] = [];//经营者/法人身份证信息
            $data['id_doc_info']['id_doc_name'] = '@' . $Legal['cardName'];//身份证姓名
            $data['id_doc_info']['id_doc_number'] = '@' . $Legal['cardNumber'];//身份证号码
            $data['id_doc_info']['id_doc_copy'] = $Legal['uploadMediaIDA'];//身份证人像面照片
            $data['id_doc_info']['doc_period_end'] = $Legal['cardExpire'][1];//身份证有效期限
        }

        $data['contact_info'] = [];//超级管理员信息
        $data['contact_info']['contact_type'] = '65';//超级管理员类型
        $data['contact_info']['contact_name'] = '@' . $admin['cardName'];//超级管理员姓名
        $data['contact_info']['contact_id_card_number'] = '@' . $admin['cardNumber'];//超级管理员身份证件号码
        $data['contact_info']['mobile_phone'] = '@' . $admin['cardMobile'];//超级管理员手机
        $data['contact_info']['contact_email'] = '@' . $admin['cardMail'];//超级管理员邮箱
        if ($Legal['cardNumber'] !== $admin['cardNumber']) $data['contact_info']['contact_type'] = '66';//66- 负责人

        $data['account_info'] = [];//结算银行账户
        $data['account_info']['bank_account_type'] = '74';//账户类型，对公
        if ($bank['bankCardID']) $data['account_info']['bank_account_type'] = '75';//账户类型，对私
        $data['account_info']['account_bank'] = $bank['bankCodeName'];//开户银行
        $data['account_info']['account_name'] = '@' . ($bank['licName'] ?: $bank['cardName']);//开户名称
        $data['account_info']['bank_address_code'] = $bank['bankArea'];//开户银行省市编码
        if ($bank['bankIndex']) $data['account_info']['bank_branch_id'] = $bank['bankIndex'];//开户银行联行号
        $data['account_info']['bank_name'] = $bank['bankName'];//开户银行全称 （含支行）
        $data['account_info']['account_number'] = '@' . $bank['bankNumber'];//银行帐号

        $data['merchant_shortname'] = $shop['shopTitle'];//最多16个汉字长度。将在支付完成页向买家展示，需与商家的实际售卖商品相符 。
        $data['sales_scene_info'] = [];//店铺信息
        $data['sales_scene_info']['store_name'] = $shop['shopName'];//店铺名称
//        $data['sales_scene_info']['store_url'] = 333333;//店铺链接
        $data['sales_scene_info']['store_qr_code'] = $shop['uploadMediaID'];//店铺二维码
//        $data['sales_scene_info']['mini_program_sub_appid'] = 333333;//小程序AppID


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