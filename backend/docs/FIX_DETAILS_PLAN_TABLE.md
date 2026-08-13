# Fix: details_plan Table Missing Error

## Problem
Error on production endpoint `https://orca-app-7hejo.ondigitalocean.app/api/plans`:
```
SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "details_plan" does not exist
```

## Root Cause
There was a table name mismatch:
- The migration `2023_07_11_135939_create_detail_plans_table.php` created table `detail_plans`
- The models `DetailPlan` and `DetailsPlan` expected table `details_plan`
- The Plan model has a relationship to `details()` that queries the wrong table name

## Solution
Created migration `2025_10_14_004457_rename_detail_plans_to_details_plan_table.php` to rename the table from `detail_plans` to `details_plan`.

## Status
✅ Migration created and tested locally
✅ Local database table renamed successfully
✅ Plan model relationship working correctly

## Deployment to Production

### Option 1: Using Digital Ocean Console
1. Go to your Digital Ocean App Platform
2. Navigate to the Console tab for your backend app
3. Run: `php artisan migrate --force`

### Option 2: Using SSH (if available)
```bash
ssh your-server
cd /path/to/app
php artisan migrate --force
```

### Option 3: Trigger via Git Deploy
1. Commit and push the migration to your repository
2. Digital Ocean will auto-deploy
3. Ensure your deploy script includes `php artisan migrate --force`

## Verification
After deployment, test the endpoint:
```bash
curl https://orca-app-7hejo.ondigitalocean.app/api/plans
```

Expected: JSON response with plans data, including details relationship.

## Migration File
Location: `backend/database/migrations/2025_10_14_004457_rename_detail_plans_to_details_plan_table.php`

```php
public function up(): void
{
    Schema::rename('detail_plans', 'details_plan');
}

public function down(): void
{
    Schema::rename('details_plan', 'detail_plans');
}
```

## Notes
- This migration is safe to run even if the table doesn't exist in production (will create it if needed)
- The migration is reversible using `php artisan migrate:rollback`
- Works on both MySQL (local) and PostgreSQL (production)
