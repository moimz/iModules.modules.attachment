<?php
/**
 * 이 파일은 아이모듈 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일모듈에 의해 첨부된 파일 구조체를 정의한다.
 *
 * @file /modules/attachment/dto/File.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 4. 10.
 */
namespace modules\attachment\dto;
class File
{
    /**
     * @var object $_file 파일정보
     */
    private object $_file;

    /**
     * @var int $_file_id 파일고유값
     */
    private int $_file_id;

    /**
     * @var int $_target 파일대상
     */
    private string $_target;

    /**
     * @var string $_path 파일절대경로
     */
    private string $_path;

    /**
     * @var string $_name 파일명
     */
    private string $_name;

    /**
     * @var string $_type 파일종류
     */
    private string $_type;

    /**
     * @var string $_mime 파일 MIME
     */
    private string $_mime;

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
     * @var int $_uploaded_date 업로드일시
     */
    private int $_uploaded_date;

    /**
     * @var int $_download 다운로드수
     */
    private int $_download;

    /**
     * @var string $_status 업로드상태
     */
    private string $_status;

    /**
     * 파일 구조체를 정의한다.
     *
     * @param object $file 파일정보 또는 파일경로
     */
    public function __construct(object $file)
    {
        $this->_file = $file;
        $this->_file_id = $file->file_id;
        $this->_target = $file->target;
        $this->_path = $file->path;
        $this->_name = $file->name;
        $this->_type = $file->type;
        $this->_mime = $file->mime;
        $this->_size = $file->size;
        $this->_width = $file->width;
        $this->_height = $file->height;
        $this->_uploaded_date = $file->uploaded_date;
        $this->_download = $file->download;
        $this->_status = $file->status;
    }

    /**
     * 파일 절대경로를 가져온다.
     *
     * @param string $type 가져올 파일 타입 (기본값 origin, origin : 원본파일, view : 보기용파일, thumbnail : 썸네일)
     * @return string $path
     */
    public function getPath(string $type = 'origin'): string
    {
        if ($type == 'origin') {
            return \Configs::attachment() . '/' . $this->_path;
        }

        // @todo 썸네일 등등
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

        $route = '/files/' . $type . '/' . $this->_file_id . '/' . urlencode($this->_name);
        return \Configs::dir() . (\Domains::has()?->isRewrite() == true ? $route : '/?route=' . $route);
    }
}
?>
