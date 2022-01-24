<?php
namespace Zodream\ThirdParty\OAuth;


/**
 * 小米OAUTH
 * @package Zodream\ThirdParty\OAuth
 */
class Mi extends BaseOAuth {

    protected string $configKey = 'mi';

    public function getLogin() {
        return $this->getBaseHttp()
            ->url('https://account.xiaomi.com/oauth2/authorize', [
                'response_type' => 'code',
                '#client_id',
                '#redirect_uri',
                '#state',
                'scope',
                'skip_confirm',
            ]);
    }

    public function getAccess() {
        return $this->getBaseHttp()
            ->url('https://account.xiaomi.com/oauth2/token', [
                'grant_type' => 'authorization_code',
                '#client_id',
                '#client_secret',
                '#code',
                '#redirect_uri'
            ]);
    }

    public function getInfo() {
        return $this->getBaseHttp()
            ->url('https://open.account.xiaomi.com/user/profile', [
                '#clientId:client_id',
                '#openid',
                '#token:access_token'
            ]);
    }


    public function callback() {
        if (parent::callback() === false) {
            return false;
        }
        /**
         * access_token	授权令牌，Access_Token。
         * expires_in	该access token的有效期，单位为秒。
         * refresh_token
         */
        $access = $this->getAccess()->text();
        if (!is_array($access) || !array_key_exists('access_token', $access)) {
            return false;
        }
        $access['identity'] = $access['openId'];
        $this->set($access);
        return $access;
    }

    /**
     * 获取用户信息
     * @return array|bool
     * @throws \Exception
     */
    public function info() {
        $user = $this->getInfo()->text();
        if (!is_array($user) || !array_key_exists('miliaoNick', $user)) {
            return false;
        }
        $user['username'] = $user['miliaoNick'];
        $user['avatar'] = $user['miliaoIcon'];
        $user['sex'] = 'M';
        $this->set($user);
        return $user;
    }
}