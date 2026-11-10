#!/usr/bin/env bash
# Menyesuaikan file bawaan "laravel new" (jalankan dari root proyek, SETELAH overlay disalin).
set -euo pipefail

# 1) config/app.php -> tambah force_https
if ! grep -q "force_https" config/app.php; then
  sed -i "/'name' => env('APP_NAME'/a\\
\\
    'force_https' => (bool) env('FORCE_HTTPS', false)," config/app.php
fi

# 2) composer script "test"
composer config scripts.test --json '["@putenv XDEBUG_MODE=coverage","@php artisan config:clear --ansi","vendor/bin/phpunit --coverage-text"]'

# 3) Hapus file bawaan yang tidak dipakai / bentrok dengan spesifikasi
rm -f tests/Feature/ExampleTest.php tests/Unit/ExampleTest.php app/Http/Controllers/Controller.php

echo "Selesai. Lanjut: composer require livewire/livewire phpoffice/phpspreadsheet"
