<?php

/**
 * Static deploy check — does not use Laravel routes (avoids 404 from route cache).
 * Open: https://app.kuhu.org.in/ping.php
 */
header('Content-Type: application/json; charset=utf-8');

$root = dirname(__DIR__);
$blade = $root . '/resources/views/dashboard.blade.php';
$content = is_readable($blade) ? file_get_contents($blade) : '';

echo json_encode([
    'ping' => 'ok',
    'laravel_root' => $root,
    'dashboard_blade_exists' => is_readable($blade),
    'tabs_v2_marker' => str_contains($content, 'dashboard-tabs-v2'),
    'workspace_header_in_view' => str_contains($content, 'workspace-header'),
    'blade_mtime' => is_readable($blade) ? date('c', filemtime($blade)) : null,
    'build_status_file' => is_readable($root . '/public/build-status.json'),
], JSON_PRETTY_PRINT);
