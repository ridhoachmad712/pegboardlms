<?php

namespace App\Exports;

use App\Models\Course;
use App\Services\GradeCalculator;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NilaiExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    private array $data;

    public function __construct(private Course $course)
    {
        $this->data = (new GradeCalculator())->forCourse($course);
    }

    public function title(): string
    {
        return 'Nilai';
    }

    public function headings(): array
    {
        $cols = ['NIM', 'Nama'];
        foreach ($this->data['components'] as $c) {
            $cols[] = $c->name.' ('.$c->weight.'%)';
        }
        $cols[] = 'Nilai Akhir';
        $cols[] = 'Huruf';

        return $cols;
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->data['rows'] as $row) {
            $line = [$row['student']->nim_nip, $row['student']->name];
            foreach ($this->data['components'] as $c) {
                $line[] = $row['components'][$c->id] ?? '';
            }
            $line[] = $row['final'];
            $line[] = $row['letter'];
            $rows[] = $line;
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('1:1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('206BC4');
        $sheet->getStyle('1:1')->getFont()->getColor()->setRGB('FFFFFF');

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
