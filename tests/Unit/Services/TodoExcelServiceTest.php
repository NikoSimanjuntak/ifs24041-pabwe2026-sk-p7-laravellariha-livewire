<?php

namespace Tests\Unit\Services;

use App\Features\Todo\Enums\TodoStatus;
use App\Features\Todo\Models\Todo;
use App\Features\Todo\Services\TodoExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Tests\Concerns\BuildsSpreadsheets;
use Tests\TestCase;

class TodoExcelServiceTest extends TestCase
{
    use BuildsSpreadsheets;
    use RefreshDatabase;

    private TodoExcelService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TodoExcelService;
    }

    public function test_export_produces_valid_xlsx_with_headers_and_safe_strings(): void
    {
        $todos = collect([
            Todo::factory()->create(['title' => '=1+1', 'description' => 'Deskripsi', 'due_date' => '2026-12-01']),
            Todo::factory()->status(TodoStatus::Completed)->create(['title' => 'Kedua', 'description' => null, 'due_date' => null]),
        ]);

        $content = $this->service->export($todos);
        $this->assertStringStartsWith('PK', $content);

        $path = tempnam(sys_get_temp_dir(), 'chk_').'.xlsx';
        file_put_contents($path, $content);
        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertSame(TodoExcelService::HEADERS, array_slice($sheet->toArray()[0], 0, 5));
        $this->assertSame('=1+1', $sheet->getCell('A2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('A2')->getDataType());
        $this->assertSame('Selesai', $sheet->getCell('C3')->getValue());
        $this->assertSame('2026-12-01', $sheet->getCell('D2')->getValue());
    }

    public function test_export_with_no_todos(): void
    {
        $this->assertStringStartsWith('PK', $this->service->export(collect()));
    }

    public function test_import_valid_rows_with_various_formats(): void
    {
        $serial = 46000;
        $path = $this->makeXlsxFile([
            ['Judul', 'Deskripsi', 'Status', 'Jatuh Tempo'],
            ['Satu', 'Desk satu', 'Selesai', '2026-12-31'],
            ['Dua', null, 'in_progress', $serial],
            [null, null, null, null],
            ['Tiga', '', '', null],
        ]);

        $result = $this->service->import($path);

        $this->assertSame([], $result['errors']);
        $this->assertCount(3, $result['rows']);
        $this->assertSame(TodoStatus::Completed, $result['rows'][0]['status']);
        $this->assertSame('2026-12-31', $result['rows'][0]['due_date']);
        $this->assertSame('Desk satu', $result['rows'][0]['description']);
        $this->assertSame(TodoStatus::InProgress, $result['rows'][1]['status']);
        $this->assertSame(Date::excelToDateTimeObject($serial)->format('Y-m-d'), $result['rows'][1]['due_date']);
        $this->assertNull($result['rows'][1]['description']);
        $this->assertSame(TodoStatus::Pending, $result['rows'][2]['status']);
        $this->assertNull($result['rows'][2]['due_date']);
    }

    public function test_import_accepts_english_header_aliases(): void
    {
        $path = $this->makeXlsxFile([
            ['title', 'description', 'status', 'due_date'],
            ['Task', 'Desc', 'pending', '2026-01-05'],
        ]);

        $result = $this->service->import($path);

        $this->assertCount(1, $result['rows']);
        $this->assertSame('2026-01-05', $result['rows'][0]['due_date']);
    }

    public function test_import_reports_row_errors(): void
    {
        $path = $this->makeXlsxFile([
            ['Judul', 'Deskripsi', 'Status', 'Jatuh Tempo'],
            [null, 'tanpa judul', 'pending', null],
            [str_repeat('x', 256), null, 'pending', null],
            ['Status salah', null, 'ngawur', null],
            ['Tanggal salah', null, 'pending', 'bukan tanggal'],
            ['Valid', null, 'pending', null],
        ]);

        $result = $this->service->import($path);

        $this->assertCount(1, $result['rows']);
        $this->assertSame([
            'Baris 2: judul wajib diisi.',
            'Baris 3: judul maksimal 255 karakter.',
            'Baris 4: status "ngawur" tidak valid.',
            'Baris 5: jatuh tempo tidak valid.',
        ], $result['errors']);
    }

    public function test_import_without_title_column(): void
    {
        $path = $this->makeXlsxFile([['Nama', 'Status'], ['A', 'pending']]);

        $result = $this->service->import($path);

        $this->assertSame([], $result['rows']);
        $this->assertCount(1, $result['errors']);
    }

    public function test_import_empty_sheet(): void
    {
        $result = $this->service->import($this->makeXlsxFile([]));

        $this->assertSame([], $result['rows']);
        $this->assertCount(1, $result['errors']);
    }

    public function test_import_csv_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'csv_').'.csv';
        file_put_contents($path, "Judul,Deskripsi,Status,Jatuh Tempo\nBeli buku,Toko A,menunggu,2026-05-01\n");

        $result = $this->service->import($path);

        $this->assertSame([], $result['errors']);
        $this->assertSame('Beli buku', $result['rows'][0]['title']);
        $this->assertSame(TodoStatus::Pending, $result['rows'][0]['status']);
    }
}
