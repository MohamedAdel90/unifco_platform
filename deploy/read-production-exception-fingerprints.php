<?php

// Read only. The public Actions log receives constrained diagnostics, never
// Laravel exception messages, stack arguments, request bodies or DB values.
$files = glob('storage/logs/laravel*.log') ?: [];
usort($files, fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));
$files = array_slice($files, 0, 3);

foreach ($files as $file) {
    $handle = fopen($file, 'rb');
    if (! $handle) continue;
    $length = filesize($file);
    if ($length > 4 * 1024 * 1024) fseek($handle, -4 * 1024 * 1024, SEEK_END);
    $contents = stream_get_contents($handle);
    fclose($handle);
    if (! is_string($contents)) continue;

    preg_match_all('/^\[(\d{4}-\d\d-\d\d \d\d:\d\d:\d\d)\] [A-Za-z0-9_-]+\.(ERROR|CRITICAL): /m', $contents, $headers, PREG_OFFSET_CAPTURE);
    $count = count($headers[0]);
    echo 'FILE_DATE='.date('Y-m-d', filemtime($file)).' ERROR_COUNT_IN_TAIL='.$count.PHP_EOL;
    for ($i = max(0, $count - 16); $i < $count; $i++) {
        $start = $headers[0][$i][1];
        $end = $i + 1 < $count ? $headers[0][$i + 1][1] : strlen($contents);
        $block = substr($contents, $start, min(20000, $end - $start));
        $timestamp = $headers[1][$i][0];
        $level = $headers[2][$i][0];
        $class = preg_match('/\b([A-Za-z][A-Za-z0-9_]*(?:Exception|Error))\b/', $block, $match) ? $match[1] : 'unknown';
        $sqlstate = preg_match('/SQLSTATE\[([A-Z0-9]{5})\]/', $block, $match) ? $match[1] : '-';
        $column = preg_match('/Unknown column [\x27\x60]([A-Za-z0-9_.]+)[\x27\x60]/', $block, $match) ? $match[1] : '-';
        $source = preg_match('~(?:/var/www/unifco_platform/)?(app/[A-Za-z0-9_/.-]+\.php)(?:\(|:)(\d+)~', $block, $match)
            ? $match[1].':'.$match[2] : '-';
        $view = preg_match('~(resources/views/[A-Za-z0-9_/.-]+\.blade\.php)~', $block, $match) ? $match[1] : '-';
        echo implode(' ', ["TIME=$timestamp", "LEVEL=$level", "TYPE=$class", "SQLSTATE=$sqlstate", "COLUMN=$column", "SOURCE=$source", "VIEW=$view"]).PHP_EOL;
    }
}

if (! $files) echo 'NO_LARAVEL_LOG_FILES'.PHP_EOL;
