<?php
namespace Zodream\ThirdParty\ALi;
/**
 * 芝麻分
 * @package Zodream\ThirdParty\ALi
 *
 */
class ZhiMa extends BaseALi {
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

    public function score($token, $transaction_id) {
        return $this->getScore()->parameters([
            'auth_token' => $token,
            'transaction_id' => $transaction_id
        ])->text();
    }
}