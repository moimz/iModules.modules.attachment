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
class File
{
    /**
     * @var string $_hash 파일해시
     */
    private string $_hash;

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
     * @var \modules\attachment\Attachment $_attachment 첨부파일모듈
     */
    private static \modules\attachment\Attachment $_attachment;

    /**
     * 파일 구조체를 정의한다.
     *
     * @param object $file 파일정보
     */
    public function __construct(object|string $file)
    {
        if (is_object($file) == true) {
            $this->_hash = $file->hash;
            $this->_path = \Configs::attachment() . '/' . $file->path;
            $this->_type = $file->type;
            $this->_mime = $file->mime;
            $this->_extension = $file->extension;
            $this->_size = $file->size;
            $this->_width = $file->width;
            $this->_height = $file->height;
            $this->_created_at = $file->created_at;
        } else {
            $this->_path = $file;
        }
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
     * 파일해시를 가져온다.
     *
     * @return string $hash
     */
    public function getHash(): string
    {
        if (isset($this->_hash) == false) {
            $this->_hash = \File::hash($this->_path);
        }
        return $this->_hash;
    }

    /**
     * 파일명을 정제하여 가져온다.
     *
     * @return string $origin 원본파일명
     */
    public function getName(?string $origin = null, ?string $extension = null): string
    {
        $name = explode('.', $origin ?? basename($this->_path));
        $oExtension = end($name);
        $extension = $extension ?? $this->getExtension();
        if (count($name) > 1 && $oExtension != $extension) {
            array_pop($name);
            $name = implode('.', $name);
            return $name . '.' . $extension;
        }

        return implode('.', $name);
    }

    /**
     * 파일 절대경로를 가져온다.
     *
     * @return string $path
     */
    public function getPath(): string
    {
        return $this->_path;
    }

    /**
     * 파일종류를 가져온다.
     *
     * @return string $type
     */
    public function getType(): string
    {
        if (isset($this->_type) == false) {
            $this->_type = $this->getModule()->getFileType($this->getMime());
        }
        return $this->_type;
    }

    /**
     * 파일 MIME를 가져온다.
     *
     * @return string $mime
     */
    public function getMime(): string
    {
        if (isset($this->_mime) == false) {
            $this->_mime = $this->getModule()->getFileMime($this->_path);
        }
        return $this->_mime;
    }

    /**
     * 파일 확장자를 가져온다.
     *
     * @return string $extension
     */
    public function getExtension(): string
    {
        if (isset($this->_extension) == false) {
            $this->_extension = $this->getModule()->getFileExtension(basename($this->_path), $this->getMime());
        }
        return $this->_extension;
    }

    /**
     * 파일크기를 가져온다.
     *
     * @return int $size
     */
    public function getSize(): int
    {
        if (isset($this->_size) == false) {
            $this->_size = filesize($this->_path);
        }
        return $this->_size;
    }

    /**
     * 이미지파일 너비를 가져온다.
     *
     * @param int $width
     */
    public function getWidth(): int
    {
        if (isset($this->_width) == false) {
            $size = $this->getModule()->getImageSize($this->_path);
            $this->_width = $size[0];
            $this->_height = $size[1];
        }

        return $this->_width;
    }

    /**
     * 이미지파일 높이를 가져온다.
     *
     * @param int $height
     */
    public function getHeight(): int
    {
        if (isset($this->_height) == false) {
            $size = $this->getModule()->getImageSize($this->_path);
            $this->_width = $size[0];
            $this->_height = $size[1];
        }

        return $this->_height;
    }

    /**
     * 생성일자를 가져온다.
     *
     * @param int $created_at
     */
    public function getCreatedAt(): int
    {
        if (isset($this->_created_at) == false) {
            $this->_created_at = filemtime($this->_path);
        }
        return $this->_created_at;
    }

    /**
     * 파일이 썸네일을 생성할 수 있는지 확인한다.
     *
     * @return bool $resizable
     */
    public function isResizable(): bool
    {
        return $this->getType() == 'image' && in_array($this->getExtension(), ['jpg', 'jpeg', 'png', 'gif']) == true;
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
}
