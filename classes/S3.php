<?php
/**
 * 이 파일은 아이모듈 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * AWS S3 클래스를 정의한다.
 *
 * @file /modules/attachment/classes/S3.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 9. 1.
 */
namespace modules\attachment;
class S3Object
{
    private string $_body;
    private string $_content_type;
    private int $_content_length;

    public function __construct(string $body, string $content_type, int $content_length)
    {
        $this->_body = $body;
        $this->_content_type = $content_type;
        $this->_content_length = $content_length;
    }

    /**
     * 파일 byte 를 가져온다.
     *
     * @return string $byte
     */
    public function getBody(): string
    {
        return $this->_body;
    }

    /**
     * 파일형식을 가져온다.
     *
     * @return string $mime
     */
    public function getContentType(): string
    {
        return $this->_content_type;
    }

    /**
     * 파일크기를 가져온다.
     *
     * @return int $size
     */
    public function getContentLength(): int
    {
        return $this->_content_length;
    }
}

class S3
{
    /**
     * @var string $_key IAM 키
     */
    private string $_key;

    /**
     * @var string $_secret IAM 비밀키
     */
    private string $_secret;

    /**
     * @var string $_region S3 리전
     */
    private string $_region;

    /**
     * @var string $_bucket S3 버킷명
     */
    private string $_bucket;

    /**
     * S3 클래스를 생성한다.
     *
     * @param string $key IAM 키
     * @param string $secret IAM 비밀키
     * @param string $region S3 리전
     * @param string $bucket S3 버킷명
     */
    public function __construct(string $key, string $secret, string $region, string $bucket)
    {
        $this->_key = $key;
        $this->_secret = $secret;
        $this->_region = $region;
        $this->_bucket = $bucket;
    }

    /**
     * 경로상의 한글은 URLENCODE 처리하고, 경로의 첫 시작이 / 가 아닐 경우 추가한다.
     *
     * @param string $fileKey
     * @return string $fileKey
     */
    public function fileKey(string $fileKey)
    {
        if (strpos($fileKey, '/') !== 0) {
            $fileKey = '/' . $fileKey;
        }

        $keys = explode('/', $fileKey);
        $keys = array_map(function ($key) {
            return urlencode($key);
        }, $keys);
        return implode('/', $keys);
    }

    /**
     * S3 에 파일을 업로드한다.
     *
     * @param string $filePath 업로드할 파일 경로
     * @param string $fileKey S3 파일키 (업로드할 경로 / 부터 시작)
     * @return bool $success
     */
    public function upload(string $filePath, string $fileKey): bool
    {
        $fileKey = $this->fileKey($fileKey);
        $time = time();

        $fileContents = file_get_contents($filePath);
        $hashed_payload = hash('sha256', $fileContents);

        $headers = [
            'Host' => $this->_bucket . '.s3.' . $this->_region . '.amazonaws.com',
            'Content-Type' => mime_content_type($filePath),
            'x-amz-content-sha256' => $hashed_payload,
            'x-amz-date' => gmdate('r', $time),
        ];
        ksort($headers);
        $signed_headers_string = strtolower(implode(';', array_keys($headers)));

        $canonical_request = "PUT\n";
        $canonical_request .= $fileKey . "\n";
        $canonical_request .= "\n";
        foreach ($headers as $header => $value) {
            $canonical_request .= strtolower($header) . ':' . trim($value) . "\n";
        }
        $canonical_request .= "\n";
        $canonical_request .= $signed_headers_string . "\n";
        $canonical_request .= $hashed_payload;

        $string_to_sign = "AWS4-HMAC-SHA256\n";
        $string_to_sign .= gmdate('r', $time) . "\n";
        $string_to_sign .= gmdate('Ymd', $time) . '/' . $this->_region . "/s3/aws4_request\n";
        $string_to_sign .= hash('sha256', $canonical_request);

        $signature_date = hash_hmac('sha256', gmdate('Ymd', $time), 'AWS4' . $this->_secret, true);
        $signature_region = hash_hmac('sha256', $this->_region, $signature_date, true);
        $signature_service = hash_hmac('sha256', 's3', $signature_region, true);
        $signature_request = hash_hmac('sha256', 'aws4_request', $signature_service, true);
        $signature = hash_hmac('sha256', $string_to_sign, $signature_request);

        $headers['Authorization'] =
            'AWS4-HMAC-SHA256 Credential=' .
            $this->_key .
            '/' .
            gmdate('Ymd', $time) .
            '/' .
            $this->_region .
            '/s3/aws4_request,';
        $headers['Authorization'] .= 'SignedHeaders=' . $signed_headers_string . ',';
        $headers['Authorization'] .= 'Signature=' . $signature;

        $curl_headers = [];
        foreach ($headers as $header => $value) {
            $curl_headers[] = "{$header}: {$value}";
        }

        $curl = curl_init();
        $url = 'http://' . $this->_bucket . '.s3.' . $this->_region . '.amazonaws.com' . $fileKey;
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_PUT, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $fileContents);
        curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return $code == 200;
    }

    /**
     * S3 에서 파일이 존재하는지 확인한다.
     *
     * @param string $fileKey S3 파일키 (파일경로)
     * @return bool $existed
     */
    public function has(string $fileKey, bool $is_headers = false): bool|array
    {
        $fileKey = $this->fileKey($fileKey);
        $time = time();

        $hashed_payload = hash('sha256', '');

        $headers = [
            'Host' => $this->_bucket . '.s3.amazonaws.com',
            'x-amz-content-sha256' => $hashed_payload,
            'x-amz-date' => gmdate('r', $time),
        ];
        ksort($headers);
        $signed_headers_string = strtolower(implode(';', array_keys($headers)));

        $canonical_request = "HEAD\n";
        $canonical_request .= $fileKey . "\n";
        $canonical_request .= "\n";
        foreach ($headers as $header => $value) {
            $canonical_request .= strtolower($header) . ':' . trim($value) . "\n";
        }
        $canonical_request .= "\n";
        $canonical_request .= $signed_headers_string . "\n";
        $canonical_request .= $hashed_payload;

        $string_to_sign = "AWS4-HMAC-SHA256\n";
        $string_to_sign .= gmdate('r', $time) . "\n";
        $string_to_sign .= gmdate('Ymd', $time) . '/' . $this->_region . "/s3/aws4_request\n";
        $string_to_sign .= hash('sha256', $canonical_request);

        $signature_date = hash_hmac('sha256', gmdate('Ymd', $time), 'AWS4' . $this->_secret, true);
        $signature_region = hash_hmac('sha256', $this->_region, $signature_date, true);
        $signature_service = hash_hmac('sha256', 's3', $signature_region, true);
        $signature_request = hash_hmac('sha256', 'aws4_request', $signature_service, true);
        $signature = hash_hmac('sha256', $string_to_sign, $signature_request);

        $headers['Authorization'] =
            'AWS4-HMAC-SHA256 Credential=' .
            $this->_key .
            '/' .
            gmdate('Ymd', $time) .
            '/' .
            $this->_region .
            '/s3/aws4_request,';
        $headers['Authorization'] .= 'SignedHeaders=' . $signed_headers_string . ',';
        $headers['Authorization'] .= 'Signature=' . $signature;

        $curl_headers = [];
        foreach ($headers as $header => $value) {
            $curl_headers[] = "{$header}: {$value}";
        }

        $curl = curl_init();
        $url = 'http://' . $this->_bucket . '.s3.' . $this->_region . '.amazonaws.com' . $fileKey;
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'HEAD');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_exec($curl);
        $cinfo = curl_getinfo($curl);
        curl_close($curl);

        if ($is_headers === true) {
            return $cinfo;
        } else {
            return $cinfo['http_code'] == 200;
        }
    }

    /**
     * S3 에서 파일 콘텐츠를 가져온다.
     *
     * @param string $fileKey S3 파일키 (파일경로)
     * @return S3Object|bool $byte (false 인 경우 파일을 읽어올 수 없음)
     */
    public function get(string $fileKey): S3Object|bool
    {
        $fileKey = $this->fileKey($fileKey);
        $time = time();

        $hashed_payload = hash('sha256', '');

        $headers = [
            'Host' => $this->_bucket . '.s3.' . $this->_region . '.amazonaws.com',
            'x-amz-content-sha256' => $hashed_payload,
            'x-amz-date' => gmdate('r', $time),
        ];
        ksort($headers);
        $signed_headers_string = strtolower(implode(';', array_keys($headers)));

        $canonical_request = "GET\n";
        $canonical_request .= $fileKey . "\n";
        $canonical_request .= "\n";
        foreach ($headers as $header => $value) {
            $canonical_request .= strtolower($header) . ':' . trim($value) . "\n";
        }
        $canonical_request .= "\n";
        $canonical_request .= $signed_headers_string . "\n";
        $canonical_request .= $hashed_payload;

        $string_to_sign = "AWS4-HMAC-SHA256\n";
        $string_to_sign .= gmdate('r', $time) . "\n";
        $string_to_sign .= gmdate('Ymd', $time) . '/' . $this->_region . "/s3/aws4_request\n";
        $string_to_sign .= hash('sha256', $canonical_request);

        $signature_date = hash_hmac('sha256', gmdate('Ymd', $time), 'AWS4' . $this->_secret, true);
        $signature_region = hash_hmac('sha256', $this->_region, $signature_date, true);
        $signature_service = hash_hmac('sha256', 's3', $signature_region, true);
        $signature_request = hash_hmac('sha256', 'aws4_request', $signature_service, true);
        $signature = hash_hmac('sha256', $string_to_sign, $signature_request);

        $headers['Authorization'] =
            'AWS4-HMAC-SHA256 Credential=' .
            $this->_key .
            '/' .
            gmdate('Ymd', $time) .
            '/' .
            $this->_region .
            '/s3/aws4_request,';
        $headers['Authorization'] .= 'SignedHeaders=' . $signed_headers_string . ',';
        $headers['Authorization'] .= 'Signature=' . $signature;

        $curl_headers = [];
        foreach ($headers as $header => $value) {
            $curl_headers[] = "{$header}: {$value}";
        }

        $curl = curl_init();
        $url = 'http://' . $this->_bucket . '.s3.' . $this->_region . '.amazonaws.com' . $fileKey;
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $body = curl_exec($curl);
        $cinfo = curl_getinfo($curl);
        curl_close($curl);

        return $cinfo['http_code'] == 200
            ? new S3Object($body, $cinfo['content_type'], $cinfo['download_content_length'])
            : false;
    }

    /**
     * S3 에서 파일 콘텐츠를 읽는다.
     *
     * @param string $fileKey S3 파일키 (파일경로)
     */
    public function read(string $fileKey): void
    {
        $cinfo = $this->has($fileKey, true);

        if ($cinfo['http_code'] !== 200) {
            header($_SERVER['SERVER_PROTOCOL'] . ' ' . $cinfo['http_code']);
            exit();
        }

        header('Content-Type: ' . $cinfo['content_type'], true);
        header('Content-Length: ' . $cinfo['download_content_length'], true);

        $fileKey = $this->fileKey($fileKey);
        $time = time();

        $hashed_payload = hash('sha256', '');

        $headers = [
            'Host' => $this->_bucket . '.s3.' . $this->_region . '.amazonaws.com',
            'x-amz-content-sha256' => $hashed_payload,
            'x-amz-date' => gmdate('r', $time),
        ];
        ksort($headers);
        $signed_headers_string = strtolower(implode(';', array_keys($headers)));

        $canonical_request = "GET\n";
        $canonical_request .= $fileKey . "\n";
        $canonical_request .= "\n";
        foreach ($headers as $header => $value) {
            $canonical_request .= strtolower($header) . ':' . trim($value) . "\n";
        }
        $canonical_request .= "\n";
        $canonical_request .= $signed_headers_string . "\n";
        $canonical_request .= $hashed_payload;

        $string_to_sign = "AWS4-HMAC-SHA256\n";
        $string_to_sign .= gmdate('r', $time) . "\n";
        $string_to_sign .= gmdate('Ymd', $time) . '/' . $this->_region . "/s3/aws4_request\n";
        $string_to_sign .= hash('sha256', $canonical_request);

        $signature_date = hash_hmac('sha256', gmdate('Ymd', $time), 'AWS4' . $this->_secret, true);
        $signature_region = hash_hmac('sha256', $this->_region, $signature_date, true);
        $signature_service = hash_hmac('sha256', 's3', $signature_region, true);
        $signature_request = hash_hmac('sha256', 'aws4_request', $signature_service, true);
        $signature = hash_hmac('sha256', $string_to_sign, $signature_request);

        $headers['Authorization'] =
            'AWS4-HMAC-SHA256 Credential=' .
            $this->_key .
            '/' .
            gmdate('Ymd', $time) .
            '/' .
            $this->_region .
            '/s3/aws4_request,';
        $headers['Authorization'] .= 'SignedHeaders=' . $signed_headers_string . ',';
        $headers['Authorization'] .= 'Signature=' . $signature;

        $curl_headers = [];
        foreach ($headers as $header => $value) {
            $curl_headers[] = "{$header}: {$value}";
        }

        $curl = curl_init();
        $url = 'http://' . $this->_bucket . '.s3.' . $this->_region . '.amazonaws.com' . $fileKey;
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_headers);
        curl_exec($curl);
        curl_close($curl);

        exit();
    }

    /**
     * S3 에서 파일을 삭제한다.
     *
     * @param string $fileKey S3 파일키 (파일경로)
     * @return bool $success
     */
    public function delete(string $fileKey): bool
    {
        $fileKey = $this->fileKey($fileKey);
        $time = time();

        $hashed_payload = hash('sha256', '');

        $headers = [
            'Host' => $this->_bucket . '.s3.' . $this->_region . '.amazonaws.com',
            'x-amz-content-sha256' => $hashed_payload,
            'x-amz-date' => gmdate('r', $time),
        ];
        ksort($headers);
        $signed_headers_string = strtolower(implode(';', array_keys($headers)));

        $canonical_request = "DELETE\n";
        $canonical_request .= $fileKey . "\n";
        $canonical_request .= "\n";
        foreach ($headers as $header => $value) {
            $canonical_request .= strtolower($header) . ':' . trim($value) . "\n";
        }
        $canonical_request .= "\n";
        $canonical_request .= $signed_headers_string . "\n";
        $canonical_request .= $hashed_payload;

        $string_to_sign = "AWS4-HMAC-SHA256\n";
        $string_to_sign .= gmdate('r', $time) . "\n";
        $string_to_sign .= gmdate('Ymd', $time) . '/' . $this->_region . "/s3/aws4_request\n";
        $string_to_sign .= hash('sha256', $canonical_request);

        $signature_date = hash_hmac('sha256', gmdate('Ymd', $time), 'AWS4' . $this->_secret, true);
        $signature_region = hash_hmac('sha256', $this->_region, $signature_date, true);
        $signature_service = hash_hmac('sha256', 's3', $signature_region, true);
        $signature_request = hash_hmac('sha256', 'aws4_request', $signature_service, true);
        $signature = hash_hmac('sha256', $string_to_sign, $signature_request);

        $headers['Authorization'] =
            'AWS4-HMAC-SHA256 Credential=' .
            $this->_key .
            '/' .
            gmdate('Ymd', $time) .
            '/' .
            $this->_region .
            '/s3/aws4_request,';
        $headers['Authorization'] .= 'SignedHeaders=' . $signed_headers_string . ',';
        $headers['Authorization'] .= 'Signature=' . $signature;

        $curl_headers = [];
        foreach ($headers as $header => $value) {
            $curl_headers[] = "{$header}: {$value}";
        }

        $curl = curl_init();
        $url = 'http://' . $this->_bucket . '.s3.' . $this->_region . '.amazonaws.com' . $fileKey;
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return $code == 204;
    }
}
