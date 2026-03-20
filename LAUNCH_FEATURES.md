# SI Laundry - Launch Features

Dokumen ini merangkum fitur produk SI Laundry yang siap untuk fase launching.

## 1) Setup Awal Aplikasi
- Installer berbasis web untuk konfigurasi database.
- Test koneksi database sebelum setup dijalankan.
- Pembuatan akun owner awal saat setup.
- Installer lock otomatis setelah setup berhasil.
- Fitur reset installer oleh owner.
- Sanitasi error installer (tidak membocorkan detail sensitif).
- Rate limit pada test koneksi installer.

## 2) Autentikasi & Otorisasi
- Login 1 field menggunakan username atau email.
- Validasi kombinasi login + password.
- Rate limit login untuk mitigasi brute force.
- Redirect user berdasarkan role (owner/admin/kasir).
- Role-based access control untuk route dan halaman.

## 3) Manajemen User (Owner Only)
- Tambah user baru (owner/admin/kasir).
- Edit data user: nama, username, email, role, status aktif.
- Reset password user (opsional saat edit).
- Hapus user.
- Guard keamanan:
  - Owner tidak bisa hapus diri sendiri.
  - Owner terakhir tidak bisa diturunkan/dinonaktifkan/dihapus.
  - User yang punya histori transaksi tidak bisa dihapus (hanya dinonaktifkan).

## 4) Profil Akun
- Update profil user (nama, username, email).
- Validasi username: unik, lowercase, angka, underscore.
- Hint validasi format username di form.

## 5) POS & Checkout
- Input order via POS.
- Input customer saat checkout.
- Perhitungan otomatis total, diskon, final amount.
- Pembuatan nomor resi otomatis.
- Simpan detail item transaksi.
- Pencatatan log aktivitas transaksi.

## 6) Tracking Board
- Tracking order berbasis status (antrean -> proses cuci -> proses setrika -> selesai -> diambil).
- Perubahan status sesuai aturan transisi.
- Upload bukti foto saat order dinyatakan selesai.
- Update status pembayaran (unpaid/partial/paid).
- Set metode pembayaran per order.

## 7) Aturan Pembayaran Sebelum "Diambil"
- Status order tidak bisa lanjut ke "diambil" jika belum memilih metode pembayaran.
- Jika metode pembayaran transfer atau QRIS:
  - Wajib upload bukti pembayaran.
  - Tanpa bukti pembayaran, status tidak bisa lanjut ke "diambil".
- Bukti pembayaran dapat dilihat ulang dari tracking/detail order.

## 8) Koreksi Data Customer
- Edit data customer langsung dari halaman detail order.
- Lepas/hapus relasi customer dari order jika salah input.
- Semua perubahan tercatat pada transaction logs.
- Guard tambahan:
  - Untuk order status "diambil", kasir tidak bisa koreksi data customer.
  - Owner/Admin tetap bisa melakukan koreksi jika diperlukan.

## 9) Invoice & Dokumen
- Generate invoice per order.
- Download invoice PDF.
- Buka/copy link invoice.

## 10) Integrasi WhatsApp
- Kirim pesan WA customer dari tracking/detail order.
- Template WA bisa dikelola.
- Pesan dapat menyertakan link invoice dan link bukti foto.

## 11) Master Data & Pengaturan
- Kelola master layanan, durasi, addon.
- Kelola opsi pembayaran (cash/transfer/qris).
- Kelola profil laundry.
- Kelola template WA.

## 12) Laporan
- Laporan keuangan dengan filter tanggal.
- Ringkasan total order, gross, discount, net.
- Export laporan ke format spreadsheet.

## 13) Kualitas & Stabilitas
- Test suite backend lulus.
- Fitur-fitur inti sudah tervalidasi melalui test otomatis.

---

## Catatan Launch
- Versi ini sudah siap untuk soft launch operasional.
- Rekomendasi pasca-launch:
  - Monitoring log harian (aplikasi + installer).
  - Backup database terjadwal.
  - Review feedback kasir/owner pada minggu pertama operasional.
