<?php
namespace Zodream\ThirdParty\API;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/6/2
 * Time: 12:55
 */
use Zodream\ThirdParty\ThirdParty;

class Microsoft extends ThirdParty  {

    public function getFaceScore() {
        return $this->getHttp('http://kan.msxiaobing.com/Api/ImageAnalyze/Process?service=yanzhi')
            ->maps([
                'MsgId',
                'CreateTime',
                'Content[imageUrl]'
            ]);
    }

    public function getUpload() {
        return $this->getHttp('http://kan.msxiaobing.com/Api/Image/UploadBase64');
    }

    public function getWallpager() {
        return $this->getHttp()
            ->url('https://bing.com/HPImageArchive.aspx?format=js', [
                'idx' => 0,
                'n' => 1,
                'mkt' =>'zh-CN'
            ]);
    }

    /**
     * 颜值测试
     * @param string $img base64_encode
     * @return array
     * @throws \Exception
     */
    public function faceScore($img) {
        /**
         * {"Host":"","Url":""}
         */
        $data = $this->getUpload()->parameters($img)->json();
        $this->set(array(
            'MsgId' => time().'063',
            'CreateTime' => time(),
            'Content[imageUrl]' => $data['Host'].$data['Url']
        ));
        return $this->getFaceScore()
            ->parameters($this->get())->json();
    }

    /**
     * 获取壁纸
     * @param int $amount
     * @param int $dayAgo -1 表示明天，> 1 表示昨天以前
     * @return array{url: string,m_url: string,title: string}[]
     * @throws \Exception
     */
    public function wallpager(int $amount = 1, int $dayAgo = 0) {
        if ($amount > 8) {
            $amount = 8;
        } elseif ($amount < 1) {
            $amount = 1;
        }
        if ($dayAgo > 16) {
            $dayAgo = 16;
        } elseif ($dayAgo < -1) {
            $dayAgo = -1;
        }
        $data = $this->getWallpager()
            ->parameters([
                'n' => $amount,
                'idx' => $dayAgo
            ])->json();
        if (empty($data) || empty($data['images'])) {
            return [];
        }
        $baseHost = 'https://bing.com';
        return array_map(function ($item) use ($baseHost) {
            return [
                'url' => $baseHost. $item['url'],
                'm_url' => $this->wallpagerUrl($baseHost. $item['urlbase'], 1080, 1920),
                'title' => $item['title'],
            ];
        }, $data['images']);
    }

    private function wallpagerUrl(string $base, int $width, int $height): string {
        return  sprintf('%s_%dx%d.jpg&rf=LaDigue_%dx%d.jpg&pid=hp', $base, $width, $height, $width, $height);
    }
}