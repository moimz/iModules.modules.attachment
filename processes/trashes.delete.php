<?php
/**
 * 이 파일은 아이모듈 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 검색된 모든 쓰레기파일을 삭제한다.
 *
 * @file /modules/attachment/processes/trashes.delete.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 4.
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

$trashes = $me
    ->db()
    ->select(['path'])
    ->from($me->table('trashes'))
    ->get('path');

$progress = new Progress(count($trashes));
foreach ($trashes as $index => $path) {
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

    $progress->progress($index + 1);
}

$progress->end(true);
