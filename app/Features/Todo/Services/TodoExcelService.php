<?php

namespace App\Features\Todo\Services;

use App\Features\Todo\Enums\TodoStatus;
use App\Features\Todo\Models\Todo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class TodoExcelService
{
    public const HEADERS = ['Judul', 'Deskripsi', 'Status', 'Jatuh Tempo', 'Dibuat'];

    private const COLUMN_ALIASES = [
        'title' => ['judul', 'title'],
        'description' => ['deskripsi', 'description'],
        'status' => ['status'],
        'due_date' => ['jatuh tempo', 'jatuh_tempo', 'due date', 'due_date'],
    ];

    /**
     * Menghasilkan isi file .xlsx (binary string).
     *
     * @param  Collection<int, Todo>  $todos
     */
    public function export(Collection $todos): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Todolist');
        $sheet->fromArray(self::HEADERS, null, 'A1');

        $row = 2;

        foreach ($todos as $todo) {
            $values = [
                $todo->title,
                (string) $todo->description,
                $todo->status->label(),
                $todo->due_date?->format('Y-m-d') ?? '',
                $todo->created_at?->format('Y-m-d H:i') ?? '',
            ];

            foreach ($values as $index => $value) {
                // String eksplisit mencegah formula injection (=, +, -, @).
                $sheet->setCellValueExplicit([$index + 1, $row], (string) $value, DataType::TYPE_STRING);
            }

            $row++;
        }

        $last = $row - 1;

        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
        ]);
        $sheet->getStyle("A1:E{$last}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getStyle("A1:E{$last}")->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_TOP);

        foreach (['A', 'C', 'D', 'E'] as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->getColumnDimension('B')->setWidth(50);

        $path = (string) tempnam(sys_get_temp_dir(), 'todo_');
        (new Xlsx($spreadsheet))->save($path);
        $content = (string) file_get_contents($path);
        unlink($path);

        return $content;
    }

    /**
     * Membaca file .xlsx/.xls/.csv. Mengembalikan baris valid dan daftar kesalahan.
     *
     * @return array{rows: list<array<string, mixed>>, errors: list<string>}
     */
    public function import(string $path): array
    {
        $data = IOFactory::load($path)->getActiveSheet()->toArray(null, true, false, false);
        $headerRow = array_shift($data) ?? [];

        $columns = $this->mapColumns($headerRow);

        if (! isset($columns['title'])) {
            return ['rows' => [], 'errors' => ['Kolom "Judul" tidak ditemukan pada baris pertama.']];
        }

        $rows = [];
        $errors = [];

        foreach ($data as $index => $cells) {
            $number = $index + 2;
            $title = trim((string) ($cells[$columns['title']] ?? ''));
            $descriptionRaw = trim((string) ($cells[$columns['description'] ?? -1] ?? ''));
            $statusRaw = trim((string) ($cells[$columns['status'] ?? -1] ?? ''));
            $dueRaw = $cells[$columns['due_date'] ?? -1] ?? null;

            if ($title === '' && $descriptionRaw === '' && $statusRaw === '' && blank($dueRaw)) {
                continue;
            }

            if ($title === '') {
                $errors[] = "Baris {$number}: judul wajib diisi.";

                continue;
            }

            if (mb_strlen($title) > 255) {
                $errors[] = "Baris {$number}: judul maksimal 255 karakter.";

                continue;
            }

            $status = $this->parseStatus($statusRaw);

            if ($status === null) {
                $errors[] = "Baris {$number}: status \"{$statusRaw}\" tidak valid.";

                continue;
            }

            try {
                $due = $this->parseDate($dueRaw);
            } catch (InvalidArgumentException) {
                $errors[] = "Baris {$number}: jatuh tempo tidak valid.";

                continue;
            }

            $rows[] = [
                'title' => $title,
                'description' => $descriptionRaw === '' ? null : $descriptionRaw,
                'status' => $status,
                'due_date' => $due,
            ];
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    /**
     * @param  array<int, mixed>  $headerRow
     * @return array<string, int>
     */
    private function mapColumns(array $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $index => $header) {
            $name = mb_strtolower(trim((string) $header));

            foreach (self::COLUMN_ALIASES as $key => $aliases) {
                if (in_array($name, $aliases, true) && ! isset($map[$key])) {
                    $map[$key] = $index;
                }
            }
        }

        return $map;
    }

    private function parseStatus(string $value): ?TodoStatus
    {
        if ($value === '') {
            return TodoStatus::Pending;
        }

        $needle = mb_strtolower($value);

        foreach (TodoStatus::cases() as $status) {
            if ($needle === $status->value || $needle === mb_strtolower($status->label())) {
                return $status;
            }
        }

        return null;
    }

    private function parseDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(Date::excelToDateTimeObject((float) $value))->toDateString();
            }

            return Carbon::parse((string) $value)->toDateString();
        } catch (Throwable) {
            throw new InvalidArgumentException('Tanggal tidak valid.');
        }
    }
}
