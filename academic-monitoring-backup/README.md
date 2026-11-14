# Lab 7 – Student Portal Management with Automated Academic Monitoring and Student Support System

A Laravel 12 Student Portal that extends Lab 5 (MVC and CRUD) and Lab 6 (admin role separation and assignment tracking).
The lab's inventory example was adapted to a student portal:

| Lab 7 example | This project |

| Supplier | Student |
| Purchase Order | Academic Intervention Request |
| Purchase Order Approval | Intervention Approval |
| Reorder Point | Academic Backlog Threshold (5 pending assignments) |
| Replenishment | Student Academic Support |
| Supplier Performance | Academic Progress Monitoring |
| Inventory Dashboard | Academic Monitoring Dashboard |

## New features in Lab 7

### Academic Intervention management
- Create, view, edit and delete academic intervention requests for students
- Approval workflow: pending → approved / rejected → in progress → completed
- Soft deletes: deleted interventions go to an archive and can be restored
- Reference numbers and priority levels (Low, Medium, High, Critical)

### Authorization
- A Gate (`admin-only`) for general administrator checks
- `AcademicInterventionPolicy` with rules based on the request status
  (for example, only pending requests can be approved, rejected, edited or deleted)

### Automation
- Artisan command `students:check-academic-risk` finds students with heavy assignment backlogs
- The command is scheduled daily at 08:00 and its output is logged to
  `storage/logs/academic-risk-check.log`

### Email notifications (Laravel Mailables)
- New intervention created
- Intervention approved
- Academic risk alert for administrators

### Dashboard and performance
- Dashboard widgets (heavy backlog, pending, approved and total interventions) provided by a View Composer
- Statistics cached for 60 seconds with automatic cache clearing when data changes
- JSON API endpoints for academic statistics

### Search, filtering and exports
- Interventions can be searched by reference number, type or student name/ID
- Filters for status, priority, date range and archived records
- CSV and PDF exports that follow the active filters
- Academic Progress Report page with Chart.js charts

### Testing
- Feature tests for access control, creation, approval, soft delete and restore,
  search, CSV export, API output and cache refresh

## Requirements
- PHP 8.2 or higher
- Composer
- MySQL (XAMPP)
- Node.js is not required to run the app

## Installation
1. `composer install`
2. Copy `.env.example` to `.env`
3. `php artisan key:generate`
4. Set your database values in `.env`
5. `php artisan migrate`
6. `php artisan db:seed` (see `DatabaseSeeder` for the sample admin account)

## Mail settings
For testing, either keep `MAIL_MAILER=log` (emails appear in `storage/logs/laravel.log`)
or copy the SMTP settings from a Mailtrap inbox into `.env`, then run `php artisan config:clear`.
Set `APP_URL=http://127.0.0.1:8000` so email links are correct.

## Commands to run
| Command | Purpose |
| `php artisan serve` | Start the development server |
| `php artisan students:check-academic-risk` | Run the academic-risk check manually |
| `php artisan schedule:list` | Show scheduled tasks |
| `php artisan schedule:run` | Run the scheduler once |
| `php artisan test --filter=AcademicInterventionTest` | Run the Lab 7 tests |

## Main routes (administrator only)
| URL | Description |
|
| `/interventions` | Intervention list with search, filters and exports |
| `/reports/academic-progress` | Academic Progress Report |
| `/api/academic-dashboard/stats` | Dashboard statistics (JSON) |