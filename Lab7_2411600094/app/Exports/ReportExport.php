<?php

namespace App\Exports;

interface ReportExport
{
    public function title(): string;

    public function filename(): string;

    /** @return array<int, string> */
    public function headings(): array;

    /** @return iterable<int, array<int, string|int|float|null>> */
    public function rows(): iterable;
}
