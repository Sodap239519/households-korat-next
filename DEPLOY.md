# 🚀 Deploy บน Plesk ผ่าน Git

> สำหรับ shared hosting (Plesk) ที่ไม่มี SSH — ใช้กลยุทธ์ **"build-locally + production-branch"**

---

## 📐 ภาพรวม Strategy

```
Local (เครื่องคุณ)              GitHub                     Plesk
───────────────────             ─────────                  ───────
[main branch]                                              households-koratnopoor.nrru.ac.th/
   ↓                                                          ├── public/  ← document root
   ├── ทำ feature                                              ├── app/
   ├── git commit                                              ├── vendor/    ← มากับ git
   ├── git push origin main ──────────►                        ├── public/build/  ← มากับ git
   ↓                                                          └── .env      ← upload เอง
[deploy script]
   ├── composer install --no-dev
   ├── npm ci && npm run build
   ├── ลบ .env, vendor, build จาก .gitignore (ใน prod branch เท่านั้น)
   ├── git checkout production
   ├── git merge main
   ├── git add -f vendor public/build
   └── git push origin production ──────► Plesk pull อัตโนมัติ (Webhook)
```

---

## 🔧 ขั้น 0 — ครั้งแรกเตรียม Repo

### 0.1 Push commit ปัจจุบันขึ้น `main`

```bash
git status
git add -A
git commit -m "feat: production-ready release"
git checkout main          # ถ้ายังอยู่ feature branch
git merge feature/ui-revamp-and-marketing
git push origin main
```

### 0.2 สร้าง branch `production` (ครั้งเดียว)

```bash
# จาก main
git checkout -b production
# Override .gitignore ด้วยไฟล์ที่อนุญาต vendor + build
cp .gitignore.production .gitignore
git add .gitignore
git commit -m "chore(prod): include vendor and built assets"
git push -u origin production
```

### 0.3 สร้าง deploy script (เก็บไว้ที่ local)

ใช้ไฟล์ `deploy.sh` ที่ผมเตรียมไว้ — จะ build + commit + push อัตโนมัติทุกครั้ง

```bash
bash deploy.sh
```

---

## 🌐 ขั้น 1 — ตั้งค่า Plesk Git

### 1.1 เข้า Plesk Panel
- เข้า `https://your-plesk-host:8443`
- ไปที่ **Domains → households-koratnopoor.nrru.ac.th → Git**

### 1.2 Connect Repository
- คลิก **"Add Repository"**
- Mode: **"Pull updates from a remote Git repository"**
- Remote Git repository URL:
  ```
  https://github.com/Sodap239519/households-korat-next.git
  ```
- หรือ SSH (ถ้าจะใช้ deploy key):
  ```
  git@github.com:Sodap239519/households-korat-next.git
  ```
- **Server path**:
  ```
  /httpdocs
  ```
  หรือ path เต็มที่ Plesk ระบุ — ที่ที่อยู่เหนือ `public/` (Plesk จะใช้ `public/` เป็น document root)
- **Branch to track**: `production` ⚠️ **สำคัญ** — ห้าม `main`
- **Pull mode**: **Automatically deploy when commits are pushed**

### 1.3 Webhook (จะถูกสร้างอัตโนมัติ)
- Plesk จะให้ webhook URL — copy แล้วเอาไปใส่ที่ GitHub:
  - GitHub repo → **Settings → Webhooks → Add webhook**
  - Payload URL: (ที่ Plesk ให้มา)
  - Content type: `application/json`
  - Events: **Just the push event**

### 1.4 Deploy Key (กรณีใช้ SSH/private repo)
- Plesk → Git → "Generate SSH key" → copy public key
- GitHub repo → **Settings → Deploy keys → Add deploy key** → paste

---

## 🗄️ ขั้น 2 — ตั้งค่า Database บน Plesk

### 2.1 สร้าง Database
- Plesk → **Databases → Add Database**
- Name: `households_korat`
- User: สร้างใหม่ + จดรหัสผ่านไว้

### 2.2 Import ข้อมูล
- ใช้ **phpMyAdmin** ที่ Plesk → **Import**
- Upload ไฟล์:
  ```
  storage/backups/households_korat_before_import_20260508_141344.sql
  ```
  หรือ dump ที่ปรับใหม่:
  ```bash
  # ใน local:
  C:\xampp\mysql\bin\mysqldump -u root households_korat > prod-import.sql
  ```
  แล้ว import `prod-import.sql` ผ่าน phpMyAdmin

> ⚠️ ถ้าไฟล์ใหญ่กว่า 50 MB อาจต้อง split ก่อน import

---

## 📝 ขั้น 3 — สร้าง .env บน Server

ผ่าน **Plesk → File Manager** ที่ root ของ app (เหนือ `public/`):

1. **Upload** `.env.production.example`
2. **Rename** เป็น `.env`
3. **Edit** กรอก:
   - `APP_KEY` (generate ใน local: `php artisan key:generate --show` แล้วเอาค่ามาใส่)
   - `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` ตามที่ตั้งใน Plesk
   - ตรวจ `APP_URL`, `SESSION_DOMAIN`, `SANCTUM_STATEFUL_DOMAINS` ให้ตรงกับ domain จริง

---

## ✅ ขั้น 4 — Deploy ครั้งแรก

### Local:
```bash
bash deploy.sh
```

หรือทำมือเอง:
```bash
# 1. Build
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 2. Switch ไป production branch
git checkout production
git merge main --no-ff -m "deploy: from main"

# 3. Force-add vendor + build
git add -f vendor public/build
git commit -m "deploy: build artifacts $(date +%Y-%m-%d)" --allow-empty

# 4. Push → Plesk auto-pull
git push origin production
```

### บน Plesk:
- ดู Plesk Git → **Pull Updates** (จะ pull อัตโนมัติถ้าตั้ง webhook ไว้)
- หรือคลิก **"Deploy"** เอง

---

## 🔧 ขั้น 5 — Permissions (ครั้งเดียว)

ผ่าน Plesk File Manager → ตั้ง permission สำหรับ:

| โฟลเดอร์ | Permission |
|---------|------------|
| `storage/` | 775 (recursive) |
| `bootstrap/cache/` | 775 (recursive) |

ถ้า Plesk ไม่ให้แก้ — ติดต่อ admin host

---

## 🔁 ขั้น 6 — Deploy รอบถัดไป (workflow ปกติ)

```bash
# 1. ทำงานบน main เหมือนเดิม
git checkout main
# ... แก้โค้ด ...
git commit -m "feat: ..."
git push origin main

# 2. Deploy
bash deploy.sh
```

---

## ⚠️ เรื่องที่ต้องระวัง

1. **Migration ใหม่** — ถ้ามี migration ใหม่ที่เพิ่งสร้าง คุณต้อง **import เข้า prod DB เอง** ผ่าน phpMyAdmin (เพราะไม่มี CLI)
   - วิธีง่ายที่สุด: รันบน local ก่อน แล้ว `mysqldump` schema ไป import บน prod
   - หรือ: copy SQL จาก migration file แล้วรันใน phpMyAdmin

2. **Cache** — ถ้าโค้ดเปลี่ยนแล้วบน prod ไม่อัพเดต ลบไฟล์ใน:
   - `bootstrap/cache/config.php`
   - `bootstrap/cache/routes-v7.php`
   - ผ่าน Plesk File Manager

3. **`.env` ห้าม commit ลง git เด็ดขาด** — ตรวจ `.gitignore` ทุกครั้ง

4. **Storage symbolic link** — ถ้ามีอัพไฟล์ ต้องสร้าง link `public/storage` → `storage/app/public`
   - Plesk shared hosting อาจไม่ให้ทำ symlink — ใช้ File Manager copy folder แทน

5. **HTTPS / cookie** — ถ้า prod ใช้ https `SESSION_SECURE_COOKIE=true` (ตั้งใน .env แล้ว)

---

## 📞 ถ้า Deploy ไม่สำเร็จ

ตรวจตามลำดับ:
1. **Pull ใน Plesk Git สำเร็จไหม?** — ดู log ใน Plesk Git
2. **`.env` มีในเซิร์ฟเวอร์ไหม?**
3. **DB connect ได้ไหม?** — ลอง phpMyAdmin
4. **`storage/logs/laravel.log`** — ดู error
5. ถ้าได้ 500 — เปิด `APP_DEBUG=true` ชั่วคราวเพื่อดู error หน้าเว็บ
