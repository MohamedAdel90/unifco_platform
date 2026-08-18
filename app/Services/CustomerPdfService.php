<?php

namespace App\Services;

class CustomerPdfService
{
    public function make(string $title, array $lines): string
    {
        $safe = static fn (string $value): string => preg_replace('/[^\x20-\x7E]/', '?', $value) ?? '';
        $text = ['BT','/F1 16 Tf','50 790 Td','('.$this->escape($safe($title)).') Tj','0 -28 Td','/F1 10 Tf'];
        foreach ($lines as $line) {
            $text[] = '('.$this->escape($safe((string)$line)).') Tj';
            $text[] = '0 -16 Td';
        }
        $text[] = 'ET';
        $stream = implode("\n", $text);

        $objects = [
            '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj',
            '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj',
            '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >> endobj',
            "4 0 obj << /Length ".strlen($stream)." >> stream\n{$stream}\nendstream endobj",
            '5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object."\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($i=1;$i<=5;$i++) $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        $pdf .= "trailer << /Size 6 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
        return $pdf;
    }

    private function escape(string $value): string
    {
        return str_replace(['\\','(',')'], ['\\\\','\\(','\\)'], $value);
    }
}
