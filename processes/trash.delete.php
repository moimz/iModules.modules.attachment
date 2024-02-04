<?php
/**
 * 이 파일은 아이모듈 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 쓰레기파일을 삭제한다.
 *
 * @file /modules/attachment/processes/trash.delete.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 1. 26.
 *
 * @var \modules\attachment\Attachment $me
 */
if (defined('__IM_PROCESS__') == false) {
    exit();
}

/**
 * 관리자권한이 존재하는지 확인한다.
 */
if ($me->getAdmin()->checkPermission('attachments') == false) {
    $results->success = false;
    $results->message = $me->getErrorText('FORBIDDEN');
    return;
}

$paths = Request::get('paths', true);
$paths = explode(',', $paths);

foreach ($paths as $path) {
    $trash = $me
        ->db()
        ->select()
        ->from($me->table('trashes'))
        ->where('path', $path)
        ->getOne();
    if ($trash === null) {
        continue;
    }

    $removed =
        File::remove(Configs::attachment() . '/' . $path) &&
        File::remove(Configs::attachment() . '/' . $path . '.view') &&
        File::remove(Configs::attachment() . '/' . $path . '.thumbnail');

    if ($removed == true) {
        $me->db()
            ->delete($me->table('trashes'))
            ->where('path', $path)
            ->execute();
    }
}

$results->success = true;
