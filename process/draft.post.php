<?php
/**
 * 이 파일은 아이모듈 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 파일업로드를 위한 업로드 URL 을 가져온다.
 *
 * @file /modules/attachment/process/draft.post.php
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

$errors = [];
$name = $input->get('name', $errors);
$size = $input->get('size', $errors);

if (count($errors) == 0) {
    $me->db()->lock($me->table('drafts'), 'WRITE');
    while (true) {
        $hash = '@' . sha1($name . $size) . Format::random(7);
        if (
            $me
                ->db()
                ->select()
                ->from($me->table('drafts'))
                ->where('hash', $hash)
                ->has() === false
        ) {
            $me->db()
                ->insert($me->table('drafts'), [
                    'hash' => $hash,
                    'name' => $name,
                    'path' => $hash . Format::random(4) . '.temp',
                    'size' => $size,
                    'created_at' => time(),
                    'expired_at' => time() + 60 * 60 * 24,
                ])
                ->execute();
            break;
        }
    }
    $me->db()->unlock();

    $results->success = true;
    $results->upload = $me->getProcessUrl('upload/' . $hash) . '?debug=true';
} else {
    $results->success = false;
    $resutls->errors = $errors;
}
