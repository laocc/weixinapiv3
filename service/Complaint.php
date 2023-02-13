<?php

namespace esp\weiPay\service;


use esp\weiPay\ApiV3Base;

class Complaint extends ApiV3Base
{
    /**
     * 文档：
     *
     * https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter10_2_11.shtml
     */
    private string $comApi = '/v3/merchant-service/complaint-notifications';

    public function notifyUrl(string $action, string $url = null)
    {
        switch ($action) {
            case 'get':
                $data = $this->get($this->comApi);
                break;
            case 'set':
                $param = [];
                $param['url'] = $url;
                $data = $this->post($this->comApi, $param, ['type' => 'post']);
                break;
            case 'update':
                $param = [];
                $param['url'] = $url;
                $data = $this->post($this->comApi, $param, ['type' => 'put']);
                break;
            case 'delete':
                $data = $this->get($this->comApi, null, ['type' => 'delete']);
                break;
            default:
                return '';
        }
        if (is_string($data)) return $data;

        return $data;
    }


    public function reply(string $mchID, string $comID, string $content)
    {
        $param = [];
        $param['complainted_mchid'] = $mchID;
        $param['response_content'] = $content;
//        $param['response_images'] = [];
        $data = $this->post("/v3/merchant-service/complaints-v2/{$comID}/response",
            $param, ['type' => 'post', 'returnCode' => true]);
        if (is_string($data)) return $data;
        return $data === 204;
    }


}