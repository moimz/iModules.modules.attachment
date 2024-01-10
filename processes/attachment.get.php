<?php
/**
 * 이 파일은 아이모듈 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일 정보를 가져온다.
 *
 * @file /modules/attachment/processes/attachment.get.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 4. 10.
 *
 * @var \modules\attachment\Attachment $me
 */
if (defined('__IM_PROCESS__') == false) {
    exit();
}

$id = Request::get('id', true);
$attachment = $me->getAttachment($id);

$results->success = true;
$results->attachment = $attachment->getInfo();
