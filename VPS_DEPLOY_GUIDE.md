# SI Laundry Demo - VPS Deploy Guide

## 1. Package Upload
Upload file zip project ke VPS, lalu extract ke folder release, contoh:
- /var/www/si-laundry-demo

## 2. Install Dependency di VPS
Jalankan di folder project:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

## 3. Environment
Copy environment:

```bash
cp .env.example .env
php artisan key:generate
```

Atur nilai minimal pada `.env`:
- APP_ENV=production
- APP_DEBUG=false
- APP_URL=https://demo-domain-kamu
- DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

## 4. Permission
```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

## 5. Database & Cache
```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 6. Queue Worker (Disarankan)
Jika pakai queue database:

```bash
php artisan queue:work --tries=3 --timeout=90
```

Gunakan Supervisor/systemd agar auto restart.

## 7. Scheduler (Disarankan)
Tambahkan cron:

```cron
* * * * * cd /var/www/si-laundry-demo && php artisan schedule:run >> /dev/null 2>&1
```

## 8. Nginx
Gunakan root ke folder `public`:
- /var/www/si-laundry-demo/public

Contoh config ada di file `nginx-si-laundry-demo.conf`.

## 9. Verifikasi Pasca Deploy
- Halaman login terbuka.
- Endpoint utama return 200.
- Upload bukti pembayaran berjalan.
- Status order flow berjalan sampai diambil.

## 10. Update Deploy
Untuk update versi baru:

```bash
git pull
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan optimize
```
