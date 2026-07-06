# CLAUDE-CONTEXT — read me first

You (Claude) are picking up the **ASH AI Subcon module** to refine it further.
This file gives you everything you need. Josh (the owner) may continue from a
different PC; treat this as the source of truth for how this module works and how
he likes things built.

## Who / what
- **Josh** — Owner/President of **Alpha Centauri Garments Corp (ACGC)**, Quezon City. Communicates in **Taglish**; deliverables in **English**. Sole developer + domain authority — his workflow corrections override all docs.
- **ASH AI (Apparel Smart Hub)** — a custom role-based production ERP for ACGC, covering orders, production workflow, inventory, finance, attendance/payroll, and subcontractor management. Two brands run on it: **Sorbetes Apparel Studio** (B2B, make-to-order, silkscreen) and **REEFER Streetwear** (B2C, TikTok Shop).

## Stack & environment
- Backend: **Laravel 12 · PHP 8.5 · Sanctum · Spatie/permission (RBAC) · MySQL (prod) / SQLite (Pest 3)**. API base **`/api/v2`**. Deployed on **Hostinger shared hosting** (`api.sorbetesapparel.com`) — **no persistent queue workers / no WebSockets; use polling + scheduled cron**.
- Frontend: React 19 + Vite + Tailwind v4 (separate; not in this bundle).
- Dev: Windows / PowerShell / XAMPP / VS Code.

## This module (Subcon) — the business it models
Josh sends **cut pieces** to **piece-rate subcontractor sewers** via a PO, they sew, he receives + QA-checks, and **pays them weekly**. They are contractors, **not employees** — so **no SSS/PhilHealth/tax withholding** (that's the payroll module's job, a different flow).

**Business rules (already confirmed by Josh and encoded — do not change without asking):**
1. **Rate is per piece, set per PO** (same subcon can have different rates on different POs).
2. **Rejects are NOT paid until repaired.** Accepted pieces are payable in the week of delivery; repaired pieces are payable in the week of the repair.
3. **Scrapped** pieces are never paid (defect accountability).
4. **No cash advance / vale** — payout is the straight total.
5. **Week = Monday–Sunday.** Payout is computed per subcon per week.
6. **Logistics** (Lalamove hatid/kuha) is tracked, plus **extra trips** caused by shortage (`extra_kulang`) or things left behind (`extra_naiwan`) — these are the subcon's fault and are highlighted separately.
7. **True cost / piece** = (sewing earned + all logistics) ÷ accepted pieces — the real price of a subcon, which can differ a lot from the nominal rate. This is the key management metric.
8. **Evidence photos** attach to deliveries (work, defects) and trips (Lalamove receipt, proof of delivery) for dispute resolution.

## What's built (file inventory)
- **Migrations (6):** `subcontractors`, `subcon_pos`, `subcon_deliveries` (repairs/scraps as JSON arrays of `{qty,date[,reason]}`), `subcon_trips`, `subcon_payouts` (unique per subcon+week), `subcon_attachments`.
- **Models (6):** with computed helpers — `SubconDelivery::pendingRejects()/repairedQty()/scrappedQty()`, `SubconPo::deliveredQty()`.
- **Support:** `SubconConstants` (trip kinds, statuses, owner types, finance sources).
- **Services (2):**
  - `SubconPayoutService` — `weekStart()`, `computeWeek()`, `markPaid()` (idempotent), `trueCost()`.
  - `SubconFinanceBridge` — guarded posting to the finance module (`class_exists` checks; no-op if finance not installed).
- **Controllers (6):** Subcontractor, SubconPo, SubconDelivery (receiving + repair + scrap + delete), SubconTrip, SubconPayout (week/pay/export), SubconAttachment (upload/list/delete).
- **Routes:** `routes/subcon.php` — distinct `subcon` first segment, `auth:sanctum` + Spatie `permission:` alias.
- **Seeders (2):** `SubconRbacSeeder`, `SubconDemoSeeder` (sample data mirroring the HTML demo Josh tested).
- **Tests (4 Pest):** payout week attribution, repair-only-when-repaired + scrap-never-paid, idempotent payout, true cost/piece.

## How it connects to the rest of ASH AI
- **Finance module** (separate bundle: `finance_categories`, `finance_transactions` with `(source, source_ref)` idempotency): subcon **payouts** post as `source=subcon, source_ref=SUBCON-{subId}-{week}` → "Subcon sewing"; **trips** post as `source=sublog, source_ref=SUBLOG-{tripId}` → "Subcon logistics". Reposting updates, never duplicates. If finance isn't present, posting is skipped cleanly.
- **Attendance/Payroll modules** handle *employees* (with statutory deductions). Subcons deliberately bypass payroll.
- **Phase 5-I subcontract shipment tracking** (in Josh's main repo) is the *physical* layer; this module is the *money + accountability* layer on top.

## Conventions Josh insists on (follow these)
- **Cardinal rule:** always read the live uploaded repo before changing integration points — never trust memory/docs alone. (If Josh gives you his `ash-ai-backend` zip, wire against that.)
- **Route ordering (BUG-010):** register fixed routes before the `portal/{role}` wildcard.
- **Spatie tests:** any test touching `$user->can()` must seed all five Spatie tables in `beforeEach` in order: permissions, roles, role_has_permissions, model_has_roles, model_has_permissions; test users need `domain_access => ['ash']`.
- **Every new column → add to model `$fillable`.** (BUG-016)
- **One permission middleware per route group**; the pipe-separated single-middleware pattern for Spatie AND. (BUG-017/018)
- **No DB-level foreign keys** (ASH convention) — this module follows that.
- **MySQL ENUM → VARCHAR** with PHP constants for SQLite/Pest compatibility (done here via string columns + `SubconConstants`).
- **Idempotency everywhere** money is posted: `(source, source_ref)` upsert.
- **Delivery contract:** one zip per testable checkpoint with an APPLY-AND-VERIFY.md; PHP files LF line endings; run Pest + tinker before frontend; build modified files from pristine copies, never mutated working copies.
- **Standalone-first:** new modules ship standalone with a one-line merge hook, then merge into `ash-ai-backend`.
- Tinker snippets: one statement per line. Avoid MySQL-only SQL funcs in tests (SQLite). Green Pest ≠ migration applied to live DB.

## Sensible next steps / extension points (not yet built)
- **Cut-piece / bundle issuance & loss:** track pieces *issued* to a subcon vs returned (e.g. 300 cut sets out, 295 back = 5 lost) — add `issued_qty` to POs or a `subcon_issuances` table; factor fabric loss into true cost. (Josh flagged this as important; confirm scope with him.)
- **Scrap → materials cost impact** on true cost (scrapped pieces waste fabric).
- **Attach evidence + signed payout sheet to the payout record** for one-click dispute resolution.
- **React frontend** for this module (Josh tested an HTML demo; the real UI is pending — read `/mnt/skills/public/frontend-design/SKILL.md` first, mind Taglish low-literacy floor UI: big buttons, minimal typing).
- **Scheduler:** weekly payout draft generation (needs Hostinger cron `php artisan schedule:run`).

## How Josh works with you
Approval-driven: he confirms with terse "Go/Proceed/Yup" and says "scan mo" to trigger a fresh audit pass (each pass should find *real* issues, not just confirm clean). Surface genuine decision forks as tap-button questions before building; don't make unilateral money/logic decisions. Keep production-floor UI copy in Taglish, flagged for his review.
