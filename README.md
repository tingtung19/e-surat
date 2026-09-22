# E-Surat

Sistem persuratan digital berbasis Laravel untuk mengelola surat internal, surat resmi ke Direktur, surat eksternal, komentar, disposisi, lampiran, dan histori audit.

## Persyaratan Sistem

- PHP 8.3 atau lebih baru
- Composer 2.x
- Node.js dan npm (opsional untuk asset frontend)
- SQLite (default) atau MySQL

## Instalasi Lokal

Jalankan perintah berikut dari folder aplikasi:

```powershell
cd app
composer install
Copy-Item .env.example .env
php artisan key:generate
```

### Database SQLite (Default)

Buat file database SQLite jika belum tersedia:

```powershell
New-Item database\database.sqlite -ItemType File
```

Pastikan `.env` menggunakan:

```env
DB_CONNECTION=sqlite
```

Lanjutkan dengan migration dan data demo:

```powershell
php artisan migrate --seed
php artisan storage:link
```

### Database MySQL (Opsional)

Buat database MySQL, lalu ubah konfigurasi `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=esurat
DB_USERNAME=root
DB_PASSWORD=
```

Kemudian jalankan:

```powershell
php artisan migrate --seed
php artisan storage:link
```

> Jangan menjalankan `migrate:fresh` pada database production karena perintah tersebut menghapus seluruh tabel dan data.

## Menjalankan Aplikasi

```powershell
php artisan serve
```

Buka alamat berikut di browser:

```text
http://127.0.0.1:8000
```

Untuk development dengan frontend Vite:

```powershell
npm install
npm run dev
```

## Akun Demo

Perintah `php artisan migrate --seed` membuat akun berikut. Password semua akun demo adalah:

```text
password
```

| Role | Nama | Email | Kegunaan |
|---|---|---|---|
| Divisi | Divisi Umum | `demo@esurat.local` | Membuat surat internal/resmi dan membalas disposisi |
| Divisi | Divisi Keuangan | `keuangan@esurat.local` | Menerima disposisi dan memberikan balasan |
| Administrasi Umum | Administrasi Umum | `admin@esurat.local` | Verifikasi, penomoran, kategori, dan arsip surat |
| Direktur Utama | Direktur Utama | `director@esurat.local` | Membuka surat, memberi komentar, disposisi, CC, dan menutup surat |

> Akun demo hanya untuk development. Ganti password dan buat akun baru sebelum deployment.

## Cara Login

1. Buka `http://127.0.0.1:8000/login`.
2. Masukkan email salah satu akun demo.
3. Masukkan password `password`.
4. Klik **Masuk**.
5. Setelah login, pengguna diarahkan ke dashboard.
6. Gunakan tombol keluar pada bagian kanan atas untuk logout.

## Cara Menggunakan Berdasarkan Role

### Divisi

1. Login menggunakan akun divisi.
2. Pilih **Buat Surat**.
3. Pilih jenis surat:
   - **Internal** untuk komunikasi langsung ke divisi lain.
   - **Official/Resmi** untuk surat yang harus diverifikasi Administrasi Umum dan diteruskan ke Direktur.
4. Isi judul dan deskripsi.
5. Pilih divisi tujuan jika membuat surat internal.
6. Tambahkan satu atau beberapa lampiran jika diperlukan.
7. Pilih **Simpan draft** atau **Submit surat**.
8. Pada surat yang diterima melalui disposisi, tulis jawaban dan kirim balasan.

Surat yang dibuat oleh divisi dapat dibatalkan selama belum dibuka oleh penerima.

### Administrasi Umum

1. Login menggunakan `admin@esurat.local`.
2. Buka surat berstatus **Menunggu Verifikasi**.
3. Isi nomor surat.
4. Pilih kategori atau sub-kategori.
5. Klik **Verifikasi & teruskan**.
6. Untuk surat eksternal, gunakan menu **Buat Surat**, pilih jenis **Eksternal**, isi pengirim eksternal, nomor, kategori, dan lampiran hasil scan.
7. Gunakan opsi **Tutup surat** setelah seluruh disposisi selesai untuk mengarsipkan surat.

### Direktur Utama

1. Login menggunakan `director@esurat.local`.
2. Buka surat resmi atau surat eksternal.
3. Berikan komentar atau tanggapan.
4. Untuk tindak lanjut, pilih divisi dan isi instruksi disposisi.
5. Gunakan **Tambah tembusan** untuk pihak yang hanya perlu menerima informasi.
6. Tutup surat setelah seluruh disposisi wajib-balas telah dijawab.

Tembusan bersifat informatif dan tidak dapat dibalas sebagai disposisi atau diteruskan.

### Data Master Administrasi

Menu data master hanya tersedia untuk role `admin_umum` dan `super_admin`:

- **Data Users**: `/master/users`
  - Tambah akun divisi, Administrasi Umum, Direktur, atau Super Admin.
  - Edit nama, email, divisi, role, dan status aktif.
  - Hapus akun lain. Akun yang sedang digunakan tidak dapat dihapus.
  - Reset password melalui halaman edit user. Password baru minimal 8 karakter.
- **Kategori Surat**: `/master/categories`
  - Tambah, edit, dan hapus kategori.
  - Buat sub-kategori dengan memilih kategori induk.
  - Struktur dibatasi maksimal satu parent (dua level: induk dan sub-kategori).
  - Atur pola nomor surat, misalnya `UM/{year}/{number}`.

Kategori yang sudah digunakan oleh surat atau memiliki sub-kategori tidak dapat dihapus untuk menjaga integritas data.

## Lampiran File

Format yang didukung:

- PDF
- DOC/DOCX
- XLS/XLSX
- JPG/JPEG
- PNG

Ukuran maksimum setiap file adalah 10 MB. File disimpan pada Laravel filesystem di `storage/app` dan dapat diakses melalui symbolic link `public/storage`.

Jika lampiran tidak dapat dibuka, jalankan:

```powershell
php artisan storage:link
```

## Status Surat

Status yang digunakan dalam aplikasi:

- `Draft`
- `Waiting Verification`
- `Sent`
- `Opened`
- `Waiting Reply`
- `Cancelled`
- `Closed`

Histori surat dicatat pada timeline dan tidak disediakan operasi edit atau hapus dari UI.

## Pengujian dan Pemeriksaan Kode

Jalankan test:

```powershell
php artisan test --compact
```

Jalankan formatter:

```powershell
vendor\bin\pint --format agent
```

Periksa daftar route:

```powershell
php artisan route:list --except-vendor
```

## Konfigurasi Produksi

Sebelum deployment:

1. Ubah `APP_ENV=production`.
2. Ubah `APP_DEBUG=false`.
3. Isi `APP_URL` dengan domain aplikasi.
4. Gunakan database MySQL production.
5. Ganti semua password akun demo.
6. Konfigurasikan filesystem dan backup lampiran.
7. Jalankan cache konfigurasi setelah `.env` final:

```powershell
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Integrasi Pusher, notifikasi real-time, CRUD master data, pencarian/filter, dan laporan analitik masih menjadi bagian pengembangan lanjutan.
