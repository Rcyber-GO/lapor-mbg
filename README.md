# Lapor MBG

Aplikasi web pelaporan dan pemantauan keamanan makanan MBG (Mini Project Proyek 3 — Kasus A). Masyarakat melaporkan keluhan kesehatan setelah konsumsi makanan MBG, Pemantau Komunitas meninjau laporan, dan Koordinator Komunitas mencatat penerusan informasi kepada pihak terkait.

## Identitas

| | |
|---|---|
| Nama | Muhammad Rif'q A |
| NIM | 251511023 |
| Kelas | 2A-D3 |
| Judul | Pelaporan dan Pemantauan Keamanan Makanan MBG |
| Repository | https://github.com/Rcyber-GO/lapor-mbg |

## Kebutuhan Environment

- PHP 8.3 atau lebih baru, dengan ekstensi `pdo_mysql`, `fileinfo`, dan `gd` (gd dipakai seeder untuk membuat file bukti dummy)
- Composer
- MySQL atau MariaDB (mis. XAMPP)

Tampilan memakai Blade dan CSS biasa, sehingga tidak perlu Node.js/npm atau proses build.

## Langkah Instalasi

```bash
git clone https://github.com/Rcyber-GO/lapor-mbg.git
cd lapor-mbg
composer install
cp .env.example .env
php artisan key:generate
```

## Konfigurasi Database

1. Buat database kosong:

   ```sql
   CREATE DATABASE lapor_mbg CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Sesuaikan bagian database di `.env` (nilai bawaan sudah cocok untuk XAMPP tanpa password):

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=lapor_mbg
   DB_USERNAME=root
   DB_PASSWORD=
   ```

## Migration dan Seeding

```bash
php artisan migrate --seed
```

Untuk mengembalikan data demo ke kondisi awal kapan saja:

```bash
php artisan migrate:fresh --seed
```

Seeder menyiapkan akun demo, 6 sekolah, 18 distribusi, dan 6 laporan dalam status berbeda beserta file bukti dummy di `storage/app/private/evidence`. Semua data fiktif.

## Akun Demo

Semua akun memakai password `password`.

| Peran | Email | Kondisi data demo |
|---|---|---|
| Pelapor | rina@example.test | Laporan #1 Perlu Dilengkapi, #4 Sedang Ditinjau, #6 Selesai Dipantau |
| Pelapor | agus@example.test | Laporan #2 Ditandai untuk Diteruskan, #3 Dikirim, #5 Sudah Diteruskan |
| Pemantau Komunitas | dedi@example.test | Meninjau laporan, memberi catatan |
| Koordinator Komunitas | sari@example.test | Kelola sekolah/distribusi, rekap, catat penerusan |

Registrasi mandiri (`/daftar`) selalu membuat akun Pelapor. Akun Pemantau dan Koordinator hanya disiapkan melalui seeder.

## Menjalankan Aplikasi

```bash
php artisan serve
```

Buka http://127.0.0.1:8000 lalu masuk dengan salah satu akun demo.

Batas unggah bukti adalah 2 MB, sama dengan nilai bawaan `upload_max_filesize` PHP. File yang lebih besar tetap ditolak dengan pesan yang jelas.

## Ringkasan Fitur

| Peran | Fitur |
|---|---|
| Pelapor | Buat laporan (sekolah → distribusi dimuat dengan `fetch()`), unggah bukti JPG/PNG/PDF maks. 2 MB, ubah laporan selama diperbolehkan, pantau status dan catatan peninjauan |
| Pemantau Komunitas | Laporan Masuk dengan pencarian/filter/pagination, lihat keterkaitan laporan pada distribusi yang sama, ubah status dan beri catatan, rekap |
| Koordinator Komunitas | Kelola sekolah dan distribusi, Laporan Masuk, catat penerusan kepada sekolah/SPPG/fasilitas kesehatan/lainnya, rekap |

### Alur Status

```
Dikirim → Sedang Ditinjau → Perlu Dilengkapi → (pelapor memperbaiki) → Dikirim
                          → Ditandai untuk Diteruskan → (koordinator mencatat penerusan) → Sudah Diteruskan
                          → Selesai Dipantau
```

Nama status sengaja tidak memberi kesan diagnosis atau penetapan resmi.

### Business Rule

Seluruh business rule diterapkan di `app/Services/ReportWorkflow.php` (BR-03 di `app/Http/Requests/StoreReportRequest.php`).

| Kode | Aturan |
|---|---|
| BR-01 | Pelapor hanya dapat mengubah laporan berstatus Dikirim atau Perlu Dilengkapi; setelah diperbaiki, status kembali Dikirim |
| BR-02 | Perubahan status hanya mengikuti alur di atas; laporan berstatus akhir tidak dapat diubah, dan catatan wajib untuk Perlu Dilengkapi, Ditandai untuk Diteruskan, dan Selesai Dipantau |
| BR-03 | Distribusi harus milik sekolah yang dipilih, dan tanggal kejadian harus di antara tanggal distribusi dan hari ini |
| BR-04 | Penerusan hanya dapat dicatat untuk laporan yang sudah Ditandai untuk Diteruskan (atau sudah diteruskan, untuk pihak tambahan) |
| BR-05 | Satu pelapor hanya boleh memiliki satu laporan aktif untuk distribusi yang sama |

Hak akses (siapa boleh melakukan apa) diatur di `app/Policies/ReportPolicy.php` dan Gate di `app/Providers/AppServiceProvider.php`. Perubahan status dicatat `app/Observers/ReportObserver.php` ke tabel `report_status_logs` dan dapat dilihat staf pada bagian "Riwayat Status" di detail laporan.

## Skenario Uji Cepat (Data Demo)

| AC | Langkah |
|---|---|
| AC-05 | Login sebagai Rina, buka `/laporan/2` (milik Agus) → 404 |
| AC-06 / AC-08 | Login sebagai Dedi, buka laporan #3 → Mulai Tinjau, lalu Selesai Dipantau dengan catatan. Mencoba Selesai tanpa catatan ditolak |
| AC-08 (BR-01) | Rina mengubah laporan #1 (boleh) dan laporan #4 (ditolak) |
| AC-08 (BR-04) | Sari mencatat penerusan untuk #2 (boleh) dan #4 (ditolak) |
| AC-08 (BR-05) | Rina membuat laporan baru untuk distribusi terbaru SMP Harapan → ditolak karena laporan #1 masih aktif |
| AC-09 | Rina membuka Buat Laporan, pilih sekolah, lalu amati request `GET /sekolah/{id}/distribusi` di DevTools Network |
| AC-12 | Setelah Dedi mengubah status, buka detail laporan → Riwayat Status, atau tabel `report_status_logs` |
| AC-13 | Login sebagai Dedi atau Sari, buka menu Rekap dan gunakan filter |

## Menjalankan Test

Feature test memakai database MySQL/MariaDB terpisah agar foreign key dan unique constraint diuji sama seperti di aplikasi:

```sql
CREATE DATABASE lapor_mbg_test;
```

```bash
php artisan test
```
