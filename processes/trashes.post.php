<?php
/**
 * 이 파일은 아이모듈 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 데이터베이스에 연결되지 않은 쓰레기파일을 검색한다.
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

$folders = array_merge(range(0, 9), range('a', 'f'));
$progress = new Progress(count($folders) * count($folders));

$index = 0;
foreach ($folders as $depth1) {
    foreach ($folders as $depth2) {
        $folder = '/files/' . $depth1 . '/' . $depth2;

        $files = File::getDirectoryItems(Configs::attachment() . $folder, 'file', true);
        $progress->progress(++$index, ['folder' => $folder, 'files' => count($files)]);

        foreach ($files as $file) {
            $path = str_replace(Configs::attachment() . '/', '', $file);

            if (preg_match('/\.(view|thumbnail)$/', $file) == true) {
                $origin = preg_replace('/\.(view|thumbnail)$/', '', $file);
                if (is_file($origin) == false) {
                    $me->db()
                        ->replace($me->table('trashes'), [
                            'path' => $path,
                            'size' => filesize($file),
                            'created_at' => filemtime($file),
                        ])
                        ->execute();
                }
            } else {
                if (
                    $me
                        ->db()
                        ->select()
                        ->from($me->table('files'))
                        ->where('path', $path)
                        ->has() == false
                ) {
                    $me->db()
                        ->replace($me->table('trashes'), [
                            'path' => $path,
                            'size' => filesize($file),
                            'created_at' => filemtime($file),
                        ])
                        ->execute();
                }
            }
        }
    }
}

$progress->end(true);
