<?php
/**
 * 이 파일은 아이모듈 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 임시파일 정보를 가져온다.
 *
 * @file /modules/attachment/processes/drafts.get.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 10.
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
    ->from($me->table('drafts'));

if ($filters !== null) {
    foreach ($filters as $field => $filter) {
        if ($filter->operator == '=') {
            $records->where($field, $filter->value);
        }
    }
}

if ($keyword !== null) {
    $records->where('(name like ?)', array_fill(0, 1, '%' . $keyword . '%'));
}

$total = $records->copy()->count();
if ($sorters !== null) {
    foreach ($sorters as $field => $direction) {
        $records->orderBy($field, $direction);
    }
}
$records = $records->limit($start, $limit)->get();
foreach ($records as &$record) {
    $attachment = $me->getAttachment($record->draft_id);
    $record = $attachment->getJson();
    $record->draft_id = $record->id;
    $record->path = str_replace(Configs::attachment(), '', $attachment->getPath());
    $record->realsize = filesize($attachment->getPath());
    unset($record->id);
}

$results->success = true;
$results->records = $records;
$results->total = $total;
