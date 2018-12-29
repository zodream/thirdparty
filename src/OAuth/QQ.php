<?php
namespace Zodream\ThirdParty\OAuth;


/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/10
 * Time: 15:25
 *
 */
use Zodream\Helpers\Json;
use Zodream\Http\Http;

class QQ extends BaseOAuth {

    /**
     * EXAMPLE:
     * 'qq' => [
        'client_id' => '',
        'redirect_uri' => '',
        'client_secret' => ''
    ]
     * @var string
     */
    protected $configKey = 'qq';

    public function getLogin() {
        return $this->getBaseHttp()
            ->url('https://graph.qq.com/oauth2.0/authorize', [
                'response_type' => 'code',
                '#client_id',
                '#redirect_uri',
                '#state',
                'scope',
                'display',
                'g_ut'
            ]);
    }

    public function getAccess() {
        return $this->getBaseHttp()
            ->url('https://graph.qq.com/oauth2.0/token', [
                'grant_type' => 'authorization_code',
                '#client_id',
                '#client_secret',
                '#code',
                '#redirect_uri'
            ])->decode([$this, 'decodeString']);
    }

    /**
     * 自动续期
     * @return Http
     */
    public function getRefresh() {
        return $this->getBaseHttp()
            ->url('https://graph.qq.com/oauth2.0/token',
                [
                    'grant_type' => 'refresh_token',
                    '#client_id',
                    '#client_secret',
                    '#refresh_token'
                ])->decode([$this, 'decodeJson']);
    }

    public function getOpenid() {
        return $this->getBaseHttp()
            ->url('https://graph.qq.com/oauth2.0/me', [
                '#access_token'
            ])->decode([$this, 'decodeJson']);
    }

    public function getInfo() {
        return $this->getBaseHttp()
            ->url('https://graph.qq.com/user/get_user_info', [
                    '#oauth_consumer_key:client_id',
                    '#openid',
                    '#access_token'
                ])->decode([$this, 'decodeJson']);
    }

    /**
     * 解密 json
     * @param $data
     * @return mixed
     */
    protected function decodeJson($data) {
        if (strpos($data, 'callback') !== false) {
            $leftPos = strpos($data, '(');
            $rightPos = strrpos($data, ')');
            $data = substr($data, $leftPos + 1, $rightPos - $leftPos -1);
        }
        return Json::decode($data);
    }

    /**
     * 解密 string
     * @param $data
     * @return array
     */
    protected function decodeString($data) {
        $args = [];
        parse_str($data, $args);
        return $args;
    }

    /**
     * @return array|false
     * @throws \Exception
     */
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
        /**
         *
         */
        $openId = $this->getOpenid()->parameters($access)->text();
        if (!is_array($openId) || !array_key_exists('openid', $openId)) {
            return false;
        }
        $access['identity'] = $access['openid'] = $openId['openid'];
        $this->set($access);
        return $access;
    }

    /**
     * ret    返回码
     * msg    如果ret<0，会有相应的错误信息提示，返回数据全部用UTF-8编码。
     * nickname    用户在QQ空间的昵称。
     * figureurl    大小为30×30像素的QQ空间头像URL。
     * figureurl_1    大小为50×50像素的QQ空间头像URL。
     * figureurl_2    大小为100×100像素的QQ空间头像URL。
     * figureurl_qq_1    大小为40×40像素的QQ头像URL。
     * figureurl_qq_2    大小为100×100像素的QQ头像URL。需要注意，不是所有的用户都拥有QQ的100x100的头像，但40x40像素则是一定会有。
     * gender    性别。 如果获取不到则默认返回"男"
     * is_yellow_vip    标识用户是否为黄钻用户（0：不是；1：是）。
     * vip    标识用户是否为黄钻用户（0：不是；1：是）
     * yellow_vip_level    黄钻等级
     * level    黄钻等级
     * is_yellow_year_vip    标识是否为年费黄钻用户（0：不是； 1：是）
     * @return array|false
     * @throws \Exception
     */
    public function info() {
        $user = $this->getInfo()->text();
        if (!is_array($user) || !array_key_exists('nickname', $user)) {
            return false;
        }
        $user['username'] = $user['nickname'];
        $user['avatar'] = $user['figureurl_qq_2'];
        $user['sex'] = $user['gender'] == '男' ? 'M' : 'F';
        $this->set($user);
        return $user;
    }
}