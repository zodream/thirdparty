<?php
namespace Zodream\ThirdParty\OAuth;

use Zodream\Helpers\Str;
use Zodream\Http\Http;
use Zodream\Service\Factory;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/5/13
 * Time: 11:33
 */
class WeChat extends BaseOAuth {

    /**
     * EXAMPLE:
     * 'wechat' => [
        'appid' => '',
        'redirect_uri' => '',
        'secret' => ''
    ]
     * @var string
     */
    protected $configKey = 'wechat';

    public function getLogin() {
        return $this->getBaseHttp()
            ->url('https://open.weixin.qq.com/connect/qrconnect', [
                '#appid',
                '#redirect_uri',
                'response_type' => 'code',
                'scope' => 'snsapi_login',
                'state'
            ]);
    }

    public function getAccess() {
        return $this->getBaseHttp()
            ->url('https://api.weixin.qq.com/sns/oauth2/access_token', [
                '#appid',
                '#secret',
                '#code',
                'grant_type' => 'authorization_code'
            ]);
    }

    public function getRefresh() {
        return $this->getBaseHttp()
            ->url('https://api.weixin.qq.com/sns/oauth2/refresh_token', [
                '#appid',
                'grant_type' => 'refresh_token',
                '#refresh_token'
            ]);
    }

    public function getInfo() {
        return $this->getBaseHttp()
            ->url('https://api.weixin.qq.com/sns/userinfo', [
                '#access_token',
                '#openid',
                'lang'
            ]);
    }

    /**
     * @return array|false
     */
    public function callback() {
        if (parent::callback() === false) {
            return false;
        }
        /**
         * access_token	接口调用凭证
         * expires_in	access_token接口调用凭证超时时间，单位（秒）
         * refresh_token	用户刷新access_token
         * openid	授权用户唯一标识
         * scope	用户授权的作用域，使用逗号（,）分隔
         * unionid	当且仅当该网站应用已获得该用户的userinfo授权时，才会出现该字段。
         */
        $access = $this->getAccess()->json();
        if (!is_array($access) || !array_key_exists('access_token', $access)) {
            return false;
        }
        $access['identity'] = $access['openid'];
        $this->set($access);
        return $access;
    }
    
    public function info() {
        /**
         * openid	普通用户的标识，对当前开发者帐号唯一
        nickname	普通用户昵称
        sex	普通用户性别，1为男性，2为女性
        province	普通用户个人资料填写的省份
        city	普通用户个人资料填写的城市
        country	国家，如中国为CN
        headimgurl	用户头像，最后一个数值代表正方形头像大小（有0、46、64、96、132数值可选，0代表640*640正方形头像），用户没有头像时该项为空
        privilege	用户特权信息，json数组，如微信沃卡用户为（chinaunicom）
        unionid	用户统一标识。针对一个微信开放平台帐号下的应用，同一用户的unionid是唯一的。
         */
        $user = $this->getInfo()->json();
        if (!is_array($user) || !array_key_exists('nickname', $user)) {
            return false;
        }
        $user['username'] = $user['nickname'];
        $user['avatar'] = $user['headimgurl'];
        $user['sex'] = $user['sex'] == 2 ? 'F' : 'M';
        $user['identity'] = isset($user['unionid']) ? $user['unionid'] : $user['openid'];
        $this->set($user);
        return $user;
    }

    public function webLogin(array $args = []) {
        $state = Str::randomNumber(7);
        if (function_exists('session')) {
            session([
                'state' => $state
            ]);
        }
        $this->set('state', $state);
        $data = json_encode(Http::getMapParameters([
            'id' => 'login_container',
            '#appid',
            'scope' => 'snsapi_login',
            '#redirect_uri',
            '#state',
            'style' => 'black',
            'href'
        ], $this->merge($args)));
        return <<<HTML
<script src="http://res.wx.qq.com/connect/zh_CN/htmledition/js/wxLogin.js"></script>
<script>
var obj = new WxLogin({$data});
</script>
HTML;

    }
}