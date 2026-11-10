# Todolist Pro - PABWE 2026 Praktikum 7 (Laravel 13 & Livewire 4)

Aplikasi Todolist dengan **Clean Architecture & Feature-First**: Laravel 13, Livewire 4, MySQL,
PhpSpreadsheet (import/export Excel) dan integrasi OpenAI (generator todo).

## Cara memasang (di atas proyek hasil `laravel new`)

```bash
# 1. Buat proyek kosong (tanpa starter kit)
laravel new {username}-pabwe2026-latihan-p7-laravel-livewire --database=mysql --phpunit --no-node --no-boost --no-interaction
cd {username}-pabwe2026-latihan-p7-laravel-livewire

# 2. Salin seluruh isi folder overlay ini ke root proyek (timpa file yang sudah ada)
#    lalu jalankan skrip penyesuaian:
bash patch-project.sh

# 3. Dependensi
composer require livewire/livewire phpoffice/phpspreadsheet
npm install --ignore-scripts && npm run build

# 4. Database + environment
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS pabwe_p7_todolist CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
cp .env.example .env && php artisan key:generate
#    isi OPENAI_KEY di .env bila ingin memakai fitur AI

# 5. Migrasi + seeder + storage link
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

Akun seeder: `admin` / `admin` (admin) dan `member` / `member` (member).

## Pengujian

```bash
composer test     # PHPUnit + coverage (butuh PCOV atau Xdebug)
composer audit
```

Target: coverage `app/` 100% (Classes/Methods/Lines), `OK (... tests, ... assertions)` tanpa notice.
Artefak uji (`coverage-*`, `coverage.txt`, `coverage.xml`, `junit.xml`, ...) sudah di `.gitignore`.

## Rute

| Method | URI | Keterangan |
|---|---|---|
| GET | `/` | guest -> login, admin -> `/admin/users`, member -> `/todos` |
| GET | `/login`, `/register` | guest |
| POST | `/logout` | auth |
| GET | `/todos`, `/todos/{todo}` | member (`role:member`, `owns.todo`) |
| GET | `/profile` | auth |
| GET | `/admin/users`, `/admin/users/{user}/todos` | admin, read-only |

ID form login: `login-username`, `login-password`, `login-submit`.
