# Laboratory Exercise 5 — Implementing MVC Architecture with Laravel
## Student Portal · Campus Supplies Inventory (Inventory Management System)

Laravel 12 + Breeze (Blade stack) inventory system for a campus supplies centre:
products, brands, categories and an `inventory_transactions` ledger, themed with the
Lab 4 pink Bootstrap 5 palette.

---

## Part 1 — Environment setup

| Item | Value |
| --- | --- |
| Framework | Laravel 12 (PHP 8.2.12) |
| Auth | Laravel Breeze, Blade stack |
| Database | MySQL `lab5_db` (XAMPP, user `root`, no password) |
| Front-end | Bootstrap 5.3 + Bootstrap Icons + Chart.js (CDN), `public/css/custom.css` |

```bash
composer install
copy .env.example .env      # then set APP_URL, DB_DATABASE=lab5_db, DB_USERNAME=root, DB_PASSWORD=
php artisan key:generate
php artisan storage:link
```

In `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lab5_db
DB_USERNAME=root
DB_PASSWORD=
```

## Part 2 — Authentication & test user

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
```

"Remember Me" is removed from `resources/views/auth/login.blade.php` (Lab 4 login style),
and the login/register views use the pink theme.

### Step 2 — create the test user

Either run the seeder (see Part 8) or create the account by hand with Tinker, exactly as
the exercise sheet asks:

```bash
php artisan tinker
```

```php
>>> use App\Models\User;
>>> use Illuminate\Support\Facades\Hash;

>>> User::create([
        'name'              => 'Supply Centre Admin',
        'email'             => 'admin@example.com',
        'password'          => Hash::make('password123'),
        'email_verified_at' => now(),
    ]);

>>> User::all();          // verify the account exists
>>> exit
```

Login with **admin@example.com / password123**. The `User` model casts `password` to
`hashed`, so `DatabaseSeeder` can pass the plain string and the same hash is produced.

## Part 3 — Models, migrations & relationships

```bash
php artisan make:model Product -mcr
php artisan make:model Brand -mc
php artisan make:model Category -mc
php artisan make:model InventoryTransaction -m
```

| Table | Columns |
| --- | --- |
| `brands` | `id`, `name` (unique), `description`, timestamps |
| `categories` | `id`, `name` (unique), `description`, timestamps |
| `products` | `id`, `name`, `sku` (unique), `description`, `brand_id` FK, `category_id` FK, `quantity` (default 0), `reorder_level`, `unit_price`, `supplier`, timestamps |
| `inventory_transactions` | `id`, `product_id` FK, `type` (`in` / `out`), `quantity`, `reference`, `user_id` FK, timestamps |

`Product` → `belongsTo(Brand)`, `belongsTo(Category)`, `hasMany(InventoryTransaction)`,
plus `isLowStock()` / `isOutOfStock()` and the `lowStock` / `outOfStock` query scopes.
`Brand` and `Category` → `hasMany(Product)`.

`InventoryTransaction::booted()` syncs `products.quantity` on `created` / `deleting`, so
the ledger is the source of truth for stock on hand.

## Part 4 — Controllers, validation & custom messages

```bash
php artisan make:controller ProductController --resource --model=Product
php artisan make:controller DashboardController
php artisan make:controller ReportController
```

`ProductController` validates `name` (required, string, max), `sku` (required, unique —
`Rule::unique('products')->ignore($id)` on update), `description`, `brand_id` /
`category_id` (`exists:brands,id` / `exists:categories,id`), `quantity` & `reorder_level`
(integer, min 0), `unit_price` (numeric, min 0) and `supplier`, each with its own
`messages()` entry. `BrandController` / `CategoryController` provide index/store/update/
destroy driven by Bootstrap modals.

## Part 5 — Views

- `resources/views/layouts/master.blade.php` — pink navbar + sidebar, flash messages.
- `products/index.blade.php` — table with brand and category columns, low-stock badges,
  view / edit / delete buttons.
- `products/_form.blade.php` — **one partial shared by create and edit**, with CSRF,
  validation errors and the brand / category dropdowns (each with a "+" button that opens
  the brand/category modal so a new one can be added without leaving the form).
- `products/show.blade.php` — product details plus its related transactions.
- `partials/item-modal.blade.php` and `partials/item-delete-modal.blade.php` — the add /
  edit modal (a hidden `modal_key` input reopens the modal when validation fails) and the
  delete confirmation, reused by both brands and categories.

## Part 6 — Routes

```bash
php artisan route:list
```

`routes/web.php` keeps everything behind `auth` middleware:

```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('products', ProductController::class);
    Route::resource('brands', BrandController::class)->except(['create', 'edit', 'show']);
    Route::resource('categories', CategoryController::class)->except(['create', 'edit', 'show']);
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
});
```

## Part 7 — Dashboard with real data

`DashboardController` supplies Total Products, Low Stock, Out of Stock and Inventory
Value (`SUM(quantity * unit_price)`) cards, Recent Products, the Low Stock Alerts list,
stock-movement totals, the brand and category tables (with per-row product-count badges
and a "N brands / N categories" badge), and Chart.js charts.

## Part 8 — Seeders

`CategorySeeder` (Books & Modules, Stationery, Lab Equipment, IT Peripherals,
Uniforms & Merch, Sports & PE) and `BrandSeeder` (10 school-supply brands) run **before**
`ProductSeeder`, which creates 25 products — a mix of in-stock, low-stock and out-of-stock
— each linked to a brand and a category, and posts the matching `in` / `out` transactions.

```bash
php artisan migrate:fresh --seed
```

## Part 9 — Theme & navigation

`public/css/custom.css` keeps the Lab 4 pink palette (`#FB6F92`, `#FF8FAB`, `#FFB3C6`,
`#FFE5EC`). Sidebar: **Dashboard / Products / Brands / Categories / Reports / Logout**.

```bash
php artisan serve
```

## Part 10 — Testing checklist

- Login as `admin@example.com` / `password123`; guests are bounced from every route.
- Product CRUD: create with an invalid SKU duplicate, missing brand, negative price →
  custom errors shown; blank SKU / unknown brand rejected server-side.
- Brand & Category: add via modal, duplicate name reopens the modal with the error and
  keeps the typed value, edit works in place, deleting a brand that still has products is
  blocked with a friendly message, and the "+" button on the product form returns to the
  form with the new option already selected and the half-typed draft restored.
- Dashboard: cards, low-stock list and charts match the seeded data.
- Report page and CSV export.

## Part 11 — Publish to GitHub

```bash
git init
git add .
git commit -m "Lab 5: Inventory Management System (MVC with Laravel)"
git branch -M main
git remote add origin https://github.com/<username>/Lab5_2411600094.git
git push -u origin main
```
