# Development journal

Use this document as a development journal to keep notes for yourself or another agent when needed.
## Things to track in this document:

Anything you feel you or another agent, human or AI, might need to complete this project. You should also include:
- Location of the project directories and important files
- Descriptions of any platforms or codebases used
- The intent of the overall project
- The intent of updates you've preformed and plan to preform


---

## Journal entries

---

### 2026-03-29 — Admin recovery, regression repairs, dashboard cleanup, and security sweep

**Agent:** GitHub Copilot (GPT-5.4)

---

#### Summary

Today focused on stabilising the plugin after multiple admin-side regressions, restoring safe WordPress admin behaviour, repairing broken CRUD/reporting screens, removing a duplicate dashboard render, and doing a targeted security sweep on the changed code.

---

#### Major fixes completed

1. **Restored normal WordPress admin access**
   - Root cause was incorrect CPT capability mapping that effectively polluted core capability resolution.
   - Fixed by stopping dangerous `read_post`/core `read` interactions and reapplying AIGIS roles/caps safely.

2. **Restored missing AIGIS menu sections**
   - AIGIS CPTs were using custom primitive caps with `map_meta_cap` enabled, which caused WordPress to remap menu capability checks into meta-cap checks and hide sections.
   - Fixed by disabling `map_meta_cap` on the affected CPTs and providing explicit capability maps.

3. **Repaired original broken feature areas**
   - Prompt settings save path fixed and legacy prompt meta compatibility added.
   - Policy detail save contract fixed by aligning nonce names and field names between the metabox view and save handler.
   - Workflow editor repaired: Mermaid source loading/saving, node JSON decoding, model list wiring, and diagram rendering compatibility.
   - Incident admin list repaired by explicitly including custom incident statuses in the main `edit.php` admin query.
   - Inventory editor repaired to use the real schema and redirect cleanly after POST actions.
   - Cost & Budgets repaired for schema/object handling and post-submit flow.
   - Stress Tests repaired for controller/view field mismatches, run creation flow, and post-submit redirect behaviour.
   - Evaluation screen aligned with the real schema and now renders current data.
   - Dashboard duplicate output fixed by removing the second callback-backed registration for the same dashboard slug.

4. **Analytics/test-data alignment repairs**
   - Seeded usage logs now populate `prompt_post_id` deterministically so prompt-related analytics sections have usable data.
   - Inventory, evaluation, cost, and provider lookups were updated to handle object-returning DB helpers correctly.

---

#### Security sweep

Security review concentrated on the modified files and WordPress admin/REST entry points.

**Issue fixed immediately:**
- `admin/js/aigis-workflow-diagram.js`
  - Mermaid had been switched to `securityLevel: 'loose'`, which creates a stored XSS risk because workflow diagrams are user-editable and the preview inserts rendered SVG/HTML into the DOM.
  - Restored Mermaid to `securityLevel: 'strict'`.

**Other reviewed areas:**
- Verified nonce/capability checks are in place on the repaired Inventory, Cost, Stress Test, Policy, Incident, Eval, and AJAX handlers.
- Confirmed the dashboard duplication was a registration/rendering bug, not duplicated data generation.
- No additional concrete high-severity issues were found in the touched files during this pass.

---

#### Validation performed

- Static error checks on the recently edited PHP/JS files: clean.
- Runtime validation in the Docker WordPress environment confirmed:
  - policy detail values persist on save;
  - incidents admin list query includes custom statuses and returns existing records;
  - dashboard registration now only has one callback-backed dashboard page.

---

#### Recommended next steps

1. Do a dedicated dashboard data pass for any remaining chart/data completeness issues outside the duplicate-render bug.
2. Add a lightweight regression checklist for admin flows: prompt save, policy save, inventory edit/delete, budget save/delete, stress test run, incident list, dashboard load.
3. Add automated PHPCS/WordPress coding standards checks in CI so nonce/capability/schema drift is caught earlier.
4. Decide whether the repository should formally migrate from `master` to `main` as the default branch on GitHub.

---

### 2026-03-29 — Repository structure cleanup

**Agent:** GitHub Copilot (GPT-5.4)

---

#### What changed

- Moved internal project documents out of `_resources/` into a conventional `docs/` tree:
   - `docs/development/dev-journal.md`
   - `docs/specifications/project-specifications.md`
   - `docs/reference/user-manual.html`
- Grouped plugin bootstrap/orchestration classes under `includes/core/`:
   - `class-aigis-loader.php`
   - `class-aigis-activator.php`
   - `class-aigis-deactivator.php`
   - `class-aigis-plugin.php`
- Updated the plugin bootstrap loader in `ai-governance-suite.php` to require the new `includes/core/` paths.
- Updated repository documentation to describe the cleaned structure.

#### Rationale

- `_resources/` worked as a scratch area but is not a strong long-term repository convention.
- `docs/` makes it clear which materials are for developers and maintainers rather than runtime plugin code.
- `includes/core/` separates framework wiring from feature code and makes the top-level `includes/` tree easier to scan.

---

### 2026-05-XX — Test Data Generator & User Manual added

**Agent:** GitHub Copilot (Claude Sonnet 4.6)

---

#### Changes made

**New files:**
- `includes/helpers/class-aigis-test-data.php` — `AIGIS_Test_Data` static class with `generate()`, `purge()`, `count_existing()`. Creates 3 inventory records, 3 prompts, 2 policies, 2 workflows, 2 incidents, 12 usage logs, 8 audit rows, 3 cost budgets, 7 eval results, 5 guardrail triggers. CPT records tagged with `_aigis_test_data = '1'`; DB row IDs tracked in WP option `aigis_test_data_db_ids`.
- `includes/admin/class-aigis-page-manual.php` — `AIGIS_Page_Manual` class, renders the manual view.
- `admin/views/manual/manual.php` — Comprehensive 12-section user manual (Overview, Getting Started, AI Inventory, Prompts, Policies, Workflows, Incidents, Analytics & Cost, Stress Tests & Eval, Audit Log, REST API, Roles & Permissions). Uses URL-based tab navigation (`manual_tab` GET param).

**Edited files:**
- `ai-governance-suite.php` — Added `require_once` for both new classes inside the `is_admin()` block.
- `includes/admin/class-aigis-page-settings.php` — Added `'developer' => 'Developer Tools'` to TABS constant.
- `admin/views/settings/settings.php` — (a) Fixed nonce action: removed `_' . $active_tab` suffix to match `handle_save()`. (b) Fixed hidden input name: `aigis_settings_tab` → `aigis_active_tab`. (c) Added Developer Tools tab section with test data counts table and Generate/Remove buttons. (d) Extended submit button condition to also exclude `developer` tab. (e) Added `'developer' => 'Developer Tools'` to local `$tabs` array.
- `includes/admin/class-aigis-admin.php` — Added `aigis_generate_test_data` and `aigis_purge_test_data` AJAX registrations; added User Manual submenu entry (before Settings); added `generateTestData`/`purgeTestData` nonces; added i18n strings `confirmGenerateTestData`, `confirmPurgeTestData`, `generating`, `purging`; added `ajax_generate_test_data()` and `ajax_purge_test_data()` handler methods.
- `admin/js/aigis-admin.js` — Added `#aigis-generate-test-data` and `#aigis-purge-test-data` click handlers with confirmation dialogs, AJAX calls, and 1.5 s reload on success.

#### Bugs fixed alongside new features
1. Settings nonce mismatch: view used `'aigis_save_settings_' . $active_tab` but `handle_save()` verified `'aigis_save_settings'`. Fixed in view.
2. Settings field name mismatch: view posted `aigis_settings_tab` but `handle_save()` read `$_POST['aigis_active_tab']`. Fixed in view.

---

### 2026-03-24 — Full plugin build complete + activation bug fixed

**Agent:** GitHub Copilot (Claude Sonnet 4.6)

---

#### Project overview

**Plugin:** AI Governance and Infrastructure Suite  
**Slug:** `ai-governance-suite`  
**Location:** `wp-content/plugins/ai-governance-suite/`  
**Spec:** `docs/specifications/project-specifications.md`  
**PHP:** 8.1+ | **WordPress:** 6.4+  
**Class prefix:** `AIGIS_` | **DB/option prefix:** `aigis_` | **CPT slugs:** `aigis_prompt`, `aigis_policy`, `aigis_workflow`, `aigis_incident`

---

#### Work completed across sessions

All 8 build phases are now complete. The plugin is fully scaffolded and ready to activate.

| # | Phase | Files |
|---|-------|-------|
| 1 | Bootstrap & infrastructure | `ai-governance-suite.php`, `uninstall.php`, `includes/core/class-aigis-loader.php`, `includes/core/class-aigis-activator.php`, `includes/core/class-aigis-deactivator.php`, `includes/core/class-aigis-plugin.php` |
| 2 | Database abstraction layer | `class-aigis-db.php` (abstract), `class-aigis-db-audit.php`, `class-aigis-db-inventory.php`, `class-aigis-db-usage-log.php`, `class-aigis-db-cost.php`, `class-aigis-db-eval.php` |
| 3 | Custom Post Types | `class-aigis-cpt-prompt.php`, `class-aigis-cpt-policy.php`, `class-aigis-cpt-workflow.php`, `class-aigis-cpt-incident.php` |
| 4 | Helpers | `class-aigis-capabilities.php`, `class-aigis-pii-detector.php`, `class-aigis-notifications.php`, `class-aigis-cron.php` |
| 5 | Admin pages & menus | `class-aigis-admin.php`, `class-aigis-page-dashboard.php`, `class-aigis-page-inventory.php`, `class-aigis-page-audit-log.php`, `class-aigis-page-settings.php`, `class-aigis-page-analytics.php`, `class-aigis-page-cost.php`, `class-aigis-page-stress-tests.php`, `class-aigis-page-eval.php` |
| 6 | REST API controllers | `class-aigis-rest-controller.php` (abstract), `class-aigis-rest-log.php`, `class-aigis-rest-routing.php`, `class-aigis-rest-guardrail.php`, `class-aigis-rest-eval.php` |
| 7 | AI provider adapters | `class-aigis-provider-abstract.php`, `class-aigis-provider-openai.php`, `class-aigis-provider-anthropic.php`, `class-aigis-provider-ollama.php` |
| 8 | Admin assets & views | 4 JS/CSS assets + 21 PHP view templates |

**View templates** live under `admin/views/{area}/`. Areas covered: `dashboard/`, `inventory/`, `audit-log/`, `settings/`, `analytics/`, `cost/`, `stress-tests/`, `eval/`, `prompts/`, `policies/`, `workflows/`, `incidents/`.

---

#### Bugs found and fixed during verification

The following bugs were identified during a post-build review and in response to the first activation failure:

1. **`class-aigis-plugin.php` — fatal on every page load (activation bug)**  
   `$this->loader->add_action( 'init', new AIGIS_Capabilities(), 'register' )` was calling a non-existent instance method `register()` on `AIGIS_Capabilities`. PHP fires this callback on every page load at `init`, causing `Call to undefined method`. Fixed by removing the line — capabilities and roles are set up exclusively via `AIGIS_Activator::activate()` and torn down by `uninstall.php`.

2. **`class-aigis-plugin.php` — CPT double-instantiation and wrong constructor calls**  
   CPT classes were being called with `new AIGIS_CPT_Prompt( $this->loader )` despite having no constructor, then re-instantiated a second time in `init_admin()`. Fixed to `( new AIGIS_CPT_Prompt() )->register( $this->loader )` with `init_admin()` cleaned up to only register the admin menu.

3. **`class-aigis-plugin.php` — `AIGIS_Admin` receiving wrong argument**  
   `AIGIS_Admin` expects `AIGIS_Notifications` in its constructor; the old code passed the loader. Fixed.

4. **`class-aigis-plugin.php` — `AIGIS_Cron` missing 4 required dependencies**  
   `new AIGIS_Cron()` was called with no arguments. Fixed to pass `$db_audit`, `$db_usage`, `$db_cost`, `$notifications`.

5. **`class-aigis-admin.php` — missing `aigis_inbox_unread_count` AJAX handler**  
   JS polls this endpoint every 60 seconds but the PHP action was never registered. Added registration in `register()` and added the handler method `ajax_inbox_unread_count()`.

6. **`admin/js/aigis-admin.js` — all AJAX calls using a non-existent `aigisAdmin.nonce`**  
   The localized data object uses `aigisAdmin.nonces.*` (an object of per-action nonces), not a single `nonce` property. Fixed all AJAX calls to use the correct per-action nonce key.

7. **`class-aigis-activator.php` / `class-aigis-deactivator.php` — missing `aigis_prune_usage_logs` cron schedule**  
   The cron callback was wired in `class-aigis-plugin.php` but the event was never scheduled on activation or unscheduled on deactivation. Added to both.

8. **`ai-governance-suite.php` — dead `require_once` for non-existent file**  
   `class-aigis-page-incidents.php` was required but never existed (incidents use the standard WP CPT edit screen). Removed.

9. **Incident metabox views — variable name mismatches**  
   The view templates referenced variables (`$detected_at`, `$investigation_notes`, `$linked_policies`, etc.) that didn't match what the CPT render methods actually set (`$detected`, `$notes`, `$linked_policy_ids`, etc.). All 4 incident views were corrected to use the exact variable names set by `class-aigis-cpt-incident.php`.

---

### 2026-03-25 — Activation fatal fully resolved (PHP 8.3 typed property + API key option mismatch)

**Agent:** GitHub Copilot (Claude Sonnet 4.6)

---

#### Bug 10 — PHP 8.3 fatal: typed property redeclaration in REST controller

**File:** `includes/api/class-aigis-rest-controller.php` (line 17 before fix)

**Root cause:** `AIGIS_REST_Controller` re-declared `WP_REST_Controller::$namespace` with an explicit `string` type:
```php
protected string $namespace = 'ai-governance/v1';  // ← fatal in PHP 8.3
```
`WP_REST_Controller` declares the same property without a type (`protected $namespace;`). PHP 8.3 upgraded this incompatibility from a deprecation notice (PHP 8.2) to a **hard fatal error**: `Type of AIGIS_REST_Controller::$namespace must not be defined (as in class WP_REST_Controller)`. The Docker container runs PHP 8.3.30 (`wordpress:latest`).

**Fix:** Remove the type annotation — the parent class owns the declaration:
```php
protected $namespace = 'ai-governance/v1';
```

**Diagnostic method:** Used `docker exec ... php -r "..."` to manually require-chain `WP_REST_Controller` → `AIGIS_REST_Controller` inside the container and observe the fatal directly.

---

#### Bug 11 — API key option name mismatch

**Files:** `includes/core/class-aigis-activator.php` and `includes/api/class-aigis-rest-controller.php`

**Root cause:** During activation, the plugin API key hash was stored under the option name `aigis_api_key`:
```php
add_option( 'aigis_api_key', password_hash( $raw_key, PASSWORD_BCRYPT ) );
```
But the REST authentication check read it back under a different name:
```php
$stored_hash = get_option( 'aigis_api_key_hash', '' );
```
This made every `X-AIGIS-API-Key` authenticated request return "No API key has been configured" even after a clean activation.

**Fix:** Align the option name — both activator and uninstaller now use `aigis_api_key_hash`. The architecture note in this journal and the `uninstall.php` cleanup array have also been updated.

---

#### Architecture reminders for future work

- **Loader pattern:** Components call `$loader->add_action()` / `$loader->add_filter()` in their `register()` method. `AIGIS_Plugin::run()` calls `$loader->run()` once to wire everything to WordPress.
- **View includes:** Admin page controllers `include AIGIS_PLUGIN_DIR . 'admin/views/{area}/{template}.php'` and set variables in local scope before the include. Views must only reference those exact variable names.
- **DB audit immutability:** `AIGIS_DB_Audit::update()` and `delete()` throw `RuntimeException`. Never call them.
- **REST auth:** `X-AIGIS-API-Key` header checked via `password_verify()` against bcrypt hash stored in `aigis_api_key_hash` option.
- **Provider keys:** Stored XOR-encrypted in `wp_options`. Use `AIGIS_Provider_Abstract::decrypt_option()` to read them.
- **Vendor enqueue:** Chart.js and Mermaid.js minified files must be placed in `admin/js/vendor/` (they are referenced but not bundled by the plugin — the dev environment must supply them or they must be downloaded separately).

---

#### What to test next

- [x] Activate the plugin — succeeded
- [x] Check that the top-level "AI Governance" admin menu appears — confirmed
- [x] Create a test AI Inventory entry — test data generator working
- [x] Confirm REST endpoint `POST /ai-governance/v1/log` accepts requests with `X-AIGIS-API-Key`
- [x] Verify Chart.js charts render on the Analytics page — fixed (see 2026-03-25 session)

---

### 2026-03-25 — Display bugs, chart rendering, audit log, workflow diagrams

**Agent:** GitHub Copilot (Claude Sonnet 4.6)

---

#### Context

Plugin is activated and KPI cards are displaying correctly (42 sessions, 117,910 tokens, $0.76 MTD cost, 1 open incident, 3 active models). Remaining work was fixing blank charts/graphs, empty audit log, and blank workflow diagram previews.

---

#### Bugs fixed

**Bug 12 — Audit log: 5 variable mismatches**  
**File:** `includes/admin/class-aigis-page-audit-log.php` + `admin/views/audit-log/list.php`

Controller was passing `$entries`, `$total_items`, `$total_pages` but the view expected `$items`, `$total`, `$pages`. `$filters` was never set at all. Additionally `$row->created_at` referenced a non-existent DB column — the actual column in `wp_aigis_audit_trail` is `occurred_at`.

All five issues corrected.

---

**Bug 13 — Workflow diagram metabox: variable and POST key mismatches**  
**File:** `includes/cpt/class-aigis-cpt-workflow.php`

`render_diagram_metabox()` set `$mermaid_source` but the view (`metabox-diagram.php`) reads `$diagram_source` → textarea empty on load.

`save_metaboxes()` read `$_POST['aigis_mermaid_source']` but the form field has `name="aigis_diagram_source"` → saves silently discarded.

Both corrected.

---

**Bug 14 — Workflow test data: wrong meta key**  
**File:** `includes/helpers/class-aigis-test-data.php`

All 5 workflow test-data posts stored Mermaid content under `aigis_workflow_mermaid` (no leading underscore, wrong name). The CPT reads `_aigis_mermaid_source`. All 5 entries corrected. Also removed the unused `aigis_workflow_status` meta key from test data.

---

**Bug 15 — Workflow nonce mismatch (save never fired)**  
**File:** `includes/cpt/class-aigis-cpt-workflow.php`

`save_metaboxes()` checked for `$_POST['aigis_workflow_diagram_nonce']` with action `aigis_workflow_diagram`, but `metabox-diagram.php` generates `aigis_workflow_nonce` / action `aigis_save_workflow`. The nonce check always failed silently — diagram saves never persisted. Fixed `save_metaboxes()` to match the view.

---

**Bug 16 — Dashboard charts not loading: missing enqueue hook**  
**File:** `includes/admin/class-aigis-admin.php`

`enqueue_assets()` only listed `ai-governance_page_aigis-analytics`, `ai-governance_page_aigis-cost`, `ai-governance_page_aigis-eval` in the Chart.js condition. The dashboard hook `toplevel_page_aigis-dashboard` was absent. Added.

---

**Bug 17 — Chart.js ESM build incompatible with WordPress `<script>` tags**  
**File:** `includes/admin/class-aigis-admin.php` + `admin/js/vendor/`

`admin/js/vendor/chart.min.js` was the ESM build (uses `import` statements) — incompatible with WordPress's plain `<script>` tags; caused `SyntaxError: Cannot use import statement outside a module` and `ReferenceError: Chart is not defined`.

Fix: Updated enqueue to check for `chart.umd.min.js` locally first, then fall back to the jsDelivr CDN UMD build. User subsequently downloaded `chart.umd.min.js` to `admin/js/vendor/` — CDN fallback no longer needed but remains as a safety net.

---

**Bug 18 — Charts only rendering first per page: undefined data keys in `aigis-charts.js`**  
**File:** `admin/js/aigis-charts.js`

The JS chart initialisation IIFE called `d.values` for both `modelBreakdown` and `deptCost` charts. Controllers never send a `values` key — `modelBreakdown` sends `tokens`; `deptCost` sends `costs`. The `undefined` values caused Chart.js to throw, halting the rest of the IIFE so only the first chart on each page rendered.

Fixed: `modelBreakdown` now reads `d.tokens`; `deptCost` now reads `d.costs`.

---

**Bug 19 — Cost trend chart: controller sending wrong data key**  
**File:** `includes/admin/class-aigis-page-cost.php` + `includes/db/class-aigis-db-usage-log.php`

Cost trend chart expected `costTrend.actual` (USD spend array) but the controller was localising `tokens` instead. Additionally `get_sessions_over_time()` didn't select `cost_usd` at all.

Fix: Added `COALESCE(SUM(cost_usd), 0) AS cost_usd` to the `get_sessions_over_time()` query. Updated the cost controller to pass `actual` (array of floats from `cost_usd`).

---

**Bug 20 — Eval trend chart: missing `fail_rate` dataset**  
**File:** `includes/admin/class-aigis-page-eval.php`

Eval controller only localised `pass_rate`; the JS chart expected a `fail_rate` array too. Added `fail_rate` derived as `(1 - pass_rate) × 100` for each trend row.

---

#### Architecture reminders added

- **Chart.js vendor:** Must be the **UMD** build (`chart.umd.min.js`), not the ESM build (`chart.min.js`). UMD creates `window.Chart` global needed by `wp_enqueue_script`. The ESM build requires `<script type="module">` which WordPress does not add.
- **Mermaid vendor:** `mermaid.min.js` is a confirmed IIFE bundle — no issues.
- **`aigisChartData` keys per page:**
  - Dashboard: `usageTrend` (`labels`, `sessions`, `tokens`), `modelBreakdown` (`labels`, `tokens`)
  - Analytics: `usageTrend`, `modelBreakdown` (`labels`, `tokens`, `calls`), `deptCost` (`labels`, `costs`)
  - Cost: `costTrend` (`labels`, `actual`)
  - Eval: `evalTrend` (`labels`, `pass_rate`, `fail_rate`)
- **Workflow meta key:** `_aigis_mermaid_source` (leading underscore, hidden from custom fields UI)
- **Workflow form field:** `name="aigis_diagram_source"` — nonce field: `aigis_workflow_nonce` / action `aigis_save_workflow`
- **Audit trail DB column:** `occurred_at` (not `created_at`)

---

#### Status at end of session

- ✅ All KPI cards rendering correctly
- ✅ Dashboard charts (usage trend, model breakdown) rendering
- ✅ Analytics charts (usage trend, model breakdown, dept cost) rendering
- ✅ Cost chart (monthly spend trend) rendering
- ✅ Eval chart (pass-rate / fail-rate trend) rendering
- ✅ Audit log populating correctly
- ✅ Workflow diagram code paths all corrected; nonce save works
- ⚠️ Existing test-data workflow posts still have the old `aigis_workflow_mermaid` meta key — need Purge + Regenerate to see diagram previews

---

#### Next steps for tomorrow

- [ ] **Regenerate test data** — Developer Tools → Purge Test Data → Generate Test Data. This will create fresh workflow posts with the correct `_aigis_mermaid_source` meta key and verify diagram previews render in the metabox editor.
- [ ] **Workflow list view** — Check that the workflow list screen (CPT list table) looks correct and the status column renders.
- [ ] **Incident CPT full flow** — Create a new incident manually, walk through status changes (Open → Investigating → Resolved), confirm audit trail entries are written.
- [ ] **Policy CPT full flow** — Create a policy, approve it, verify it appears in the dashboard "Policies Expiring Soon" card.
- [ ] **REST API end-to-end test** — `POST /ai-governance/v1/log` with a valid `X-AIGIS-API-Key`, confirm the log row appears in Analytics and the Audit Log.
- [ ] **Guardrail endpoint** — `POST /ai-governance/v1/guardrail/check` with a PII-containing prompt; verify the trigger is blocked and logged.
- [ ] **Stress test sandbox** — Run a test via the Stress Tests page; confirm a result row is written to `wp_aigis_eval_results`.
- [ ] **Budget alert cron** — Manually trigger `aigis_check_budget_alerts` via WP-CLI; verify notifications fire when spend > 80% of budget.
- [ ] **User Manual review** — Read through the manual in the admin and check all sections are accurate against the final implementation.
- [ ] **PHP lint sweep** — Run `php -l` across all plugin files in the Docker container to catch any remaining syntax issues before first release tag.
- [ ] **README / repo** — Push to private GitHub repo with MIT licence. ✅ Done this session (initial push).

