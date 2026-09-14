<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
putenv('STUDIO_SHARED_ROOT=/srv/kuhni/design/shared');
putenv('STUDIO_ENV=staging');
$kirby = require '/srv/kuhni/design/current/bootstrap.php';
if ($kirby->users()->count() !== 0) {
    fwrite(STDERR, "An account already exists. Use the Panel to manage users.\n");
    exit(1);
}
fwrite(STDOUT, 'Administrator email: ');
$email = trim(fgets(STDIN));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) exit(1);
fwrite(STDOUT, 'Password (at least 16 characters): ');
$tty = trim((string)shell_exec('stty -g'));
if ($tty === '') exit(1);
register_shutdown_function(static fn () => system('stty ' . escapeshellarg($tty)));
system('stty -echo');
$password = rtrim(fgets(STDIN), "\r\n");
system('stty ' . escapeshellarg($tty));
fwrite(STDOUT, "\n");
if (strlen($password) < 16) exit(1);
$kirby->impersonate('kirby');
$kirby->users()->create(['email' => $email, 'password' => $password, 'role' => 'admin', 'language' => 'ru']);
fwrite(STDOUT, "Administrator created.\n");
