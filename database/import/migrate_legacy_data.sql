-- =============================================================================
-- Migrate data from `households_korat_legacy` → `households_korat`
-- Run AFTER:
--   1. mysqldump backup of current households_korat
--   2. mysql -u root -e "CREATE DATABASE households_korat_legacy"
--   3. mysql -u root households_korat_legacy < "households_korat (4).sql"
--   4. php artisan migrate (current DB ต้องรัน migration ทุกตัวแล้ว)
--
-- Usage:
--   mysql -u root households_korat < database/import/migrate_legacy_data.sql
-- =============================================================================

USE households_korat;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- 0) Clear tables we're about to import (in dependency order)
--    NOTE: This wipes existing data in these tables. Make sure you have a backup.
-- =============================================================================
TRUNCATE TABLE mushroom_followups;
TRUNCATE TABLE mushroom_allocations;
TRUNCATE TABLE mushroom_quota_districts;
TRUNCATE TABLE households;

-- For users — keep table structure but clear (FK is_approved/approved_by points to itself)
DELETE FROM users;
ALTER TABLE users AUTO_INCREMENT = 1;

-- =============================================================================
-- 1) USERS
--    is_superadmin → role mapping:
--      is_superadmin = 1 → role = 'superadmin'
--      is_superadmin = 0 → role = 'staff'  (admin can promote later)
--    All imported users are auto-approved (is_approved=1).
-- =============================================================================
INSERT INTO households_korat.users (
    id, name, email, email_verified_at, password, remember_token,
    role, is_approved, approved_at, approved_by, assigned_districts,
    created_at, updated_at
)
SELECT
    id, name, email, email_verified_at, password, remember_token,
    CASE WHEN is_superadmin = 1 THEN 'superadmin' ELSE 'staff' END AS role,
    1                AS is_approved,
    COALESCE(updated_at, NOW()) AS approved_at,
    NULL             AS approved_by,
    NULL             AS assigned_districts,
    created_at, updated_at
FROM households_korat_legacy.users;

-- =============================================================================
-- 2) HOUSEHOLDS  (just add NULL for new columns)
-- =============================================================================
INSERT INTO households_korat.households (
    id, recorded_by, household_code, prefix, first_name, last_name, head_full_name,
    age, dob, gender, education, health, members_count,
    main_occupation, secondary_occupation, income_month, expense_month,
    debt_amount, debt_source,
    survey_date, surveyor, has_mushroom_area, mushroom_area_size, water_source,
    has_electricity, distance_to_market_km, ever_agriculture, ever_mushroom,
    smartphone_use, social_media_use, interest_level, interest_reason,
    hours_per_week, initial_investment, group_member, group_readiness,
    poverty_score, motivation_score, experience_score, grouping_score,
    potential_score, area_score, market_score, total_score, priority, passed, completed,
    province, district, sub_district, village, house_number, moo_number, phone,
    created_at, updated_at,
    -- new columns from later migrations
    id_card, postal_code, is_active, note, deleted_at
)
SELECT
    id, recorded_by, household_code, prefix, first_name, last_name, head_full_name,
    age, dob, gender, education, health, members_count,
    main_occupation, secondary_occupation, income_month, expense_month,
    debt_amount, debt_source,
    survey_date, surveyor, has_mushroom_area, mushroom_area_size, water_source,
    has_electricity, distance_to_market_km, ever_agriculture, ever_mushroom,
    smartphone_use, social_media_use, interest_level, interest_reason,
    hours_per_week, initial_investment, group_member, group_readiness,
    poverty_score, motivation_score, experience_score, grouping_score,
    potential_score, area_score, market_score, total_score, priority, passed, completed,
    province, district, sub_district, village, house_number, moo_number, phone,
    created_at, updated_at,
    NULL, NULL, 1, NULL, NULL
FROM households_korat_legacy.households;

-- =============================================================================
-- 3) MUSHROOM QUOTAS  (rename round_no → round, active → is_active, add province)
-- =============================================================================
INSERT INTO households_korat.mushroom_quota_districts (
    id, district, province, year, round, quota_bags, is_active, note,
    created_at, updated_at
)
SELECT
    id, district,
    'นครราชสีมา' AS province,
    year, round_no AS round, quota_bags,
    active AS is_active, note,
    created_at, updated_at
FROM households_korat_legacy.mushroom_quota_districts;

-- =============================================================================
-- 4) MUSHROOM ALLOCATIONS
--    Skip rows where quota_id IS NULL (NEW schema makes it required)
--    Map:  remark → note,  status → 'pending' (default)
--    Drop: year, round_no, district, sub_district, village, source, created_by
-- =============================================================================
INSERT INTO households_korat.mushroom_allocations (
    id, quota_id, household_id, group_code, group_label,
    bags, allocated_date, status, note,
    created_at, updated_at
)
SELECT
    id, quota_id, household_id,
    NULL AS group_code,
    NULL AS group_label,
    bags, allocated_date,
    'pending' AS status,
    remark   AS note,
    created_at, updated_at
FROM households_korat_legacy.mushroom_allocations
WHERE quota_id IS NOT NULL;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- 5) Verify counts
-- =============================================================================
SELECT 'users'        AS tbl, COUNT(*) AS rows_imported FROM users
UNION ALL
SELECT 'households',          COUNT(*) FROM households
UNION ALL
SELECT 'mushroom_quotas',     COUNT(*) FROM mushroom_quota_districts
UNION ALL
SELECT 'mushroom_allocations',COUNT(*) FROM mushroom_allocations;
