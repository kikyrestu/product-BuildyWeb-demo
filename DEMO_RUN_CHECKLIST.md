# SI Laundry Demo - Run Checklist

## 1. Persiapan
- Pastikan PostgreSQL aktif.
- Buat database: si_laundry_demo.
- Masuk ke folder project demo: source-code-normal/si-laundry-demo.

## 2. Install Dependensi
- Backend: composer install
- Frontend: npm install

## 3. Setup Environment
- Pastikan file .env sudah ada.
- Nilai default demo:
  - APP_NAME="SI Laundry Demo"
  - APP_URL=http://127.0.0.1:8001
  - DB_DATABASE=si_laundry_demo

## 4. Setup Aplikasi
- Jalankan: php artisan key:generate
- Jalankan: php artisan migrate --force

## 5. Jalankan Server Demo
- Backend: php artisan serve --host=127.0.0.1 --port=8001
- Frontend: npm run dev -- --host 127.0.0.1 --port 5174

## 6. URL Akses
- Aplikasi backend: http://127.0.0.1:8001
- Frontend Vite dev server: http://127.0.0.1:5174

## 7. Verifikasi Cepat
- Halaman login bisa dibuka.
- Login bisa pakai username atau email.
- Tracking board tampil.
- Ubah payment method berjalan.

## 8. Catatan Demo
- Project ini lisensi proprietary.
- Lihat LICENSE dan LICENSE.id.md untuk ketentuan penggunaan.
