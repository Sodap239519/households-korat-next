<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop views and trigger if they exist
        DB::unprepared('DROP VIEW IF EXISTS vw_mushroom_revenue_by_enterprise');
        DB::unprepared('DROP VIEW IF EXISTS vw_mushroom_revenue_by_district');
        DB::unprepared('DROP VIEW IF EXISTS vw_mushroom_household_revenue');
        DB::unprepared('DROP VIEW IF EXISTS vw_mushroom_quota_vs_allocated');
        DB::unprepared('DROP VIEW IF EXISTS vw_mushroom_quota_baseline');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_ma_require_quota');

        // vw_mushroom_quota_baseline – ข้อมูลพื้นฐานโควต้าและยอดจัดสรร
        DB::unprepared('
            CREATE VIEW vw_mushroom_quota_baseline AS
            SELECT
                mqd.id              AS quota_id,
                mqd.district,
                mqd.province,
                mqd.year,
                mqd.round,
                mqd.quota_bags,
                mqd.quota_bags * 2           AS quota_qid,
                mqd.quota_bags * 0.2         AS quota_kg,
                mqd.quota_bags * 12.00       AS baseline_revenue,
                COALESCE(SUM(ma.bags), 0)    AS allocated_bags,
                mqd.quota_bags - COALESCE(SUM(ma.bags), 0) AS remaining_bags
            FROM mushroom_quota_districts mqd
            LEFT JOIN mushroom_allocations ma ON ma.quota_id = mqd.id
            GROUP BY mqd.id, mqd.district, mqd.province, mqd.year, mqd.round, mqd.quota_bags
        ');

        // vw_mushroom_quota_vs_allocated – สัดส่วนการจัดสรรต่อโควต้า
        DB::unprepared('
            CREATE VIEW vw_mushroom_quota_vs_allocated AS
            SELECT
                mqd.id              AS quota_id,
                mqd.district,
                mqd.year,
                mqd.round,
                mqd.quota_bags,
                COALESCE(SUM(ma.bags), 0)    AS total_allocated,
                mqd.quota_bags - COALESCE(SUM(ma.bags), 0) AS remaining,
                CASE WHEN mqd.quota_bags > 0
                     THEN ROUND(COALESCE(SUM(ma.bags), 0) * 100.0 / mqd.quota_bags, 2)
                     ELSE 0 END AS pct_allocated
            FROM mushroom_quota_districts mqd
            LEFT JOIN mushroom_allocations ma ON ma.quota_id = mqd.id
            GROUP BY mqd.id, mqd.district, mqd.year, mqd.round, mqd.quota_bags
        ');

        // vw_mushroom_household_revenue – รายได้รายครัวเรือน
        DB::unprepared('
            CREATE VIEW vw_mushroom_household_revenue AS
            SELECT
                h.id                AS household_id,
                h.household_code,
                CONCAT(COALESCE(h.prefix, \'\'), h.first_name, \' \', h.last_name) AS full_name,
                h.village,
                h.sub_district,
                h.district,
                h.province,
                COALESCE(SUM(ma.bags), 0)           AS total_bags_received,
                COALESCE(SUM(mf.harvest_kg), 0)     AS total_harvest_kg,
                COALESCE(SUM(mf.sold_kg), 0)        AS total_sold_kg,
                COALESCE(SUM(
                    CASE WHEN mf.revenue IS NOT NULL THEN mf.revenue
                         WHEN mf.sold_kg IS NOT NULL AND mf.price_per_kg IS NOT NULL
                              THEN mf.sold_kg * mf.price_per_kg
                         ELSE 0 END
                ), 0)                               AS total_revenue,
                COUNT(DISTINCT ma.id)               AS allocation_count,
                COUNT(DISTINCT mf.id)               AS followup_count
            FROM households h
            LEFT JOIN mushroom_allocations ma ON ma.household_id = h.id
            LEFT JOIN mushroom_followups mf ON mf.allocation_id = ma.id
            GROUP BY h.id, h.household_code, h.first_name, h.last_name, h.prefix,
                     h.village, h.sub_district, h.district, h.province
        ');

        // vw_mushroom_revenue_by_district – รายได้รายอำเภอ
        DB::unprepared('
            CREATE VIEW vw_mushroom_revenue_by_district AS
            SELECT
                h.district,
                h.province,
                COUNT(DISTINCT ma.household_id)     AS participating_households,
                COALESCE(SUM(ma.bags), 0)           AS total_allocated_bags,
                COALESCE(SUM(mf.harvest_kg), 0)     AS total_harvest_kg,
                COALESCE(SUM(mf.sold_kg), 0)        AS total_sold_kg,
                COALESCE(SUM(
                    CASE WHEN mf.revenue IS NOT NULL THEN mf.revenue
                         WHEN mf.sold_kg IS NOT NULL AND mf.price_per_kg IS NOT NULL
                              THEN mf.sold_kg * mf.price_per_kg
                         ELSE 0 END
                ), 0)                               AS total_revenue,
                CASE WHEN COALESCE(SUM(mf.sold_kg), 0) > 0
                     THEN ROUND(
                         COALESCE(SUM(
                             CASE WHEN mf.revenue IS NOT NULL THEN mf.revenue
                                  WHEN mf.sold_kg IS NOT NULL AND mf.price_per_kg IS NOT NULL
                                       THEN mf.sold_kg * mf.price_per_kg
                                  ELSE 0 END
                         ), 0) / SUM(mf.sold_kg), 2)
                     ELSE 0 END AS avg_price_per_kg
            FROM households h
            LEFT JOIN mushroom_allocations ma ON ma.household_id = h.id
            LEFT JOIN mushroom_followups mf ON mf.allocation_id = ma.id
            GROUP BY h.district, h.province
        ');

        // vw_mushroom_revenue_by_enterprise – รายได้รายวิสาหกิจ
        DB::unprepared('
            CREATE VIEW vw_mushroom_revenue_by_enterprise AS
            SELECT
                mf.enterprise_name,
                COUNT(DISTINCT ma.household_id)     AS households_count,
                COALESCE(SUM(mf.sold_kg), 0)        AS total_sold_kg,
                COALESCE(SUM(
                    CASE WHEN mf.revenue IS NOT NULL THEN mf.revenue
                         WHEN mf.sold_kg IS NOT NULL AND mf.price_per_kg IS NOT NULL
                              THEN mf.sold_kg * mf.price_per_kg
                         ELSE 0 END
                ), 0)                               AS total_revenue
            FROM mushroom_followups mf
            JOIN mushroom_allocations ma ON ma.id = mf.allocation_id
            WHERE mf.enterprise_member = 1 AND mf.enterprise_name IS NOT NULL
            GROUP BY mf.enterprise_name
        ');

        // Trigger: ตรวจสอบโควต้าก่อน INSERT mushroom_allocations
        DB::connection()->getPdo()->exec('
            CREATE TRIGGER trg_ma_require_quota
            BEFORE INSERT ON mushroom_allocations
            FOR EACH ROW
            BEGIN
                DECLARE v_remaining INT;
                SELECT (mqd.quota_bags - COALESCE(SUM(ma2.bags), 0))
                INTO v_remaining
                FROM mushroom_quota_districts mqd
                LEFT JOIN mushroom_allocations ma2 ON ma2.quota_id = mqd.id
                WHERE mqd.id = NEW.quota_id
                GROUP BY mqd.id, mqd.quota_bags;

                IF v_remaining IS NULL THEN
                    SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT = \'Quota not found\';
                END IF;

                IF NEW.bags > v_remaining THEN
                    SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT = \'Allocation exceeds remaining quota\';
                END IF;
            END
        ');
    }

    public function down(): void
    {
        try {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_ma_require_quota');
            DB::unprepared('DROP VIEW IF EXISTS vw_mushroom_revenue_by_enterprise');
            DB::unprepared('DROP VIEW IF EXISTS vw_mushroom_revenue_by_district');
            DB::unprepared('DROP VIEW IF EXISTS vw_mushroom_household_revenue');
            DB::unprepared('DROP VIEW IF EXISTS vw_mushroom_quota_vs_allocated');
            DB::unprepared('DROP VIEW IF EXISTS vw_mushroom_quota_baseline');
        } catch (\Exception $e) {
            // SQLite / test environments may not support views/triggers
        }
    }
};
