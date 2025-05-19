<?php

namespace laocc\weiPay\custom;

use esp\core\Library;
use esp\http\Http;
use laocc\weiPay\library\Crypt;
use laocc\weiPay\library\Entity;

class Base extends Library
{

    protected Entity $entity;
    protected Crypt $crypt;

    protected function post(string $api, array $data)
    {
        $data = json_encode($data, 256 | 64);
        $time = strval(microtime(true));

        $option = [];
        $option['encode'] = 'json';
        $option['decode'] = 'json';

        $option['headers'] = [];
        $option['headers']['timestamp'] = $time;
        $option['headers']['mchid'] = $this->entity->mchID;
        $option['headers']['sign'] = $this->sign($time, $data);

        $http = new Http($option);
        $request = $http->data($data)->post($api);
        return $request->data();
    }

    private function sign(string $time, string $data): string
    {
        return md5($this->entity->mchID . $time . $data . $this->entity->certKey);
    }


    protected function notifyDecrypt()
    {
        $json = file_get_contents('php://input');
        return json_decode($json, true);
    }
}