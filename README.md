# ระบบโควต้าเห็ด นครราชสีมา (Households Korat Next)

ระบบบริหารจัดการโควต้าเห็ด การจัดสรร และติดตามผลสำหรับครัวเรือนในจังหวัดนครราชสีมา

## Tech Stack

- **Backend**: Laravel 13 + PHP 8.3
- **Frontend**: Vue 3 SPA + Tailwind CSS 4 + Vue Router 4
- **Auth**: Laravel Sanctum (cookie-based stateful)
- **Database**: MariaDB / MySQL

## Business Logic

| หน่วย | ความสัมพันธ์ |
|-------|-------------|
| 1 ถุง | = 2 ขีด (qid) |
| 1 ขีด | = 0.1 กก. |
| ราคา | 6 บาท/ขีด = 60 บาท/กก. |
| รายได้พื้นฐาน | 12 บาท/ถุง |

## Roles

| Role | คำอธิบาย |
|------|----------|
| `superadmin` | ผู้ดูแลระบบ เข้าถึงได้ทั้งหมด |
| `staff` | เจ้าหน้าที่ มองเห็นข้อมูลทุกอำเภอ |

## Setup

### 1. Clone & Install

```bash
git clone <repo>
cd households-korat-next
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### 2. Database

แก้ไขค่า DB ใน `.env`:
```env
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=households_korat
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate
php artisan db:seed
```

### 3. Sanctum CSRF

ตรวจสอบ `SANCTUM_STATEFUL_DOMAINS` ใน `.env` ให้ตรงกับ domain ที่ใช้งาน

### 4. Run

```bash
php artisan serve
npm run dev
```

เปิด browser ที่: `http://localhost:8000/app`

**ข้อมูลทดสอบ:**
| Email | Password | Role |
|-------|----------|------|
| admin@households-korat.local | password | superadmin |
| staff@households-korat.local | password | staff |

## API Endpoints

```
POST   /api/login
POST   /api/logout
GET    /api/user

GET|POST              /api/households
GET|PUT|DELETE        /api/households/{id}

GET|POST              /api/mushroom-quotas
GET|PUT|DELETE        /api/mushroom-quotas/{id}

GET|POST              /api/mushroom-allocations
GET|PUT|DELETE        /api/mushroom-allocations/{id}

GET|POST              /api/mushroom-followups
GET|PUT|DELETE        /api/mushroom-followups/{id}

GET /api/reports/dashboard
GET /api/reports/by-district
GET /api/reports/quota-vs-allocated
GET /api/reports/household-revenue
GET /api/reports/by-enterprise
GET /api/reports/years
GET /api/reports/districts
```

## Database Views

| View | คำอธิบาย |
|------|----------|
| `vw_mushroom_quota_baseline` | ข้อมูลพื้นฐานโควต้าและยอดจัดสรร |
| `vw_mushroom_quota_vs_allocated` | สัดส่วนการจัดสรรต่อโควต้า |
| `vw_mushroom_household_revenue` | รายได้รายครัวเรือน |
| `vw_mushroom_revenue_by_district` | รายได้รายอำเภอ |
| `vw_mushroom_revenue_by_enterprise` | รายได้รายวิสาหกิจ |

## SPA Structure

```
resources/js/spa/
├── main.js              # Vue app entry
├── App.vue
├── api/index.js         # Axios instance
├── composables/
│   └── useAuth.js       # Auth state
├── router/index.js      # Vue Router
└── views/
    ├── LoginView.vue
    ├── MainLayout.vue
    ├── DashboardView.vue
    ├── quotas/           # โควต้าอำเภอ
    ├── allocations/      # การจัดสรร
    ├── followups/        # ติดตามผล
    ├── reports/          # รายงาน
    └── components/       # Pagination etc.
```
