<?php

namespace App\Http\Controllers;

use App\Exports\PurchaseOrdersExport;
use App\Exports\ReportExport;
use App\Exports\SupplierPerformanceExport;
use App\Models\AuditLog;
use App\Services\PdfService;
use Illuminate\Http\Request;

class ProcurementExportController extends Controller
{
    public function download(Request $request, string $report, string $format)
    {
        abort_unless(in_array($format, ['csv', 'pdf'], true), 404);

        $export = match ($report) {
            'purchase-orders' => new PurchaseOrdersExport($request),
            'supplier-performance' => new SupplierPerformanceExport(),
            default => abort(404),
        };

        AuditLog::record('exported', null, null, null, 'Exported ' . $export->title() . ' as ' . strtoupper($format));

        return $format === 'csv' ? $this->csv($export) : $this->pdf($export);
    }

    private function csv(ReportExport $export)
    {
        return response()->streamDownload(function () use ($export) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $export->headings(), ',', '"', '');

            foreach ($export->rows() as $row) {
                fputcsv($out, $row, ',', '"', '');
            }

            fclose($out);
        }, $export->filename() . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function pdf(ReportExport $export)
    {
        abort_unless(PdfService::available(), 503, 'PDF export requires the barryvdh/laravel-dompdf package.');

        return PdfService::download('procurement.exports.table', [
            'title' => $export->title(),
            'headings' => $export->headings(),
            'rows' => iterator_to_array($export->rows(), false),
            'generatedAt' => now()->format('M d, Y g:i A'),
        ], $export->filename() . '.pdf', 'landscape');
    }
}
