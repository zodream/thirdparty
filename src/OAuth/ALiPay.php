<?php
namespace Zodream\ThirdParty\OAuth;

use Zodream\ThirdParty\ALi\OAuth;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/5/21
 * Time: 16:01
 */
class ALiPay extends BaseOAuth {

    protected $configKey = 'alipay';

    protected $codeKey = 'auth_code';

    protected $apiMap = array(
        'login' => array(
            'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm',
            array(
                '#app_id',
                'scope' => 'auth_user',   // auth_userinfo   auth_base
                'response_type' => 'code',
                'redirect_uri',
                'state'
            )
        ),
    );

    /**
     * @return array|false
     */
    public function callback() {
        if (parent::callback() === false) {
            return false;
        }
        /**
         * access_token
         * user_id
         * expires_in
         * re_expires_in
         * refresh_token
         */
        $access = (new OAuth())->getToken($this->get('code'));
        if (!is_array($access) || !array_key_exists('access_token', $access)) {
            return false;
        }
        $access['identity'] = $access['user_id'];
        $this->set($access);
        return $access;
    }

    /**
     * 获取用户信息
     * @return array|false
     */
    public function getInfo() {
        /**
         * avatar	用户头像	String	如果没有数据的时候不会返回该数据，请做好容错	可空	https://tfsimg.alipay.com/images/partner/T1k0xiXXRnXXXXXXXX
        nick_name	用户昵称	String	如果没有数据的时候不会返回该数据，请做好容错	可空	张三
        province	省份	String	用户注册时填写的省份 如果没有数据的时候不会返回该数据，请做好容错	可空	浙江省
        city	城市	String	用户注册时填写的城市， 如果没有数据的时候不会返回该数据，请做好容错	可空	杭州
        gender	用户性别	String	M为男性，F为女性， 如果没有数据的时候不会返回该数据，请做好容错	可空	M
        user_type_value	用户类型	String	（1/2），1代表公司账户；2代表个人账户	不可空	1
        is_licence_auth	是否经过营业执照认证	String	T为通过营业执照认证，F为没有通过	不可空	T
        is_certified	是否通过实名认证	String	T是通过；F是没有实名认证	不可空	F
        is_certify_grade_a	是否A类认证	String	T表示是A类认证，F表示非A类认证，A类认证用户是指上传过身份证照片并且通过审核的支付宝用户	不可空	T
        is_student_certified	是否是学生	String	T表示是学生，F表示不是学生	不可空	T
        is_bank_auth	是否经过银行卡认证	String	T为经过银行卡认证，F为未经过银行卡认证	不可空	T
        is_mobile_auth	是否经过手机认证	String	T为经过手机认证，F为未经过手机认证	不可空	T
        alipay_user_id	当前用户的userId	String	支付宝用户的userId	不可空	2088411964574197
        user_id	已废弃，请勿使用	String	已废弃，请勿使用	不可空	已废弃，请勿使用
        user_status	用户状态（Q/T/B/W）	String	Q代表快速注册用户；T代表已认证用户；B代表被冻结账户；W代表已注册，未激活的账户	不可空	T
        is_id_auth	是否身份证认证	String	T为是身份证认证，F为非身份证认证	不可空	T
         */
        $user = (new OAuth())->getInfo($this->get('access_token'));
        if (!is_array($user) || $user['code'] != 10000) {
            return false;
        }
        $user['username'] = $user['nick_name'];
        $user['sex'] = $user['gender'];
        $this->set($user);
        return $user;
    }
}