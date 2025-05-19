<?php

namespace laocc\weiPay\custom;

use laocc\weiPay\ApiV3Base;

class Complaint extends Base
{

    public function notify()
    {
        return $this->notifyDecrypt();
    }

    public function reply(array $data)
    {
        $param = [];
        $param['complainted_mchid'] = $data['mchid'];
        $param['response_content'] = $data['content'];
//        $param['response_images'] = [];
        return $this->post("/v3/merchant-service/complaints-v2/{$data['complaint_id']}/response", $param, ['type' => 'post', 'returnCode' => true]);
    }

    /**
     * 投诉回调
     *
     * 文档：
     *
     * https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter10_2_11.shtml
     *
     * https://pay.weixin.qq.com/doc/v3/merchant/4012459282
     */

    public function notifyUrl(string $action, string $url = null)
    {
        $comApi = '/v3/merchant-service/complaint-notifications';

        switch ($action) {
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