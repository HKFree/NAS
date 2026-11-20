<?php

declare(strict_types=1);

use NasTests\PortalFixture;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$portal = new PortalFixture;
Assert::contains('Celková kapacita', $portal->request('/', 200));
Assert::contains('webdav_win10.pdf', $portal->request('/navody/', 200));
Assert::contains('Page Not Found', $portal->request('/no-such-presenter', 404));
$portal->request('/slozky/', 302);
Assert::contains('signInForm-submit', $portal->request('/sign/in', 200));
Assert::contains('Neplatné heslo.', $portal->request('/sign/in', 200, [
    'username' => '1234',
    'password' => 'wrong',
    '_do' => 'signInForm-submit',
]));
$portal->signIn();

foreach ([
    '/slozky/' => '/1234/demo',
    '/stats/' => 'Statistiky',
    '/slozky/edit/2' => 'slozkaEditForm-submit',
    '/ftp/edit/2' => 'ftpEditForm-submit',
    '/webdav/edit/2' => 'webdavEditForm-submit',
    '/nfs/edit/2' => 'nfsEditForm-submit',
    '/rsync/edit/2' => 'rsyncEditForm-submit',
    '/nextcloud/create/2' => 'nextcloudCreateForm-submit',
] as $path => $content) {
    Assert::contains($content, $portal->request($path, 200));
}

$portal->request('/sign/out', 302);
$portal->request('/slozky/', 302);

$portal->request('/api/authftp', 403);
Assert::same("auth_ok:1\nuid:65000\ngid:65000\ndir:/mnt/nas/1234/demo/\nend\n", $portal->request('/api/authftp', 200, [
    'username' => 'fixture-user',
    'password' => 'fixture-share-password',
]));
Assert::same("auth_ok:0\nend\n", $portal->request('/api/authftp', 200, [
    'username' => 'fixture-user',
    'password' => 'wrong',
]));
Assert::same(
    ['status' => 1, 'username' => 'fixture-webdav', 'folder' => '/mnt/nas/1234/demo'],
    json_decode($portal->request('/api/authwebdav', 200, [
        'username' => 'fixture-webdav',
        'password' => 'fixture-dav-password',
    ]), true, flags: JSON_THROW_ON_ERROR),
);
Assert::same(
    ['status' => 1, 'folder' => '/mnt/nas/1234/demo'],
    json_decode($portal->request('/api/folderwebdav', 200, [
        'username' => 'fixture-webdav',
    ]), true, flags: JSON_THROW_ON_ERROR),
);
