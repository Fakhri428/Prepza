# Prepza Service B (Intelligence Layer)

Service B adalah layer intelligence untuk sistem antrean pintar. Service ini mengambil data order dari Service A, menganalisis prioritas antrean secara fair, lalu mengirim feedback status dan trend kembali ke Service A.

## Arsitektur Singkat

- Service A: execution layer (order, queue, update status)
- Service B (repo ini): intelligence layer (analisis prioritas, trend insight, sinkronisasi katalog)
- Database lokal Service B menyimpan snapshot order, item, trend, category, menu, dan alias untuk analisis/dashboard.

## Fitur Utama

- Sinkronisasi dari Service A:
	- categories
	- menus (+ aliases)
	- queue orders (termasuk cancelled)
- Analisis antrean multi-factor:
	- base complexity score (drink=1, fried=2, heavy=3)
	- aging fairness boost
	- batch optimization (menu sama di jendela waktu dekat)
	- deadline pressure boost
	- kitchen load awareness (normal/busy/overload)
	- deterministic micro-jitter untuk mengurangi tie rigid
- Feedback ke Service A:
	- external_status + external_note
	- trend updates
- Dashboard intelligence:
	- tabel analisis order
	- action manual processing/done
	- trend insight carousel per gender
- Mock Service A endpoint (opsional) untuk pengujian lokal/E2E.

## Struktur Komponen Penting

- Analisis antrean: [app/Services/OrderAnalyzer.php](app/Services/OrderAnalyzer.php)
- Orkestrasi proses sinkron + feedback: [app/Console/Commands/ProcessOrders.php](app/Console/Commands/ProcessOrders.php)
- Integrasi API Service A: [app/Services/ServiceAApiService.php](app/Services/ServiceAApiService.php)
- Trend generation: [app/Services/TrendInsightService.php](app/Services/TrendInsightService.php)
- Dashboard intelligence controller: [app/Http/Controllers/IntelligenceDashboardController.php](app/Http/Controllers/IntelligenceDashboardController.php)
- Web routes: [routes/web.php](routes/web.php)
- API mock routes: [routes/api-service-a-mock.php](routes/api-service-a-mock.php)
- Kontrak API detail: [dokumentasiapi.md](dokumentasiapi.md)

## Setup Lokal

1. Install dependency:

```bash
composer install
npm install
```

2. Siapkan environment:

```bash
cp .env.example .env
php artisan key:generate
```

3. Siapkan database:

```bash
php artisan migrate
```

4. Jalankan aplikasi:

```bash
php artisan serve
```

## Proses Utama Antrian

Command utama:

```bash
php artisan queue:process-orders
```

Dry-run:

```bash
php artisan queue:process-orders --dry-run
```

Perilaku dry-run:

- Tidak mengirim patch/post ke Service A.
- Jika Service A tidak tersedia, command memakai snapshot lokal atau demo data.

## Scheduler Otomatis

Cron expression di env:

- `PROCESS_ORDERS_CRON` (default per menit)

Aktifkan worker scheduler:

```bash
php artisan schedule:work
```

## Konfigurasi Service A (Core)

Variabel penting di `.env`:

- Koneksi/API:
	- `SERVICE_A_BASE_URL`
	- `SERVICE_A_TOKEN`
	- `SERVICE_A_TIMEOUT`
	- `SERVICE_A_FETCH_STATUSES`
- Operasional queue:
	- `SERVICE_A_ENFORCE_PRIORITY_SEQUENCE`
	- `SERVICE_A_MAX_PROCESSING_SLOTS`
	- `SERVICE_A_AUTO_DONE_ENABLED`
	- `SERVICE_A_AUTO_DONE_MINUTES`
- Threshold kitchen:
	- `SERVICE_A_BUSY_THRESHOLD`
	- `SERVICE_A_OVERLOAD_THRESHOLD`

## Tuning Model Multi-Factor

Parameter tuning tersedia di `.env` dan di-map melalui [config/services.php](config/services.php):

- Batch & SLA:
	- `SERVICE_A_BATCH_WINDOW_MINUTES`
	- `SERVICE_A_TARGET_SLA_MINUTES`
- Score shaping:
	- `SERVICE_A_COMPLEXITY_PENALTY_MULTIPLIER`
	- `SERVICE_A_BASE_SCORE_ANCHOR`
	- `SERVICE_A_PRIORITY_HIGH_THRESHOLD`
	- `SERVICE_A_PRIORITY_MEDIUM_THRESHOLD`
- Aging:
	- `SERVICE_A_AGING_BOOST_PER_5M`
	- `SERVICE_A_AGING_BOOST_CAP`
- Batch boost:
	- `SERVICE_A_BATCH_BOOST_PER_MATCH`
	- `SERVICE_A_BATCH_BOOST_CAP`
- Deadline boost:
	- `SERVICE_A_DEADLINE_BOOST_LATE`
	- `SERVICE_A_DEADLINE_BOOST_NEAR`
	- `SERVICE_A_DEADLINE_BOOST_NEAR_OVERLOAD`
	- `SERVICE_A_DEADLINE_BOOST_WARNING`
- Jitter:
	- `SERVICE_A_JITTER_STEPS`
	- `SERVICE_A_JITTER_SCALE`

## Trend Insight

- Trend dipisah per gender (perempuan/laki-laki).
- Carousel per gender menampilkan maksimal 2 menu unik.
- Jika item teratas merujuk menu yang sama (misal alias), sistem akan mencari item kedua yang berbeda.
- Sumber gambar trend diprioritaskan dari menu database:
	- `image_external_url` → `image_url` → `image_path` → placeholder.

## Mock Service A (Opsional)

Untuk aktifkan route mock, set:

- `ENABLE_SERVICE_A_MOCK_ROUTES=true`

Route mock berada di [routes/api-service-a-mock.php](routes/api-service-a-mock.php).

## Dashboard

- Home dashboard: [resources/views/dashboard.blade.php](resources/views/dashboard.blade.php)
- Intelligence dashboard: [resources/views/intelligence/dashboard.blade.php](resources/views/intelligence/dashboard.blade.php)

Data dashboard diambil dari tabel intelligence lokal, bukan dummy statis.

## Testing

Jalankan test utama:

```bash
php artisan test
```

Test yang paling relevan untuk domain ini:

- [tests/Unit/OrderAnalyzerTest.php](tests/Unit/OrderAnalyzerTest.php)
- [tests/Unit/TrendInsightServiceTest.php](tests/Unit/TrendInsightServiceTest.php)
- [tests/Feature/ProcessOrdersCommandTest.php](tests/Feature/ProcessOrdersCommandTest.php)
- [tests/Feature/IntelligenceDashboardIntegrationTest.php](tests/Feature/IntelligenceDashboardIntegrationTest.php)

## Catatan Operasional

- Setelah ubah `.env`, jalankan:

```bash
php artisan config:clear
```

- Jika integrasi gagal, cek:
	- `storage/logs/laravel.log`
	- nilai `SERVICE_A_BASE_URL`
	- status endpoint Service A

## Lisensi

Project ini menggunakan basis Laravel dan mengikuti lisensi MIT.
