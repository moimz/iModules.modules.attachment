<?php
/**
 * 이 파일은 아이모듈 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일을 삭제한다.
 *
 * @file /modules/attachment/processes/attachments.delete.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 5. 17.
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

$attachment_ids = Request::get('attachment_ids', true);
$attachment_ids = explode(',', $attachment_ids);

foreach ($attachment_ids as $attachment_id) {
    $attachment = $me
        ->db()
        ->select()
        ->from($me->table('attachments'))
        ->where('attachment_id', $attachment_id)
        ->getOne();
    if ($attachment === null) {
        continue;
    }

    $me->deleteFile($attachment_id);
}

$results->success = true;
