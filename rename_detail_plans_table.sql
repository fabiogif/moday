-- SQL script to rename detail_plans table to details_plan
-- This fixes the error: relation "details_plan" does not exist
-- Run this on PostgreSQL production database

-- Check if detail_plans table exists and details_plan doesn't
DO $$
BEGIN
    -- Check if the old table exists and new one doesn't
    IF EXISTS (SELECT FROM pg_tables WHERE schemaname = 'public' AND tablename = 'detail_plans') 
       AND NOT EXISTS (SELECT FROM pg_tables WHERE schemaname = 'public' AND tablename = 'details_plan') THEN
        -- Rename the table
        ALTER TABLE detail_plans RENAME TO details_plan;
        RAISE NOTICE 'Table renamed from detail_plans to details_plan successfully';
    ELSIF EXISTS (SELECT FROM pg_tables WHERE schemaname = 'public' AND tablename = 'details_plan') THEN
        RAISE NOTICE 'Table details_plan already exists. No action needed.';
    ELSIF NOT EXISTS (SELECT FROM pg_tables WHERE schemaname = 'public' AND tablename = 'detail_plans') THEN
        RAISE NOTICE 'Table detail_plans does not exist. Creating details_plan table...';
        
        -- Create the table if neither exists
        CREATE TABLE details_plan (
            id BIGSERIAL PRIMARY KEY,
            name VARCHAR(191) NOT NULL,
            description VARCHAR(191) NOT NULL,
            plan_id BIGINT NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            CONSTRAINT details_plan_plan_id_foreign FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE
        );
        
        -- Create index on plan_id
        CREATE INDEX details_plan_plan_id_index ON details_plan(plan_id);
        
        RAISE NOTICE 'Table details_plan created successfully';
    END IF;
END $$;
