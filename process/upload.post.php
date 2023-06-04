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

$draft_id = $path;
$draft = $me
    ->db()
    ->select()
    ->from($me->table('drafts'))
    ->where('draft_id', $draft_id)
    ->getOne();

if ($draft === null || $draft->expired_at < time()) {
    $results->success = false;
    $results->message = $me->getErrorText('NOT_FOUND_DRAFT');
    return;
}

if (preg_match('/bytes ([0-9]+)\-([0-9]+)\/([0-9]+)/', $_SERVER['HTTP_CONTENT_RANGE'] ?? '', $range) == true) {
    $chunk = $input->body();
    $rangeStart = intval($range[1]);
    $rangeEnd = intval($range[2]);
    $fileSize = intval($range[3]);

    if ($fileSize != $draft->size) {
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

    $filePath = \Configs::attachment() . '/' . $draft->path;

    if ($rangeStart == 0) {
        $fp = fopen($filePath, 'w');
    } else {
        $fp = fopen($filePath, 'a');
    }

    fseek($fp, $rangeStart);
    fwrite($fp, $chunk);
    fclose($fp);

    if ($rangeEnd + 1 === $fileSize) {
        if (is_file($filePath) == false || filesize($filePath) != $fileSize) {
            $results->success = false;
            $results->status = 'FAIL';
            return;
        }

        $file = $me->getRawFile($filePath);

        $me->db()
            ->update($me->table('drafts'), [
                'hash' => $file->getHash(),
                'name' => $file->getName($draft->name, $me->getFileExtension($draft->name, $file->getMime())),
                'type' => $file->getType(),
                'mime' => $file->getMime(),
                'extension' => $me->getFileExtension($draft->name, $file->getMime()),
                'width' => $file->getWidth(),
                'height' => $file->getHeight(),
            ])
            ->where('draft_id', $draft_id)
            ->execute();

        $results->success = true;
        $results->id = $draft_id;
        $results->status = 'COMPLETE';
        $results->uploaded = $fileSize;
        $results->attachment = $me->getAttachment($draft_id)->getInfo();
    } else {
        $results->success = true;
        $results->id = $draft_id;
        $results->status = 'UPLOADING';
        $results->uploaded = filesize($filePath);
    }
} else {
    $results->success = false;
    $results->message = $me->getErrorText('INVALID_HTTP_CONTENT_RANGE');
}
