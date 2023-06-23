<?php
/**
 * 이 파일은 아이모듈 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일모듈에 의해 첨부된 파일 구조체를 정의한다.
 *
 * @file /modules/attachment/dto/Attachment.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 4. 10.
 */
namespace modules\attachment\dto;
class Attachment
{
    /**
     * @var string $_id 첨부파일고유값
     */
    private string $_id;

    /**
     * @var string $_hash 파일해시
     */
    private string $_hash;

    /**
     * @var string $_component_type 첨부한 컴포넌트종류
     * @var string $_component_name 첨부한 컴포넌트명
     */
    private string $_component_type;
    private string $_component_name;

    /**
     * @var string $_position_type 업로드위치
     * @var string $_position_id 업로드위치고유값
     */
    private string $_position_type;
    private string $_position_id;

    /**
     * @var string $_name 파일명
     */
    private string $_name;

    /**
     * @var string $_path 파일절대경로
     */
    private string $_path;

    /**
     * @var string $_type 파일종류
     */
    private string $_type;

    /**
     * @var string $_mime 파일 MIME
     */
    private string $_mime;

    /**
     * @var string $_extension 파일 확장자
     */
    private string $_extension;

    /**
     * @var int $_size 파일용량
     */
    private int $_size;

    /**
     * @var int $_width 이미지파일인 경우 이미지파일 가로크기
     */
    private int $_width;

    /**
     * @var int $_height 이미지파일인 경우 이미지파일 세로크기
     */
    private int $_height;

    /**
     * @var int $_created_at 생성일시
     */
    private int $_created_at;

    /**
     * @var int $_download 다운로드수
     */
    private int $_download;

    /**
     * @var ?object $_extras 추가정보
     */
    private ?object $_extras;

    /**
     * @var \modules\attachment\Attachment $_attachment 첨부파일모듈
     */
    private static \modules\attachment\Attachment $_attachment;

    /**
     * 파일 구조체를 정의한다.
     *
     * @param object $attachment 첨부파일정보
     */
    public function __construct(object $attachment)
    {
        if (isset($attachment->attachment_id) == true) {
            $this->_id = $attachment->attachment_id;
            $this->_component_type = $attachment->component_type;
            $this->_component_name = $attachment->component_name;
            $this->_position_type = $attachment->position_type;
            $this->_position_id = $attachment->position_id;
            $this->_download = $attachment->download;
            $this->_extras = json_decode($attachment->extras ?? '');
        } else {
            $this->_id = $attachment->draft_id;
        }

        $this->_hash = $attachment->hash;
        $this->_name = $attachment->name;
        $this->_path = $attachment->path;
        $this->_type = $attachment->type;
        $this->_mime = $attachment->mime;
        $this->_extension = $attachment->extension;
        $this->_size = $attachment->size;
        $this->_width = $attachment->width;
        $this->_height = $attachment->height;
        $this->_created_at = $attachment->created_at;
    }

    /**
     * 첨부파일모듈 클래스를 가져온다.
     *
     * @return \modules\attachment\Attachment $mAttachment
     */
    public function getModule(): \modules\attachment\Attachment
    {
        if (isset(self::$_attachment) == false) {
            /*
             * @var \modules\attachment\Attachment $mAttachment
             */
            self::$_attachment = \Modules::get('attachment');
        }

        return self::$_attachment;
    }

    /**
     * 첨부파일고유값을 가져온다.
     *
     * @return string $id
     */
    public function getId(): string
    {
        return $this->_id;
    }

    /**
     * 파일해시를 가져온다.
     *
     * @return string $name
     */
    public function getHash(): string
    {
        return $this->_hash;
    }

    /**
     * 파일명을 가져온다.
     *
     * @return string $name
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * 파일 절대경로를 가져온다.
     *
     * @return string $path
     */
    public function getPath(): string
    {
        return \Configs::attachment() . '/' . $this->_path;
    }

    /**
     * 파일종류를 가져온다.
     *
     * @return string $type
     */
    public function getType(): string
    {
        return $this->_type;
    }

    /**
     * 파일 MIME를 가져온다.
     *
     * @return string $mime
     */
    public function getMime(): string
    {
        return $this->_mime;
    }

    /**
     * 파일 확장자를 가져온다.
     *
     * @return string $extension
     */
    public function getExtension(): string
    {
        return $this->_extension;
    }

    /**
     * 파일크기를 가져온다.
     *
     * @return int $size
     */
    public function getSize(): int
    {
        return $this->_size;
    }

    /**
     * 이미지파일 너비를 가져온다.
     *
     * @param int $width
     */
    public function getWidth(): int
    {
        return $this->_width;
    }

    /**
     * 이미지파일 높이를 가져온다.
     *
     * @param int $height
     */
    public function getHeight(): int
    {
        return $this->_height;
    }

    /**
     * 생성일자를 가져온다.
     *
     * @param int $created_at
     */
    public function getCreatedAt(): int
    {
        return $this->_created_at;
    }

    /**
     * 파일 URL 을 가져온다.
     *
     * @param ?string $type URL종류 (thumbnail : 이미지썸네일, view : 이미지보기, origin : 원본, download : 다운로드, NULL인 경우 파일 종류에 따라 자동으로 선택)
     * @return string $url
     */
    public function getUrl(?string $type = null): string
    {
        if ($type === null) {
            if (in_array($this->_type, ['image', 'text']) == true) {
                $type = 'view';
            } else {
                $type = 'download';
            }
        }

        $route = '/files/' . $type . '/' . $this->_id . '/' . urlencode($this->_name);
        return \Configs::dir() . (\Domains::has()?->isRewrite() == true ? $route : '/?route=' . $route);
    }

    /**
     * 도메인을 포함한 파일 URL 을 가져온다.
     *
     * @param ?string $type URL종류 (thumbnail : 이미지썸네일, view : 이미지보기, origin : 원본, download : 다운로드, NULL인 경우 파일 종류에 따라 자동으로 선택)
     * @return string $url
     */
    public function getFullUrl(?string $type = null): string
    {
        return \Domains::get()->getUrl() . $this->getUrl($type);
    }

    /**
     * 파일정보를 가져온다.
     *
     * @return object $info
     */
    public function getInfo(): object
    {
        $info = new \stdClass();

        $info->id = $this->_id;
        $info->name = $this->_name;
        $info->type = $this->_type;
        $info->mime = $this->_mime;
        $info->extension = $this->_extension;
        $info->size = $this->_size;
        $info->width = $this->_width;
        $info->height = $this->_height;
        $info->view = $this->isViewable() == true ? $this->getUrl('view') : null;
        $info->download = $this->getUrl('download');
        $info->thumbnail = $this->isResizable() == true ? $this->getUrl('thumbnail') : null;

        return $info;
    }

    /**
     * 파일이 썸네일을 생성할 수 있는지 확인한다.
     *
     * @return bool $resizable
     */
    public function isResizable(): bool
    {
        return in_array($this->getType(), ['image', 'svg', 'icon']) == true;
    }

    /**
     * 파일을 브라우저에서 볼 수 있는지 확인한다.
     *
     * @return bool $viewable
     */
    public function isViewable(): bool
    {
        return in_array($this->getType(), ['image', 'svg', 'icon', 'text']) == true ||
            in_array($this->getExtension(), ['pdf']) == true;
    }

    /**
     * 첨부파일이 출판된 상태인지 확인한다.
     *
     * @return bool $is_published
     */
    public function isPublished(): bool
    {
        return isset($this->_component_type) == true && isset($this->_component_name) == true;
    }
}
