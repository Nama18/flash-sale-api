# Flash Sale API

REST API untuk online store dengan fitur flash sale, dibuat menggunakan Laravel 13 + Filament v4.

## Tech Stack

-   **PHP** 8.3
-   **Laravel** 13
-   **MySQL**
-   **Filament** v4 (Admin Panel)

## Admin Panel

|              |                                                               |
| ------------ | ------------------------------------------------------------- |
| **URL**      | `https://flash-sale-api-production-b42f.up.railway.app/admin` |
| **Email**    | `admin@admin.com`                                             |
| **Password** | `password`                                                    |

## API Endpoints

`https://flash-sale-api-production-b42f.up.railway.app/`

### Products

| Method | Endpoint             | Deskripsi         |
| ------ | -------------------- | ----------------- |
| GET    | `/api/products`      | List semua produk |
| POST   | `/api/products`      | Buat produk baru  |
| GET    | `/api/products/{id}` | Detail produk     |
| PUT    | `/api/products/{id}` | Update produk     |
| DELETE | `/api/products/{id}` | Hapus produk      |

### Orders

| Method | Endpoint           | Deskripsi             |
| ------ | ------------------ | --------------------- |
| GET    | `/api/orders`      | List semua order      |
| POST   | `/api/orders`      | Buat order baru       |
| GET    | `/api/orders/{id}` | Detail order          |
| PUT    | `/api/orders/{id}` | Update order          |
| DELETE | `/api/orders/{id}` | Hapus order & restock |

## Contoh Request

### Buat Order

```json
POST /api/orders
{
    "customer_name": "Budi",
    "items": [
        { "product_id": 1, "quantity": 2 }
    ]
}
```

### Response Sukses

```json
{
    "success": true,
    "message": "Order berhasil dibuat.",
    "data": {
        "id": 1,
        "customer_name": "Budi",
        "total_price": 598000,
        "status": "confirmed",
        "items": [...]
    }
}
```

## Race Condition Handling

Sistem menggunakan **pessimistic locking** (`SELECT ... FOR UPDATE`) di dalam **DB transaction** untuk mencegah overselling saat flash sale.

## Menjalankan Test

```bash
php artisan test --filter=FlashSaleRaceConditionTest
```

## Setup Lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```
