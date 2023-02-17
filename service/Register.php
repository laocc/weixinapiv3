<?php

namespace laocc\weiPay\service;

use esp\http\HttpResult;
use laocc\weiPay\ApiV3Base;

class Register extends ApiV3Base
{

    /**
     * 普通商户进件提交
     *
     * @param array $apy
     * @param array $admin
     * @return array|HttpResult|mixed|string|null
     */
    public function register(array $apy, array $admin)
    {
//        return $this->post("/v3/ecommerce/applyments/", $data);
        $data = [];
        $data['business_code'] = $apy['apyNumber'];//业务申请编号，用本平台商户号;

        $data['contact_info'] = [];//超级管理员信息
        $data['contact_info']['contact_type'] = 'LEGAL';//超级管理员类型
        $data['contact_info']['contact_name'] = '@' . $admin['cardName'];//超级管理员姓名
        /**
         * 当超级管理员类型是经办人时，请上传超级管理员证件类型。
         * IDENTIFICATION_TYPE_IDCARD：中国大陆居民-身份证
         * IDENTIFICATION_TYPE_OVERSEA_PASSPORT：其他国家或地区居民-护照
         * IDENTIFICATION_TYPE_HONGKONG_PASSPORT：中国香港居民-来往内地通行证
         * IDENTIFICATION_TYPE_MACAO_PASSPORT：中国澳门居民-来往内地通行证
         * IDENTIFICATION_TYPE_TAIWAN_PASSPORT：中国台湾居民-来往大陆通行证
         * IDENTIFICATION_TYPE_FOREIGN_RESIDENT：外国人居留证
         * IDENTIFICATION_TYPE_HONGKONG_MACAO_RESIDENT：港澳居民证
         * IDENTIFICATION_TYPE_TAIWAN_RESIDENT：台湾居民证
         * 示例值：IDENTIFICATION_TYPE_IDCARD
         */
        $data['contact_info']['contact_id_doc_type'] = 'IDENTIFICATION_TYPE_IDCARD';
        $data['contact_info']['contact_id_card_number'] = '@' . $admin['cardNumber'];//超级管理员身份证件号码
        $data['contact_info']['contact_id_doc_copy'] = $admin['cardNumber'];//身份证正面
        $data['contact_info']['contact_id_doc_copy_back'] = $admin['cardNumber'];//身份证背面
        $data['contact_info']['contact_period_begin'] = $admin['cardNumber'];//证件有效期开始日期
        $data['contact_info']['contact_period_end'] = $admin['cardNumber'];//证件有效结束，长期的就填长期
//        $data['contact_info']['business_authorization_letter'] = '';//当超级管理员类型是经办人时，请上传业务办理授权函
//        $data['contact_info']['openid'] = '';//该字段选填，若上传则超级管理员签约时，会校验微信号是否与该微信OpenID一致
        $data['contact_info']['mobile_phone'] = '@' . $admin['cardMobile'];//超级管理员手机
        $data['contact_info']['contact_email'] = '@' . $admin['cardMail'];//超级管理员邮箱

        $data['subject_info'] = [];
        /**
         * 主体类型需与营业执照/登记证书上一致，可参考选择主体指引。
         * SUBJECT_TYPE_INDIVIDUAL（个体户）：营业执照上的主体类型一般为个体户、个体工商户、个体经营；
         * SUBJECT_TYPE_ENTERPRISE（企业）：营业执照上的主体类型一般为有限公司、有限责任公司；
         * SUBJECT_TYPE_GOVERNMENT （政府机关）：包括各级、各类政府机关，如机关党委、税务、民政、人社、工商、商务、市监等；
         * SUBJECT_TYPE_INSTITUTIONS（事业单位）：包括国内各类事业单位，如：医疗、教育、学校等单位；
         * SUBJECT_TYPE_OTHERS（社会组织）： 包括社会团体、民办非企业、基金会、基层群众性自治组织、农村集体经济组织等组织。
         */
        $data['subject_info']['subject_type'] = 'SUBJECT_TYPE_ENTERPRISE';
        $data['subject_info']['finance_institution'] = false;//非金融机构
        $data['subject_info']['business_license_info'] = [];//主体为个体户/企业，必填
        $data['subject_info']['business_license_info']['license_copy'] = [];//营业执照照片
        $data['subject_info']['business_license_info']['license_number'] = [];//注册号/统一社会信用代码
        $data['subject_info']['business_license_info']['merchant_name'] = [];//商户名称
        $data['subject_info']['business_license_info']['legal_person'] = [];//个体户经营者/法人姓名
        $data['subject_info']['business_license_info']['license_address'] = [];//注册地址
        $data['subject_info']['business_license_info']['period_begin'] = [];//有效期限开始日期
        $data['subject_info']['business_license_info']['period_end'] = [];//有效期限结束日期	，若证件有效期为长期，请填写：长期。


        $data['business_info'] = [];
        $data['settlement_info'] = [];
        $data['bank_account_info'] = [];

        if (isset($corp['addition'])) {
            $data['addition_info'] = $corp['addition'];
        }
        return $this->post("/v3/applyment4sub/applyment/", $data);
    }

}