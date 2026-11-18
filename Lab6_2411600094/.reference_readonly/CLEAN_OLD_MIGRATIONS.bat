@echo off
REM Removes OLD migration files left over from earlier builds (they cause duplicate tables / wrong order).
REM Run this once from the project folder:  CLEAN_OLD_MIGRATIONS.bat
cd /d "%~dp0"
powershell -NoProfile -Command "$keep=@('0001_01_01_000000_create_users_table.php','0001_01_01_000001_create_cache_table.php','0001_01_01_000002_create_jobs_table.php','2026_09_15_141244_create_products_table.php','2026_09_15_141300_create_inventory_transactions_table.php','2026_11_15_100000_add_role_to_users_table.php','2026_11_15_100100_create_categories_table.php','2026_11_15_100200_create_brands_table.php','2026_11_16_130000_add_pending_changes_to_users_table.php','2026_11_17_000001_allow_deleting_users_with_transactions.php','2026_11_17_100000_add_is_active_to_products_table.php','2026_11_18_100000_add_adjustments_to_inventory_transactions_table.php','2026_11_18_100100_create_notifications_table.php','2026_11_18_100200_add_soft_deletes_to_products_table.php','2026_11_18_100300_add_quantity_tracking_to_inventory_transactions_table.php','2026_11_18_100400_create_audit_logs_table.php'); Get-ChildItem 'database\migrations\*.php' | Where-Object { $keep -notcontains $_.Name } | ForEach-Object { Write-Host ('Deleting ' + $_.Name); Remove-Item $_.FullName }"
echo Done. Now run: php artisan migrate:fresh --seed
pause
