# 📌 KerjaIn Backend (BE) - SkillMatch SMK Platform

**Bridge Your Skills: Gak Pakai Lama, Langsung KerjaIn Aja!**

**ID Tim:** CC26-PS092  
**Tema:** Future Ready Work & Economy  
**Program:** Coding Camp 2026 powered by DBS Foundation

## 📖 Deskripsi Singkat Proyek

KerjaIn adalah platform web berbasis **two-sided marketplace** yang menghubungkan siswa SMK dengan industri melalui pendekatan berbasis skill.

Backend ini dibangun menggunakan **Laravel 12** dan berfungsi sebagai REST API yang menangani:

* Authentication (Student & Professional)
* Interest & Skill Assessment
* AI Roadmap Generation
* Progress Tracking
* Portfolio Management
* Job Listing & Matching System

Tujuan utama sistem ini adalah:

* Membantu siswa memahami minat & skill
* Memberikan roadmap belajar berbasis AI
* Menghubungkan siswa dengan pekerjaan yang relevan berdasarkan skill

---

## ⚙️ Petunjuk Setup Environment

### 1. Clone Repository

```bash
git clone https://github.com/your-username/kerjain-backend.git
cd kerjain-backend
```

---

### 2. Install Dependencies

```bash
composer install
```

---

### 3. Setup Environment File

Copy file `.env.example` menjadi `.env`:

```bash
cp .env.example .env
```

---

### 4. Konfigurasi Environment

Edit file `.env` sesuai kebutuhan:

```env
APP_NAME=KerjaIn
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kerjain
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database

SANCTUM_STATEFUL_DOMAINS=localhost
```

---

### 5. Generate App Key

```bash
php artisan key:generate
```

---

### 6. Setup Database

Buat database baru, lalu jalankan:

```bash
php artisan migrate
```

Jika ada seeder:

```bash
php artisan db:seed
```

### 7. Jalankan Server

```bash
php artisan serve
```

Akses API di:

```
http://127.0.0.1:8000
```

---

## Tautan Model Machine Learning

Backend ini menggunakan AI untuk:

* Generate Learning Roadmap
* Generate Skill Tree

### 🔗 AI Provider

Saat ini menggunakan:

* **Groq API / LLM Provider (OpenAI-compatible)**

### 🔧 Konfigurasi

Tambahkan di `.env`:

```env
GROQ_API_KEY=your_api_key_here
```

### 📥 Catatan

* Tidak ada model lokal yang perlu di-download
* Semua proses AI dilakukan via API (external service)

---

##  Cara Menjalankan Aplikasi

### 1. Jalankan Server

```bash
php artisan serve
```

### 2. Testing API (Postman / Thunder Client)

---

## Tech Stack

### Backend

* Laravel 12
* Laravel Sanctum
* REST API Architecture

### Database

* MySQL
* Eloquent ORM

### AI Integration

* Groq API / LLM Provider
