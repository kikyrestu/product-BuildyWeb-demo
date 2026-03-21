# Docker Deploy Guide (VPS)

Panduan cepat deploy SI Laundry Demo ke VPS menggunakan Docker Compose.

## 1. Prasyarat VPS

- Docker Engine terpasang
- Docker Compose plugin terpasang (`docker compose version`)
- Port 8080 dibuka atau diproxy via Nginx/Caddy

## 2. Pull source code

```bash
git clone https://github.com/kikyrestu/product-BuildyWeb-demo.git
cd product-BuildyWeb-demo
```

## 3. Siapkan environment Docker

```bash
cp .env.docker.example .env.docker
```

Isi minimal variabel berikut di `.env.docker`:

- `APP_URL=https://domain-kamu.com`
- `APP_KEY=base64:...`
- `DB_URL=postgresql://...`
- `APP_PORT=8080` (ganti misalnya `8081` jika port 8080 sudah dipakai)
- `RUN_MIGRATIONS=true`

Jika belum punya `APP_KEY`, generate dulu:

```bash
docker compose run --rm app php artisan key:generate --show
```

## 4. Build dan jalankan service

```bash
docker compose up -d --build
```

Aplikasi akan tersedia di `http://IP_VPS:APP_PORT`.

## 5. Operasional dasar

Cek status service:

```bash
docker compose ps
```

Lihat log aplikasi:

```bash
docker compose logs -f app
```

Restart service:

```bash
docker compose restart app
```

Stop service:

```bash
docker compose down
```

## 6. Update aplikasi

```bash
git pull
docker compose up -d --build
```

## 7. Rekomendasi production

- Pasang reverse proxy Nginx/Caddy + SSL (Let's Encrypt)
- Set `APP_ENV=production` dan `APP_DEBUG=false`
- Setelah migrasi pertama sukses, opsional ubah `RUN_MIGRATIONS=false`
- Gunakan database managed (Neon/RDS) untuk stabilitas
