<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SubmissionExport extends DefaultValueBinder implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithCustomValueBinder, WithEvents
{
    public function __construct(
        private readonly array $headings,
        private readonly array $rows,
        private readonly string $orientation = 'landscape',
    ) {}

    public function headings(): array { return $this->headings; }

    public function array(): array { return $this->rows; }

    public function bindValue(Cell $cell, $value): bool
    {
        // Jangan mengeksekusi jawaban form sebagai formula spreadsheet.
        $cell->setValueExplicit((string) ($value ?? ''), DataType::TYPE_STRING);
        return true;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1A5C45']]]];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $pageSetup = $event->sheet->getDelegate()->getPageSetup();
                $pageSetup->setOrientation($this->orientation === 'portrait'
                    ? \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT
                    : \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $pageSetup->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
                $pageSetup->setFitToWidth(1)->setFitToHeight(0)->setFitToPage(true);
            },
        ];
    }
}
