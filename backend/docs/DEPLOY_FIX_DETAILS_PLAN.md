# 🚀 Deploy Fix: details_plan Table Missing

## ⚠️ Error
```
SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "details_plan" does not exist
LINE 1: select * from "details_plan" where "details_plan"."plan_id" ...
```

## 🎯 Solution
The table `detail_plans` needs to be renamed to `details_plan` to match the model configuration.

---

## 📋 Deployment Options

### ✅ Option 1: Run Migration via Console (RECOMMENDED)

#### Step 1: Access Digital Ocean Console
1. Go to https://cloud.digitalocean.com/
2. Navigate to Apps → Your Backend App (`orca-app-7hejo`)
3. Click the "Console" tab
4. Click "Open Console"

#### Step 2: Run Migration Command
```bash
cd /workspace
php artisan migrate --force
```

#### Step 3: Verify Success
```bash
php artisan migrate:status | grep rename_detail_plans
```

You should see:
```
2025_10_14_004457_rename_detail_plans_to_details_plan_table ... [X] Ran
```

#### Step 4: Test the API
```bash
curl https://orca-app-7hejo.ondigitalocean.app/api/plans
```

Expected: JSON response with plans data (no error).

---

### ✅ Option 2: SQL Direct (If Console Unavailable)

#### Step 1: Access Database
1. Go to Digital Ocean → Databases
2. Select your PostgreSQL database
3. Click "Connection Details"
4. Use the connection string to connect via `psql` or database client

#### Step 2: Run SQL Script
Execute the contents of `rename_detail_plans_table.sql`:

```sql
ALTER TABLE detail_plans RENAME TO details_plan;
```

Or if the table doesn't exist yet, create it:
```sql
CREATE TABLE details_plan (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    description VARCHAR(191) NOT NULL,
    plan_id BIGINT NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT details_plan_plan_id_foreign FOREIGN KEY (plan_id) 
        REFERENCES plans(id) ON DELETE CASCADE
);

CREATE INDEX details_plan_plan_id_index ON details_plan(plan_id);
```

#### Step 3: Verify Table Exists
```sql
SELECT table_name FROM information_schema.tables 
WHERE table_schema = 'public' AND table_name = 'details_plan';
```

---

### ✅ Option 3: Auto-Deploy with Git Push

#### Step 1: Update Deploy Hook (Already Done)
The `.platform.app.yaml` needs to include migrations in the deploy hook.

#### Step 2: Commit and Push
```bash
git add .
git commit -m "fix: rename detail_plans to details_plan table"
git push origin main
```

#### Step 3: Wait for Deployment
Digital Ocean will automatically:
1. Pull the latest code
2. Run the build process
3. Execute migrations via the deploy hook
4. Restart the app

---

## 🔍 Verification Steps

### Check Migration Status
```bash
php artisan migrate:status
```

### Check Table Exists
```bash
php artisan tinker
>>> \DB::select("SELECT table_name FROM information_schema.tables WHERE table_name = 'details_plan'");
```

### Test API Endpoint
```bash
curl -X GET https://orca-app-7hejo.ondigitalocean.app/api/plans \
  -H "Accept: application/json"
```

### Test with Authentication (if required)
```bash
curl -X GET https://orca-app-7hejo.ondigitalocean.app/api/plans \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 📝 Migration File
**Location:** `backend/database/migrations/2025_10_14_004457_rename_detail_plans_to_details_plan_table.php`

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

---

## ⚡ Quick Command Reference

```bash
# Check current directory
pwd

# Run migrations
php artisan migrate --force

# Check migration status
php artisan migrate:status

# Test database connection
php artisan tinker --execute="echo \DB::connection()->getDatabaseName();"

# Clear all caches
php artisan optimize:clear

# Restart the application
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

---

## 🐛 Troubleshooting

### Error: "could not find driver"
**Solution:** Ensure PDO PostgreSQL extension is installed
```bash
php -m | grep pdo_pgsql
```

### Error: "permission denied"
**Solution:** Check database user permissions
```sql
GRANT ALL PRIVILEGES ON TABLE details_plan TO your_db_user;
```

### Error: "table already exists"
**Solution:** The fix was already applied. Just clear cache:
```bash
php artisan optimize:clear
```

---

## 📊 Status

- ✅ Migration created: `2025_10_14_004457_rename_detail_plans_to_details_plan_table.php`
- ✅ Tested locally on MySQL: Success
- ✅ SQL script created: `rename_detail_plans_table.sql`
- ⏳ Production deployment: **PENDING YOUR ACTION**

---

## 🎉 Expected Result

After successful deployment:
- ✅ Table `details_plan` exists in production database
- ✅ `/api/plans` endpoint returns data without errors
- ✅ Plan details relationship works correctly
- ✅ No more "relation details_plan does not exist" errors
