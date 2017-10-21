<?php
namespace Zodream\ThirdParty\ALi;

class OAuth extends BaseALi {
    protected $apiMap = [
        'token' => [
            'method' => 'alipay.system.oauth.token',
            [
                'code',       // 二选一
                'refresh_token', //二选一
            ],
            'grant_type' => 'authorization_code'  //值为authorization_code时，代表用code换取；值为refresh_token时，代表用refresh_token换取
        ],
        'info' => [
            '#auth_token',
        ]
    ];

    /**
     * 获取token
     * @param $code
     * @return mixed
     */
    public function getToken($code) {
        if (is_array(!$code)) {
            $code = [
                'code' => $code,
            ];
        }
        $args = $this->getJson('token', $code);
        return reset($args);
    }

    /**
     * 获取用户信息
     * @param $token
     * @return mixed
     */
    public function getInfo($token) {
        $args = $this->getJson('info', [
            'auth_token' => $token
        ]);
        return reset($args);
    }
}