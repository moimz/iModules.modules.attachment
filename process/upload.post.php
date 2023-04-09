<?php
/**
 * 이 파일은 아이모듈 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 파일을 업로드 받는다.
 *
 * @file /modules/attachment/process/upload.post.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 4. 10.
 *
 * @var \modules\attachment\Attachment $me
 * @var Input $input
 */
if (defined('__IM_PROCESS__') == false) {
    exit();
}

$hash = $path;
$file = $me
    ->db()
    ->select()
    ->from($me->table('drafts'))
    ->where('hash', $hash)
    ->getOne();

if ($file === null) {
    $results->success = false;
    $results->hash = $hash;
    $results->message = $me->getErrorText('NOT_FOUND_DRAFT');
    return;
}

if (preg_match('/bytes ([0-9]+)\-([0-9]+)\/([0-9]+)/', $_SERVER['HTTP_CONTENT_RANGE'] ?? '', $range) == true) {
    $chunk = $input->body();
    $rangeStart = intval($range[1]);
    $rangeEnd = intval($range[2]);
    $fileSize = intval($range[3]);

    if ($fileSize != $file->size) {
        $results->success = false;
        $results->message = $me->getErrorText('INVALID_FILE_SIZE');
        return;
    }

    if ($rangeEnd - $rangeStart + 1 != strlen($chunk)) {
        $results->success = false;
        $results->message = $me->getErrorText(
            'INVALID_CHUNK_SIZE' . ($rangeEnd - $rangeStart + 1) . '/' . strlen($chunk)
        );
        return;
    }

    if ($rangeStart == 0) {
        $fp = fopen($me->getTempPath($file->path), 'w');
    } else {
        $fp = fopen($me->getTempPath($file->path), 'a');
    }

    fseek($fp, $rangeStart);
    fwrite($fp, $chunk);
    fclose($fp);

    if ($rangeEnd + 1 === $fileSize) {
        // @todo 업로드 완료처리
        $results->success = true;
        $results->status = 'COMPLETE';
        $results->uploaded = $file->size;
    } else {
        $results->success = true;
        $results->status = 'UPLOADING';
        $results->uploaded = filesize($me->getTempPath($file->path));
    }
} else {
    $results->success = false;
    $results->message = $me->getErrorText('INVALID_HTTP_CONTENT_RANGE');
}
