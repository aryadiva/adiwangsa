# Panduan Pengguna & Indeks Fitur

Dashboard Operasi Konstruksi & Manajemen Back-Office — v0.2.0

Panduan ini mencakup semua yang bisa Anda lakukan di panel admin. Semua URL di bawah relatif terhadap alamat aplikasi, mis. `https://perusahaananda.example.com/admin/daily-reports`.

---

## Ringkasan Visual Aplikasi

Menu sidebar terbagi menjadi enam grup:

| Grup | Isi | Halaman |
|---|---|---|
| **Site Activity** | Laporan shift harian dari lapangan | [§2.1](#21-daily-reports) |
| **Operations** | Site, proyek, pekerja, pelacakan keterlambatan sub-pekerjaan | §2.2 – §2.5 |
| **Administration** | Data klien, akun pengguna, peran | §2.6 – §2.8 |
| **Finance** | Payroll dua mingguan | §2.9 |
| **Documents** | PDF yang dihasilkan dan unduhan | §2.10 |
| **Human Resources** | Absensi pekerja via kamera | §2.11 |

Pengalih bahasa (English / Bahasa Indonesia) tersedia di halaman login dan di menu pengguna (avatar kanan atas).

---

## 1. Autentikasi

### Login

- **Lokasi UI:** `/admin/login`
- **Tujuan:** Masuk dengan email dan kata sandi. Akses setiap menu otomatis dibatasi sesuai peran Anda.
- **Sistem Terhubung:** Kontrol akses berbasis peran (admin, Site Engineer, HRD); autentikasi sesi.

### Pengalih Bahasa

- **Lokasi UI:** Halaman login (area atas) dan Menu Pengguna (avatar kanan atas)
- **Tujuan:** Ganti tampilan antara English dan Bahasa Indonesia. Pilihan saat login diingat per pengguna; di halaman login berlaku untuk sesi browser saat ini.
- **Sistem Terhubung:** Layanan locale, bahasa notifikasi per penerima untuk email dan notifikasi dalam aplikasi.

---

## 2. Indeks Fitur

### 2.1 Daily Reports

- **Lokasi UI:** `/admin/daily-reports` — Sidebar > **Site Activity** > Daily Reports
- **Tujuan:** Laporan inti lapangan: catat apa yang terjadi di site selama shift (siang/malam), cuaca, kemajuan pekerjaan, alokasi tenaga kerja, dan foto progres sebelum/sesudah. Laporan melalui alur persetujuan sebelum resmi.
- **Sistem Terhubung:**
  - **Alur persetujuan:** Draft → Need Approval → Published, dengan cabang Revision Requested. Semua peralihan ditangani aksi khusus (`submitForApproval`, `requestRevision`, `resubmitForApproval`, `approveAndPublish`).
  - **Pembuatan PDF:** Saat dipublikasikan, PDF dibuat di latar belakang (GeneratePdfJob) dan muncul di Generated PDFs.
  - **Email klien:** Saat dipublikasikan, klien otomatis menerima PDF laporan via email (SendClientReportEmailJob). Hanya saat *published* — tidak pernah pada draft atau menunggu persetujuan.
  - **Mesin target harian:** Setiap laporan membawa target harian yang dihitung sistem; target yang tak tercapai dialihkan ke target hari berikutnya (deficit carry-forward) dan kekurangan berkelanjutan memberi notifikasi ke admin.
  - **Foto:** Tepat satu pasang sebelum/sesudah per shift, diambil dengan **kamera dalam aplikasi** (tanpa unggah berkas untuk Site Engineer).
  - **Notifikasi:** Pengiriman memberi tahu admin; persetujuan memberi tahu penulis; permintaan revisi memberi tahu penulis dengan catatan pemeriksa.

**Halaman utama:**
| Halaman | URL | Yang Anda lakukan |
|---|---|---|
| Daftar | `/admin/daily-reports` | Telusuri, filter per site/tanggal/status, buka laporan |
| Buat | `/admin/daily-reports/create` | Isi laporan shift baru |
| Edit | `/admin/daily-reports/{id}/edit` | Lanjutkan draft atau revisi laporan setelah permintaan revisi |

### 2.2 Sites

- **Lokasi UI:** `/admin/sites` — Sidebar > **Operations** > Sites
- **Tujuan:** Kelola lokasi kerja fisik. Setiap laporan harian, catatan absensi, dan milestone proyek terkait ke sebuah site.
- **Sistem Terhubung:** Pembatasan site — Site Engineer hanya melihat site tempat mereka ditugaskan; proteksi shift duplikat bekerja per site per tanggal per shift.

### 2.3 Projects

- **Lokasi UI:** `/admin/projects` — Sidebar > **Operations** > Projects
- **Tujuan:** Wadah utama setiap pekerjaan konstruksi: klien, engineer yang ditugaskan, site, zona waktu, anggaran, tanggal rencana, dan status. Setiap proyek memuat daftar milestone-nya.
- **Sistem Terhubung:**
  - **Milestone (bertingkat):** Buka proyek untuk mengelola milestone dengan persentase bobot — total bobot antar milestone harus 100%.
  - **Kaskade keterlambatan:** Keterlambatan sub-pekerjaan otomatis menggeser tanggal milestone tergantung dan tanggal akhir proyek (satu operasi atomik).
  - **Dokumen PDF:** PDF ringkasan tingkat proyek dibuat di latar belakang sesuai permintaan.
  - **Penanganan zona waktu:** Waktu disimpan UTC dan ditampilkan sesuai zona waktu proyek.

### 2.4 Workers

- **Lokasi UI:** `/admin/workers` — Sidebar > **Operations** > Workers
- **Tujuan:** Daftar pekerja — nama, identitas, dan data upah harian/jam yang dipakai absensi dan payroll.
- **Sistem Terhubung:** Worker Attendance (sumber kebenaran jam kerja) dan Payroll (upah dihitung dari absensi, bukan dari alokasi laporan).

### 2.5 Sub-Job Delays

- **Lokasi UI:** `/admin/sub-job-delay-events` — Sidebar > **Operations** > Sub-Job Delays
- **Tujuan:** Lacak keterlambatan sub-pekerjaan milestone dengan sistem lampu lalu lintas: **Red** (keterlambatan terdeteksi) → **Yellow** (rencana mitigasi dikirim) → **Green** (pulih). Keterlambatan baru setelah pemulihan memulai event Red baru.
- **Sistem Terhubung:**
  - **Deteksi otomatis:** Job malam (sub-job-delays:detect, 00:45) mendeteksi keterlambatan dan membuat event Red, menggeser tanggal milestone/proyek hilir dalam transaksi yang sama.
  - **Alur mitigasi:** Kirim rencana mitigasi pada event Red, lalu tandai pulih saat jadwal kembali normal.
  - **Validasi bobot:** Bobot sub-pekerjaan dan milestone divalidasi berjumlah 100% saat disimpan.

### 2.6 Clients

- **Lokasi UI:** `/admin/clients` — Sidebar > **Administration** > Clients
- **Tujuan:** Kelola direktori klien (kontak, detail perusahaan). Klien menerima PDF laporan yang dipublikasikan via email — tidak ada login klien di v0.2.0.
- **Sistem Terhubung:** Email laporan klien saat publish (email penerima dari data klien).

### 2.7 Users

- **Lokasi UI:** `/admin/users` — Sidebar > **Administration** > Users
- **Tujuan:** Kelola akun panel dan peran: **Admin** (akses penuh), **Site Engineer** (hanya laporan harian), **HRD** (hanya absensi).
- **Sistem Terhubung:** Sistem peran/izin (Filament Shield) — setiap daftar dan aksi difilter per peran.

### 2.8 Roles

- **Lokasi UI:** `/admin/shield/roles` — Sidebar > **Administration** > Roles
- **Tujuan:** Lihat dan sesuaikan set izin yang melekat pada setiap peran.
- **Sistem Terhubung:** Sistem izin yang sama dengan Users; perubahan berlaku pada login/refresh berikutnya.

### 2.9 Payroll Runs

- **Lokasi UI:** `/admin/payroll-runs` — Sidebar > **Finance** > Payroll Runs
- **Tujuan:** Buat dan setujui payroll dua mingguan (14 hari). Setiap run berisi satu baris per pekerja dengan upah reguler dan lembur, dihitung dari absensi.
- **Sistem Terhubung:**
  - **Pembuatan otomatis:** Job malam (payroll:generate, 01:15) membuat run saat siklus 14 hari berakhir.
  - **Sumber kebenaran absensi:** Upah reguler/lembur dihitung dari catatan Worker Attendance.
  - **Alur run:** Draft → For Review → Approved → Paid, via aksi khusus (`submitForReview`, `approve`, `markPaid`).
  - **PDF payroll/ringkasan:** Dibuat di latar belakang dan diarsipkan di Generated PDFs.

### 2.10 Generated PDFs

- **Lokasi UI:** `/admin/generated-documents` — Sidebar > **Documents** > Generated PDFs
- **Tujuan:** Perpustakaan tunggal semua PDF yang dihasilkan sistem: laporan harian yang dipublikasikan, ringkasan proyek, dokumen payroll. Unduh dokumen apa pun dengan tautan aman yang kedaluwarsa.
- **Sistem Terhubung:** Pembuatan PDF di latar belakang (job antrean), penyimpanan S3, endpoint unduhan dengan pembatasan laju (`/generated-documents/{id}/download`).

### 2.11 Worker Attendance

- **Lokasi UI:** `/admin/worker-attendances` — Sidebar > **Human Resources** > Worker Attendance
- **Tujuan:** Catat siapa yang hadir, kapan, dan berapa lama — diambil oleh HRD dengan **foto kamera live** saat check-in (tanpa unggah galeri).
- **Sistem Terhubung:**
  - **Penjaga duplikat:** Memblokir absensi ganda untuk pekerja/site/hari yang sama.
  - **Validasi foto:** Server memeriksa metadata pengambilan (kebaruan waktu) dan isi gambar.
  - **Payroll:** Absensi menjadi dasar perhitungan payroll dua mingguan.

---

## 3. Alur Kerja Umum ("Bagaimana Caranya...?")

### ...mengirim laporan harian?
1. Sidebar > **Site Activity** > Daily Reports > **New Daily Report** (`/admin/daily-reports/create`)
2. Pilih site, tanggal, dan shift (Day/Night).
3. Isi cuaca, deskripsi, alokasi tenaga kerja, dan ambil foto sebelum/sesudah dengan kamera live.
4. Simpan sebagai **Draft**, lalu klik **Submit for Approval**.
5. Admin meninjau: dipublikasikan (email klien terkirim otomatis) atau diminta revisi (Anda mendapat notifikasi; kirim ulang dari halaman edit laporan).

### ...mencatat absensi pekerja?
1. Sidebar > **Human Resources** > Worker Attendance > **New Worker Attendance** (`/admin/worker-attendances/create`)
2. Pilih pekerja dan site.
3. Ambil foto check-in dengan **kamera live** (pemilih berkas tidak tersedia untuk HRD).
4. Simpan — duplikat untuk pekerja/site/hari yang sama diblokir otomatis.

### ...menangani keterlambatan sub-pekerjaan?
1. Event **Red** muncul otomatis di Sidebar > **Operations** > Sub-Job Delays setelah deteksi malam.
2. Buka event, isi **Mitigation Plan**, dan kirim — status jadi **Yellow**.
3. Saat jadwal pulih, klik **Mark Recovered** — status jadi **Green** dan tanggal milestone disesuaikan.
4. Jika sub-pekerjaan yang sama terlambat lagi, event **Red baru** dibuat.

### ...mengelola milestone dan bobot proyek?
1. Buka proyek (`/admin/projects` > buka data) > tab **Milestones**.
2. Tambah/edit milestone; atur tanggal rencana dan persentase bobot.
3. Sistem memvalidasi total bobot milestone berjumlah **100%** sebelum disimpan (aturan sama untuk sub-pekerjaan di setiap milestone).
4. Notifikasi dikirim ke admin jika set milestone tetap belum lengkap.

### ...membuat payroll?
1. Tunggu run otomatis (atau picu via perintah payroll) — run **Draft** muncul di Finance > Payroll Runs.
2. Buka untuk meninjau upah reguler/lembur tiap pekerja.
3. Klik **Submit for Review**, lalu **Approve**, lalu **Mark Paid** setelah pencairan.
4. PDF payroll dibuat di latar belakang dan muncul di Documents > Generated PDFs.

### ...mengirim laporan ke klien via email?
Tidak perlu tindakan manual — mempublikasikan laporan harian otomatis mengirim email klien (PDF terlampir). Pastikan email kontak klien benar di Administration > Clients.

### ...mengunduh PDF yang dihasilkan?
1. Sidebar > **Documents** > Generated PDFs (`/admin/generated-documents`)
2. Temukan dokumen, klik **Download**. Tautan aman dan kedaluwarsa; unduh ulang jika tautan sudah lama.

### ...mengganti bahasa tampilan?
Menu pengguna (kanan atas) > **pengalih bahasa**, atau pengalih di halaman login. Email/notifikasi mengikuti bahasa tersimpan masing-masing penerima.

---

## 4. Tabel Referensi Cepat

| Fitur | URL / Jalur Menu | Fungsi Utama |
|---|---|---|
| Login | `/admin/login` | Masuk; pengalih EN/ID di halaman |
| Daily Reports | `/admin/daily-reports` | Laporan shift dengan foto, alur persetujuan, PDF otomatis + email klien |
| Mesin target harian | (otomatis) | Hitung target harian, alihkan defisit, peringatkan kekurangan berkelanjutan |
| Sites | `/admin/sites` | Kelola lokasi kerja; penjaga shift duplikat per site |
| Projects | `/admin/projects` | Data utama proyek, milestone, tanggal sadar zona waktu |
| Project Milestones | Halaman proyek > tab Milestones | Milestone berbobot (total 100%), sub-pekerjaan |
| Workers | `/admin/workers` | Daftar pekerja dan tarif |
| Sub-Job Delays | `/admin/sub-job-delay-events` | Pelacakan keterlambatan Red→Yellow→Green, rencana mitigasi, kaskade tanggal |
| Clients | `/admin/clients` | Direktori klien; penerima email laporan terpublikasi |
| Users | `/admin/users` | Akun panel dan peran |
| Roles | `/admin/shield/roles` | Manajemen izin |
| Payroll Runs | `/admin/payroll-runs` | Payroll dua mingguan; dibuat otomatis, review→approve→paid |
| Generated PDFs | `/admin/generated-documents` | Perpustakaan PDF dengan unduhan aman kedaluwarsa |
| Worker Attendance | `/admin/worker-attendances` | Catatan check-in khusus kamera; sumber kebenaran payroll |
| Pengalih bahasa | Menu pengguna / halaman login | Bahasa tampilan EN ↔ ID |
| Otomatisasi malam | (otomatis, 00:30–01:15) | Hitung ulang target → deteksi keterlambatan → buat payroll |

---

*Dihasilkan dari peta arsitektur Graphify (graphify-out/) — 4.738 node / 13.404 edge mencakup 43 berkas kode aplikasi.*