# SI Laundry

Sistem manajemen laundry berbasis web untuk operasional harian, meliputi:

- POS checkout
- Tracking status order
- Invoice web dan PDF
- Progress page pelanggan
- Pengaturan profil laundry
- Pengaturan metode pembayaran
- Laporan keuangan
- Installer awal database

## Menjalankan Project

### Rekomendasi Port Demo (Agar Tidak Bentrok)

- Backend demo: 127.0.0.1:8001
- Frontend demo: 127.0.0.1:5174

1. Install dependency backend:

	composer install

2. Install dependency frontend:

	npm install

3. Jalankan backend:

	php artisan serve --host=127.0.0.1 --port=8001

4. Jalankan frontend:

	npm run dev -- --host 127.0.0.1 --port 5174

## Catatan

- Pastikan konfigurasi database di file .env sudah sesuai.
- Untuk instance baru tanpa installer, jalankan migrasi database.

## Deploy Docker ke VPS

1. Copy environment docker:

	cp .env.docker.example .env.docker

2. Isi variabel penting di `.env.docker`:

	- `APP_URL`
	- `APP_KEY`
	- `DB_URL` (contoh Neon/PostgreSQL)

3. Generate APP_KEY jika belum ada:

	docker compose run --rm app php artisan key:generate --show

	Lalu tempel hasilnya ke `APP_KEY` pada `.env.docker`.

4. Jalankan container:

	docker compose up -d --build

5. Cek log jika perlu:

	docker compose logs -f app

Default app akan jalan di port `8080` host VPS.

## Lisensi

Project ini menggunakan lisensi proprietary.

- Lisensi utama (English): [LICENSE](LICENSE)
- Lisensi terjemahan (Bahasa Indonesia): [LICENSE.id.md](LICENSE.id.md)

Untuk penggunaan komersial di luar ketentuan lisensi (redistribusi, sublicensing, deployment multi-client), wajib persetujuan tertulis dari pemilik hak cipta.

## Deploy ke VPS

- Panduan Docker VPS: [DOCKER_DEPLOY_GUIDE.md](DOCKER_DEPLOY_GUIDE.md)
- Panduan deploy: [VPS_DEPLOY_GUIDE.md](VPS_DEPLOY_GUIDE.md)
- Contoh config Nginx: [nginx-si-laundry-demo.conf](nginx-si-laundry-demo.conf)
- Checklist security demo: [SECURITY_DEMO_CHECKLIST.md](SECURITY_DEMO_CHECKLIST.md)
- Paket upload siap pakai ada di root source-code-normal: `si-laundry-demo-vps.zip`
