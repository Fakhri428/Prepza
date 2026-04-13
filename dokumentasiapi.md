# Dokumentasi API Integrasi Layer 2

Dokumen ini menjelaskan kontrak integrasi antara Layer 1 (sistem Laravel ini) dan Layer 2.

## Ringkasan Arah Integrasi

1. Layer 2 mengambil data antrian dari Layer 1:
- GET /api/categories
- GET /api/menus
- GET /api/queue/orders
- GET /api/queue/board

2. Layer 2 mengirim update ke Layer 1:
- PATCH /api/queue/orders/{order}/external-update
- POST /api/queue/trends/update

## Konvensi Status

### external_status (sinkronisasi dari Layer 2)
Hanya 3 nilai yang dipakai:
- waiting
- processing
- done

Catatan kompatibilitas:
- Nilai lama not_set/received dinormalisasi ke waiting.

### orders.status (status order internal Layer 1)
Nilai yang mungkin:
- queued
- waiting
- processing
- done
- cancelled

## 1) API yang Layer 2 Ambil dari Layer 1

## GET /api/categories
Ambil daftar kategori aktif untuk pengelompokan menu di Layer 2.

### Contoh Request
GET /api/categories

### Contoh Response 200
```json
{
  "data": [
    {
      "id": 1,
      "name": "Makanan",
      "slug": "makanan",
      "description": "Menu makanan utama",
      "is_active": true,
      "menu_count": 12,
      "created_at": "2026-04-12T07:20:00.000000Z"
    },
    {
      "id": 2,
      "name": "Minuman",
      "slug": "minuman",
      "description": "Menu minuman",
      "is_active": true,
      "menu_count": 8,
      "created_at": "2026-04-12T07:20:30.000000Z"
    }
  ]
}
```

## GET /api/menus
Ambil daftar menu aktif untuk dipakai Layer 2 (pricing, nama menu, kategori, alias, dan gambar).

### Contoh Request
GET /api/menus

### Contoh Response 200
```json
{
  "data": [
    {
      "id": 10,
      "name": "Nasi Goreng",
      "slug": "nasi-goreng",
      "description": "Nasi goreng spesial",
      "image_path": "menus/nasi-goreng.jpg",
      "image_external_url": null,
      "image_url": "/storage/menus/nasi-goreng.jpg",
      "price": "20000.00",
      "is_active": true,
      "category_id": 1,
      "category": {
        "id": 1,
        "name": "Makanan",
        "slug": "makanan"
      },
      "aliases": [
        {
          "id": 44,
          "alias": "nasgor",
          "normalized_alias": "nasgor"
        }
      ]
    }
  ]
}
```

## GET /api/queue/orders
Ambil daftar order untuk diproses/sinkron.

### Query Params (opsional)
- status: daftar status dipisah koma.
  Contoh: status=queued,waiting,processing,done,cancelled

### Contoh Request
GET /api/queue/orders?status=queued,waiting,processing

### Contoh Response 200
```json
{
  "data": [
    {
      "id": 12,
      "order_code": "ORD-01JXYZ...",
      "customer_name": "Budi",
      "gender": "male",
      "status": "waiting",
      "external_status": "waiting",
      "external_note": "menunggu dipanggil",
      "external_updated_at": "2026-04-12T16:10:22+07:00",
      "total_amount": "42000.00",
      "created_at": "2026-04-12T16:00:01+07:00",
      "items": [
        {
          "id": 88,
          "order_id": 12,
          "item_name": "Nasi Goreng",
          "note": "tidak pedas",
          "qty": 1,
          "subtotal": "20000.00"
        }
      ],
      "queue": {
        "queue_number": 5,
        "order_id": 12,
        "status": "waiting"
      }
    }
  ]
}
```

## GET /api/queue/board
Ambil data ringkas untuk layar display antrian.

### Contoh Request
GET /api/queue/board

### Contoh Response 200
```json
{
  "data": {
    "current": {
      "order_id": 12,
      "queue_number": 5,
      "order_code": "ORD-01JXYZ...",
      "customer_name": "Budi",
      "status": "processing"
    },
    "upcoming": [
      {
        "order_id": 13,
        "queue_number": 6,
        "order_code": "ORD-01JABC...",
        "customer_name": "Siti"
      }
    ],
    "recent_done": [
      {
        "order_id": 11,
        "queue_number": 4,
        "done_at": "2026-04-12T15:55:10+07:00",
        "external_updated_at": "2026-04-12T15:55:10+07:00",
        "announce_key": "11:2026-04-12T15:55:10+07:00"
      }
    ],
    "trend": {
      "id": 3,
      "title": "Mie Pedas Lagi Naik",
      "image_url": "https://cdn.example.com/trend/mie.jpg",
      "caption": "Naik 32% minggu ini",
      "score": 84,
      "source_timestamp": "2026-04-12T15:40:00+07:00",
      "expires_at": "2026-04-12T22:00:00+07:00"
    },
    "server_time": "2026-04-12T16:12:00+07:00"
  }
}
```

## 2) API yang Layer 1 Terima dari Layer 2

## PATCH /api/queue/orders/{order}/external-update
Layer 2 mengirim perubahan status proses/selesai untuk order tertentu.

### Path Param
- order: id order (integer)

### Body JSON
- external_status: nullable, enum waiting|processing|done
- external_note: nullable, string max 500
- queue_status: nullable, enum waiting|processing|done|cancelled

Catatan perilaku:
- Jika queue_status tidak dikirim tapi external_status dikirim, sistem otomatis menyamakan queue_status = external_status.
- Jika queue_status = cancelled, queue internal disimpan sebagai done (sesuai implementasi saat ini).

### Contoh Request (proses)
```json
{
  "external_status": "processing",
  "external_note": "pesanan sedang dimasak"
}
```

### Contoh Request (selesai)
```json
{
  "external_status": "done",
  "external_note": "siap diambil"
}
```

### Contoh Response 200
```json
{
  "status": "ok",
  "message": "Simulasi input eksternal berhasil diproses.",
  "order": {
    "id": 12,
    "order_code": "ORD-01JXYZ...",
    "status": "done",
    "external_status": "done",
    "external_note": "siap diambil",
    "external_updated_at": "2026-04-12T16:15:30+07:00",
    "items": [
      {
        "id": 88,
        "order_id": 12,
        "item_name": "Nasi Goreng",
        "note": "tidak pedas",
        "qty": 1,
        "subtotal": "20000.00"
      }
    ],
    "queue": {
      "queue_number": 5,
      "order_id": 12,
      "status": "done"
    }
  },
  "updated_by": null
}
```

### Contoh Response 422 (validasi)
```json
{
  "message": "The external status field must be one of: waiting, processing, done.",
  "errors": {
    "external_status": [
      "The external status field must be one of: waiting, processing, done."
    ]
  }
}
```

### Contoh Response 404
```json
{
  "message": "No query results for model [App\\Models\\Order] 99999"
}
```

## POST /api/queue/trends/update
Layer 2 mengirim data tren makanan terbaru untuk ditampilkan di queue board.

### Body JSON
- title: required, string max 120
- image_url: required, url max 2048
- caption: nullable, string max 300
- score: nullable, integer 0..100
- source_timestamp: nullable, date
- expires_at: nullable, date (harus setelah waktu sekarang)
- is_active: nullable, boolean (default true)

### Contoh Request
```json
{
  "title": "Ayam Geprek Terlaris",
  "image_url": "https://cdn.example.com/trend/geprek.jpg",
  "caption": "Paling banyak dipesan jam makan siang",
  "score": 91,
  "source_timestamp": "2026-04-12T16:00:00+07:00",
  "expires_at": "2026-04-12T23:59:59+07:00",
  "is_active": true
}
```

### Contoh Response 200
```json
{
  "status": "ok",
  "message": "Tren makanan berhasil diperbarui.",
  "data": {
    "id": 4,
    "title": "Ayam Geprek Terlaris",
    "image_url": "https://cdn.example.com/trend/geprek.jpg",
    "caption": "Paling banyak dipesan jam makan siang",
    "score": 91,
    "source_timestamp": "2026-04-12T16:00:00+07:00",
    "expires_at": "2026-04-12T23:59:59+07:00",
    "is_active": true
  }
}
```

### Contoh Response 422 (validasi)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "image_url": [
      "The image url field must be a valid URL."
    ]
  }
}
```

## Matriks Respons Utama

- 200 OK: request sukses diproses.
- 403 Forbidden: endpoint internal yang dibatasi (contoh /start dan /finish dari Layer 1 UI).
- 404 Not Found: order id tidak ditemukan.
- 422 Unprocessable Entity: payload tidak valid atau tidak memenuhi aturan.
- 500 Internal Server Error: kegagalan tak terduga di server.

## Rekomendasi Kontrak Minimal untuk Layer 2

Untuk sinkron status, Layer 2 cukup kirim ke external-update:
```json
{
  "external_status": "waiting|processing|done",
  "external_note": "opsional"
}
```

Jika ingin kontrol penuh status order internal, tambahkan queue_status:
```json
{
  "external_status": "processing",
  "queue_status": "processing",
  "external_note": "sedang dimasak"
}
```

## Catatan Keamanan Saat Ini

Saat ini endpoint integrasi masih terbuka tanpa token khusus Layer 2.
Disarankan menambahkan signature atau API token agar hanya sistem Layer 2 resmi yang dapat mengakses endpoint update.
