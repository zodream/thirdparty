<?php
namespace Zodream\ThirdParty\OAuth;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/6/21
 * Time: 11:08
 */
class DouBan extends BaseOAuth {

    protected $configKey = 'douban';

    public function getLogin() {
        return $this->getBaseHttp()
            ->url('https://www.douban.com/service/auth2/auth',
                [
                    '#client_id',
                    '#redirect_uri',
                    'response_type' => 'code',
                    'scope',
                    'state'
                ]);
    }

    public function getAccess() {
        return $this->getBaseHttp('https://www.douban.com/service/auth2/token')
            ->maps([
                '#client_id',
                '#client_secret',
                '#redirect_uri',
                'grant_type' => 'authorization_code',
                '#code'
            ]);
    }

    public function getRefresh() {
        return $this->getBaseHttp('https://www.douban.com/service/auth2/token')
            ->maps([
                '#client_id',
                '#client_secret',
                '#redirect_uri',
                'grant_type' => 'refresh_token',
                '#refresh_token'
            ]);
    }

    public function getInfo() {
        return $this->getBaseHttp('https://api.douban.com/v2/user/~me');
    }

    /**
     * @return array|bool|mixed
     * @throws \Exception
     */
    public function callback() {
        if (parent::callback() === false) {
            return false;
        }
        $access = $this->getAccess()->json();
        if (!is_array($access) || !array_key_exists('douban_user_id', $access)) {
            return false;
        }
        $access['identity'] = $access['douban_user_id'];
        $this->set($access);
        return $access;
    }

    /**
     * 获取用户信息
     * @return array|false
     * @throws \Exception
     */
    public function info() {
        $user = $this->getInfo()->json();
        if (!is_array($user) || !array_key_exists('name', $user)) {
            return false;
        }
        $user['username'] = $user['name'];
        $user['sex'] = 'F';
        return $user;
    }
}