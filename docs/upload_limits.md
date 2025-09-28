# Konfigurasi Batas Upload

Nilai saat ini (hasil `php artisan upload:limits`):

-   max_file_uploads: 20
-   upload_max_filesize: 2G
-   post_max_size: 2G

Masalah: `max_file_uploads` = 20 membatasi jumlah file yang bisa diterima PHP dalam satu request. Aplikasi mencoba mengirim ±28 dokumen sehingga 8 terakhir tidak masuk ke Laravel.

## Target

Naikkan `max_file_uploads` menjadi **30** (atau lebih besar, misal 60 untuk future-proof).

## Cara Mengubah

### 1. Ubah php.ini (Direkomendasikan)

Cari file `php.ini` Anda (contoh Windows XAMPP: `C:/xampp/php/php.ini`). Edit / tambahkan:

```
max_file_uploads = 60
upload_max_filesize = 8M ; (opsional turunkan agar tidak terlalu besar)
post_max_size = 64M
```

Lalu restart web server / PHP-FPM / hentikan & jalankan ulang `php artisan serve`.

### 2. Menggunakan `.user.ini` (Jika tidak bisa sentuh php.ini)

Buat file `.user.ini` di root public (misal folder `public/`):

```
max_file_uploads=60
```

Catatan: Tidak semua environment menghormati `.user.ini` (tergantung SAPI). Tunggu beberapa detik (cache interval) lalu jalankan lagi:

```
php artisan upload:limits
```

### 3. Apache (htaccess)

Jika memakai Apache + mod_php, bisa di `public/.htaccess` (kadang TIDAK didukung untuk directive ini):

```
php_value max_file_uploads 60
```

Jika muncul 500 error, berarti directive tidak diizinkan—hapus dan pakai metode (1) atau (2).

## Verifikasi

Jalankan ulang:

```
php artisan upload:limits
```

Pastikan output:

```
max_file_uploads: 60
```

## Rekomendasi Tambahan

-   Turunkan `upload_max_filesize` dari 2G ke nilai realistis (4M atau 8M) agar request tidak terlalu besar.
-   Pastikan `post_max_size` > (jumlah_file _ rata2_ukuran + margin). Contoh 30 file _ 2MB = 60MB → pakai 64M.
-   Jika tetap ingin batasi user, Anda bisa tambahkan warning di view saat `$needs->count() > 30`.

## Catatan

`max_file_uploads` bersifat PHP_INI_SYSTEM → **tidak bisa diubah dengan `ini_set()` dari code Laravel**.

Setelah nilai dinaikkan, proses upload 28 dokumen sekaligus akan diterima semua dan loop di controller akan memprosesnya tanpa modifikasi tambahan.
