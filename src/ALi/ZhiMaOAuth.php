<?php
namespace Zodream\ThirdParty\ALi;

use Exception;
use Zodream\Helpers\Json;

class ZhiMaOAuth extends BaseZhiMa {

    public function getLogin() {
        return $this->getBaseHttp()
            ->appendMaps([
                'method' => 'zhima.auth.info.authorize',
                '#params' => [
                    'identity_type' => '1', //1:按照手机号进行授权 2:按照身份证+姓名进行授权
                    '#identity_param' => [
                        'mobileNo',
                        'certNo',
                        'name'
                    ],
                    'biz_params' => [
                        'auth_code',  //M_MOBILE_APPPC  M_APPPC_CERT M_H5 M_APPSDK
                        'channelType', // appsdk:sdk接入 apppc:商户pc页面接入 api:后台api接入 windows:支付宝服务窗接入 app:商户app接入
                        'state'
                    ]
                ]
            ]);
    }

    public function getScore() {
        return $this->getBaseHttp()
            ->appendMaps([
                'method' => 'zhima.credit.score.get',
                '#params' => [
                    '#transaction_id',
                    'product_code' => 'w1010100100000000001',
                    '#open_id'
                ]
            ]);
    }


    public function login() {
        $http = $this->getLogin();
        return $http->getUrl().'?'.$http->buildPostParameters();
    }


    public function callback() {
        $params = $_GET['params'];
        $sign = $_GET['sign'];
        $params = strstr ( $params, '%' ) ? urldecode ( $params ) : $params;
        $sign = strstr ( $sign, '%' ) ? urldecode ( $sign ) : $sign;
        $params = $this->getRsa()->decrypt($params);
        if (!$this->getRsa()->verify($params, $sign)) {
            throw new Exception("验签失败:".$params);
        }
        return Json::decode($params);
    }

    public function score($token, $transaction_id) {
        $data = $this->getScore()->parameters([
            'auth_token' => $token,
            'transaction_id' => $transaction_id
        ])->text();
        return isset($data['zm_score']) ? $data['zm_score'] : false;
    }


}