<?php
/**
 * 이 파일은 아이모듈 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일 클래스를 정의한다.
 * 첨부된 파일데이터를 관리하고, 첨부파일 업로드가 필요한 곳에 파일업로더를 제공한다.
 *
 * @file /modules/attachment/Attachment.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 12. 1.
 */
namespace modules\attachment;
use \Router;
use \Route;
use \ErrorHandler;
use \ErrorData;
class Attachment extends \Module
{
    /**
     * @var File[] $_files 파일정보를 보관한다.
     */
    private static array $_files = [];

    /**
     * 모듈을 설정을 초기화한다.
     */
    public function init(): void
    {
        /**
         * 모듈 라우터를 초기화한다.
         */
        Router::add('/files/{type}/{file_id}/{name}', '#', 'blob', [$this, 'doRoute']);
    }

    /**
     * 첨부파일이 존재하는지 확인한다.
     *
     * @param int $file_id 첨부파일 고유값
     * @return bool $has_file
     */
    public function hasFile(int $file_id): bool
    {
        return $this->getFile($file_id) !== null;
    }

    /**
     * 첨부파일 정보를 가져온다.
     *
     * @param int $file_id 첨부파일 고유값
     * @return dto\File $file
     */
    public function getFile(int $file_id): ?dto\File
    {
        if (isset(self::$_files[$file_id]) == true) {
            return self::$_files[$file_id];
        }

        $file = $this->db()
            ->select()
            ->from($this->table('files'))
            ->where('file_id', $file_id)
            ->getOne();
        self::$_files[$file_id] = $file !== null ? new dto\File($file) : null;

        return self::$_files[$file_id];
    }

    /**
     * 파일정보를 실제파일 데이터로 갱신한다.
     *
     * @param int $file_id 첨부파일 고유값
     * @return bool $success
     */
    public function updateFile(int $file_id): bool
    {
        $file = $this->db()
            ->select()
            ->from($this->table('files'))
            ->where('file_id', $file_id)
            ->getOne();

        if ($file == null || is_file(\Configs::attachment() . '/' . $file->path) == false) {
            return false;
        }

        $path = \Configs::attachment() . '/' . $file->path;

        $updated = [
            'mime' => $this->getFileMime($path),
            'type' => $this->getFileType($this->getFileMime($path)),
            'size' => filesize($path),
            'width' => $this->getImageSize($path)[0],
            'height' => $this->getImageSize($path)[1],
        ];

        $hash = md5_file(\Configs::attachment() . '/' . $file->path);
        if (preg_match('/^' . $hash . '\./', basename($file->path)) == false) {
            $oname = basename($file->path);
            $name = explode('.', $oname);
            $name[0] = $hash;
            $name = implode('.', $name);
            $updated['path'] = str_replace($oname, $name, $file->path);
        }

        $result = $this->db()
            ->update($this->table('files'), $updated)
            ->where('file_id', $file_id)
            ->execute();

        if ($result->success == true) {
            if (isset($updated['path']) == true) {
                rename(\Configs::attachment() . '/' . $file->path, \Configs::attachment() . '/' . $updated['path']);
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * 첨부파일에 의해 첨부된 파일이 아닌, 실제 파일경로를 이용하여 파일 정보를 가져온다.
     *
     * @param string $path 첨부파일 고유값
     * @return File $file
     */
    public function getRawFile(string $path): ?dto\File
    {
        if (isset(self::$_files[$path]) == true) {
            return self::$_files[$path];
        }

        if (is_file($path) == false) {
            return null;
        }

        $file = new \stdClass();
        $file->file_id = 0;
        $file->target = 'UNKNOWN';
        $file->path = $path;
        $file->name = basename($path);
        $file->mime = $this->getFileMime($path);
        $file->type = $this->getFileType($file->mime);
        $file->size = filesize($path);

        $imagesize = $this->getImageSize($path);
        $file->width = $imagesize[0];
        $file->height = $imagesize[1];
        $file->uploaded_date = filemtime($path);
        $file->download = 0;
        $file->status = 'UNREGISTED';

        self::$_files[$path] = new dto\File($file);

        return self::$_files[$path];
    }

    /**
     * 이미지파일의 너비 및 높이를 가져온다.
     *
     * @param string $path 첨부파일 고유값
     * @return int[] [너비, 높이]
     */
    public function getImageSize(string $path): array
    {
        $type = $this->getFileType($this->getFileMime($path));

        switch ($type) {
            case 'svg':
                $svg = simplexml_load_string(File::read($path));
                $width = intval($svg->attributes()->width);
                $height = intval($svg->attributes()->height);
                break;

            case 'icon':
            case 'image':
                $imagesize = getimagesize($path);
                $width = $imagesize[0];
                $height = $imagesize[1];
                break;

            default:
                $width = 0;
                $height = 0;
        }

        return [$width, $height];
    }

    /**
     * 파일의 MIME 데이터를 가져온다.
     *
     * @param string $path 파일경로
     * @return string $mime
     */
    public function getFileMime(string $path): string
    {
        if (is_file($path) == true) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $path);
            finfo_close($finfo);

            return $mime;
        } else {
            return '';
        }
    }

    /**
     * 파일의 MIME 값을 이용하여 파일종류를 정리한다.
     *
     * @param string $mime 파일 MIME
     * @return string $type 파일종류
     */
    function getFileType(string $mime): string
    {
        if (preg_match('/^image\/(.*?)$/', $mime, $match) == true) {
            switch ($match[1]) {
                case 'svg+xml':
                    return 'svg';

                case 'jpg':
                case 'png':
                case 'gif':
                    return 'image';

                case 'x-icon':
                case 'vnd.microsoft.icon':
                    return 'icon';
            }
        }

        if (preg_match('/^application\/(.*?)(pdf|officedocument|CDFV2)/', $mime) == true) {
            return 'document';
        }

        if (preg_match('/text\//', $mime) == true) {
            return 'text';
        }

        if (preg_match('/^(video|audio)\//', $mime, $match) == true) {
            return $match[1];
        }

        if (preg_match('/application\/(zip|gzip|x\-rar\-compressed|x\-gzip)/', $mime) == true) {
            return 'archive';
        }

        return 'file';
    }

    /**
     * 파일의 확장자만 가져온다.
     *
     * @param string $filename 파일명
     * @return string $extension 파일 확장자
     */
    function getFileExtension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * 파일 라우팅을 처리한다.
     *
     * @param Route $route 현재경로
     * @param string $type 파일접근종류 (origin, view, thumbnail, download)
     * @param int $file_id 파일고유값
     * @param string $name 파일명
     */
    public function doRoute(Route $route, string $type, int $file_id, string $name): void
    {
        $file = $this->getFile($file_id);
        if ($file === null || is_file($file->getPath()) == false) {
            ErrorHandler::print($this->error('NOT_FOUND_FILE', $route->getUrl()));
        }

        session_write_close();

        if ($file->getType() == 'image') {
            $path = $file->getPath($type);
        } else {
            $path = $file->getPath();
        }

        if ($type != 'download') {
            header('Content-Type: ' . $file->getMime());
            header('Content-Length: ' . filesize($path));
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
            header('Cache-Control: max-age=3600');
            header('Pragma: public');

            readfile($path);
            exit();
        } else {
            header('Content-Type: ' . $file->getMime());
            header('Content-Length: ' . filesize($path));
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Cache-Control: private', false);
            header('Pragma: public');
            header('Expires: 0');
            header(
                'Content-Disposition: attachment; filename="' .
                    rawurlencode($name) .
                    '"; filename*=UTF-8\'\'' .
                    rawurlencode($name)
            );
            header('Content-Transfer-Encoding: binary');

            readfile($path);
            exit();
        }
    }

    /**
     * 특수한 에러코드의 경우 에러데이터를 현재 클래스에서 처리하여 에러클래스로 전달한다.
     *
     * @param string $code 에러코드
     * @param ?string $message 에러메시지
     * @param ?object $details 에러와 관련된 추가정보
     * @return \ErrorData $error
     */
    public function error(string $code, ?string $message = null, ?object $details = null): ErrorData
    {
        switch ($code) {
            case 'NOT_FOUND_FILE':
                $error = ErrorHandler::data();
                $error->message = $this->getErrorText($code);
                $error->suffix = $message;
                return $error;

            default:
                return parent::error($code, $message, $details);
        }
    }
}
