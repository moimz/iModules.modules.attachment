<?php
/**
 * 이 파일은 아이모듈 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 파일업로드를 위한 업로드 URL 을 가져온다.
 *
 * @file /modules/attachment/processes/draft.post.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 1. 26.
 *
 * @var \modules\attachment\Attachment $me
 */
if (defined('__IM_PROCESS__') == false) {
    exit();
}

$errors = [];
$name = Input::get('name', $errors);
$size = Input::get('size', $errors);

if (count($errors) == 0) {
    $draft_id = UUID::v1($name . $size);
    $me->db()
        ->insert($me->table('drafts'), [
            'draft_id' => $draft_id,
            'name' => $name,
            'path' => $me->getDraftDir() . '/' . $draft_id . '-' . Format::random(4),
            'extension' => $me->getFileExtension($name),
            'size' => $size,
            'created_at' => time(),
            'expired_at' => time() + 60 * 60 * 24,
        ])
        ->execute();

    $results->success = true;
    $results->upload = $me->getProcessUrl('upload/' . $draft_id) . '?debug=true';
} else {
    $results->success = false;
    $resutls->errors = $errors;
}
