<?php
namespace Zodream\ThirdParty\ALi;

class OAuth extends BaseALi {

    public function getToken() {
        return $this->getBaseHttp()->appendMaps([
            'method' => 'alipay.system.oauth.token',
            [
                'code',       // 二选一
                'refresh_token', //二选一
            ],
            'grant_type' => 'authorization_code'  //值为authorization_code时，代表用code换取；值为refresh_token时，代表用refresh_token换取
        ]);
    }

    public function getInfo() {
        return $this->getBaseHttp()->appendMaps([
            'method' => 'alipay.user.info.share',
        ]);
    }

    /**
     * 获取token
     * @param $code
     * @return mixed
     * @throws \Exception
     */
    public function token($code) {
        if (!is_array($code)) {
            $code = [
                'code' => $code,
            ];
        }
        $args = $this->getToken()->parameters($code)->json();
        return reset($args);
    }

    /**
     * 获取用户信息
     * @param $token
     * @return mixed
     * @throws \Exception
     */
    public function info($token) {
        $args = $this->getInfo()->parameters([
            'auth_token' => $token
        ])->json();
        return reset($args);
    }
}