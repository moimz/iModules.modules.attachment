<?php
/**
 * 이 파일은 아이모듈 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일모듈에 의해 첨부된 파일 구조체를 정의한다.
 *
 * @file /modules/attachment/dtos/File.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 4. 22.
 */
namespace modules\attachment\dtos;
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
