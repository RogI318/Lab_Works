# Changes (reference: lab5_2411600052)

Modified: routes/web.php, app/Models/User.php, app/Models/Product.php,
app/Http/Controllers/ProductController.php, app/Http/Controllers/DashboardController.php,
resources/views/layouts/app.blade.php (3 sidebar links), resources/views/dashboard.blade.php,
resources/views/products/_form.blade.php, database/seeders/BrandSeeder.php, DatabaseSeeder.php
Deleted: Student Portal leftovers, duplicate migration 2026_11_15_100300_create_products_table.php

# Run (inside the project folder)
composer dump-autoload
php artisan optimize:clear
php artisan migrate:fresh --seed     # wipes the database
php artisan serve

Login: test@example.com / password (admin)

# Test
1. Sidebar shows Brands, Categories, Users. Dashboard shows Stock by Brand / Stock by Category tables.
2. Brands + Categories: add, edit, view, delete. Duplicate name is rejected.
3. Rename a brand -> its products update. Delete a brand/category in use -> blocked with a message.
4. Products > Add: Category and Brand are dropdowns filled from those pages. Edit a seeded product and save.
5. Users (admin): add a staff user, edit, delete. You cannot delete yourself or change your own role.
6. Log in as the staff user: no Users link; /users returns 403.
7. Stock In / Stock Out on a product still works.
