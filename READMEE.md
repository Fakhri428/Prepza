# Prepza

## Demo Alur Service A -> Service B -> Service A

Jalankan simulasi tanpa mengirim perubahan nyata ke Service A:

`php artisan queue:process-orders --dry-run`

Output ringkas untuk demo sekarang berbentuk:

- `[DRY-RUN] Order {id} | {external_status_lama} -> {external_status_baru} | note: {ringkasan_note}`
- `[DRY-RUN] Trend | {title} | score: {score} | exp: {expires_at}`

Dengan format ini, alur analisis dan feedback balik ke Service A lebih mudah dibaca saat presentasi.

Jika Service A tidak bisa diakses, mode `--dry-run` akan otomatis fallback ke snapshot order lokal dari database.
Jika snapshot lokal kosong, command akan memakai data demo internal agar simulasi tetap berjalan.
