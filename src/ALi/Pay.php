<?php
namespace Zodream\ThirdParty\ALi;


use Zodream\Http\Http;
use Zodream\Service\Factory;

class Pay extends BaseALi {

    /**
     * PC 网站支付
     * @return Http
     */
    public function getWebPay() {
        return $this->getBaseHttp()
            ->appendMaps([
                'method' => 'alipay.trade.page.pay',
                'return_url',
                'notify_url',
                '#biz_content' => [
                    '#out_trade_no',
                    'product_code' => 'FAST_INSTANT_TRADE_PAY',
                    '#total_amount',
                    '#subject',
                    'body',
                    'goods_detail',
                    'passback_params',
                    'extend_params',
                    'goods_type',
                    'timeout_express',
                    'enable_pay_channels',
                    'disable_pay_channels',
                    'auth_token',
                    'qr_pay_mode',
                    'qrcode_width'
                ]
            ]);
    }

    /**
     * H5 支付
     * @return Http
     */
    public function getWapPay() {
        return $this->getBaseHttp()
            ->appendMaps([
                'method' => 'alipay.trade.wap.pay',
                'return_url',
                'notify_url',
                '#biz_content' => [
                    '#out_trade_no',
                    'product_code' => 'QUICK_WAP_WAY',
                    '#total_amount',
                    '#subject',
                    'body',
                    'promo_params',
                    'passback_params',
                    'extend_params',
                    'goods_type',
                    'timeout_express',
                    'time_expire',
                    'enable_pay_channels',
                    'disable_pay_channels',
                    'auth_token',
                    'store_id',
                    'quit_url',
                    'ext_user_info'
                ]
            ]);
    }

    /**
     * 手机APP支付
     * @return Http
     */
    public function getAppPay() {
        return $this->getBaseHttp()
            ->appendMaps([
                'method' => 'alipay.trade.app.pay',
                'notify_url',
                '#biz_content' => [
                    '#out_trade_no',
                    'product_code' => 'QUICK_MSECURITY_PAY',
                    '#total_amount',
                    '#subject',
                    'body',
                    'promo_params',
                    'passback_params',
                    'extend_params',
                    'timeout_express',
                    'time_expire',
                    'enable_pay_channels',
                    'disable_pay_channels',
                    'store_id',
                    'ext_user_info'
                ]
            ]);
    }

    /**
     * 当面支付
     * @return Http
     */
    public function getPay() {
        return $this->getBaseHttp()
            ->appendMaps([
                'method' => 'alipay.trade.pay',
                'notify_url',
                '#biz_content' => [
                    '#out_trade_no',
                    '#scene',
                    'product_code' => 'FACE_TO_FACE_PAYMENT',
                    '#total_amount',
                    'buyer_id',
                    'seller_id',
                    '#subject',
                    'body',
                    'discountable_amount',
                    'goods_detail',
                    'operator_id',
                    'store_id',
                    'terminal_id',
                    'extend_params',
                    'timeout_express',
                ]
            ]);
    }

    /**
     * 统一收单交易退款接口
     * @return Http
     */
    public function getRefund() {
        return $this->getBaseHttp()
            ->appendMaps([
                'method' => 'alipay.trade.refund',
                '#biz_content' => [
                    [
                        'out_trade_no',
                        'trade_no'
                    ],
                    '#refund_amount',
                    'refund_reason',
                    'out_request_no',
                    'operator_id',
                    'store_id',
                    'terminal_id',
                    'goods_detail' => [
                        '#goods_id',
                        'alipay_goods_id',
                        '#goods_name',
                        '#quantity',
                        '#price',
                        'goods_category',
                        'body',
                        'show_url'
                    ]
                ]
            ]);
    }


    /**
     *
     * @return mixed
     * @throws \Exception
     */
    public function callback() {
        Factory::log()
            ->info('ALIPAY CALLBACK: '.var_export($_POST, true));
        $data = $_POST;//Requests::isPost() ? $_POST : $_GET;
        if (!$this->verify($data)) {
            throw new \Exception('验签失败！');
        }
        return $data;
    }

}