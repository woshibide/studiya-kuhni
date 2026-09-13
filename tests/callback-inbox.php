<?php

require dirname(__DIR__) . '/kirby/bootstrap.php';

use Kirby\Cms\App;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Exception\NotFoundException;
use Kirby\Exception\PermissionException;
use Kirby\Filesystem\Dir;
use Studio\Callback\Inbox;
use Studio\Callback\Store;

$root = dirname(__DIR__);
$temporary = sys_get_temp_dir() . '/studio-callback-inbox-' . bin2hex(random_bytes(8));
mkdir($temporary . '/config', 0700, true);
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};
$throws = static function (callable $action, string $type, string $message) use ($assert): void {
    try { $action(); } catch (Throwable $error) { $assert($error instanceof $type, $message . ': ' . $error->getMessage()); return; }
    $assert(false, $message);
};

try {
    $kirby = new App([
        'roots' => ['index' => $root, 'config' => $temporary . '/config', 'accounts' => $temporary . '/accounts', 'sessions' => $temporary . '/sessions', 'cache' => $temporary . '/cache', 'content' => $temporary . '/content'],
        'options' => ['studio.callback.storage' => $temporary, 'debug' => true],
        'blueprints' => [
            'users/editor' => ['name' => 'editor', 'permissions' => ['access' => ['panel' => true]]],
            'users/manager' => ['name' => 'manager', 'permissions' => ['studio.callback' => ['manage' => true]]],
            'users/revoked' => ['name' => 'revoked', 'permissions' => ['access' => ['studio-callback' => false], 'studio.callback' => ['manage' => true]]],
        ],
        'users' => [
            ['id' => 'testadmin', 'email' => 'admin@example.test', 'role' => 'admin'],
            ['id' => 'testeditor', 'email' => 'editor@example.test', 'role' => 'editor'],
            ['id' => 'testmanager', 'email' => 'manager@example.test', 'role' => 'manager'],
            ['id' => 'testrevoked', 'email' => 'revoked@example.test', 'role' => 'revoked'],
        ],
    ]);
    $store = Store::for($kirby);
    $managerPermissions = $kirby->roles()->find('callback-manager')->permissions();
    $assert($managerPermissions->for('access', 'studio-callback') && $managerPermissions->for('studio.callback', 'manage'), 'Shipped manager role can use inbox');
    foreach (['site', 'system', 'users', 'languages'] as $area) $assert(!$managerPermissions->for('access', $area), 'Manager cannot enter unrelated area: ' . $area);
    $assert(!$managerPermissions->for('user', 'changeRole'), 'Manager cannot escalate own role');
    $assert(!$managerPermissions->for('users', 'changeRole') && !$managerPermissions->for('users', 'create'), 'Manager cannot administer accounts');
    $values = ['name' => '<img src=x onerror=alert(1)>', 'telephone' => '+7 999 123-45-67', 'email' => 'client@example.test'];
    $id = $store->create($values, 'Контакты', 'https://example.test/contacts');
    $assert($store->find($id)['status'] === 'new', 'New submission enters inbox');
    $assert((fileperms($temporary . '/callbacks.sqlite') & 0777) === 0600, 'Database is owner-only');
    $assert(!is_dir($temporary . '/content'), 'No callback enters public content');
    $otherConnection = Store::for($kirby);
    $assert($otherConnection->find($id)['email'] === $values['email'], 'New connection reads durable data');
    $store->update($id, ['status' => 'progress', 'notes' => 'Call tomorrow', 'revision' => 1], 'testmanager');
    $assert($store->find($id)['updated_by'] === 'testmanager', 'Editor identity recorded');
    $throws(fn () => $otherConnection->update($id, ['status' => 'done', 'notes' => 'Stale', 'revision' => 1], 'testadmin'), InvalidArgumentException::class, 'Concurrent stale update rejected');
    $assert($store->find($id)['notes'] === 'Call tomorrow', 'Conflict does not overwrite notes');
    $throws(fn () => $store->delete($id, 1), InvalidArgumentException::class, 'Concurrent stale deletion rejected');
    foreach ([['status' => 'invalid', 'notes' => ''], ['status' => 'done', 'notes' => str_repeat('x', 5001)], ['status' => ['done'], 'notes' => '']] as $input) {
        $throws(fn () => $store->update($id, $input + ['revision' => 2], 'testadmin'), InvalidArgumentException::class, 'Malformed input rejected');
    }
    $store->emailStatus($id, false);
    $assert($store->find($id)['email_status'] === 'failed', 'Email failure retained without deleting request');
    for ($i = 0; $i < 22; $i++) $store->create($values, 'Контакты', 'https://example.test/contacts');
    $first = $store->listing('new');
    $second = $store->listing('new', 2);
    $assert(count($first['records']) === 20 && count($second['records']) === 2, 'Pagination applies status filter before slicing');
    $assert(array_intersect(array_column($first['records'], 'id'), array_column($second['records'], 'id')) === [], 'Stable ordering has no overlap');
    $assert($first['counts']['progress'] === 1 && $store->listing('all')['pagination']['total'] === 23, 'Counts include all statuses');
    $throws(fn () => $store->listing("' OR 1=1"), InvalidArgumentException::class, 'Unknown filter rejected');
    $throws(fn () => $store->find('../callbacks.sqlite'), NotFoundException::class, 'Malformed IDs rejected');

    foreach ([null, 'testeditor', 'testrevoked'] as $user) {
        $kirby->impersonate($user);
        $assert(!Inbox::allowed($kirby), 'Access denied: ' . ($user ?? 'anonymous'));
        foreach ([fn () => Inbox::listing($kirby), fn () => Inbox::detail($kirby, $id), fn () => Inbox::edit($kirby, $id)] as $action) {
            $throws($action, PermissionException::class, 'Every read checks authorization');
        }
        $dialogs = Inbox::area($kirby)['dialogs'];
        $throws(fn () => $dialogs['callback.update']['submit']($id), PermissionException::class, 'Direct update denied');
        $throws(fn () => $dialogs['callback.delete']['load']($id, '2'), PermissionException::class, 'Delete dialog load denied');
        $throws(fn () => $dialogs['callback.delete']['submit']($id, '2'), PermissionException::class, 'Direct delete denied');
    }
    foreach (['testadmin', 'testmanager'] as $user) {
        $kirby->impersonate($user);
        $assert(Inbox::allowed($kirby), 'Explicitly authorized user allowed');
        $assert(Inbox::detail($kirby, $id)['props']['values']['telephone'] === $values['telephone'], 'Authorized detail loads');
        $item = Inbox::listing($kirby)['props']['items'][0];
        $assert(str_contains($item['text'], '&lt;img'), 'Native HTML-capable list receives escaped user text');
        $dialogs = Inbox::area($kirby)['dialogs'];
        $throws(fn () => $dialogs['callback.update']['submit']($id), PermissionException::class, 'Missing CSRF rejects authorized update');
        $throws(fn () => $dialogs['callback.delete']['submit']($id, '2'), PermissionException::class, 'Missing CSRF rejects authorized deletion');
    }
    $store->delete($id, 2);
    $throws(fn () => $store->find($id), NotFoundException::class, 'Deletion removes personal data');
    $throws(fn () => new Store($root, $root), RuntimeException::class, 'Public root cannot store sensitive records');
    $throws(fn () => new Store($root . '/site', $root), RuntimeException::class, 'Public subdirectories cannot store sensitive records');
    symlink($root . '/site', $temporary . '/public-link');
    $throws(fn () => new Store($temporary . '/public-link', $root), RuntimeException::class, 'Symlink cannot bypass public-root boundary');
    unlink($temporary . '/public-link');
    $throws(fn () => new Store('', $root), RuntimeException::class, 'Missing storage fails closed');
} finally {
    if (isset($kirby)) $kirby->session()->destroy();
    Dir::remove($temporary);
}

echo "Callback inbox: {$checks} persistence, authorization, CSRF, conflict and privacy checks passed.\n";
