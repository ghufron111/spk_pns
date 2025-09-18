<h1 align="center">SPK Kenaikan Pangkat PNS</h1>
<p align="center"><strong>Sistem Pendukung Keputusan untuk proses kenaikan pangkat PNS (Reguler, Pilihan, Ijazah).</strong></p>

## 1. Ringkasan

Sistem ini membantu mengelola proses pengajuan dan persetujuan kenaikan pangkat PNS secara terstruktur dengan peran (role) terpisah: Pegawai, Admin, dan Pimpinan. Dokumen persyaratan fleksibel (dapat dikonfigurasi), periode pengajuan dikelola melalui tabel `batas_waktu`, dan promosi pangkat mengikuti aturan bisnis yang dibedakan per jenis pengajuan.

## 2. Fitur Utama

-   Autentikasi & otorisasi multi-role (Pegawai / Admin / Pimpinan)
-   Pengelolaan periode pengisian (aktif / non-aktif) melalui batas waktu
-   Dynamic required documents (konfigurasi pola dokumen per jenis pengajuan)
-   Upload berkas per periode dengan grouping dan status
-   Pengajuan tiga jenis: Reguler, Pilihan, Ijazah
-   Target pangkat dinamis (hanya lebih tinggi dari pangkat saat ini, dibatasi maksimum IVe)
-   Persistensi target pangkat (tidak berubah saat re-upload)
-   Validasi admin (pemeriksaan berkas) & hasil SPK (dipertimbangkan)
-   Dashboard pimpinan dengan ringkasan & halaman khusus “Pengajuan Dipertimbangkan”
-   Persetujuan / penolakan pimpinan dengan update otomatis pangkat pegawai
-   Riwayat & notifikasi ke pegawai (disetujui / ditolak)
-   Halaman data pegawai + history kenaikan

## 3. Arsitektur & Tabel Inti

| Tabel          | Deskripsi Singkat                                         |
| -------------- | --------------------------------------------------------- |
| users          | Data akun + role + referensi pangkat (pangkat_id)         |
| pangkat        | Master pangkat (nama_pangkat, golongan, ruang)            |
| uploads        | Header pengajuan (jenis, periode, status, target_pangkat) |
| detail_uploads | Detail file tiap dokumen persyaratan                      |
| hasil_spk      | Hasil penilaian SPK (hasil, catatan)                      |
| batas_waktu    | Periode upload (tanggal_mulai, tanggal_selesai, aktif)    |
| notifikasi     | Notifikasi ke user                                        |
| spk_settings   | Konfigurasi pola dokumen dinamis                          |

Referensi urutan pangkat disimpan di `config/pangkat.php` (orde & maks).

## 4. Instalasi & Setup

1. Clone repo & masuk folder.
2. Salin `.env.example` menjadi `.env` lalu sesuaikan koneksi database.
3. Jalankan:

```bash
composer install
php artisan key:generate
php artisan migrate --seed   # jika ada seeder awal
```

4. (Opsional) Build asset front-end:

```bash
npm install
npm run build   # atau: npm run dev
```

5. Jalankan server pengembangan:

```bash
php artisan serve
```

## 5. Konfigurasi Awal (Admin)

1. Login sebagai Admin (buat manual lewat seeder / tinker jika belum ada).
2. Buka menu Periode Pengisian: aktifkan / buat periode baru (hanya satu aktif).
3. Atur dokumen wajib per jenis (menu Pengaturan Dokumen) menggunakan pola yang tersedia (misal: docpattern_reguler_sk_cpns, dll).
4. Pastikan master tabel pangkat terisi (golongan & ruang) sesuai kebutuhan.

## 6. Alur Per Role

### Pegawai

1. Login -> Dashboard menampilkan ringkasan & status periode aktif.
2. Menu Upload Berkas: pilih jenis pengajuan (reguler / pilihan / ijazah).
3. Sistem menampilkan daftar dokumen wajib (dinamik). Unggah semua file yang diminta.
4. Pilih target pangkat (untuk pilihan / ijazah). Sistem otomatis menyaring hanya pangkat yang lebih tinggi.
5. Submit: status awal menunggu validasi admin. Target pangkat terkunci setelah pertama kali simpan.
6. Notifikasi akan muncul saat berkas divalidasi dan saat keputusan pimpinan.

### Admin

1. Validasi Berkas: meninjau setiap upload & dokumen baru / revisi.
2. Menjalankan proses SPK (menu Proses SPK) untuk memberi hasil (dipertimbangkan) pada pengajuan yang lolos.
3. Mengatur Periode & Dokumen dinamis sesuai kebutuhan kebijakan.
4. Tidak memutuskan kenaikan (hanya mempersiapkan untuk pimpinan).

### Pimpinan

1. Dashboard: melihat ringkasan (jumlah pegawai, dipertimbangkan, disetujui, ditolak, aktif).
2. Menu Pengajuan Dipertimbangkan: daftar pengajuan status “dipertimbangkan”.
3. Menyetujui / menolak:
    - Reguler: sistem otomatis naikkan satu tingkat (maksimal IVe).
    - Pilihan / Ijazah: gunakan `target_pangkat` (dibatasi tidak melebihi batas maksimal & harus lebih tinggi).
4. Setelah disetujui pangkat user diperbarui (`users.pangkat` & `pangkat_id`).
5. Menu Persetujuan Kenaikan (jika masih dipakai) dapat difokuskan ke riwayat final.
6. Menu Data Pegawai: daftar semua pegawai + history kenaikan (jumlah & terakhir).

## 7. Aturan Promosi Pangkat

| Jenis   | Mekanisme                                                   |
| ------- | ----------------------------------------------------------- |
| Reguler | Naik otomatis ke pangkat berikut dalam orde                 |
| Pilihan | Menggunakan target_pangkat yang dipilih user (lebih tinggi) |
| Ijazah  | Sama seperti pilihan (verifikasi ijazah dalam dokumen)      |

Batas atas ditentukan oleh `config('pangkat.maks')` default: IVe.

## 8. Dokumen Dinamis

Setiap pola disimpan di `spk_settings` dengan key `docpattern_{jenis}_{slug}`. Admin dapat menambah / menghapus dokumen tanpa modifikasi kode. Form upload akan menghasilkan daftar wajib berdasarkan pola yang tersimpan.

## 9. Periode Upload (batas_waktu)

Hanya satu periode aktif. Form upload menolak pengajuan di luar rentang aktif. Periode lama tetap menyimpan histori pengajuan.

## 10. Notifikasi

Tersimpan di tabel `notifikasi` dengan flag `dibaca`. Pegawai & admin dapat menandai semua sebagai telah dibaca atau menghapus.

## 11. Riwayat & History Kenaikan

Halaman Data Pegawai menghitung jumlah upload yang berstatus disetujui dan menampilkan informasi terakhir (periode, jenis, target). Pangkat aktual user selalu yang terbaru setelah persetujuan.

## 12. Perintah Artisan Umum

```bash
php artisan migrate            # Migrasi database
php artisan migrate:fresh       # Reset database (hati-hati)
php artisan db:seed             # Jalankan seeder
php artisan tinker              # Interaktif shell
php artisan cache:clear         # Bersihkan cache aplikasi
php artisan config:clear        # Bersihkan cache konfigurasi
```

## 13. Pengujian

Menggunakan Pest / PHPUnit (contoh bawaan Laravel). Jalankan:

```bash
php artisan test
```

## 14. Deployment Singkat

1. Jalankan migrasi & optimisasi:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

2. Pastikan direktori `storage/` dan `bootstrap/cache/` writable.
3. Sesuaikan `APP_ENV=production` & `APP_DEBUG=false`.

## 15. Troubleshooting

| Masalah                            | Penyebab Umum                  | Solusi                                         |
| ---------------------------------- | ------------------------------ | ---------------------------------------------- |
| Tidak bisa upload (periode)        | Periode tidak aktif            | Aktifkan periode di menu admin                 |
| Target pangkat kosong              | Jenis reguler / sudah terkunci | Ini normal; reguler auto-next                  |
| File tidak tersimpan               | Kolom hash/detail tidak sesuai | Pastikan `hash` ada di fillable `DetailUpload` |
| Pangkat tidak naik setelah approve | Parsing gagal / config orde    | Cek `config/pangkat.php` & relasi pangkat_id   |

## 16. Lisensi

Proyek ini berbasis Laravel (MIT). Silakan gunakan & modifikasi sesuai kebutuhan internal.

---

Untuk pengembangan lanjutan: audit trail promosi, export CSV, grafik tren dashboard, dan penyesuaian multi-periode paralel dapat ditambahkan.

Selamat menggunakan sistem SPK Kenaikan Pangkat PNS.
