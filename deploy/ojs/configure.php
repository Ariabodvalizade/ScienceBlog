<?php

/**
 * Render /var/www/html/config.inc.php from the stock config.TEMPLATE.inc.php
 * shipped with the OJS image, applying values from environment variables.
 *
 * Only keys listed in $settings are touched; everything else keeps the OJS
 * default for the installed version, so upgrades pick up new keys.
 * Unset/empty environment variables leave the template value unchanged.
 */

$template = '/var/www/html/config.TEMPLATE.inc.php';
$target = getenv('PKP_CONF_PATH') ?: '/var/www/html/config.inc.php';

function env(string $name, ?string $default = null): ?string
{
    $value = getenv($name);
    return ($value === false || $value === '') ? $default : $value;
}

function onOff(?string $value): ?string
{
    if ($value === null) {
        return null;
    }
    return in_array(strtolower($value), ['1', 'on', 'true', 'yes'], true) ? 'On' : 'Off';
}

function quoted(?string $value): ?string
{
    if ($value === null) {
        return null;
    }
    if (str_contains($value, '"')) {
        fwrite(STDERR, "[journal-configure] Values must not contain double quotes.\n");
        exit(1);
    }
    return '"' . $value . '"';
}

$domain = env('JOURNAL_DOMAIN', 'localhost');
$baseUrl = env('OJS_BASE_URL', 'https://' . $domain);
$smtp = env('SMTP_HOST') !== null;

// [section => [key => value|null]]
$settings = [
    'general' => [
        'installed' => onOff(env('OJS_INSTALLED', 'Off')),
        'app_key' => quoted(env('OJS_APP_KEY')),
        'base_url' => quoted($baseUrl),
        'restful_urls' => 'On',
        'allowed_hosts' => env('OJS_ALLOWED_HOSTS') ? "'" . env('OJS_ALLOWED_HOSTS') . "'" : null,
        'trust_x_forwarded_for' => onOff(env('OJS_TRUST_X_FORWARDED_FOR', 'On')),
        'time_zone' => quoted(env('OJS_TIME_ZONE', 'UTC')),
        'enable_beacon' => 'Off',
    ],
    'database' => [
        'driver' => 'mysqli',
        'host' => env('OJS_DB_HOST', 'db'),
        'username' => quoted(env('OJS_DB_USER', 'ojs')),
        'password' => quoted(env('OJS_DB_PASSWORD')),
        'name' => quoted(env('OJS_DB_NAME', 'ojs')),
    ],
    'files' => [
        'files_dir' => '/var/www/files',
        'public_files_dir' => 'public',
    ],
    'security' => [
        'force_ssl' => onOff(env('OJS_FORCE_SSL', 'On')),
        'salt' => quoted(env('OJS_SALT')),
        'api_key_secret' => quoted(env('OJS_API_KEY_SECRET')),
    ],
    'email' => [
        'default' => $smtp ? 'smtp' : env('OJS_MAIL_DEFAULT', 'log'),
        'smtp' => $smtp ? 'On' : null,
        'smtp_server' => env('SMTP_HOST'),
        'smtp_port' => env('SMTP_PORT'),
        'smtp_auth' => env('SMTP_AUTH'),
        'smtp_username' => quoted(env('SMTP_USER')),
        'smtp_password' => quoted(env('SMTP_PASSWORD')),
        'allow_envelope_sender' => env('MAIL_ENVELOPE_SENDER') ? 'On' : null,
        'default_envelope_sender' => env('MAIL_ENVELOPE_SENDER'),
        'force_default_envelope_sender' => env('MAIL_ENVELOPE_SENDER') ? 'On' : null,
        'force_dmarc_compliant_from' => env('MAIL_ENVELOPE_SENDER') ? 'On' : null,
    ],
    'oai' => [
        'oai' => 'On',
        'repository_id' => env('OJS_OAI_REPOSITORY_ID', $domain),
    ],
    'captcha' => [
        'altcha' => env('OJS_ALTCHA_HMACKEY') ? 'on' : 'off',
        'altcha_hmackey' => env('OJS_ALTCHA_HMACKEY') ? "'" . env('OJS_ALTCHA_HMACKEY') . "'" : null,
        'altcha_on_register' => 'on',
        'altcha_on_login' => 'on',
        'altcha_on_lost_password' => 'on',
    ],
    'debug' => [
        'display_errors' => onOff(env('OJS_DISPLAY_ERRORS', 'Off')),
    ],
    'queues' => [
        // Background work runs in the dedicated worker/scheduler services.
        'job_runner' => onOff(env('OJS_WEB_JOB_RUNNER', 'Off')),
    ],
    'schedule' => [
        'task_runner' => onOff(env('OJS_WEB_TASK_RUNNER', 'Off')),
    ],
];

$lines = file($template, FILE_IGNORE_NEW_LINES);
if ($lines === false) {
    fwrite(STDERR, "[journal-configure] Cannot read $template\n");
    exit(1);
}

// Two passes: real "key = value" lines win; otherwise the first commented-out
// "; key = value" example is replaced; otherwise the key is appended.
$seen = [];
$sectionEnd = [];
foreach ([false, true] as $commented) {
    $section = null;
    foreach ($lines as $i => $line) {
        if (preg_match('/^\[([a-z_]+)\]\s*$/', $line, $m)) {
            $section = $m[1];
            continue;
        }
        if ($section === null || !isset($settings[$section])) {
            continue;
        }
        $sectionEnd[$section] = $i;
        $pattern = $commented ? '/^;\s*([a-z_]+)\s*=/' : '/^([a-z_]+)\s*=/';
        if (!preg_match($pattern, $line, $m) || !array_key_exists($m[1], $settings[$section])) {
            continue;
        }
        $key = $m[1];
        $value = $settings[$section][$key];
        if ($value === null || isset($seen[$section][$key])) {
            continue;
        }
        $lines[$i] = "$key = $value";
        $seen[$section][$key] = true;
    }
}

// Append keys that do not exist in the template (commented or not).
$inserts = [];
foreach ($settings as $sec => $pairs) {
    foreach ($pairs as $key => $value) {
        if ($value !== null && !isset($seen[$sec][$key]) && isset($sectionEnd[$sec])) {
            $inserts[$sectionEnd[$sec]][] = "$key = $value";
        }
    }
}
krsort($inserts);
foreach ($inserts as $at => $newLines) {
    array_splice($lines, $at + 1, 0, $newLines);
}

if (file_put_contents($target, implode("\n", $lines) . "\n") === false) {
    fwrite(STDERR, "[journal-configure] Cannot write $target\n");
    exit(1);
}
chmod($target, 0640);
echo "[journal-configure] Wrote $target (installed = {$settings['general']['installed']})\n";
