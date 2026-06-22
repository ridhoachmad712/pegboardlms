<?php

namespace App\Exports;

use App\Models\Course;
use App\Services\AttendanceService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AbsensiExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    private array $grid;

    private const LETTER = ['hadir' => 'H', 'izin' => 'I', 'sakit' => 'S', 'alpa' => 'A'];

    public function __construct(private Course $course)
    {
        $this->grid = (new AttendanceService())->gridForCourse($course);
    }

    public function title(): string
    {
        return 'Absensi';
    }

    public function headings(): array
    {
        $cols = ['NIM', 'Nama'];
        foreach ($this->grid['meetings'] as $m) {
            $cols[] = 'P'.$m->number;
        }
        $cols[] = 'Hadir';
        $cols[] = '%';

        return $cols;
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->grid['students'] as $s) {
            $line = [$s->nim_nip, $s->name];
            foreach ($this->grid['meetings'] as $m) {
                $status = $this->grid['matrix'][$s->id][$m->id] ?? null;
                $line[] = $status ? (self::LETTER[$status] ?? '-') : '-';
            }
            $sum = $this->grid['summary'][$s->id];
            $line[] = $sum['hadir'].'/'.$sum['sessions'];
            $line[] = is_null($sum['percent']) ? '-' : $sum['percent'].'%';
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

        return [1 => ['font' => ['bold' => true]]];
    }
}
