<?php

namespace App\Support;

final class DemoPdf
{
    /**
     * Builds a small, structurally valid one-page PDF so seeded demo files
     * open in a real viewer and the download routes have actual bytes to
     * stream. ASCII text only — the PDF string syntax used here cannot carry
     * Bangla, and demo blobs do not need to.
     *
     * @param  array<int, string>  $lines
     */
    public static function generate(string $title, array $lines = []): string
    {
        $escape = fn (string $text): string => str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $text,
        );

        $content = 'BT /F1 18 Tf 72 770 Td ('.$escape($title).') Tj ET'."\n";
        $y = 740;

        foreach ($lines as $line) {
            $content .= 'BT /F1 11 Tf 72 '.$y.' Td ('.$escape($line).') Tj ET'."\n";
            $y -= 18;
        }

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R'
                .' /Resources << /Font << /F1 5 0 R >> >> >>',
            4 => '<< /Length '.strlen($content).' >>'."\nstream\n".$content.'endstream',
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number." 0 obj\n".$body."\nendobj\n";
        }

        $xrefPosition = strlen($pdf);

        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n".$xrefPosition."\n%%EOF";

        return $pdf;
    }
}
