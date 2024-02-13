<?php
/**
 * 이 파일은 아이모듈 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 보관기간이 만료된 모든 임시파일을 삭제한다.
 *
 * @file /modules/attachment/processes/drafts.delete.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 14.
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

$drafts = $me
    ->db()
    ->select(['draft_id', 'path'])
    ->from($me->table('drafts'))
    ->where('expired_at', time(), '<')
    ->get();

$progress = new Progress(count($drafts));
foreach ($drafts as $index => $draft) {
    if (strpos($draft->path, 'drafts') === 0) {
        $removed =
            File::remove(Configs::attachment() . '/' . $draft->path) &&
            File::remove(Configs::attachment() . '/' . $draft->path . '.view') &&
            File::remove(Configs::attachment() . '/' . $draft->path . '.thumbnail');
    } else {
        $removed = true;
    }

    if ($removed == true) {
        $me->db()
            ->delete($me->table('drafts'))
            ->where('draft_id', $draft->draft_id)
            ->execute();
    }

    $progress->progress($index + 1);
}

$progress->end(true);
