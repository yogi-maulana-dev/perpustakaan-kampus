# Tahap 7 — Deployment & Optimasi Production

**Sistem Informasi Perpustakaan Kampus** · Laravel 13 · PHP 8.3 · PostgreSQL

## 1. Persyaratan Server
- PHP 8.3+ dengan ekstensi: `pdo_pgsql`, `pgsql`, `mbstring`, `gd` (cover/KTM), `zip`, `bcmath`, `fileinfo`, `openssl`.
- PostgreSQL 14+.
- Node.js LTS (build asset), Composer 2.
- Web server Nginx/Apache + supervisor (queue) + cron (scheduler).

## 2. Konfigurasi `.env` Production
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://perpustakaan.kampus.ac.id

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=perpustakaan
DB_USERNAME=perpus_app          # user terbatas, BUKAN superuser
DB_PASSWORD=__rahasia_kuat__

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

QUEUE_CONNECTION=database        # notifikasi diproses queue
CACHE_STORE=database             # atau redis bila tersedia
FILESYSTEM_DISK=public

MAIL_MAILER=smtp                 # untuk notifikasi email (opsional)
```

## 3. Langkah Deploy
```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force   # idempotent
php artisan storage:link

# Optimasi (cache config/route/view/event)
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```
Untuk rollback cache saat debugging: `php artisan optimize:clear`.

## 4. Queue Worker (notifikasi)
Gunakan Supervisor:
```ini
[program:perpus-worker]
command=php /var/www/perpustakaan/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=1
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/perpustakaan/storage/logs/worker.log
```

## 5. Scheduler (pengingat jatuh tempo)
Tambahkan ke crontab `www-data`:
```cron
* * * * * cd /var/www/perpustakaan && php artisan schedule:run >> /dev/null 2>&1
```
Menjalankan `loans:remind` setiap hari 08:00 (pengingat H-1 + tandai terlambat).

## 6. Keamanan (yang sudah diterapkan)
- **Authentication**: Laravel Breeze + gating status akun (`pending/rejected/suspended` tidak bisa login) di `LoginForm` & middleware `EnsureAccountActive` (alias `active`).
- **Authorization**: Spatie Permission (4 role, 14 permission), middleware `role`/`permission`/`role_or_permission` per-route, `Gate::before` bypass Super Admin, **Policy** (`LoanPolicy`) untuk aksi peminjaman/pengembalian, `abort_unless` di tiap komponen Livewire.
- **Validasi**: rules pada Livewire + Form Request; semua input divalidasi.
- **CSRF**: aktif bawaan Laravel (form & Livewire).
- **Upload aman**: cover & KTM divalidasi mime/ukuran, disimpan via `Storage` (bukan path user), diakses lewat symlink `storage`.
- **Mass assignment**: `$fillable` eksplisit di tiap model.
- **Audit trail**: `activity_logs` mencatat approve/reject/return/fine.

### Hardening tambahan
- Set header keamanan di web server (HSTS, X-Frame-Options, X-Content-Type-Options).
- Batasi user DB hanya privilege yang diperlukan.
- Pertimbangkan `php artisan key:generate` sekali saat setup (jangan commit `APP_KEY`).

## 7. Nginx (ringkas)
```nginx
server {
    listen 80;
    server_name perpustakaan.kampus.ac.id;
    root /var/www/perpustakaan/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~ /\.(?!well-known).* { deny all; }
}
```
HTTPS: `sudo certbot --nginx -d perpustakaan.kampus.ac.id`.
