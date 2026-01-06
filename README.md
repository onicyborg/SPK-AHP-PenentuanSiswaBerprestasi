# SPK AHP – Penentuan Siswa Berprestasi

<p align="center">
  <strong>Sistem Pendukung Keputusan untuk Menentukan Siswa Berprestasi menggunakan Metode AHP</strong><br>
  Dibangun dengan Laravel 10 (PHP ^8.1)
</p>

---

## 📋 Tentang Project

Aplikasi ini merupakan Sistem Pendukung Keputusan (SPK) yang menerapkan metode Analytic Hierarchy Process (AHP) untuk membantu menentukan siswa berprestasi berdasarkan beberapa kriteria penilaian. Administrator dapat mengelola kriteria, memasukkan data kandidat/siswa, melakukan perbandingan berpasangan, menghitung bobot prioritas, menguji konsistensi (CR), dan menghasilkan pemeringkatan akhir.

### ✨ Fitur Utama

- **Manajemen Kriteria & Subkriteria**
- **Input Data Siswa/Kandidat**
- **Perbandingan Berpasangan (Pairwise Comparison)**
- **Perhitungan Bobot Prioritas & Normalisasi**
- **Uji Konsistensi (Consistency Ratio/CR)**
- **Pemeringkatan Alternatif (Ranking)**
- **Laporan Ringkas**

### 🛠️ Teknologi yang Digunakan

- **Backend:** Laravel 10 (PHP ^8.1)
- **Database:** MySQL / MariaDB / PostgreSQL
- **Templating:** Blade
- **ORM:** Eloquent

---

## 🚀 Instalasi

Ikuti langkah-langkah berikut untuk menjalankan project ini dari GitHub di local environment.

### 📋 Prasyarat

- PHP >= 8.1
- Composer
- Database server (MySQL/MariaDB/PostgreSQL)
- Git

### 1️⃣ Clone Repository

```bash
git clone https://github.com/onicyborg/SPK-AHP-PenentuanSiswaBerprestasi.git
cd SPK-AHP-PenentuanSiswaBerprestasi
```

### 2️⃣ Install Dependencies

```bash
composer install
```

### 3️⃣ Setup Environment

Salin file environment dan generate app key.

- Windows (PowerShell):
```powershell
copy .env.example .env
php artisan key:generate
```

- macOS/Linux:
```bash
cp .env.example .env
php artisan key:generate
```

### 4️⃣ Konfigurasi Database

Edit file `.env` sesuai konfigurasi database Anda. Contoh (MySQL):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=spk_ahp
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 5️⃣ Buat Database

Buat database baru pada server database Anda, contoh (MySQL):

```sql
CREATE DATABASE spk_ahp;
```

*Perintah diatas dijalankan di phpMyAdmin.

### 6️⃣ Migrasi Database

```bash
php artisan migrate
```

### 7️⃣ Seeder Admin User

```bash
php artisan db:seed AdminUserSeeder
```

### 8️⃣ Setup Storage Link

```bash
php artisan storage:link
```

### 9️⃣ Jalankan Aplikasi

```bash
php artisan serve
```

Aplikasi akan berjalan di `http://localhost:8000`.

---

## 🤝 Kontribusi

Kontribusi sangat terbuka. Langkah umum:

1. Fork repository ini.
2. Buat branch baru: `git checkout -b feature/NamaFitur`
3. Commit perubahan: `git commit -m "feat: menambahkan fitur X"`
4. Push ke branch Anda: `git push origin feature/NamaFitur`
5. Buat Pull Request

---

## 📞 Kontak

Jika ada pertanyaan atau saran, silakan hubungi:

- **Nama:** Akhmad Fauzi
- **Email:** akhmadfauzy40@gmail.com
- **GitHub/LinkedIn:** https://github.com/onicyborg | https://www.linkedin.com/in/geats/

---

## 🙏 Acknowledgments

- Laravel Framework (https://laravel.com)
- MySQL (https://mysql.com)
- MariaDB (https://mariadb.org)
- PostgreSQL (https://postgresql.org)
