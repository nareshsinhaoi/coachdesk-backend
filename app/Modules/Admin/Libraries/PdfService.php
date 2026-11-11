<?php

namespace App\Modules\Admin\Libraries;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService
{
    /**
     * Render HTML into a PDF and stream it as a download.
     *
     * @param string $html     Full HTML document (with inline CSS)
     * @param string $filename Output filename (without .pdf)
     * @param string $mode     'download' | 'inline'
     */
    public function render(string $html, string $filename, string $mode = 'download'): void
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('dpi', 150);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $dompdf->stream($filename . '.pdf', ['Attachment' => $mode === 'download']);
        exit;
    }
}