# Alur Sistem SPK Kenaikan Pangkat PNS

Dokumen ini menjelaskan end-to-end flow sistem mulai dari konfigurasi oleh Admin sampai keputusan final Pimpinan. Fokus pada entitas utama: User (Pegawai), Upload, DetailUpload, SPK Settings, HasilSpk, dan Notifikasi.

---

## 1. Persiapan & Konfigurasi (Admin)

### 1.1. Pengaturan Periode

Admin membuka menu Periode dan menetapkan periode aktif (label + tanggal mulai & selesai). Langkah ini:

-   Menonaktifkan periode aktif sebelumnya (jika ada)
-   Membuat record baru `batas_waktu` dengan `aktif=true`
-   Periode aktif digunakan sebagai default `periode` saat pegawai membuat upload baru (jika tidak diisi manual)

### 1.2. Pengaturan Dokumen & Bobot (SPK Settings)

Admin dapat:

-   Melihat daftar dokumen per jenis kenaikan (reguler / pilihan / ijazah / dst.)
-   Menyesuaikan bobot (weight) tiap dokumen via `spk_settings` (key: `weight_<jenis>_<slug>` atau fallback `weight_<slug>`)
-   Menambahkan dokumen dinamis baru dengan pola filename (key: `docpattern_<jenis>_<slug>`)
-   Menetapkan ambang:
    -   `threshold_approved` (default 0.85) → Rekomendasi “disetujui” SPK
    -   `threshold_consider` (default 0.55) → Rekomendasi “dipertimbangkan” SPK
    -   `threshold_min_valid_docs` (default 3) → Minimal dokumen bernilai agar ikut pemrosesan SPK

### 1.3. Konfigurasi Limit Upload Server

Gunakan command `php artisan upload:limits` untuk melihat batas PHP (`max_file_uploads`, `upload_max_filesize`, dll.). Sesuaikan php.ini bila perlu.

---

## 2. Registrasi & Persiapan Pegawai

1. Pegawai membuat akun / login.
2. Dashboard menampilkan notifikasi dan (jika ada) upload aktif yang belum final.
3. Pegawai membuka form Upload:
    - Sistem menentukan `jenis` (reguler/pilihan/ijazah) — dikunci jika ada proses aktif belum final.
    - Menentukan `periode` (default: periode aktif / tahun berjalan).
    - Jika jenis `pilihan` atau `ijazah`, pegawai memilih `target_pangkat` (divalidasi: harus di atas pangkat sekarang dan ≤ batas maksimum konfigurasi).
4. Sistem memetakan daftar dokumen wajib dari konfigurasi + dokumen dinamis.

---

## 3. Proses Upload Dokumen

### 3.1. Pembuatan / Reuse Batch Upload

-   Data disimpan ke tabel `uploads` (user_id, jenis, periode, target_pangkat, status awal `pending`).
-   Setiap dokumen yang diunggah menjadi record `detail_uploads` dengan status awal `pending`.

### 3.2. Validasi File

Per file dilakukan:

-   Validasi ekstensi (pdf/jpg/jpeg/png)
-   Validasi ukuran (≤ 2MB)
-   Penamaan file menyesuaikan pattern + NIP + tag periode & jenis
-   Jika dokumen sebelumnya status `ditolak`, unggahan baru menggantikan dan status kembali `pending`

### 3.3. Notifikasi Admin

Jika ada minimal satu dokumen berhasil diunggah / diperbarui, semua user `admin` mendapat notifikasi: ringkasan daftar dokumen baru.

---

## 4. Validasi Dokumen oleh Admin

Admin memeriksa setiap `DetailUpload` dan mengubah status:

-   `valid` : Dokumen sah, menunggu kemungkinan upgrade ke `disetujui`
-   `disetujui` : Dokumen final bernilai penuh
-   `pending` : Masih menunggu / perlu klarifikasi
-   `ditolak` : Bermasalah (pegawai harus reupload)

Perubahan status tertentu (pending / ditolak / ada catatan) memicu notifikasi ke pegawai.

Jika minimal `threshold_min_valid_docs` terpenuhi (jumlah dokumen bernilai = status valid atau disetujui), batch siap masuk perhitungan SPK.

---

## 5. Eksekusi SPK (Admin)

Admin menekan tombol “Jalankan SPK” → Controller menghitung skor untuk setiap upload yang memenuhi syarat.

### 5.1. Penentuan Bobot Efektif

Untuk jenis upload tertentu:

1. Ambil daftar bobot dokumen (`jenisWeights`).
2. Hitung hanya total bobot dokumen yang benar-benar ADA (diunggah) → `effectiveWeight`.

### 5.2. Skoring per Dokumen

StatusFactor:

-   disetujui = 1.0
-   valid = 0.9
-   pending = 0.4
-   ditolak / tidak_disetujui / missing = 0

Raw Score = Σ (weight \* statusFactor) untuk dokumen yang ada.
Normalisasi = Raw / EffectiveWeight → Nilai 0..1 → dikali 100 disimpan sebagai persentase (`[SKOR: xx / 100]`).

### 5.3. Klasifikasi Hasil

-   norm ≥ threshold_approved → hasilSpk = `disetujui` (rekomendasi)
-   norm ≥ threshold_consider tapi < approved → `dipertimbangkan`
-   sisanya → `ditolak`

### 5.4. Catatan

Sistem membuat catatan dinamis yang menjelaskan kondisi: dokumen pending, ditolak, dokumen kunci hilang, dsb. Catatan diawali tag `[SKOR: xx / 100]`.

Record disimpan / diupdate di tabel `hasil_spk` (relasi 1 upload : 1 hasil).

---

## 6. Tahap Pimpinan

### 6.1. Pemisahan Dua Tab

-   Tab "Rekomendasi SPK": memuat upload dengan `hasil_spk.hasil = disetujui` namun `uploads.status` belum final.
-   Tab "Perlu Dipertimbangkan": memuat upload dengan hasil `dipertimbangkan`.

### 6.2. Keputusan Final

Pimpinan memilih Setujui / Tolak:

-   Mengubah `uploads.status` menjadi `disetujui` atau `ditolak`.
-   Menandai hasilSpk (catatan ditambah `[Disetujui pimpinan]` atau `[Ditolak pimpinan]`).
-   Jika disetujui:
    -   Untuk jenis reguler: sistem mencoba menaikkan pangkat 1 tingkat (jika belum di atas batas maksimum configurasi).
    -   Untuk jenis pilihan / ijazah: pangkat ditetapkan ke `target_pangkat` (jika valid).
-   Notifikasi dikirim ke pegawai.

### 6.3. Riwayat Pangkat

Menu Data Pegawai: menampilkan riwayat uploads disetujui terakhir (periode, jenis, target pangkat).

---

## 7. Notifikasi

Sumber notifikasi:

-   Pegawai upload dokumen → Admin
-   Admin validasi (pending / ditolak / ada catatan) → Pegawai
-   Eksekusi SPK tidak langsung kirim notifikasi (opsional bisa ditambah)
-   Pimpinan keputusan akhir → Pegawai

Format disimpan di tabel `notifikasi` dengan flag `dibaca`.

---

## 8. Edge Cases & Fallback

| Kasus                                                           | Penanganan                                                                               |
| --------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| Dokumen baru ditambahkan admin setelah sebagian upload berjalan | Bobot otomatis terdeteksi (default_weight) dan bisa langsung dipakai pada SPK berikutnya |
| Semua dokumen belum diunggah                                    | effectiveWeight = Σ bobot dokumen yang ada (bisa 0) → upload di-skip jika < minValidDocs |
| Periode lama vs baru                                            | Tampilan SPK & pimpinan hanya menampilkan upload terbaru per user (dibatasi grouping)    |
| Parsing periode gagal (format custom)                           | Fallback urut berdasarkan id upload (lebih besar = lebih baru)                           |
| Berat bobot 0                                                   | Dokumen tidak memengaruhi skor meski ada (aman)                                          |

---

## 9. Tabel Inti

| Tabel          | Fungsi                               | Kolom Penting                                        |
| -------------- | ------------------------------------ | ---------------------------------------------------- |
| uploads        | Batch dokumen per user+jenis+periode | user_id, jenis, periode, target_pangkat, status      |
| detail_uploads | Dokumen individual                   | upload_id, nama_berkas, path_berkas, status, catatan |
| spk_settings   | Konfigurasi dinamis                  | key, value (weight*\*, docpattern*_, threshold\__)   |
| hasil_spk      | Hasil scoring                        | upload_id, hasil, catatan                            |
| notifikasi     | Pesan event                          | user_id, pesan, dibaca                               |
| batas_waktu    | Periode aktif                        | label, mulai, selesai, aktif                         |
| pangkat        | Master pangkat                       | nama_pangkat, golongan, ruang                        |

---

## 10. Ringkasan Algoritma SPK (Pseudo)

```
for each upload meeting minValidDocs:
  weights = getJenisWeights(upload.jenis)
  effectiveWeight = sum(weight[d] for d in weights if detail exists)
  raw = 0
  for each doc in weights:
     if detail exists:
        factor = statusFactor[detail.status]
        raw += factor * weights[doc]
  norm = effectiveWeight ? raw / effectiveWeight : 0
  percent = round(norm * 100,2)
  hasil = (norm>=approved) ? 'disetujui' : (norm>=consider ? 'dipertimbangkan' : 'ditolak')
  catatan = "[SKOR: percent / 100]" + penjelasan dinamis
  upsert hasil_spk
```

---

## 11. Jalur Keputusan Akhir

1. Pegawai unggah & perbaiki dokumen → status valid / disetujui mencukupi.
2. Admin jalankan SPK → menghasilkan rekomendasi.
3. Pimpinan melihat:
    - Rekomendasi (disetujui) → boleh langsung Setujui / Tolak.
    - Dipertimbangkan → cek catatan, bisa minta revisi (dengan menolak sehingga pegawai lanjut upload baru pada periode berikutnya).
4. Keputusan final mengubah status upload dan (jika setuju) melakukan update pangkat user.

---

## 12. Rekomendasi Pengembangan Lanjutan

-   Simpan skor numerik terpisah (kolom percent_score) untuk hindari parsing catatan.
-   Audit trail tabel keputusan pimpinan (menyimpan siapa & kapan memutuskan).
-   Fitur re-run SPK otomatis ketika ada dokumen baru valid di upload aktif.
-   Export laporan (CSV/PDF) daftar rekomendasi per periode.
-   Penilaian tambahan (misal penalti keterlambatan unggah).

---

## 13. Glossary

| Istilah      | Definisi                                                                      |
| ------------ | ----------------------------------------------------------------------------- |
| Upload       | Satu batch pengajuan kenaikan pangkat milik pegawai untuk 1 jenis & 1 periode |
| DetailUpload | Dokumen individual dalam batch Upload                                         |
| SPK          | Proses pengambilan keputusan terstruktur (skoring normalisasi + klasifikasi)  |
| Rekomendasi  | Hasil SPK otomatis (disetujui/dipertimbangkan) sebelum persetujuan pimpinan   |
| Final        | Keputusan pimpinan yang mengunci status upload                                |

---

Dokumen ini dapat diperbarui seiring perubahan logika bisnis. Versi pertama.
