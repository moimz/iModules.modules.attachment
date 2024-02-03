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

/**
 * 관리자권한이 존재하는지 확인한다.
 */
if ($me->getAdmin()->checkPermission('attachments') == false) {
    $results->success = false;
    $results->message = $me->getErrorText('FORBIDDEN');
    return;
}

$sorters = Request::getJson('sorters');
$start = Request::getInt('start') ?? 0;
$limit = Request::getInt('limit') ?? 50;
$filters = Request::getJson('filters');
$keyword = Request::get('keyword');

$records = $me
    ->db()
    ->select()
    ->from($me->table('attachments'), 'a')
    ->join($me->table('files'), 'f', 'f.hash=a.hash', 'LEFT');

if ($filters !== null) {
    foreach ($filters as $field => $filter) {
        if ($filter->operator == '=') {
            $records->where('a.' . $field, $filter->value);
        }
    }
}

if ($keyword !== null) {
    $records->where('(a.name)', array_fill(0, 1, '%' . $keyword . '%'));
}

$total = $records->copy()->count();
if ($sorters !== null) {
    foreach ($sorters as $field => $direction) {
        $records->orderBy('a.' . $field, $direction);
    }
}
$records = $records->limit($start, $limit)->get();
foreach ($records as &$record) {
    $attachment = $me->getAttachment($record->attachment_id);
    $record = $attachment->getJson();
    $record->attachment_id = $record->id;
    $record->component_icon = $attachment->getComponent()?->getIcon() ?? null;
    $record->component_title = $attachment->getComponent()?->getTitle() ?? null;
    $record->position_type = $attachment->getPositionType();
    $record->position_id = $attachment->getPositionId();
    $record->path = str_replace(Configs::attachment(), '', $attachment->getPath());
    unset($record->id);
}

$results->success = true;
$results->records = $records;
$results->total = $total;
