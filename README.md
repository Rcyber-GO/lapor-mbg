# Lapor MBG

Aplikasi web pelaporan dan pemantauan keamanan makanan MBG (Mini Project Proyek 3 — Kasus A).

## Identitas

| | |
|---|---|
| Nama | Muhammad Rif'q A |
| NIM | 251511023 |
| Kelas | 2A-D3 |
| Judul | Pelaporan dan Pemantauan Keamanan Makanan MBG |
| Repository | https://github.com/Rcyber-GO/lapor-mbg |

## Kebutuhan Environment

- PHP 8.3+
- Composer
- MySQL / MariaDB (mis. XAMPP)
- Node.js & npm

## Akun Demo

Semua akun memakai password `password` (tersedia setelah `php artisan migrate --seed`).

| Peran | Email |
|---|---|
| Pelapor | rina@example.test |
| Pelapor | agus@example.test |
| Pemantau Komunitas | dedi@example.test |
| Koordinator Komunitas | sari@example.test |

Registrasi mandiri (`/daftar`) selalu membuat akun Pelapor. Akun Pemantau dan Koordinator hanya disiapkan melalui seeder.

## Menjalankan Test

Feature test memakai database MySQL/MariaDB terpisah agar foreign key dan unique constraint diuji sama seperti di aplikasi:

```sql
CREATE DATABASE lapor_mbg_test;
```

```bash
php artisan test
```

_Langkah instalasi lengkap akan dilengkapi seiring pengembangan._
