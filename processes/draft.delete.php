<?php
/**
 * 이 파일은 아이모듈 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 임시파일을 삭제한다.
 *
 * @file /modules/attachment/processes/draft.delete.php
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

$draft_ids = Request::get('draft_ids', true);
$draft_ids = explode(',', $draft_ids);

foreach ($draft_ids as $draft_id) {
    $draft = $me
        ->db()
        ->select()
        ->from($me->table('drafts'))
        ->where('draft_id', $draft_id)
        ->getOne();
    if ($draft === null) {
        continue;
    }

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
            ->where('draft_id', $draft_id)
            ->execute();
    }
}

$results->success = true;
