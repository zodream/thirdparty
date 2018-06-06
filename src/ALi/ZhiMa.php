<?php
namespace Zodream\ThirdParty\ALi;

use Zodream\Helpers\Json;

/**
 * 芝麻分
 * 授权链接请 设置 scope：auth_zhima
 *
 * @package Zodream\ThirdParty\ALi
 *
 */
class ZhiMa extends BaseALi {

    /**
     * 授权查询
     * @return \Zodream\Http\Http
     */
    public function getAuthQuery() {
        return $this->getBaseHttp()
            ->appendMaps([
                'method' => 'zhima.auth.info.authquery',
                '#biz_content' => [
                    '#identity_param' => [
                        '#userId'
                    ],
                ]
            ]);
    }
    /**
     * 获取芝麻分
     */
    public function getScore() {
        return $this->getBaseHttp()
            ->appendMaps([
                'method' => 'zhima.credit.score.get',
                '#biz_content' => [
                    '#transaction_id',
                    'product_code' => 'w1010100100000000001',
                ]
            ]);
    }

    /**
     * 用于商户做准入判断 商户输入准入分 判断用户是否准入
     * @return \Zodream\Http\Http
     */
    public function getHasScore() {
        return $this->getBaseHttp()
            ->appendMaps([
                'method' => 'zhima.credit.score.brief.get',
                '#biz_content' => [
                    '#transaction_id',
                    'product_code' => 'w1010100000000002733',
                    'cert_type' => 'IDENTITY_CARD',
                    '#cert_no',
                    'name',
                    '#admittance_score',
                    'linked_merchant_id'
                ]
            ]);
    }

    /**
     * 判断用户是否授权
     * @param $userId
     * @return bool
     * @throws \Exception
     */
    public function authQuery($userId) {
        $data = $this->getAuthQuery()->parameters([
            'identity_param' => Json::encode([
                'userId' => $userId
            ])
        ])->text();
        return isset($data['authorized']) && $data['authorized'];
    }

    /**
     * 获取芝麻分
     * @param $token
     * @param $transaction_id
     * @return integer|boolean
     * @throws \Exception
     */
    public function score($token, $transaction_id) {
        $data = $this->getScore()->parameters([
            'auth_token' => $token,
            'transaction_id' => $transaction_id
        ])->text();
        return isset($data['zm_score']) ? $data['zm_score'] : false;
    }

    /**
     * 用于商户做准入判断 商户输入准入分 判断用户是否准入
     * @param $cert_no
     * @param $score
     * @param string $cert_type
     * @return bool
     * @throws \Exception
     */
    public function hasScore($cert_no, $score, $cert_type = 'IDENTITY_CARD') {
        $data = $this->getHasScore()->parameters([
            'cert_type' => $cert_type,
            'cert_no' => $cert_no,
            'admittance_score' => $score,
        ])->text();
        return isset($data['is_admittance']) && $data['is_admittance'] == 'Y';
    }
}