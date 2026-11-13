# Laboratory Exercise 5 — Implementing MVC Architecture with Laravel
## RogieMart Hardware — Inventory Management System (construction materials)

Laravel 12 + Breeze (Blade stack) inventory system for a hardware business:
products, brands and categories, themed with the
Lab 4 pink Bootstrap 5 palette.

## What My the system has

- **Sign in** — Breeze Blade auth (login + password reset, no public registration); every
  inventory page sits behind `auth`.
- **Dashboard** — four stat cards (Total Products, Low Stock, Out of Stock, Inventory Value)
  above Low Stock Alerts, Recent Products, Brands and Categories.
- **Products** — full CRUD for name, SKU, category, brand, quantity, reorder level, unit price
  and supplier, with search (name / SKU / supplier), category + brand + stock-status filters
  and 10 rows per page. SKUs are stored in capitals.
- **Stock status** — Healthy / Low Stock / Out of Stock computed from `quantity` against
  `reorder_level`, shown as badges everywhere.
- **Brands & Categories** — CRUD from modals; each row shows its product count and units on hand.
- **Users** — administrators only (`admin` middleware): create users, grant or revoke admin,
  delete accounts. An admin cannot demote or delete themselves.
- **Look** — My Lab 4 pink palette  in `public/css/custom.css`.


## Part 1 — Environment setup

| Item | On my system |
| --- | --- |
| OS | Windows 11 (build 26200) with XAMPP |
| PHP | 8.2.12 (`pdo_mysql`, `mbstring`, `curl`, `fileinfo`, `openssl`) |
| Framework | Laravel 12.69.2 |
| Auth | Laravel Breeze, Blade stack |
| Database | MariaDB 10.4.32 (XAMPP MySQL), `lab5_db`, user `root`, no password |
| Front-end | Bootstrap 5.3 + Bootstrap Icons (CDN) and `public/css/custom.css` |
| Build | Vite/Tailwind come with Breeze but the layouts load CDN CSS, so they are unused |
| Tooling | Composer 2.10.3, Git 2.55.0, Node 24.21.0 / npm 11.19.0 |
| Server | `php artisan serve` → http://127.0.0.1:8000 |

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

Either run the seeder (see Part 8) or create the account by hand with Tinker
```bash
php artisan tinker
```

```php
>>> use App\Models\User;
>>> use Illuminate\Support\Facades\Hash;

>>> User::create([
        'name'              => 'RogieMart Admin',
        'email'             => 'rogielynamisola27@gmail.com',
        'password'          => Hash::make('password123'),
        'email_verified_at' => now(),
    ]);

>>> User::all();          // verify the account exists
>>> exit
```

Login with **rogielynamisola27@gmail.com** / **password123** — the same credentials
`DatabaseSeeder` seeds, so the seeder and this hand-made account produce the same
hash. The `User` model casts `password` to `hashed`, so `DatabaseSeeder` can pass the
plain string and the same hash is produced.

## Part 3 — Models, migrations & relationships

```bash
php artisan make:model Product -mcr
php artisan make:model Brand -mc
php artisan make:model Category -mc
```

| Table | Columns |
| --- | --- |
| `brands` | `id`, `name` (unique), `description`, timestamps |
| `categories` | `id`, `name` (unique), `description`, timestamps |
| `products` | `id`, `name`, `sku` (unique), `description`, `brand_id` FK, `category_id` FK, `quantity` (default 0), `reorder_level`, `unit_price`, `supplier`, timestamps |

`Product` → `belongsTo(Brand)`, `belongsTo(Category)`,
plus `isLowStock()` / `isOutOfStock()` and the `lowStock` / `outOfStock` query scopes.
`Brand` and `Category` → `hasMany(Product)`.

## Part 4 — Controllers, validation & custom messages

```bash
php artisan make:controller ProductController --resource --model=Product
php artisan make:controller DashboardController
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
- `products/show.blade.php` — product details with its stock status.
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
});
```

## Part 7 — Dashboard with real data

`DashboardController` supplies the Total Products, Low Stock, Out of Stock and Inventory
Value (`SUM(quantity * unit_price)`) cards, the Low Stock Alerts list, Recent Products,
and the brand and category tables (with per-row product-count badges
and a "N brands / N categories" badge).

## Part 8 — Seeders

`CategorySeeder` (Lumber, Hardware, Tools, Electrical, Plumbing, Paint) and
`BrandSeeder` (12 hardware brands — Stanley, Makita, Bosch, DeWalt, Boysen, Davies,
Philips, Firefly, Omni Power, Plytech, Marson, Dover) run **before**
`ProductSeeder`, which creates 23 products — a mix of in-stock, low-stock and out-of-stock
— each linked to a brand and a category and saved with its quantity on hand.

```bash
php artisan migrate:fresh --seed
```

## Part 9 — Theme & navigation

`public/css/custom.css` keeps the Lab 4 pink palette (`#FB6F92`, `#FF8FAB`, `#FFB3C6`,
`#FFE5EC`). Sidebar: **Dashboard / Products / Brands / Categories / Logout** (plus
**Users** for administrators).

```bash
php artisan serve
```

## Part 10 — Testing checklist

- Login as `rogielynamisola27@gmail.com` with `password123`; guests are bounced from every route.
- Product CRUD: create with an invalid SKU duplicate, missing brand, negative price →
  custom errors shown; blank SKU / unknown brand rejected server-side.
- Brand & Category: add via modal, duplicate name reopens the modal with the error and
  keeps the typed value, edit works in place, deleting a brand that still has products is
  blocked with a friendly message, and the "+" button on the product form returns to the
  form with the new option already selected and the half-typed draft restored.
- Dashboard: the four cards and the low-stock list match the seeded data.

## Part 11 — Publish to GitHub

```bash
git init
git add .
git commit -m "Lab 5: Inventory Management System (MVC with Laravel)"
git branch -M main
git remote add origin https://github.com/<username>/Lab5_2411600094.git
git push -u origin main
```
