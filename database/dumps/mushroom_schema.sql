-- =============================================================
-- Mushroom Quota Management System - Schema Reference
-- Database: MariaDB / MySQL
-- Project: Households Korat Next
-- =============================================================

-- households
CREATE TABLE IF NOT EXISTS `households` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `household_code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'รหัสครัวเรือน',
    `prefix`         VARCHAR(20) NULL COMMENT 'คำนำหน้า',
    `first_name`     VARCHAR(100) NOT NULL COMMENT 'ชื่อ',
    `last_name`      VARCHAR(100) NOT NULL COMMENT 'นามสกุล',
    `id_card`        VARCHAR(13) NULL UNIQUE COMMENT 'เลขบัตรประชาชน',
    `phone`          VARCHAR(20) NULL,
    `village`        VARCHAR(100) NULL COMMENT 'หมู่บ้าน',
    `sub_district`   VARCHAR(100) NULL COMMENT 'ตำบล',
    `district`       VARCHAR(100) NULL COMMENT 'อำเภอ',
    `province`       VARCHAR(100) NULL DEFAULT 'นครราชสีมา',
    `postal_code`    VARCHAR(10) NULL,
    `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
    `note`           TEXT NULL,
    `created_at`     TIMESTAMP NULL,
    `updated_at`     TIMESTAMP NULL,
    `deleted_at`     TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- mushroom_quota_districts
CREATE TABLE IF NOT EXISTS `mushroom_quota_districts` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `district`    VARCHAR(100) NOT NULL COMMENT 'ชื่ออำเภอ',
    `province`    VARCHAR(100) NOT NULL DEFAULT 'นครราชสีมา',
    `year`        SMALLINT UNSIGNED NOT NULL COMMENT 'ปี พ.ศ.',
    `round`       TINYINT UNSIGNED NOT NULL COMMENT 'รอบ',
    `quota_bags`  INT UNSIGNED NOT NULL COMMENT 'จำนวนถุงโควต้า',
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `note`        TEXT NULL,
    `created_at`  TIMESTAMP NULL,
    `updated_at`  TIMESTAMP NULL,
    UNIQUE KEY `uq_district_year_round` (`district`, `year`, `round`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- mushroom_allocations
CREATE TABLE IF NOT EXISTS `mushroom_allocations` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `quota_id`       BIGINT UNSIGNED NOT NULL,
    `household_id`   BIGINT UNSIGNED NOT NULL,
    `bags`           INT UNSIGNED NOT NULL COMMENT 'จำนวนถุงที่จัดสรร',
    `allocated_date` DATE NULL,
    `status`         VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending|active|completed',
    `note`           TEXT NULL,
    `created_at`     TIMESTAMP NULL,
    `updated_at`     TIMESTAMP NULL,
    CONSTRAINT `fk_ma_quota`     FOREIGN KEY (`quota_id`)     REFERENCES `mushroom_quota_districts`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_ma_household` FOREIGN KEY (`household_id`) REFERENCES `households`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- mushroom_followups
CREATE TABLE IF NOT EXISTS `mushroom_followups` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `allocation_id`    BIGINT UNSIGNED NOT NULL,
    `followup_round`   TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'รอบติดตาม',
    `followup_date`    DATE NULL,
    `harvest_kg`       DECIMAL(10,3) NULL DEFAULT 0 COMMENT 'ผลผลิต กก.',
    `sold_kg`          DECIMAL(10,3) NULL DEFAULT 0 COMMENT 'ขายได้ กก.',
    `price_per_kg`     DECIMAL(8,2) NULL COMMENT 'ราคา/กก.',
    `revenue`          DECIMAL(12,2) NULL COMMENT 'รายได้',
    `sale_channel`     VARCHAR(50) NULL COMMENT 'direct|online|enterprise|market',
    `sale_place`       VARCHAR(255) NULL,
    `enterprise_member` TINYINT(1) NOT NULL DEFAULT 0,
    `enterprise_name`  VARCHAR(255) NULL,
    `note`             TEXT NULL,
    `created_at`       TIMESTAMP NULL,
    `updated_at`       TIMESTAMP NULL,
    UNIQUE KEY `uq_allocation_round` (`allocation_id`, `followup_round`),
    CONSTRAINT `fk_mf_allocation` FOREIGN KEY (`allocation_id`) REFERENCES `mushroom_allocations`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Business Logic Constants
-- 1 ถุง = 2 ขีด (qid)
-- 1 ขีด = 0.1 กก.
-- ราคาพื้นฐาน 6 บาท/ขีด = 60 บาท/กก.
-- รายได้พื้นฐานต่อถุง = 12 บาท
-- =============================================================
