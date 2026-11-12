# ARUNIKA: Aplikasi Rekapitulasi Nilai \& Kedisiplinan Kelas

> \*\*Tugas Besar Praktikum Pemrograman Web 2026\*\* — Kelompok \*\*21\*\*  
> \*Platform Evaluasi Akademik, Disiplin Pengumpulan Tugas, dan Pemetaan 4 Kuadran Belajar Terpadu.\*

\---

## Ringkasan Sistem

**Arunika** adalah platform evaluasi akademik dan kedisiplinan kelas terpadu yang dirancang untuk guru mata pelajaran dan wali kelas. Sistem ini mengintegrasikan:

1. **Manajemen Akademik \& Data Master:** Pengelolaan data kelas, siswa, mata pelajaran, periode penilaian, penugasan guru, serta data agregat penunjang akademik.
2. **Penilaian Terpadu:** Penginputan nilai berbasis komponen (Tugas, Kuis, UTS, UAS) dengan validasi batas rentang skor ($0 - 100$).
3. **Monitoring Kedisiplinan:** Pelacakan tenggat (*deadline*), pencatatan waktu kumpul, status penyerahan (*Tepat Waktu*, *Terlambat*, *Belum Kumpul*), dan kalkulasi Indeks Kedisiplinan.
4. **Perhitungan Nilai \& Laporan Akademik:** Pengaturan bobot nilai (total wajib $100%$), kalkulasi nilai akhir otomatis di backend, status kelengkapan, dan cetak rekap via peramban (`window.print()`).
5. **Klasifikasi Siswa (Matriks 4 Kuadran Belajar):** Pemetaan dan visualisasi kondisi siswa ke dalam matriks kuadran belajar:

   * **Bintang Kelas:** Rajin kumpul tugas \& capaian nilai tinggi.
   * **Butuh Cara Baru:** Rajin kumpul tugas, namun capaian nilai masih rendah.
   * **Bakat Terpendam:** Nilai tinggi, namun sering terlambat/absen tugas.
   * **Perlu Pendampingan:** Nilai rendah dan indeks disiplin rendah.
6. **Catatan Kualitatif \& Bimbingan:** Dokumentasi catatan perkembangan personal dan rencana tindak lanjut guru terhadap hasil klasifikasi kuadran siswa.

\---

## Kepatuhan Standar Praktikum (100% Native Architecture)

Proyek ini dibangun mematuhi aturan monorepo LabKom TI Unila tanpa pustaka/kerangka kerja eksternal (*Zero Framework \& Zero Dependency*):

* **Frontend UI:** Vanilla HTML5 Semantic \& Native CSS (CSS Variables, Flexbox, CSS Grid, Native `<dialog>`).
* **Frontend Interactivity:** Vanilla JavaScript DOM \& Fetch API asinkron (`async/await`).
* **Backend Logics:** PHP Native (PDO Prepared Statements) dengan format pertukaran data JSON murni (RESTful API).
* **Database:** Relational MySQL Server.
* **Isolasi Monorepo:** Seluruh pengerjaan dan berkas berada strictly di dalam `kelompok/kelompok\_21/`.

\---

## Pemetaan 6 Materi Praktikum

|No|Materi Praktikum|Implementasi Berkas|Peran Teknis \& Fungsional|
|-|-|-|-|
|**1**|**HTML5**|`index.php`, `login.php`, `views/\*.php`|Menyusun struktur semantik tata letak dasbor, form penilaian, tabel matriks, dialog modal native (`<dialog>`).|
|**2**|**CSS Native**|`css/style.css`|Styling mandiri menggunakan CSS Variables, CSS Grid (Matriks 4 Kuadran), dan Flexbox tanpa library UI/framework CSS.|
|**3**|**JavaScript (DOM)**|`js/app.js`, `js/\*.js`|Manipulasi DOM dinamis, validasi skor ($0-100$) dan bobot ($100%$), filter kelas/periode interaktif, serta pemicu `window.print()`.|
|**4**|**PHP \& Database**|`config/database.php`, `api/\*.php`|Koneksi database terproteksi, query SQL relasional (CRUD, multi-table `JOIN`, agregasi `AVG`/`SUM`/`COUNT`) menggunakan PHP PDO Prepared Statements.|
|**5**|**REST API**|`api/bootstrap.php`, `api/\*.php`|Pengendali *endpoint* backend untuk metode `GET`, `POST`, `PUT`, `DELETE`, otentikasi sesi guru, dan keluaran respon JSON murni terstandar.|
|**6**|**AJAX Fetch**|`js/app.js`, `js/\*.js`|Komunikasi asinkron (`async/await fetch()`) antara browser dan folder `api/` tanpa me-refresh halaman (*no-refresh SPA pattern*).|

\---

## Pembagian Tugas \& Tanggung Jawab Anggota (6 Orang)

Setiap anggota memiliki kepemilikan berkas modular (Endpoint API, View HTML, dan JS Fetch) secara mandiri untuk menghindari bentrok kode (*merge conflict*):

|No|Anggota \& Peran|Nama Branch Fitur|Berkas Modul yang Dikerjakan|Tanggung Jawab \& Target Fungsional|
|-|-|-|-|-|
|**1**|**Endy Yoga**<br>*(Lead \& Data Akademik)*|`feat/kelompok-21-endy-akademik`|- `config/database.php`<br>- `config/modules.php`<br>- `includes/auth.php`<br>- `login.php` \& `logout.php`<br>- `tools/tambah\_guru.php`<br>- `api/guru.php`<br>- `api/data.php`<br>- `views/akademik.php`<br>- `js/akademik.js`|Inisiasi arsitektur monorepo \& skema basis data, sistem login/logout sesi guru, CRUD kelas, siswa, mata pelajaran, periode penilaian, penugasan guru, serta penyediaan master data.|
|**2**|**Rio**<br>*(Penilaian Terpadu)*|`feat/kelompok-21-Rio-penilaian`|- `api/penilaian.php`<br>- `views/penilaian.php`<br>- `js/penilaian.js`|CRUD komponen penilaian (Tugas, Kuis, UTS, UAS), input dan modifikasi nilai per siswa per kelas, validasi skor range $0-100$, dan tampilan rekap komponen nilai.|
|**3**|**Dwi Septy**<br>*(Monitoring Pengumpulan)*|`feat/kelompok-21-Dwi-monitoring`|- `api/monitoring.php`<br>- `views/monitoring.php`<br>- `js/monitoring.js`|Penjadwalan tenggat waktu tugas, pencatatan presisi waktu kumpul, klasifikasi status (*Tepat Waktu, Terlambat, Belum Kumpul*), riwayat tugas, dan kalkulasi indeks disiplin.|
|**4**|**Jehan**<br>*(Perhitungan Nilai \& Laporan)*|`feat/kelompok-21-Jehan-kalkulasi`|- `api/kalkulasi.php`<br>- `views/laporan.php`<br>- `js/kalkulasi.js`|Konfigurasi bobot penilaian komponen (wajib $100%$), kalkulasi nilai akhir otomatis di backend, status ketuntasan/kelengkapan nilai, rekapitulasi kelas, serta cetak laporan akademik (`window.print()`).|
|**5**|**Natasya Ardiana**<br>*(Klasifikasi Siswa)*|`feat/kelompok-21-natasya-klasifikasi\_siswa`|- `index.php`<br>- `css/style.css`<br>- `api/klasifikasi.php`<br>- `views/klasifikasi.php`<br>- `js/klasifikasi.js`|Visualisasi dan agregasi data Matriks 4 Kuadran Belajar (*Jarang kumpul \& nilai, Rajin \& nilai tinggi, Jarang kumpul \& nilai rendah, Rajin \& nilai rendah*), penataan CSS Grid dasbor kuadran, dan filter interaktif.|
|**6**|**Nova Arundyna Inzani**<br>*(Catatan Kualitatif \& Bimbingan)*|`feat/kelompok-21-Nova-catatan\_bimbingan`|- `api/profil.php`<br>- `views/kualitatif.php`<br>- `js/kualitatif.js`|Pengambilan rekomendasi kuadran per siswa, CRUD catatan observasi kualitatif dan rencana bimbingan guru, profil riwayat personal siswa, serta pemantauan status tindak lanjut.|

\---

## Struktur Direktori Modular Native

```text
TUBES\_PRK\_PEMWEB\_2026/
└── kelompok/
    └── kelompok\_21/               <-- BATAS WILAYAH KERJA KELOMPOK 21
        ├── api/                   <-- \[REST API] Backend Endpoint JSON
        │   ├── bootstrap.php      <-- Setup Header JSON, Error Handler, DB Connector
        │   ├── guru.php           <-- \[Endy] CRUD Data Guru \& Auth Sesi
        │   ├── data.php           <-- \[Endy] CRUD Kelas, Siswa, Mapel, \& Data Master
        │   ├── penilaian.php      <-- \[Rio] CRUD Komponen \& Input Nilai
        │   ├── monitoring.php     <-- \[Dwi Septy] Tenggat, Log Waktu, \& Indeks Disiplin
        │   ├── kalkulasi.php      <-- \[Jehan] Bobot Nilai \& Rumus Nilai Akhir
        │   ├── klasifikasi.php    <-- \[Natasya] Data Agregasi Matriks 4 Kuadran
        │   └── profil.php         <-- \[Nova] Catatan Bimbingan \& Profil Siswa
        ├── config/                <-- \[KONFIGURASI]
        │   ├── database.php       <-- Koneksi PDO MySQL Prepared Statements
        │   └── modules.php        <-- Pendaftaran Navigasi \& Hak Akses Modul
        ├── css/                   <-- \[CSS NATIVE] Styling Mandiri
        │   └── style.css          <-- Layout Dasbor, Grid Kuadran, Utility CSS
        ├── database/              <-- \[SQL DATABASE]
        │   ├── 01\_schema.sql      <-- Struktur DDL Tabel Ter-Normalisasi
        │   ├── 02\_validasi.sql    <-- Trigger / Constraints / Integritas Relasi
        │   └── 03\_demo.sql        <-- Data Awal Seeder Demo Penilaian Asisten
        ├── includes/              <-- \[MIDDLEWARE \& LOGIK INTERNAL]
        │   └── auth.php           <-- Proteksi Sesi Login \& Session Validator
        ├── js/                    <-- \[JAVASCRIPT DOM \& ASYNC FETCH]
        │   ├── app.js             <-- Inisialisasi, Navigasi Tab, \& Helper Fetch
        │   ├── akademik.js        <-- \[Endy] Interaksi Data Kelas, Siswa, \& Mapel
        │   ├── penilaian.js       <-- \[Rio] Validasi Form \& Tabel Nilai
        │   ├── monitoring.js      <-- \[Dwi Septy] Interaksi Status Kumpul \& Disiplin
        │   ├── kalkulasi.js       <-- \[Jehan] Interaksi Form Bobot \& Trigger Cetak
        │   ├── kuadran.js         <-- \[Natasya] Visualisasi Render 4 Kuadran
        │   └── kualitatif.js      <-- \[Nova] Interaksi Profil \& Log Catatan Guru
        ├── tools/                 <-- \[UTILITAS PENGEMBANGAN]
        │   └── tambah\_guru.php    <-- Generator Akun Guru (Password Hashing BCRYPT)
        ├── views/                 <-- \[VIEW HTML5] Parsial Tampilan Antarmuka
        │   ├── akademik.php       <-- \[Endy] Modul Manajemen Master Data
        │   ├── penilaian.php      <-- \[Rio] Modul Form Input \& Rekap Nilai
        │   ├── monitoring.php     <-- \[Dwi Septy] Modul Pantauan Pengumpulan
        │   ├── laporan.php        <-- \[Jehan] Modul Rekapitulasi \& Cetak Nilai
        │   ├── kuadran.php        <-- \[Natasya] Modul Matriks 4 Kuadran
        │   └── kualitatif.php     <-- \[Nova] Modul Riwayat Bimbingan \& Tindak Lanjut
        ├── index.php              <-- Halaman Utama Aplikasi (Pemuat Modul)
        ├── login.php              <-- Antarmuka Login Guru \& Wali Kelas
        ├── logout.php             <-- Handler Penghapusan Sesi
        └── README.md              <-- Dokumentasi Teknis Sistem Arunika
```

\---

## Panduan Menjalankan Aplikasi \& Pengujian

### 1\. Prasyarat Lingkungan

* Web Server: **Apache** (XAMPP / Laragon / PHP Built-in Server).
* PHP Version: **PHP 8.0** ke atas (dengan ekstensi `pdo\_mysql` aktif).
* DBMS: **MySQL / MariaDB**.
* Browser modern yang mendukung ES6 Fetch API \& Dialog Element.

### 2\. Konfigurasi Basis Data

1. Buka phpMyAdmin atau MySQL CLI.
2. Buat database baru bernama `db\_arunika`:

```sql
   CREATE DATABASE db\_arunika;
   ```

3. Impor berkas SQL secara berurutan:

   * `kelompok/kelompok\_21/database/01\_schema.sql` (Skema tabel)
   * `kelompok/kelompok\_21/database/02\_validasi.sql` (Aturan \& integritas)
   * `kelompok/kelompok\_21/database/03\_demo.sql` (Data demo siap uji)
4. Sesuaikan kredensial koneksi di `kelompok/kelompok\_21/config/database.php` jika diperlukan (secara default `host=localhost`, `user=root`, `password=`).

### 3\. Akses Aplikasi \& Akun Uji Coba Demo

Buka peramban dan arahkan ke alamat URL lokal:

```text
http://localhost/TUBES\_PRK\_PEMWEB\_2026/kelompok/kelompok\_21/login.php
```

Kredensial demo yang disediakan pada berkas `03\_demo.sql`:

* **NIP / Username:** `198501152010011002` *(atau sesuai akun demo di seeder)*
* **Password:** `guru123`

\---

## Alur Request-Response (Architecture Flow)

```text
\[ Browser / Client ]
       │
       ▼ (1) HTTP Request / AJAX Fetch
\[ views/\*.php \& js/\*.js ]
       │
       ▼ (2) Asynchronous Payload (JSON)
\[ api/bootstrap.php \& includes/auth.php ] ──► (Validasi Sesi \& Setup Header JSON)
       │
       ▼ (3) Forward Request ke Endpoint Modul
\[ api/\*.php ] (guru.php, data.php, penilaian.php, monitoring.php, kalkulasi.php, klasifikasi.php, profil.php)
       │
       ▼ (4) Eksekusi Query PDO Prepared Statements
\[ config/database.php ──► MySQL Server ]
       │
       ▲ (5) Set Data Hasil Query
\[ api/\*.php ]
       │
       ▲ (6) Return HTTP Response (Content-Type: application/json)
\[ js/\*.js ] ──► (DOM Manipulation / Re-render UI tanpa Reload)
```

\---

## Kepatuhan Git Workflow \& Monorepo Isolasi

Tim Kelompok 21 menerapkan aturan ketat kepatuhan monorepo:

1. **Zero Contamination:** Seluruh pengerjaan hanya menyentuh path `kelompok/kelompok\_21/`.
2. **Git Check Validasi:**

```bash
   git diff --name-only upstream/main..staging
   ```

   *Output dipastikan 100% berawalan `kelompok/kelompok\_21/` agar lolos validasi otomatis robot CI/CD.*

3. **Branching Strategy:** Menggunakan integrasi cabang perantara `staging` untuk me-merge 6 cabang fitur sebelum diajukan melalui Final Pull Request ke branch `main` repositori pusat LabKom TI Unila.

