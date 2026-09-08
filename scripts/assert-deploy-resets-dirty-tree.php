<?php

/**
 * The VPS clone mutates tracked files (Laravel storage). Deploy must
 * `git reset --hard origin/main`, not `git pull --ff-only`.
 */
$root = dirname(__DIR__);
$file = $root . '/.github/workflows/deploy.yml';
$src = (string) file_get_contents($file);
$failed = false;

function fail(string $message) : void
{
    global $failed;
    $failed = true;
    fwrite(STDERR, $message . PHP_EOL);
}

if (!str_contains($src, 'git -C "$DEPLOY_PATH" reset --hard origin/main')) {
    fail('deploy.yml deve reset --hard origin/main no clone de código');
}
if (!str_contains($src, 'git -C "$ENV_REPO_PATH" reset --hard origin/main')) {
    fail('deploy.yml deve reset --hard origin/main no clone de config');
}
if (preg_match('/git -C "\$DEPLOY_PATH" pull --ff-only/', $src) === 1) {
    fail('deploy.yml não deve usar pull --ff-only no clone de código (árvore suja na VPS)');
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "OK: deploy descarta working tree sujo e alinha origin/main.\n");
exit(0);
