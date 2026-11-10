<?php

namespace Tests\Concerns;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait BuildsSpreadsheets
{
    /**
     * Membuat file .xlsx sementara dari baris-baris data dan mengembalikan path-nya.
     *
     * @param  list<list<mixed>>  $rows
     */
    protected function makeXlsxFile(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $r => $row) {
            foreach ($row as $c => $value) {
                if ($value === null) {
                    continue;
                }

                if (is_string($value)) {
                    $sheet->setCellValueExplicit([$c + 1, $r + 1], $value, DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue([$c + 1, $r + 1], $value);
                }
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'xl_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
