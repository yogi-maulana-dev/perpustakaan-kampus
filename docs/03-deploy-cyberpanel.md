# Deploy ke VPS + CyberPanel — Perpustakaan UML

Stack: Laravel 13 · PHP 8.3 · **PostgreSQL** · Livewire/Volt · Vite (Tailwind) · Queue + Scheduler.

> ⚠️ **PENTING:** aplikasi memakai PostgreSQL (query `ilike`). CyberPanel default MySQL/MariaDB — jadi **install PostgreSQL terpisah**. (Kalau mau pakai MySQL, minta developer ubah `ilike`→`lower(...) like` dulu.)

---

## 1. Spesifikasi VPS (minimal)
- Ubuntu 22.04 / 20.04 (rekomendasi CyberPanel)
- RAM ≥ 2 GB (1 GB bisa tapi pas-pasan), 2 vCPU, 20 GB+ disk
- Akses root / sudo

## 2. Install CyberPanel
```bash
sudo su -
sh <(curl https://cyberpanel.net/install.sh || wget -O - https://cyberpanel.net/install.sh)
```
Pilih: CyberPanel + OpenLiteSpeed (gratis), aktifkan service yang ditawarkan (PowerDNS opsional). Catat password admin CyberPanel.

## 3. PHP 8.3 + ekstensi (lsphp83)
Lewat **CyberPanel → Server → Install Applications** atau via apt:
```bash
sudo apt update
sudo apt install -y lsphp83 lsphp83-common lsphp83-mysql lsphp83-pgsql \
  lsphp83-curl lsphp83-intl lsphp83-gd lsphp83-mbstring lsphp83-zip \
  lsphp83-bcmath lsphp83-opcache lsphp83-imagick
```
Ekstensi wajib: **pdo_pgsql, pgsql, gd, mbstring, zip, bcmath, curl, intl, fileinfo, openssl, ctype, exif**.
Binary PHP CyberPanel: `/usr/local/lsws/lsphp83/bin/php`

## 4. Composer
```bash
cd /usr/local/lsws/lsphp83/bin
sudo curl -sS https://getcomposer.org/installer | ./php
sudo mv composer.phar /usr/local/bin/composer
sudo ln -s /usr/local/lsws/lsphp83/bin/php /usr/local/bin/php   # agar `php` = 8.3
composer --version
```

## 5. PostgreSQL
```bash
sudo apt install -y postgresql postgresql-contrib
sudo -u postgres psql
```
Di prompt psql:
```sql
CREATE DATABASE perpustakaan;
CREATE USER perpus_app WITH ENCRYPTED PASSWORD 'PASSWORD_KUAT';
GRANT ALL PRIVILEGES ON DATABASE perpustakaan TO perpus_app;
\c perpustakaan
GRANT ALL ON SCHEMA public TO perpus_app;
\q
```

## 6. Buat Website di CyberPanel
- **Websites → Create Website**: isi domain (mis. perpustakaan.uml.ac.id), email, pilih **PHP 8.3**.
- Folder root otomatis: `/home/<domain>/public_html`.

## 7. Upload kode
Opsi A — Git (rekomendasi):
```bash
cd /home/<domain>
rm -rf public_html
git clone <repo-url> public_html
cd public_html
```
Opsi B — upload manual via File Manager / SFTP ke `public_html` (JANGAN sertakan folder `vendor`, `node_modules`).

## 8. Set Document Root ke /public
Laravel harus diakses dari folder `public`. Di **CyberPanel → Websites → Manage → vHost Conf (OpenLiteSpeed)**, ubah:
```
docRoot                   $VH_ROOT/public_html/public
```
Lalu **Restart OpenLiteSpeed** (CyberPanel → Server Status → Restart, atau `sudo systemctl restart lsws`).
Pastikan rewrite Laravel aktif (OLS membaca `public/.htaccess` bawaan Laravel).

## 9. Konfigurasi aplikasi
```bash
cd /home/<domain>/public_html
cp .env.example .env
composer install --no-dev --optimize-autoloader
php artisan key:generate
```
Edit `.env`:
```env
APP_NAME="Sistem Informasi Perpustakaan Kampus"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://perpustakaan.uml.ac.id
APP_LOCALE=id

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=perpustakaan
DB_USERNAME=perpus_app
DB_PASSWORD=PASSWORD_KUAT

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=local

# Mail boleh dikosongkan—bisa diatur dari menu Pengaturan admin.
MAIL_MAILER=smtp
MAIL_HOST=smtp...
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="no-reply@uml.ac.id"
MAIL_FROM_NAME="${APP_NAME}"
```

## 10. Build asset (Vite/Tailwind)
**Cara mudah (rekomendasi): build di laptop lalu upload folder `public/build`** — VPS tak perlu Node.
```bash
# di laptop
npm install && npm run build
# upload folder public/build ke server
```
Atau install Node di VPS:
```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo bash -
sudo apt install -y nodejs
cd /home/<domain>/public_html && npm ci && npm run build
```

## 11. Migrasi + data awal
```bash
php artisan migrate --force
php artisan db:seed --force            # data demo + role + setting (HAPUS akun demo setelah live)
php artisan storage:link
```
Buat akun admin asli & ganti password; hapus akun demo (admin@/budi@ dst) lewat menu Manajemen Staff / Data Anggota.

## 12. Optimasi production
```bash
php artisan optimize        # config+route+view+event cache
php artisan view:cache
```
Kalau update kode nanti: `php artisan optimize:clear` lalu `php artisan optimize` lagi.

## 13. Izin folder (penting!)
User web OpenLiteSpeed biasanya `nobody:nogroup`.
```bash
cd /home/<domain>/public_html
sudo chown -R nobody:nogroup storage bootstrap/cache public/storage
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

## 14. Scheduler (WAJIB untuk pengingat jatuh tempo)
`crontab -e`:
```cron
* * * * * cd /home/<domain>/public_html && /usr/local/lsws/lsphp83/bin/php artisan schedule:run >> /dev/null 2>&1
```
Ini menjalankan `loans:remind` tiap hari 08:00 (pengingat H-1 & tandai terlambat).

## 15. Queue worker (opsional tapi disarankan)
Notifikasi/email lebih responsif bila pakai queue. Buat service systemd:
```bash
sudo nano /etc/systemd/system/perpus-worker.service
```
```ini
[Unit]
Description=Perpustakaan Queue Worker
After=network.target

[Service]
User=nobody
Group=nogroup
Restart=always
WorkingDirectory=/home/<domain>/public_html
ExecStart=/usr/local/lsws/lsphp83/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```
```bash
sudo systemctl enable --now perpus-worker
```

## 16. SSL (HTTPS)
CyberPanel → **SSL → Manage SSL → Issue** (Let's Encrypt) untuk domain. Setelah aktif, set `APP_URL=https://...` & `SESSION_SECURE_COOKIE=true` (sudah).

## 17. Aset gambar
Upload (atau biarkan admin upload via panel) ke `public/images/` & `public/animations/`:
- `logo-uml.png`, `rektor.png`, `slider/*.jpg`, `animations/reading.json`
- Atau atur semua lewat menu **Pengaturan / Slider / Pengurus / E-Katalog** di admin.

## 18. Cek akhir
- Buka https://domain → landing tampil, asset (CSS) termuat.
- Login admin → Pengaturan → atur **SMTP**, logo, rektor, WA, dll.
- Tes **Lupa Password** → email masuk.
- `storage` bisa ditulis (coba upload cover buku).

---

### Ringkas yang HARUS disiapkan
1. VPS Ubuntu + CyberPanel
2. **PHP 8.3 (lsphp83)** + ekstensi (terutama `pgsql`, `gd`, `zip`, `intl`, `bcmath`)
3. **PostgreSQL** + database + user
4. **Composer**
5. Node (untuk build asset) — atau build di laptop
6. Cron (scheduler) + (opsional) queue worker systemd
7. SSL Let's Encrypt
8. Document root diarahkan ke `/public`
