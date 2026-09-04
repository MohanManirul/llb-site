# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A CRM built as a **Laravel 13 + Inertia + React 19** app that talks to its own REST API.
One Laravel app serves both the Inertia pages and the versioned `/v1/*` JSON endpoints; a
mobile client uses the same API with Sanctum Bearer tokens.

The API is versioned by URL prefix, mounted from the `using:` closure in `bootstrap/app.php`.
Two files feed the one `/v1` mount (names `v1.*`): `routes/admin-v1.php` for the staff API
and `routes/client-v1.php` for the portal's. The `web` side is `routes/web.php` plus
`routes/web-admin.php` at `/admin`.

There is no unversioned `/api/*`, and **no `/v2`** — `route:list` returns zero routes under
either prefix. When a v2 becomes necessary, mount it beside these; do not assume it exists.

## Conventions: target vs. current

`docs/code-structure/*.md` describe the **target** structure. Parts of the repo have not been
migrated yet. Before writing a file, check which state its area is in and say which one
you are following.

| | Target (the docs) | Current code |
|---|---|---|
| Frontend language | TypeScript (`.tsx`, `types.ts`) | ✅ **done** — all TypeScript, zero `.js`/`.jsx` in `resources/js` |
| Page path | `js/pages/{feature}/page.tsx` | ✅ mostly — `js/pages/admin/{feature}/{index,create,edit}/page.tsx`, plus a per-feature `types.ts` |
| Components | `js/components/ui` + `index.ts` barrel, `cn()` from `lib/utils` | `js/components/ui` **with** the `index.ts` barrel; still no `cn()`, no `clsx`/`tailwind-merge` |
| API layer | per-feature `api.ts` | 🟡 both — `js/lib/api-client.ts` is the shared axios instance and 26 files import it directly. Only `call-center/api.ts` is a real per-feature layer; `projects/api.ts` wraps a single endpoint while the rest of that feature still calls the shared client. Follow the surrounding file, not the folder |
| Controllers | `Http/Controllers/{Feature}/` | `Http/Controllers/V1/{Admin\|Client}/{Feature}/` |
| Services | `Services/{Feature}/Service.php` | ✅ **done** — 16 per-feature folders; only `Services/ApiResponseService.php` is still flat |
| Comments | none | existing PHP is heavily commented, often in Bengali/Banglish |

Rules for working in this gap:

- Frontend files are TypeScript — type new props, state and API payloads rather than
  reaching for `any`. Run `npx tsc --noEmit` before calling frontend work done. Note
  `tsconfig.json` sets `strict: false`, so a clean run proves shapes line up, not that
  nulls are handled — it is a floor, not a guarantee.
- Import shared inputs from the `@/components/ui` barrel (`TextInput`, `SelectInput`,
  `SearchableSelect`, `Button`, …). Do **not** invent `cn()` — it does not exist here.
- Follow the target docs for **new** feature folders; follow surrounding code when editing
  existing ones.
- "No comments inside code" applies to code you write. Do not strip existing comments —
  they explain *why* and are load-bearing.

- [`Backend Structure`](docs/code-structure/backend-structure.md) — layering, flow, rules
- [`Frontend Structure`](docs/code-structure/frontend-structure.md) — layering, flow, rules

## Commands

**Ask whether the environment is local or Docker before running anything** — both are in
use and the commands are not interchangeable.

| | Local (Laragon/native) | Docker |
|---|---|---|
| Run everything | `composer run dev` | `docker compose up -d --build` |
| Tests | `composer run test` | `docker compose exec app php artisan test` |
| Artisan | `php artisan <cmd>` | `docker compose exec app php artisan <cmd>` |

```bash
php artisan test --filter=ApiCompanyIndexTest        # single class
php artisan test --filter=test_it_searches_by_name   # single method
vendor/bin/pint                                      # formatter, Laravel preset
```

Docker ports: app `:8030`, Vite HMR `:8031`, Postgres `:8032`, Mailpit SMTP `:8033`,
Mailpit dashboard `:8034`.
**Never run `docker compose down -v`** — it deletes the local database.

## Architecture

**Pages are render-only.** The `web` routes never fetch page data — a thin page controller
renders a React page and passes an id (`['projectId' => $project]`), at most alongside a
cheap authorisation flag (`ProjectPageController::reports` passes `canSubmitReports`). The
only other props are request-derived values the page cannot read for itself: the
reset-password pages pass the `token` and `email` off the URL. Never domain data. The page
loads its own data from `/v1/*`
via `resources/js/lib/api-client.ts` (its `baseURL` is `/v1`, so callers write
`api.get('/projects')`). All logic lives in `app/Http/Controllers/V1/`, split into
`Admin/` for the staff API and `Client/` for the portal's own endpoints.

**Flow:** Route → Controller (request/response only) → FormRequest (validation) → Service
(business logic) → Model → Resource (formatting). Multi-step domain operations live in
`app/Actions/`, triggered from `app/Observers/`.

## Database

- Eloquent only. Query Builder is allowed for performance; **no raw SQL**.
- Follow ACID principle, FK constraints enforced; wrap multi-write Service methods in transactions.

### The `boneek` connection

The call center module reads and writes a **second database** — the inventory
(boneek) app's own Postgres — through the `boneek` connection. Rules:

- **Never migrate it.** `php artisan migrate --database=boneek` is always wrong;
  the inventory project owns that schema.
- Models under `app/Models/Boneek/` carry `protected $connection = 'boneek'`.
  They use uuid string keys, not auto-increment ints.

**The allowed writes**, and nothing else. This table is also the specification
for the connection's database GRANTs — `DEPLOY.md` turns it into the `GRANT`
statements the `boneek` user needs, and the two have to change in the same
release. A write with no matching grant raises Postgres `42501` *inside* the
transaction, so the whole edit rolls back. It is not silent — nothing catches it
(`bootstrap/app.php` only maps `23503`), so it surfaces as a plain 500 and the
agent gets a generic "Server Error". That is the trap: a permissions problem is
indistinguishable from any other 500, and only the application log names
`permission denied`.

| Table | Columns | When |
|---|---|---|
| `orders` | `status`, `previous_status` | status change |
| `orders` | `sub_total`, `discount_type`, `discount_value`, `shipping_charge`, `total_vat_amount`, `total_amount`, `due_amount`, `payment_status`, `delivery_partner_id` | recalculation after an item / discount / shipping-charge change |
| `orders` | `additional_note` | the Note modal |
| `orders` | `type` | re-resolved after an item change, mirroring inventory's `OrderTypeResolver` — an order holding a supplier's goods is DROPSHIPPING and one holding the merchant's own is REGULAR. Not cosmetic: an order left REGULAR while holding dropshipped goods makes the stock sync deduct the reseller's own shelf for a line that holds none of it |
| `orders` | `call_center_charge` | the per-order call center service fee, stamped once when an agent approves the order. It moves from 0 to the configured rate and is never written again — inventory copies it into `order_settlements.call_center_charge` and deducts it from the dropshipper's payout, so a second stamp would bill the merchant twice for one call |
| `order_items` | every column (insert / update / delete) | Add Product, quantity, item discount, remove |
| `orders` | `status` on a *linked* order | cancelling one half of a dropshipping pair cancels the other, as inventory's `syncLinkedOrderCancellation` does |
| `order_shipping_addresses` | `contact_name`, `contact_phone`, `address`, `country_id`, `division_id`, `district_id`, `area_id` — **insert as well as update**, since an order can reach the CRM with no address row (`updateOrCreate`) | the Shipping Address modal |
| `customers` | `name`, `phone`, `alternative_phone`, `email`, `customer_tag_id` | the Customer Name / Contact / Tag modals — exactly where inventory writes them |
| `accounts` | `name`, and only for the customer's own account | renaming a customer renames their ledger account, which inventory does through `CustomerObserver` → `CustomerAccountService::syncAccountName`. That observer lives in the other app and never fires for a CRM write, so the rename is done explicitly. Same conditions as upstream: only when the name actually changed, only when `customers.account_id` is set. **No balance column is ever touched** |
| `orders` | **insert** — `id`, `business_id`, `parent_id`, `type`, `branch_id`, `invoice_no`, `identifier_number`, `status`, `sold_at`, `source`, `additional_note`, `shipping_charge`, `sub_total`, `total_vat_amount`, `total_amount`, `due_amount`, `payment_status`, `created_at`, `updated_at` | Send to Supplier. The only row the CRM creates in another business's name, porting inventory's `ImportDropshippingOrderAction::createSupplierOrder`. `customer_id` is deliberately absent — a supplier order opens without a customer, exactly as inventory's does |
| `orders` | `branch_id`, `sold_at`, `source`, `identifier_number` | the rebuild path re-stamps an existing supplier order when the reseller's items changed and the supplier has not started on it |
| `order_logs` | insert | the merchant-facing log for every change above |
| `business_notifications` | insert | Send to Supplier, once, for the supplier's business. Their only in-panel sign that an order arrived, porting inventory's `OrderCreated` → `SendOrderCreatedNotification`. The rebuild path does not write a second one — upstream's event fires on `created`, and a rebuild is an update |
| `business_settings` | `value`, for the `has_notification` key only — **insert as well as update**, since a business may not have the row | beside the notification above, mirroring inventory's `BusinessNotificationCreated::updateHasNotification`. The value is json-cast, so it is the literal `true`, never `"1"`. No other key is ever written |
| `product_stocks` | `quantity` — **insert as well as update**: the deduction path opens a row where the branch has none, mirroring inventory's `lockOrMakeStock`. The restock path never does, so returned stock cannot invent a row | stock movement behind an item change |

Never written: `payments`, `transactions`, and every balance column of
`accounts` — those are money-received records. (`payments` is SaaS subscription
billing, not order payment; an order's money lives in `orders.paid_amount` /
`due_amount` / `payment_status`.) `accounts.name` is the single exception above,
and it is a label, not money.

Also never written, though inventory's own order screen writes them:
`orders.customer_id` (moving an order to a different customer), inserts into
`customers` (creating one), `customers.courier_score`, every
`business_settings` key other than `has_notification` (the block-phone /
block-IP lists among them), and `order_logs` update/delete (editing or
deleting a timeline comment). Each is a deliberate exclusion, not an oversight —
the call center corrects the customer an order already has, it does not decide
who the order belongs to.

**Inventory's model observers do not fire for a CRM write.** The two apps are
separate processes on one database, so anything inventory does in an observer,
a job or a model event has to be done explicitly here or it simply does not
happen. Check the observer before porting any write. The audit as of
2026-08-10, for the tables the call center touches:

| Inventory does | Here |
|---|---|
| `OrderObserver::updating` — stamps `orders.previous_status` | ported, in `Boneek\Order::booted()` so every path gets it |
| `OrderObserver::updating` — stamps `sent_to_boneek_at` | unreachable: agents cannot set `send_to_boneek` |
| `OrderObserver::updated` — `DropshippingSalesCounter` | unreachable: it only moves on delivered/completed |
| `OrderObserver::updated` — `OrderFollowUpRespond` | **deliberately not ported.** It closes the merchant's own follow-up row on behalf of the staff member who picked it up. A CRM agent is not that person, and stamping it would credit merchant staff with a call they did not make |
| `CustomerObserver::updated` — `syncAccountName` | ported, in `OrderCustomerService::updateName` |
| `OrderObserver::creating` — stamps `orders.identifier_number` | ported, in `OrderCodeGenerator::identifierNumber()`, called from `SupplierOrderCreator`. The uniqueness scan is **global**, not per-business: the column's index is global, and upstream's per-business scan is why a cross-business collision there surfaces as an insert failure rather than a retry |
| `OrderObserver::created` → `OrderCreated` → `SendOrderCreatedNotification` — a `business_notifications` row telling the supplier an order arrived, plus a `business_settings.has_notification` flip | ported, in `SupplierOrderNotifier`, called from `SupplierOrderCreator::create` inside the same boneek transaction as the order. Both writes are plain rows in the shared database and both are now granted. What does **not** come with it is `BusinessNotificationCreated`'s other half: the Reverb broadcast and the `Cache::forget` on inventory's redis. Measured: `GET /v1/notifications` serves the row (it reads the table directly), while `GET /v1/business/settings` keeps answering `has_notification: false` from cache until inventory writes the setting itself — so the supplier finds the notification, but the bell lights late |
| `DispatchOrderCallingAgent` → `SendCallToAgentJob`, and the FCM push behind the notification | **cannot be ported today.** The CRM runs `QUEUE_CONNECTION=database` on its own database; inventory runs redis with Horizon. A job the CRM queues is one inventory's workers never see. Pointing the CRM's queue at inventory's redis would fix it and is an infrastructure decision |
| `StorefrontCacheObserver` on `ProductStock` | **cannot be ported today** — same split, on `CACHE_STORE`. The supplier's storefront shows stale stock until it expires |
| `OrderStatusService::sendToCourier` | unreachable at this status: it fires only from `ready_to_ship` with a delivery partner set |

- **Transactions must name the connection**: `DB::connection('boneek')->transaction(...)`.
  A bare `DB::transaction()` opens one on the *CRM* database, so the boneek
  writes inside it silently never roll back.
- The two databases cannot be joined (cross-database joins are unsupported), so
  cross-DB matching is done by pulling id lists into PHP.
- Inventory's stock rules (`OrderStockManager`, `OrderStockValidator`,
  `OrderStatus::stockReducing()`) and its order money math (`OrderAmountCalculator`,
  `OrderItemPersister`, `Order::calculateDiscountAmount/calculateVatAmount`) are
  **copied** into `app/Services/CallCenter/` and `app/Enums/CallCenter/`. If they
  change upstream, they must change here in the same release or the two systems'
  stock counts and order totals will diverge.
- VAT is inclusive and informational: the rate comes from
  `business_settings.vat_percentage` for the order's business, and
  `total_vat_amount` is stored but **never added** to `total_amount`.
- **`business_settings.value` holds JSON, not text.** Inventory casts the
  attribute to json, so `currency` is stored as `"BDT"` *with* the quotes and a
  rate the settings form posted as text is stored as `"5"`. Reading it raw is
  how VAT silently becomes zero — `(float) '"5"'` is `0.0`. `BusinessSetting::get()`
  decodes; anything reading that column directly must too.

### Incomplete orders, read-only

`order_incompletes` is inventory's abandoned-checkout table: a storefront or
WooCommerce basket where the customer left a phone or an email and never
finished. The CRM lists it (`IncompleteOrderService`) and writes nothing —
the whole lifecycle stays upstream, and there is a reason not to fight that:

- inventory's `OrderIncompleteService::updateOrCreate` keys on phone/email, so
  a returning customer updates the row rather than adding one;
- a real order from the same phone or email within three days **deletes** it;
- converting it to an order deletes it;
- `order:incomplete-clear` deletes anything untouched for seven days.

So a row an agent is looking at can vanish under them. Anything the CRM needs to
keep — who called, what was said — belongs in a CRM-side table, the way
`call_center_picks` snapshots an order it does not own.

Two things do not carry over from inventory:

- **the `HasBusiness` global scope**, which reads a current business the CRM has
  no notion of. `OrderIncomplete::callCenterVisible()` answers the same question
  with the business flag pair, exactly as `Order` does.
- **`items` is not a resolved basket.** It is whatever the storefront posted —
  `product_name`, `quantity`, `price`, sometimes a `variation` map — and its
  `product_id` need not still match a catalogue row. `IncompleteOrderResource`
  reads the names and the numbers and stops there; resolving them back to
  products is inventory's `IncompleteOrderItemResolver`, and it belongs with the
  convert-to-order flow, which the CRM deliberately does not have.

### Product pictures

They live on inventory's disk, not this app's. `product_images.path` is
relative to inventory's public disk (`uploads/images/…`), so a url only exists
once **`BONEEK_APP_URL`** is set — leave it empty and every product silently
falls back to the placeholder icon, which is the usual reason a new environment
shows no pictures. `products.image` sometimes holds a finished absolute url
instead; both shapes are handled in `Boneek\Product::imageUrl()`.

Inventory writes a small copy beside each upload as `<name>_thumb.png` and its
own lists render that. `thumbnailUrl()` builds the same path optimistically —
whether the file is there can only be answered by inventory's disk — and
`ProductThumb` falls back to the full-size original in the browser on a 404.

### Where the CRM deliberately differs from inventory's order screen

Each of these is a decision, not drift. Do not "fix" one into parity without
saying why.

| | Inventory | The CRM |
|---|---|---|
| The merchant's `order_logs` | shown as the order timeline | never surfaced — it carries merchant staff names and their notes. `CallCenterHidesMerchantOrderLogsTest` pins this |
| Timeline comments | written to `order_logs`, so the merchant sees them | CRM-only (`call_center_activities`), so they never do |
| Statuses an agent may set | the full lifecycle (hold, processing, delivered, in transit, send to supplier…) | approved / cancelled / follow_up, plus **send_to_supplier on a dropshipping order only** (`OrderStatus::agentSettableFor()`). The first three are what the pick outcome model is built on; the fourth is a hand-off, and `CallCenterOutcome::fromStatus` maps it back to `approved` so the work still counts |
| An approved order | leaves the agent's world entirely | **dropshipping only**: the pick stays open and the order stays editable until it is forwarded, because customers change an address or a number after saying yes. A regular order behaves as inventory's does — the pick closes on approve, and `PickedOrderController::guardPicked` then refuses the agent the details page as well |
| Send to Courier | the default action on an approved order; dispatches a real parcel | absent. An agent picks the courier and its charge; the merchant dispatches |
| From-status for Send to Supplier | none — `OrderStatusPolicy` is a *target*-status blacklist, so a merchant may forward an order sitting at `pending` | **must be `approved` first.** Approve is where the agent's outcome is recorded and where `call_center_charge` is stamped, so a send that skipped it would bill nobody and close a pick reading `approved` that nobody approved |
| Send to Supplier | the merchant presses it | the **agent** presses it, from the order details page. Same port, same 120% markup floor, same supplier-stock check, same rebuild path — see `SupplierOrderCreator`. The supplier's notification row and bell flag come with it (`SupplierOrderNotifier`); what does not is everything living outside the database — the Reverb broadcast beside that notification, the calling-agent job and the storefront cache flush |
| Customer name / contact / address | pencils hidden once the order ships | stay editable at any status — a wrong phone on a moving parcel is exactly what the call is about |
| Order-wide fixed discount above subtotal | accepted, then clamped at computation | rejected outright, with the clamp still in place behind it |
| Adding a product already on the order | opens a second line at today's catalogue price | tops up the existing line, keeping the price it was sold at |
| `order_logs` wording for an item discount | "item shipping charge was updated" (an upstream copy-paste slip) | "item discount was updated" |
| Whole-unit quantities | enforced in React only | enforced on the endpoint as well |
| Removing the last item | allowed at the removal itself; the emptied order is caught later, when `generateInvoice` refuses it ("No order item found. Please add items…"). The merchant can meanwhile delete it, rebuild it, or take a different one | refused up front. Same rule, moved to the point an agent can still act on — they can neither create nor delete orders, so an emptied one is a dead end, and a ৳0 total makes `OrderAmountService::paymentStatus` stamp it **PAID** (inventory's own importer does the same at `ImportHandler`). Cancel is the outcome they want and returns the stock the same way. `ApiCallCenterOrderItemTest` pins it |
| A basket mixing two suppliers | split at checkout into one order per owning business, then `EnsureSameBusinessOrderItems` keeps later edits from remixing | the same rule, ported as `SameSupplierOrderItems` — but it is the *whole* answer, because an order the agent did not create cannot be split. `CallCenterSameSupplierTest` pins it |
| The Add Product picker's scope | every orderable product the merchant has, since the modal has no order to scope to | narrowed again to the supplier the order is already carrying, so a row that would be refused is never offered |

### Which products the Add Product picker may offer

Ported from inventory's `ProductRepository::orderable`, which is what the
merchant's own Add Product modal lists. Orderable is a real filter, not a
synonym for published:

- **The merchant's own products** (`parent_id` null) — the order's branch is
  holding some, or `allow_out_of_stock_orders` is set on the product or on any
  of its skus. An order with no branch falls back to stock anywhere, which is
  the same thing `StockValidator` does when it cannot ask about a branch.
- **A reseller's dropshipped copy** (`parent_id` set) — judged on the supplier's
  parent instead, because the copy holds no stock of its own. The supplier's
  whole shelf counts, not one branch of it.
- **Variations** are filtered one level down by the same test, so a sold-out
  colour is not offered next to its siblings. A variable product left with no
  orderable variation is not offered at all — a row with no sku is one the add
  endpoint would refuse.

`CallCenterOrderableProductsTest` pins each of those.

# Note

- Do not commit or push without approval or if told you.
- Do not add any comments in code.
