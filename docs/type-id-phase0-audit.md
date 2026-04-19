# Phase 0 Audit — `type_id` Usage Inventory

This document captures the Phase 0 inventory requested in `docs/type-id-removal-plan-fa.md`.

## Scope scanned
- `src/`
- `resources/`
- `scripts/`
- `migrations/`

## High-level findings
1. **Runtime user-flow buy path** had `type_id` in state payloads and legacy route branches.
2. **Admin flows** remain heavily type-centric (`admin.types_packages.*`, type-based state payloads).
3. **Database schema** still includes multiple `type_id` columns and type relations (`config_types`, FK/index usage in legacy schema/migrations).
4. **Localization** still contains type-centric copy in admin + stock views.

## Hotspots by layer

### 1) Runtime (user buy flow) — targeted for Phase 1
- Legacy state gate: `buy.await_type` in router.
- Package/rules/payment state payloads used `type_id`.
- Service payment verify payloads for package gateways carried `type_id` with sentinel values.

### 2) Admin (deferred to Phase 2)
- `src/MessageHandler.php` has extensive `type_id` state and validation in admin service/tariff/inventory flows.
- Type-centric menus and labels are still active.

### 3) Database + migrations (deferred to Phase 3)
- `scripts/schema.sql` defines type-centric structures (`config_types`, `service.type_id`, package and discount relations).
- `src/Database.php` has type-bound reads/writes and checks.
- Existing migrations/scripts still assume type-based links.

### 4) Localization/content
- `resources/lang/fa.json` still includes type-centric strings for admin and stock pages.

## Phase 0 result
- Inventory complete and categorized into runtime/admin/schema/content buckets.
- Phase 1 can proceed safely by removing remaining **runtime buy-flow** payload dependency on `type_id` while keeping schema/admin compatibility for now.
