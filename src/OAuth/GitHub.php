<?php
namespace Zodream\ThirdParty\OAuth;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/5/13
 * Time: 11:33
 */
class GitHub extends BaseOAuth {

    protected $configKey = 'github';

    public function getLogin() {
        return $this->getBaseHttp()
            ->url('https://github.com/login/oauth/authorize', [
                '#client_id',
                '#redirect_uri',
                '#scope',
                'state',
                'allow_signup'
            ]);
    }

    public function getAccess() {
        return $this->getBaseHttp('https://github.com/login/oauth/access_token')
            ->maps([
                '#client_id',
                '#client_secret',
                '#code',
                'redirect_uri',
                'state'
            ])->setHeader([
                'Accept' => 'application/json'
            ]);
    }

    public function getInfo() {
        return $this->getBaseHttp()
            ->url('https://api.github.com/user', [
                '#access_token',
            ])->setHeader([
                'Authorization' => 'token OAUTH-TOKEN'
            ]);
    }

    /**
     * @return array|false
     * @throws \Exception
     */
    public function callback() {
        parent::callback();
        $access = $this->getAccess()->json();
        if (!array_key_exists('access_token', $access)) {
            return false;
        }
        $access['identity'] = $access['access_token'];
        $this->set($access);
        return $access;
    }

    /**
     * @return array|mixed
     * @throws \Exception
     */
    public function info() {
        return $this->getInfo()->json();
    }
}