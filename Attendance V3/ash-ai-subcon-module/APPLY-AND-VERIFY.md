# APPLY & VERIFY — ASH AI Subcon Module (S1)

Real Laravel backend for subcontractor sewing: POs with per-piece rates →
receiving + QA (repair/scrap) → weekly Mon–Sun payout → Lalamove logistics with
true cost/piece → evidence photos. Idempotent finance posting included.

Stack target: Laravel 12 · PHP 8.5 · Sanctum · Spatie permission · MySQL (prod) /
SQLite (Pest). API base `/api/v2`. Standalone-first with a one-line merge hook.

---

## 1. Copy files into `ash-ai-backend` (Windows PowerShell)
From the unzipped folder, inside your backend repo root:
```powershell
Copy-Item -Recurse -Force .\app\*       .\app\
Copy-Item -Recurse -Force .\database\*  .\database\
Copy-Item -Recurse -Force .\routes\*    .\routes\
Copy-Item -Recurse -Force .\tests\*     .\tests\
```
Paths already mirror Laravel, so nothing is overwritten.

## 2. Register routes (BUG-010 guard)
Paste the inner block of `routes/subcon.php` into your existing `/api/v2` group
**above** the `portal/{role}` wildcard, adding the six `use ...` imports — OR
`require base_path('routes/subcon.php');` in `bootstrap/app.php` (the file
self-applies the `api/v2` prefix + `api` middleware group, like the attendance
and finance modules).

## 3. Public disk for evidence photos
```powershell
php artisan storage:link
```
Attachments are stored on the `public` disk under `subcon/` and served via
`Storage::disk('public')->url()`.

## 4. Migrate + seed
```powershell
php artisan migrate
php artisan db:seed --class=Database\Seeders\SubconRbacSeeder
php artisan db:seed --class=Database\Seeders\SubconDemoSeeder   # optional sample data
```
> If your Spatie guard isn't `web`, edit `$guard` in `SubconRbacSeeder`.
> `permission` middleware alias is already used across ASH AI — nothing new.

## 5. Run the tests
```powershell
php artisan test --filter=Subcon
```
Expect **4 passing** (payout week attribution, repair-only-when-repaired,
idempotent payout, true cost/piece). These are service-level tests — no Spatie
seeding needed. Endpoint tests that hit permission middleware must seed all five
Spatie tables in `beforeEach`.

## 6. Tinker smoke test (one statement per line)
```php
$svc = app(App\Services\Subcon\SubconPayoutService::class);
$svc->computeWeek('2026-07-03');
$svc->trueCost(1);
$svc->markPaid(1, '2026-06-29', '2026-07-05');
App\Models\SubconPayout::first()->only(['subcontractor_id','week_start','amount']);
```

---

## API (base `/api/v2`)

| Method | Path | Perm | Purpose |
|---|---|---|---|
| GET | `/subcon/subcontractors` | access.subcon | List subcons + true cost/piece |
| POST | `/subcon/subcontractors` | action.subcon.manage | Add subcon |
| GET | `/subcon/pos` | access.subcon | POs w/ delivered, remaining, overdue |
| POST | `/subcon/pos` | action.subcon.manage | Issue PO (auto code SUB-YYYY-NNN) |
| GET | `/subcon/pos/{id}` | access.subcon | PO detail + deliveries |
| GET | `/subcon/deliveries?po_id=` | access.subcon | Deliveries w/ repaired/scrapped/pending |
| POST | `/subcon/deliveries` | action.subcon.manage | Record delivery (accepted vs reject) |
| POST | `/subcon/deliveries/{id}/repair` | action.subcon.manage | Repair n rejects (→ payable that week) |
| POST | `/subcon/deliveries/{id}/scrap` | action.subcon.manage | Scrap n rejects (never paid) |
| DELETE | `/subcon/deliveries/{id}` | action.subcon.manage | Delete delivery (+ its photos) |
| GET | `/subcon/trips` | access.subcon | Lalamove trip log |
| POST | `/subcon/trips` | action.subcon.manage | Record trip (hatid/kuha/extra_*) → posts logistics expense |
| DELETE | `/subcon/trips/{id}` | action.subcon.manage | Delete trip (+ photos, + finance row) |
| GET | `/subcon/payouts/week?date=` | access.subcon | Weekly breakdown per subcon + grand total |
| POST | `/subcon/payouts/pay` | action.subcon.manage | Mark paid (idempotent) → posts sewing expense |
| GET | `/subcon/payouts/export?date=` | access.subcon | Payout CSV |
| GET | `/subcon/attachments?owner_type=&owner_id=` | access.subcon | List evidence |
| POST | `/subcon/attachments` | action.subcon.manage | Upload photos (multipart `photos[]`) |
| DELETE | `/subcon/attachments/{id}` | action.subcon.manage | Delete photo |

**Permissions seeded:** `portal.subcon`, `access.subcon`, `action.subcon.manage`
(super admin / GM / production / warehouse roles).

## Finance integration
If the finance module is installed, payouts and trips auto-post to
`finance_transactions` idempotently (`SUBCON-{subId}-{week}`, `SUBLOG-{tripId}`)
under categories "Subcon sewing" and "Subcon logistics". If it isn't installed,
`SubconFinanceBridge` no-ops — the module runs standalone. No code change needed
to connect them later.
