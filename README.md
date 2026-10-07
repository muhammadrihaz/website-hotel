# Daniela Hotel HMS

Internal hotel management system untuk **Urbanview Daniela Jambi by RedDoorz**. Phase 1 Foundation, Phase 2 Front Office, dan Phase 3 Operations sudah tersedia: autentikasi/RBAC, audit trail, master kamar, guest/reservation, Room Board, Check-In/Out, Housekeeping, Maintenance, Guest Request, Lost & Found, dan Shift Handover.

Blueprint arsitektur, database, ERD, workflow, permission matrix, roadmap, dan risk analysis tersedia di [`docs/architecture.md`](docs/architecture.md).

## Deployment Docker server

Stack produksi memakai PHP 8.3 + Apache dan MySQL 8.4. Konfigurasi ini disiapkan untuk server `jdacserver` dengan pemetaan berikut:

- aplikasi: `127.0.0.1:8084` pada host ke port container `80`;
- MySQL: tersedia pada `127.0.0.1:33063` untuk koneksi DBeaver melalui SSH Tunnel;
- Vite: tidak dijalankan di server karena aset frontend dibangun ke dalam image.

Port `8084` dipilih agar tidak bertabrakan dengan port `80`, `8080`, `8081`, `8082`, dan `8083` yang sudah digunakan container lain. Dari direktori proyek di server, siapkan environment produksi:

```bash
cp .env.docker.example .env.docker

HOTEL_APP_KEY="base64:$(openssl rand -base64 32)"
HOTEL_DB_PASSWORD="$(openssl rand -hex 24)"
HOTEL_ROOT_PASSWORD="$(openssl rand -hex 24)"

sed -i "s|^APP_KEY=.*|APP_KEY=${HOTEL_APP_KEY}|" .env.docker
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${HOTEL_DB_PASSWORD}|" .env.docker
sed -i "s|^MYSQL_PASSWORD=.*|MYSQL_PASSWORD=${HOTEL_DB_PASSWORD}|" .env.docker
sed -i "s|^MYSQL_ROOT_PASSWORD=.*|MYSQL_ROOT_PASSWORD=${HOTEL_ROOT_PASSWORD}|" .env.docker

unset HOTEL_APP_KEY HOTEL_DB_PASSWORD HOTEL_ROOT_PASSWORD
chmod 600 .env.docker
```

Konfigurasi bawaan memakai `APP_URL=https://sistemhotel.muhammadrihaz.my.id` untuk Cloudflare Tunnel. Published application Cloudflare harus diarahkan ke `http://127.0.0.1:8084`; TLS berakhir di Cloudflare dan Laravel mempercayai header forwarded HTTPS dari Tunnel. Lalu validasi dan jalankan stack:

```bash
docker compose --env-file .env.docker config --quiet
docker compose --env-file .env.docker up -d --build
docker compose --env-file .env.docker ps
```

Migration berjalan otomatis setiap container aplikasi start. Untuk instalasi pertama, jalankan seeder master data satu kali, lalu buat akun Owner melalui prompt interaktif:

```bash
docker compose --env-file .env.docker exec app php artisan db:seed --force
docker compose --env-file .env.docker exec app php artisan app:create-owner
```

Password Owner minimal 12 karakter dan inputnya tidak ditampilkan. Setelah itu cek health endpoint dan buka aplikasi:

```bash
curl -fsS http://127.0.0.1:8084/up
docker compose --env-file .env.docker logs --tail=100 app mysql
```

Aplikasi diakses melalui `https://sistemhotel.muhammadrihaz.my.id`. Port `8084` hanya terikat ke localhost agar origin tidak dapat dilewati langsung dari internet.

Untuk update berikutnya, upload/pull source terbaru lalu jalankan kembali `docker compose --env-file .env.docker up -d --build`. Jangan menjalankan seeder lagi kecuali memang ingin menyelaraskan master bawaan.

```bash
# Mengikuti log aplikasi
docker compose --env-file .env.docker logs -f app

# Menghentikan stack tanpa menghapus data
docker compose --env-file .env.docker down
```

Data disimpan dalam named volume `daniela-hotel_mysql_data` dan `daniela-hotel_app_storage`. Hindari `docker compose down -v` karena opsi `-v` menghapus kedua volume tersebut.

### Akses database melalui DBeaver

MySQL sengaja hanya dipublikasikan pada loopback server agar tidak terbuka langsung ke internet. Buat koneksi MySQL di DBeaver dengan pengaturan utama:

- Host: `127.0.0.1`
- Port: `33063`
- Database: `website_hotel`
- Username: nilai `DB_USERNAME` dari `.env.docker`
- Password: nilai `DB_PASSWORD` dari `.env.docker`

Aktifkan tab **SSH Tunnel** di DBeaver, lalu isi host SSH dengan IP/domain server, port `22`, username SSH server, dan private key atau password SSH Anda. Tidak perlu membuka port `33063` di UFW karena koneksi database melewati SSH.

## Requirements tanpa Docker

- PHP 8.2+ (PHP 8.3+ direkomendasikan)
- Composer 2
- Node.js 20+
- MySQL 8+/MariaDB kompatibel

Laravel 12 digunakan karena environment host proyek memakai PHP 8.2. Laravel 13 mensyaratkan PHP 8.3.

```powershell
composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Automated test memakai SQLite in-memory.

## Development account

Seeder hanya membuat akun development pada `APP_ENV=local` atau `testing`:

```dotenv
SEED_ADMIN_EMAIL=admin@daniela.test
SEED_ADMIN_PASSWORD=ChangeMe123!
```

Akun development memperoleh role `Administrator` dan `Manager` agar seluruh modul development dapat diuji. Seeder tidak membuat credential production; deployment produksi menggunakan perintah `app:create-owner` pada bagian sebelumnya.

## Phase 2 workflow

- Nomor guest dan reservation memakai sequence database yang dikunci, bukan `count() + 1`.
- Guest dengan telepon, email, atau identitas sama memunculkan warning dan memerlukan konfirmasi eksplisit.
- Overlap kamar diperiksa di UI dan diulang dalam transaction setelah row kamar dikunci.
- Check-In hanya menerima reservation `CONFIRMED` dengan kamar `READY` dan `AVAILABLE`.
- Check-Out memerlukan konfirmasi review folio, menutup stay, mengubah kamar menjadi `DIRTY`, dan membuat housekeeping task `PENDING`.

## Phase 3 workflow

- Housekeeping memakai 13 checklist mandatory. Kamar berubah `DIRTY -> CLEANING -> CLEAN -> READY`; status READY hanya diberikan setelah verifikasi supervisor/manager.
- Maintenance ticket memakai nomor `MT-YYYYMMDD-XXXX`. Ticket critical otomatis memblokir kamar sebagai `OUT_OF_ORDER`; blokir dilepas setelah resolusi diverifikasi dan tidak ada blocker lain.
- Guest Request memakai nomor `GR-YYYYMMDD-XXXX`, PIC/departemen, priority, status, dan response time.
- Lost & Found memakai nomor `LF-YYYYMMDD-XXXX`, private photo storage, lokasi penyimpanan, serta transisi claim/return yang diaudit.
- Shift Handover menyimpan item unfinished per room/reservation agar tetap terlihat oleh shift berikutnya.

## Quality checks

```powershell
php artisan test
vendor/bin/pint --test
npm run build
```

## Security notes

- Semua page dilindungi session auth dan active-user middleware.
- Permission dicek pada route serta Policy/Gate ketika Livewire melakukan mutasi.
- Action sensitif dicatat pada audit log append-only.
- Double booking dicegah oleh locked transaction; filter UI bukan sumber kebenaran.
- File foto Phase 3 disimpan pada private local disk dengan validasi MIME dan batas ukuran.
- Uang disimpan sebagai `DECIMAL(15,2)` dan kalkulasi inti memakai integer minor unit.
- `.env`, vendor, node modules, build output, serta cache lokal tidak disimpan di source control.
