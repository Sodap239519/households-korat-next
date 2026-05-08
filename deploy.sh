#!/usr/bin/env bash
# =============================================================================
# deploy.sh  —  build locally + push production branch (สำหรับ Plesk shared hosting)
#
# พฤติกรรม:
#   1. ตรวจว่า working tree สะอาด
#   2. รัน composer install + npm run build
#   3. switch ไป production branch
#   4. merge จาก main
#   5. swap .gitignore เป็น .gitignore.production (ให้ commit vendor + build ได้)
#   6. force-add vendor + public/build แล้ว commit
#   7. push origin production → Plesk auto-pull
#   8. กลับมา main
#
# Usage:
#   bash deploy.sh
# =============================================================================

set -e   # หยุดทันทีถ้าคำสั่งใดผิด
set -o pipefail

# ----- Colors -----
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

step() { echo -e "${GREEN}▶${NC} $1"; }
warn() { echo -e "${YELLOW}⚠${NC} $1"; }
error() { echo -e "${RED}✗${NC} $1"; exit 1; }

# ----- 0. Sanity checks -----
step "ตรวจ working tree"
if [[ -n $(git status --porcelain) ]]; then
  error "มีไฟล์ที่ยัง commit ไม่หมด — โปรด commit ก่อนรัน deploy.sh"
fi

CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [[ "$CURRENT_BRANCH" != "main" ]]; then
  warn "ตอนนี้อยู่ branch '$CURRENT_BRANCH' (ไม่ใช่ main)"
  read -p "ยังต้องการ deploy ต่อ? (y/N) " ans
  [[ "$ans" =~ ^[Yy]$ ]] || error "ยกเลิก"
fi

# ----- 1. Build -----
step "Composer install (no-dev, optimized autoloader)"
composer install --no-dev --optimize-autoloader --no-interaction

step "NPM build"
npm ci
npm run build

# ----- 2. Switch ไป production branch -----
step "Switch ไป production branch"
if git show-ref --quiet refs/heads/production; then
  git checkout production
else
  git checkout -b production
fi

step "Merge $CURRENT_BRANCH → production"
git merge "$CURRENT_BRANCH" --no-ff -m "deploy: merge $CURRENT_BRANCH" || true

# ----- 3. Swap .gitignore -----
step "ใช้ .gitignore.production"
cp .gitignore .gitignore.dev.bak
cp .gitignore.production .gitignore

# ----- 4. Commit vendor + build -----
step "Force-add vendor + public/build"
git add .gitignore
git add -f vendor public/build composer.lock package-lock.json

if git diff --cached --quiet; then
  warn "ไม่มี artifact เปลี่ยน — skip commit"
else
  git commit -m "deploy: build artifacts $(date +%Y-%m-%d_%H%M%S)"
fi

# ----- 5. Push -----
step "Push origin production"
git push origin production

# ----- 6. Cleanup: กลับ main -----
step "Cleanup — กลับ main + restore .gitignore"
git checkout "$CURRENT_BRANCH"
# (.gitignore เดิมจะกลับมาเองเพราะ checkout)

step "✅ Deploy push เสร็จแล้ว"
echo ""
echo "ขั้นต่อไป:"
echo "  1. ดู Plesk → Git → ว่ามี webhook trigger pull ไหม"
echo "  2. ถ้าไม่ auto — เข้า Plesk panel แล้วคลิก 'Pull Updates'"
echo "  3. ทดสอบที่ https://households-koratnopoor.nrru.ac.th"
