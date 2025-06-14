<?php

namespace laocc\weiPay\auto;

use esp\error\Error;
use laocc\weiPay\library\Entity;
use laocc\weiPay\library\PayFace;
use laocc\weiPay\ecommerce\Combine as eCombine;
use laocc\weiPay\merchant\Combine as mCombine;
use laocc\weiPay\service\Combine as sCombine;

/**
 * 合单支付
 */
class Combine implements PayFace
{

    protected Entity $entity;
    private mCombine|sCombine|eCombine $combine;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    private function createPay(): mCombine|sCombine|eCombine
    {
        if (isset($this->combine)) return $this->combine;

        //服务商类型，1直连商户，2普通服务商，4电商服务商，32自建支付中心
        $this->combine = match ($this->entity->service) {
            1 => new mCombine($this->entity),
            2 => new sCombine($this->entity),
            4 => new eCombine($this->entity),
        };

        return $this->combine;
    }

    /**
     * 受理通知数据，验签，并解密
     * @return array|string
     */
    public function notify(): array|string
    {
        return $this->createPay()->notify();
    }

    /**
     * 解密
     *
     * @param string $ciphertext
     * @return string
     * @throws Error
     */
    public function decryptedCipher(string $ciphertext): string
    {
        return $this->entity->decryptedCipher($ciphertext);
    }


    /**
     * app支付
     *
     * @param array $params
     * @return array|string
     */
    public function app(array $params): array|string
    {
        return $this->createPay()->app($params);
    }

    /**
     * 发起公众号、小程序支付
     * 服务商和直连都可用，取决于 $this->entity->service
     *
     * @param array $params
     * @return array|string
     */
    public function jsapi(array $params): array|string
    {
        return $this->createPay()->jsapi($params);
    }

    public function h5(array $params): array|string
    {
        return $this->createPay()->h5($params);
    }


    /**
     * @param array $params
     * @return array|string
     */
    public function query(array $params): array|string
    {
        return $this->createPay()->query($params);
    }

    /**
     * native，也就是二维码支付
     *
     * @param array $params
     * @return array|string
     */
    public function native(array $params): array|string
    {
        return $this->createPay()->native($params);
    }


    /**
     * 关闭订单
     *
     * @param array $params
     * @return array|string
     */
    public function close(array $params): array|string
    {
        return $this->createPay()->close($params);
    }
}