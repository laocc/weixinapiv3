<?php

namespace laocc\weiPay\service;

use laocc\weiPay\ApiV3Base;

class Complaint extends ApiV3Base
{

    public function notify()
    {
        $value = $this->notifyDecrypt();
        if (is_string($value)) return $value;

        $param = [];
        $param['complainted_mchid'] = $value['mchid'];
        $param['response_content'] = $value['content'];
//        $param['response_images'] = [];
        $data = $this->post("/v3/merchant-service/complaints-v2/{$value['complaint_id']}/response", $param, ['type' => 'post', 'returnCode' => true]);
        if (is_string($data)) return $data;

        return $value;
    }


    /**
     * 文档：
     *
     * https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter10_2_11.shtml
     *
     * https://pay.weixin.qq.com/doc/v3/merchant/4012459282
     */
    public function notifyUrl(string $method, string $url = null)
    {
        $comApi = '/v3/merchant-service/complaint-notifications';

        switch ($method) {
            case 'get':
                $data = $this->get($comApi);
                break;
            case 'set':
                $param = [];
                $param['url'] = $url;
                $data = $this->post($comApi, $param, ['type' => 'post']);
                break;
            case 'update':
                $param = [];
                $param['url'] = $url;
                $data = $this->post($comApi, $param, ['type' => 'put']);
                break;
            case 'delete':
                $data = $this->get($comApi, null, ['type' => 'delete']);
                break;
            default:
                return '';
        }
        if (is_string($data)) return $data;

        return $data;
    }


}