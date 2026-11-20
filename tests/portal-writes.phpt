<?php

declare(strict_types=1);

use NasTests\PortalFixture;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$portal = new PortalFixture;
$portal->signIn();
$db = $portal->database();

$portal->request('/slozky/create', 303, [
    'name' => 'testfolder',
    'size' => '20M',
    'comment' => 'Fixture',
    'id' => '',
    '_do' => 'slozkaEditForm-submit',
]);
$folder = '/1234/testfolder';
$folderId = $db->query("SELECT id FROM folder WHERE name = '/1234/testfolder'")->fetchColumn();
Assert::type('int', $folderId);
Assert::same(20 * 1024 * 1024, $portal->storageFolders()[$folder]['quota']);
$portal->request("/slozky/edit/$folderId", 303, [
    'name' => $folder,
    'size' => '40M',
    'comment' => 'Changed',
    'id' => $folderId,
    '_do' => 'slozkaEditForm-submit',
]);
Assert::same('Changed', $db->query('SELECT comment FROM folder WHERE id = ' . $folderId)->fetchColumn());
Assert::same(40 * 1024 * 1024, $portal->storageFolders()[$folder]['quota']);
$portal->request("/slozky/delete-final/$folderId", 302);
Assert::false($db->query('SELECT id FROM folder WHERE id = ' . $folderId)->fetchColumn());
Assert::false(isset($portal->storageFolders()[$folder]));

foreach ([
    'ftp' => [1, 'fixture-ftp-updated'],
    'webdav' => [2, 'fixture-dav-updated'],
    'rsync' => ['', 'fixture-rsync'],
] as $type => [$id, $username]) {
    $portal->request("/$type/edit/2", 303, [
        'export' => '1',
        'username' => $username,
        'password' => 'fixture-updated-password',
        'folder_id' => '2',
        'id' => $id,
        '_do' => "{$type}EditForm-submit",
    ]);
}
$portal->request('/nfs/edit/2', 303, [
    'export' => '1',
    'ips' => '192.0.2.8',
    'folder_id' => '2',
    'id' => '',
    '_do' => 'nfsEditForm-submit',
]);
$portal->request('/nextcloud/create/2', 303, [
    'username' => 'testnextcloud',
    'password' => 'fixture-nextcloud-password',
    'folder_id' => '2',
    'id' => '',
    '_do' => 'nextcloudCreateForm-submit',
]);
Assert::contains('nextcloudEditForm-submit', $portal->request('/nextcloud/edit/2', 200));
$nextcloudId = $db->query('SELECT id FROM share WHERE shareType_id = 6')->fetchColumn();
$portal->request('/nextcloud/edit/2', 303, [
    'password' => 'fixture-updated-password',
    'id' => $nextcloudId,
    '_do' => 'nextcloudEditForm-submit',
]);
Assert::true(password_verify('fixture-updated-password', $db->query('SELECT var2 FROM share WHERE shareType_id = 6')->fetchColumn()));

Assert::same(
    ['fixture-ftp-updated', 'fixture-dav-updated', '192.0.2.8', 'fixture-rsync', 'testnextcloud'],
    $db->query('SELECT var FROM share WHERE folder_id = 2 ORDER BY shareType_id')->fetchAll(PDO::FETCH_COLUMN),
);
Assert::same([
    '../app/scripts/exportrsync.sh',
    '../app/scripts/testnfs.sh',
    '../app/scripts/exportnfs.sh',
], $portal->commands());
