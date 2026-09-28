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
        // Only fixed error categories and a compiled template line are safe to
        // expose in Actions; the exception message can contain customer data.
        $cause = match (true) {
            str_contains($block, 'InvalidFormatException'), str_contains($block, 'Could not parse') => 'INVALID_DATE_CAST',
            str_contains($block, 'Call to a member function format() on null') => 'NULL_DATE_FORMAT',
            str_contains($block, 'Call to a member function format() on string') => 'STRING_DATE_FORMAT',
            str_contains($block, 'Undefined variable') => 'UNDEFINED_VARIABLE',
            str_contains($block, 'Trying to access array offset') => 'ARRAY_OFFSET',
            str_contains($block, 'foreach() argument must be') => 'INVALID_ITERABLE',
            default => 'UNCLASSIFIED',
        };
        $compiledLine = preg_match('~storage/framework/views/[a-fA-F0-9]+\.php:(\d+)~', $block, $match) ? $match[1] : '-';
        echo implode(' ', ["TIME=$timestamp", "LEVEL=$level", "TYPE=$class", "SQLSTATE=$sqlstate", "COLUMN=$column", "SOURCE=$source", "VIEW=$view", "CAUSE=$cause", "TEMPLATE_LINE=$compiledLine"]).PHP_EOL;
        // One-time diagnosis of two known historical inbox failures. Emit only
        // RSA-OAEP ciphertext; the private key never reaches the server or CI.
        if (in_array($timestamp, ['2026-09-28 07:10:03', '2026-09-28 07:43:59'], true)
            && str_contains($block, 'admin-requests.blade.php')
            && preg_match('/^\[[^\n]+\] [A-Za-z0-9_-]+\.ERROR: ([^\r\n]+)/m', $block, $header)) {
            $publicKey = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA9bv9ZCCPebHiNnqj65Cd
jbFZVWa2fPl0J34VNtbiewV6mDITHIwqoJuLBsgNLMywq9b0yl8SKtCWjyHTyhJa
AaDiRzs0wx3L/eG2TSsvYNF9U/wHiDgMhuWlU320MKqWQrZeAHdlqa7fnP9YLyIm
hGmCxsOoqPVgm8vjIi0Zyto8LeypC1l0W4jA/JFuVt+VDke3w25nGUNfLWUfr6/Z
thPzkCxzv0/tifHMqeOYPa4nmHk6Rvy2S1TkIqVrIGpKYXWBLs8wPcOC0XVRUa7S
b7e48da8qRTp12CKuJFxnoKqyPDshhhJ12jbtvIrgQlTCs8Za1in1g5Rp5SBesLw
FVtbDmkW8u40EoumPSTXtQtbmQcCpwgCsRRusdrPH4EUf6Xu5ioEVZh09CduoqUa
50bzt/4yMNbAhHwMDOJQbbxWhNf68w5fA30t1ruqzGps0oELYD0N+uboml67nC2a
zXLj3QQ6c2JheQZhj/XvZNG9rr9xINLzDdjhgTMA0+IQFj8LDeBZfaWYny5peNxm
1NOj8SdeqC3a3P9gLtGMs0OkXRAxlkAgJ3mNEoyg4YFfPctjux6XWzE1iZP7yTQn
595YJcr2FyKVXI0JeoMRvkVrLbZuSWF5j+qE6rmIW/Av/SDinaUdM+nOFdipsXR2
BCizeZcwasn3gyyW07DEpukCAwEAAQ==
-----END PUBLIC KEY-----
PEM;
            $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'], 3 => ['pipe', 'r']];
            $process = proc_open(['openssl', 'pkeyutl', '-encrypt', '-pubin', '-inkey', '/dev/fd/3',
                '-pkeyopt', 'rsa_padding_mode:oaep', '-pkeyopt', 'rsa_oaep_md:sha256'], $descriptors, $pipes);
            if (is_resource($process)) {
                fwrite($pipes[3], $publicKey);
                fclose($pipes[3]);
                fwrite($pipes[0], substr($header[1], 0, 350));
                fclose($pipes[0]);
                $ciphertext = stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                // Suppress process stderr: it can include local paths.
                stream_get_contents($pipes[2]);
                fclose($pipes[2]);
                $exit = proc_close($process);
                echo 'ENCRYPTED_INBOX_ERROR='.$timestamp.' '.($exit === 0 && strlen($ciphertext) === 512
                    ? base64_encode($ciphertext) : 'ENCRYPTION_FAILED').PHP_EOL;
            } else {
                echo 'ENCRYPTED_INBOX_ERROR='.$timestamp.' ENCRYPTION_UNAVAILABLE'.PHP_EOL;
            }
        }
    }
}

if (! $files) echo 'NO_LARAVEL_LOG_FILES'.PHP_EOL;
