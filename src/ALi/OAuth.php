<?php
declare(strict_types=1);
namespace Zodream\ThirdParty\ALi;

class OAuth extends BaseALi {

    public function getToken(): Http {
        return $this->getBaseHttp()->appendMaps([
            'method' => 'alipay.system.oauth.token',
            [
                'code',       // 二选一
                'refresh_token', //二选一
            ],
            'grant_type' => 'authorization_code'  //值为authorization_code时，代表用code换取；值为refresh_token时，代表用refresh_token换取
        ]);
    }

    public function getInfo(): Http {
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
    public function token(mixed $code) {
        if (!is_array($code)) {
            $code = [
                'code' => $code,
            ];
        }
        return $this->getToken()->parameters($code)->text();
    }

    /**
     * 获取用户信息
     * @param $token
     * @return mixed
     * @throws \Exception
     */
    public function info(string $token) {
        return $this->getInfo()->parameters([
            'auth_token' => $token
        ])->text();
    }
}