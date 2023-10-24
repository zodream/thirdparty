<?php
declare(strict_types=1);
namespace Zodream\ThirdParty\Translate;

use Zodream\Helpers\Json;
use Zodream\Http\Http;
use Zodream\ThirdParty\ThirdParty;

class Tencent extends ThirdParty implements ITranslateProtocol {

    const SERVICE = 'tmt';

    public function getBaseHttp(array $data) {
        $host = sprintf('%s.tencentcloudapi.com', static::SERVICE);
        $data['ProjectId'] = $this->get('projectId');
        $now = time();
        $date = gmdate('Y-m-d');
        $data = Json::encode($data);

        $credentialScope = implode('/', [
            $date, static::SERVICE, 'tc3_request'
        ]);
        $signedHeaders = implode(';', [
            'content-type',
            'host',
        ]);
        $signData = implode("\n", [
            'POST',
            '/',
            'content-type:application/json; charset=utf-8',
            sprintf('host:%s', $host),
            $signedHeaders,
            hash('SHA256', $data)
        ]);
        $signData = implode("\n", [
            'TC3-HMAC-SHA256',
            $now,
            $credentialScope,
            hash('SHA256', $signData)
        ]);
        $sign = $this->sign($signData, $date);
        return $this->getHttp(sprintf('https://%s', $host))
            ->header('Authorization', sprintf('TC3-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s',
                $this->get('secretId'),
                $credentialScope,
                $signedHeaders,
                $sign
            ))
            ->header('X-TC-Version', '2018-03-21')
            ->header('X-TC-Timestamp', $now)
            ->header('X-TC-Region', $this->get('region', 'ap-guangzhou'))
            ->header('X-TC-Action', 'TextTranslateBatch')
            ->header('Content-Type', 'application/json; charset=utf-8')
            ->method(Http::POST)->parameters($data);
    }

    public function trans(string $sourceLang, string $targetLang, array|string $text): string|array {
        $data = $this->getBaseHttp([
            'Source' => $sourceLang,
            'Target' => $targetLang,
            'SourceTextList' => (array)$text,
        ])->json();
        if (empty($data) || empty($data['response']['TargetTextList'])) {
            throw new \Exception('any error');
        }
        $res = $data['response']['TargetTextList'];
        if (!is_array($text)) {
            return current($res);
        }
        return $res;
    }

    protected function sign(string $signData, string $date): string {
        $secretDate = hash_hmac('SHA256', $date, 'TC3'.$this->get('secretKey'), true);
        $secretService = hash_hmac('SHA256', static::SERVICE, $secretDate, true);
        $secretSigning = hash_hmac('SHA256', "tc3_request", $secretService, true);
        return hash_hmac('SHA256', $signData, $secretSigning);
    }
}