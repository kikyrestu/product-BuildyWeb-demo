# SI Laundry Demo - Security Checklist

Checklist ini fokus untuk demo online agar tidak mudah disalahgunakan.

## A. Wajib Sebelum Demo Dibuka
1. Set APP_ENV=production.
2. Set APP_DEBUG=false.
3. Gunakan APP_URL dengan HTTPS.
4. Gunakan APP_KEY yang unik (jangan pakai key lama dari mesin lain).
5. Ganti seluruh password default (DB, akun owner, email SMTP jika dipakai).
6. Pastikan file .env tidak ikut ter-upload ke publik.

## B. Hak Akses Server
1. Ownership folder aplikasi: user web server (contoh: www-data).
2. Permission hanya untuk kebutuhan runtime:
   - storage dan bootstrap/cache writable.
   - folder lain read-only sebisa mungkin.
3. Nonaktifkan directory listing di web server.
4. Root web server wajib ke folder public.

## C. Laravel Hardening
1. Jalankan dependency production only:
   - composer install --no-dev --optimize-autoloader
2. Cache config/route/view di production.
3. Pastikan SESSION_SECURE_COOKIE=true saat HTTPS.
4. Pastikan APP_DEBUG tetap false setelah deploy.
5. Hindari menampilkan stack trace ke user.

## D. Database & Credential
1. Buat database user khusus aplikasi (least privilege), jangan pakai superuser.
2. Batasi akses DB hanya dari host aplikasi.
3. Backup database terjadwal minimal harian.
4. Simpan backup di lokasi berbeda.

## E. Endpoint dan Akses Login
1. Gunakan password owner yang kuat (minimal 12 karakter, campuran).
2. Hindari membagikan akun owner ke banyak orang.
3. Buat akun kasir/admin terpisah sesuai role.
4. Pantau log kegagalan login secara rutin.

## F. Web Server & Network
1. Aktifkan HTTPS (Let's Encrypt).
2. Redirect HTTP ke HTTPS.
3. Batasi port terbuka hanya yang perlu (80/443/SSH).
4. Gunakan firewall aktif (UFW/iptables/security group).
5. Ubah port SSH default atau batasi IP admin.

## G. Proteksi Operasional Demo
1. Gunakan data dummy, jangan data pelanggan asli.
2. Jangan menaruh token API sensitif di repo.
3. Jangan kirim kredensial via chat/WA tanpa enkripsi.
4. Rotate token/password setelah sesi demo selesai.

## H. Monitoring Minimum
1. Pantau file log aplikasi setiap hari.
2. Pantau disk usage agar storage/log tidak penuh.
3. Aktifkan restart policy untuk PHP-FPM, Nginx, queue worker.

## I. Quick Verification
1. Cek halaman error tidak menampilkan stack trace.
2. Cek file .env tidak bisa diakses dari browser.
3. Cek upload bukti pembayaran tetap berjalan normal.
4. Cek login brute force dibatasi (rate limit aktif).

## J. Incident Response Ringkas
1. Jika dicurigai bocor: segera ganti password DB, owner, token, APP_KEY (jika perlu dengan prosedur yang benar).
2. Putuskan akses publik sementara jika ada indikasi kompromi.
3. Restore dari backup terakhir jika terjadi kerusakan data.
