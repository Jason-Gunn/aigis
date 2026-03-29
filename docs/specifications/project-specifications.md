# AI Governance and Infrastructure Suite — Project Specification
**Version:** 1.0.0-draft  
**Date:** 2026-03-24  
**Status:** Handoff-ready for implementation  
**Audience:** AI coding agent (Claude Opus / Sonnet) or human developer assigned to build this plugin

---

## How to Read This Document

- Features are identified by ID (e.g., **F-01**) and assigned to a phase (v1, v2, v3).
- Build phases in order. Do not begin Phase 2 until Phase 1 is complete and tested.
- Every feature section follows the same structure: **User Story → Data Model → UI Description → Access Control → Dependencies**.
- Database schema for all custom tables is in Section 4. All `CREATE TABLE` statements are final and ready to execute.
- The REST API reference (Section 6) and Integration Guide (Section 8) are written for the agents and services that will send data *into* this plugin.
- Where a decision was made between two valid approaches, the rationale is documented inline so the implementer does not re-litigate it.

---

## Table of Contents

1. [Plugin Overview](#1-plugin-overview)
2. [Plugin Architecture](#2-plugin-architecture)
3. [WordPress Roles & Capabilities](#3-wordpress-roles--capabilities)
4. [Database Schema](#4-database-schema)
5. [Feature Specifications](#5-feature-specifications)
   - [Phase 1: Foundation (v1)](#phase-1-foundation-v1)
   - [Phase 2: Analytics & Operations (v2)](#phase-2-analytics--operations-v2)
   - [Phase 3: Advanced Governance (v3)](#phase-3-advanced-governance-v3)
6. [REST API Reference](#6-rest-api-reference)
7. [Plugin Settings Reference](#7-plugin-settings-reference)
8. [Integration Guide](#8-integration-guide)
9. [Phasing Summary Table](#9-phasing-summary-table)

---

## 1. Plugin Overview

### 1.1 Purpose

The **AI Governance and Infrastructure Suite** is a WordPress plugin for internal governance teams who need a single, structured place to manage, audit, and continuously improve their organization's use of AI systems. It provides visibility into what AI tools are deployed, how they are being used, whether they are performing safely and faithfully, and who is accountable for each system.

The plugin is designed for a single organization on a single WordPress installation. It is not a SaaS product and does not need multisite support.

### 1.2 Problem Statement

As organizations adopt more AI tools, governance becomes fragmented: prompts live in personal notes, usage policies exist as PDFs, model inventories are in spreadsheets, incidents are tracked nowhere, and nobody really knows whether guardrails are working. This plugin centralizes that entire surface area into one system with audit trails, access control, and the automation needed to keep governance from becoming a ceremonial burden.

### 1.3 Technology Stack

| Layer | Choice | Notes |
|---|---|---|
| Language | PHP 8.1+ | Required minimum |
| Platform | WordPress 6.4+ | Required minimum |
| Database | MySQL 8.0+ / MariaDB 10.5+ | Uses custom tables + WP CPTs |
| Frontend | WordPress admin UI | Standard WP admin patterns only — no React, no Vue, no build step |
| Charts | Chart.js 4.x | Loaded via `wp_enqueue_scripts`; data passed via `wp_localize_script` |
| Diagrams | Mermaid.js 10.x | Loaded only on workflow diagram pages; rendered client-side from Mermaid syntax stored in post meta |
| REST API | WP REST API | Namespace: `ai-governance/v1` |
| Auth (REST) | WP Application Passwords | For agent integrations; also supports a plugin-managed API key |

### 1.4 Scope & Constraints

- **Single-tenant.** One organization, one WP install.
- **AI systems in scope:** Custom-built agents (full API access), API-connected models (OpenAI, Anthropic, Gemini), on-premise models (Ollama, LM Studio).
- **AI systems out of scope:** Black-box third-party tools (ChatGPT web, Copilot desktop, Gemini web) — these cannot submit structured telemetry.
- **Compliance frameworks:** No mapping to specific regulatory frameworks (EU AI Act, NIST, ISO 42001) in this version. The schema is designed to accommodate this in a future phase without schema migration.
- **Email:** All notification emails use `wp_mail()`. No third-party mail library.
- **Background processing:** WP-Cron for scheduled tasks (budget alerts, false-negative sampling). No external job queue.
- **No breaking changes between phases.** v2 and v3 activate additional features on top of v1 data — they do not change v1 data structures.

---

## 2. Plugin Architecture

### 2.1 Directory Structure

```
ai-governance-suite/
├── ai-governance-suite.php          # Plugin bootstrap (header comment, version constants, boot call)
├── uninstall.php                    # Cleanup on plugin deletion (drops custom tables, removes options)
├── readme.txt                       # WP plugin repo readme
│
├── includes/
│   ├── core/
│   │   ├── class-aigis-loader.php        # Hook registration engine (arrays of actions + filters)
│   │   ├── class-aigis-activator.php     # Activation hook: create tables, register caps, flush rewrite rules
│   │   ├── class-aigis-deactivator.php   # Deactivation hook: flush rewrite rules, clear cron
│   │   └── class-aigis-plugin.php        # Main orchestrator: loads all components, wires them to Loader
│   │
│   ├── cpt/                         # Custom Post Type registrations
│   │   ├── class-aigis-cpt-prompt.php
│   │   ├── class-aigis-cpt-policy.php
│   │   ├── class-aigis-cpt-workflow.php
│   │   └── class-aigis-cpt-incident.php
│   │
│   ├── db/                          # Database abstraction layer
│   │   ├── class-aigis-db.php        # Base DB class: table name helpers, generic CRUD
│   │   ├── class-aigis-db-inventory.php
│   │   ├── class-aigis-db-usage-log.php
│   │   ├── class-aigis-db-audit.php  # Insert-only; throws on attempted update/delete
│   │   ├── class-aigis-db-incidents.php
│   │   ├── class-aigis-db-cost.php
│   │   └── class-aigis-db-eval.php
│   │
│   ├── admin/                       # Admin page controllers (one class per menu page)
│   │   ├── class-aigis-admin.php     # Registers admin menus, enqueues admin assets
│   │   ├── class-aigis-page-dashboard.php
│   │   ├── class-aigis-page-inventory.php
│   │   ├── class-aigis-page-analytics.php
│   │   ├── class-aigis-page-audit-log.php
│   │   ├── class-aigis-page-incidents.php
│   │   ├── class-aigis-page-cost.php
│   │   ├── class-aigis-page-stress-tests.php
│   │   ├── class-aigis-page-eval.php
│   │   └── class-aigis-page-settings.php
│   │
│   ├── api/                         # REST API route controllers
│   │   ├── class-aigis-rest-controller.php   # Base controller with shared auth helpers
│   │   ├── class-aigis-rest-log.php          # POST /log
│   │   ├── class-aigis-rest-routing.php      # GET /routing/{agent_id}
│   │   ├── class-aigis-rest-guardrail.php    # POST /guardrail-trigger
│   │   └── class-aigis-rest-eval.php         # POST /eval-result
│   │
│   ├── providers/                   # AI provider adapters
│   │   ├── class-aigis-provider-abstract.php # Abstract base: send_prompt(), list_models()
│   │   ├── class-aigis-provider-openai.php
│   │   ├── class-aigis-provider-anthropic.php
│   │   └── class-aigis-provider-ollama.php
│   │
│   └── helpers/
│       ├── class-aigis-capabilities.php      # Capability constants and registration helpers
│       ├── class-aigis-notifications.php     # Email + webhook dispatch
│       ├── class-aigis-pii-detector.php      # Regex-based PII heuristic scanner
│       └── class-aigis-cron.php              # WP-Cron job registration and callbacks
│
├── admin/
│   ├── css/
│   │   └── aigis-admin.css
│   ├── js/
│   │   ├── aigis-admin.js            # General admin JS
│   │   ├── aigis-charts.js           # Chart.js initialization; reads data from localized vars
│   │   └── aigis-workflow-diagram.js # Mermaid.js initialization for workflow view pages
│   └── views/                       # PHP template partials for admin pages
│       ├── dashboard.php
│       ├── inventory/
│       │   ├── list.php
│       │   └── edit.php
│       ├── analytics/
│       │   └── dashboard.php
│       ├── audit-log/
│       │   └── list.php
│       ├── incidents/
│       │   ├── list.php
│       │   └── edit.php
│       ├── cost/
│       │   └── dashboard.php
│       ├── stress-tests/
│       │   ├── list.php
│       │   ├── builder.php
│       │   └── results.php
│       ├── eval/
│       │   └── dashboard.php
│       └── settings/
│           └── settings.php
│
├── docs/
│   ├── development/
│   │   └── dev-journal.md
│   ├── reference/
│   │   └── user-manual.html
│   └── specifications/
│       └── project-specifications.md    # This document
│
└── tests/
  └── .gitkeep
```

### 2.2 Boot Sequence

1. `ai-governance-suite.php` defines constants (`aigis_VERSION`, `aigis_PLUGIN_DIR`, `aigis_PLUGIN_URL`) and calls `run_aigis()`.
2. `run_aigis()` instantiates `aigis_Plugin`, which instantiates `aigis_Loader` and all component classes.
3. Each component class receives the Loader in its constructor and registers its own hooks via `$loader->add_action()` / `$loader->add_filter()`.
4. `aigis_Plugin::run()` calls `$loader->run()`, which iterates the registered hooks and calls `add_action()` / `add_filter()` on each.

### 2.3 Namespace & Class Naming Convention

- All classes prefixed: `aigis_`
- File naming: `class-aigis-{component}.php` (WordPress convention)
- No PHP namespaces (standard WordPress plugin convention; avoids autoloading complexity without Composer)

### 2.4 WordPress Admin Menu Structure

```
AI Governance (top-level)
├── Dashboard                    (aigis_view_dashboard)
├── AI Inventory                 (aigis_manage_inventory)
├── Prompts                      (aigis_manage_prompts)   → uses CPT edit screen
├── Policies                     (aigis_manage_policies)  → uses CPT edit screen
├── Workflows                    (aigis_manage_workflows) → uses CPT edit screen
├── Analytics                    (aigis_view_analytics)
├── Incidents                    (aigis_manage_incidents)
├── Audit Log                    (aigis_view_audit_log)
├── Cost & Budget                (aigis_view_cost)
├── Stress Tests                 (aigis_run_stress_tests)
├── Evaluation                   (aigis_view_eval)
└── Settings                     (manage_options)
```

The capability listed in parentheses is the minimum capability required to see each menu item.

### 2.5 Loader Class Pattern

`aigis_Loader` maintains four arrays:
- `$actions` — `[ [hook, component, method, priority, args], ... ]`
- `$filters` — same structure
- `$shortcodes` — `[ [tag, component, method], ... ]`
- `$rest_routes` — `[ component_instance, ... ]` — each rest component implements `register_routes()`

`run()` iterates each array and calls the corresponding WP registration function.

### 2.6 Custom Post Types vs. Custom Tables

**Decision:** Use Custom Post Types (CPT) for content that benefits from WordPress's editorial workflow (revisions, statuses, meta, comments). Use custom database tables for high-volume append-only data, relational query patterns WordPress doesn't handle well, or data that should never appear in the WP_Query loop.

| Data | Storage | Reason |
|---|---|---|
| Prompts | CPT (`aigis_prompt`) | Needs WP revisions for version history |
| Policies | CPT (`aigis_policy`) | Needs WP revisions + custom statuses |
| Workflows | CPT (`aigis_workflow`) | Benefits from WP editorial features |
| Incidents | CPT (`aigis_incident`) | Needs editorial workflow (assign, status) |
| AI Inventory | Custom table | Structured relational data; no revision need |
| Usage logs | Custom table | High-volume append-only |
| Audit trail | Custom table | Append-only; must never be editable via WP |
| Cost records | Custom table | Relational aggregate queries |
| Eval results | Custom table | High-volume; complex queries |
| Stress test data | Custom tables (2) | Structured; complex relationships |

---

## 3. WordPress Roles & Capabilities

### 3.1 Custom Capabilities

Register all capabilities on the `init` action via `aigis_Capabilities::register()`. Capabilities are added to WP roles using `WP_Role::add_cap()`. On plugin deletion (`uninstall.php`), all capabilities are removed.

| Capability | Description |
|---|---|
| `aigis_view_dashboard` | View the governance dashboard |
| `aigis_manage_inventory` | Create, edit, delete AI inventory records |
| `aigis_view_inventory` | Read-only access to inventory |
| `aigis_manage_prompts` | Create, edit, delete, and publish prompts |
| `aigis_review_prompts` | Can approve prompt promotion to production |
| `aigis_view_prompts` | Read-only access to prompts |
| `aigis_manage_policies` | Create, edit, delete policies |
| `aigis_review_policies` | Can approve policy publication |
| `aigis_view_policies` | Read-only access to policies |
| `aigis_manage_workflows` | Create, edit, delete workflow maps |
| `aigis_view_workflows` | Read-only access to workflow maps |
| `aigis_view_analytics` | Access to usage analytics dashboard |
| `aigis_manage_incidents` | Create, edit, assign, close incidents |
| `aigis_view_incidents` | Read-only access to incidents |
| `aigis_view_audit_log` | View the audit trail log |
| `aigis_export_audit_log` | Export audit trail to CSV |
| `aigis_view_cost` | View cost & budget dashboard |
| `aigis_manage_cost` | Manage budget records |
| `aigis_run_stress_tests` | Create and execute stress test runs |
| `aigis_view_stress_tests` | View stress test results |
| `aigis_view_eval` | View evaluation dashboard |
| `aigis_manage_eval` | Manage evaluation rules and review queue |

### 3.2 Role Matrix

The Settings page allows an admin to assign these aigis roles to WP roles. Default assignments on activation:

| Capability | `aigis_admin` | `aigis_analyst` | `aigis_reviewer` | `aigis_viewer` |
|---|:---:|:---:|:---:|:---:|
| `aigis_view_dashboard` | ✓ | ✓ | ✓ | ✓ |
| `aigis_manage_inventory` | ✓ | | | |
| `aigis_view_inventory` | ✓ | ✓ | ✓ | ✓ |
| `aigis_manage_prompts` | ✓ | | | |
| `aigis_review_prompts` | ✓ | | ✓ | |
| `aigis_view_prompts` | ✓ | ✓ | ✓ | ✓ |
| `aigis_manage_policies` | ✓ | | | |
| `aigis_review_policies` | ✓ | | ✓ | |
| `aigis_view_policies` | ✓ | ✓ | ✓ | ✓ |
| `aigis_manage_workflows` | ✓ | ✓ | | |
| `aigis_view_workflows` | ✓ | ✓ | ✓ | ✓ |
| `aigis_view_analytics` | ✓ | ✓ | ✓ | |
| `aigis_manage_incidents` | ✓ | ✓ | | |
| `aigis_view_incidents` | ✓ | ✓ | ✓ | ✓ |
| `aigis_view_audit_log` | ✓ | ✓ | ✓ | |
| `aigis_export_audit_log` | ✓ | ✓ | | |
| `aigis_view_cost` | ✓ | ✓ | | |
| `aigis_manage_cost` | ✓ | | | |
| `aigis_run_stress_tests` | ✓ | ✓ | | |
| `aigis_view_stress_tests` | ✓ | ✓ | ✓ | |
| `aigis_view_eval` | ✓ | ✓ | ✓ | |
| `aigis_manage_eval` | ✓ | | ✓ | |

**Important:** `aigis_admin` is an aigis role, not to be confused with WP's built-in `administrator` role. By default on activation, `administrator` gets all aigis capabilities, but this is configurable on the Settings page.

### 3.3 Capability Checks

Always use `current_user_can( 'capability_name' )` before performing any action or rendering any data. On REST endpoints, check capability in the `permission_callback`. Return `new WP_Error( 'rest_forbidden', ..., [ 'status' => 403 ] )` on failure.

---

## 4. Database Schema

All table names use the WordPress `$wpdb->prefix`. For a default WP install, `wp_` prefix applies. Table creation happens in `aigis_Activator::create_tables()` using `dbDelta()`.

### 4.1 `{prefix}aigis_ai_inventory`

Stores the organization's registered AI tools, models, and integrations.

```sql
CREATE TABLE {prefix}aigis_ai_inventory (
    id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_name     VARCHAR(255)        NOT NULL,
    model_name      VARCHAR(255)        NOT NULL,
    model_version   VARCHAR(100)        NOT NULL DEFAULT '',
    integration_type ENUM('custom-agent','api-model','on-prem') NOT NULL,
    api_endpoint    VARCHAR(500)        NOT NULL DEFAULT '',
    owner_user_id   BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    data_categories TEXT                NOT NULL DEFAULT '',  -- JSON array of strings
    risk_level      ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    status          ENUM('active','deprecated','under-review') NOT NULL DEFAULT 'active',
    notes           LONGTEXT            NOT NULL DEFAULT '',
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_status (status),
    KEY idx_risk_level (risk_level),
    KEY idx_integration_type (integration_type),
    KEY idx_owner (owner_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.2 `{prefix}aigis_usage_logs`

High-volume log of AI interactions submitted by integrated agents. Append-only; no UPDATE or DELETE issued by the plugin.

```sql
CREATE TABLE {prefix}aigis_usage_logs (
    id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    agent_id        VARCHAR(255)        NOT NULL,              -- matches inventory record or free-form
    inventory_id    BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,   -- FK to aigis_ai_inventory.id (soft ref)
    user_id         BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,   -- WP user ID; 0 = anonymous/system
    prompt_post_id  BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,   -- FK to aigis_prompt CPT post ID
    session_id      VARCHAR(255)        NOT NULL DEFAULT '',
    department      VARCHAR(255)        NOT NULL DEFAULT '',
    project_tag     VARCHAR(255)        NOT NULL DEFAULT '',
    input_hash      VARCHAR(64)         NOT NULL DEFAULT '',   -- SHA-256 of input (never store raw input here)
    input_tokens    INT UNSIGNED        NOT NULL DEFAULT 0,
    output_tokens   INT UNSIGNED        NOT NULL DEFAULT 0,
    latency_ms      INT UNSIGNED        NOT NULL DEFAULT 0,
    cost_usd        DECIMAL(10,6)       NOT NULL DEFAULT 0.000000,
    status          ENUM('success','error','timeout','guardrail-blocked') NOT NULL DEFAULT 'success',
    error_code      VARCHAR(100)        NOT NULL DEFAULT '',
    logged_at       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_agent_id (agent_id),
    KEY idx_user_id (user_id),
    KEY idx_inventory_id (inventory_id),
    KEY idx_logged_at (logged_at),
    KEY idx_status (status),
    KEY idx_department (department),
    KEY idx_project_tag (project_tag)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Security note:** Raw prompt text is never stored in this table. Store only `input_hash` (SHA-256). If full prompt storage is needed for a particular prompt, that is handled by the Prompt Store (F-02) with appropriate sensitivity controls.

### 4.3 `{prefix}aigis_audit_trail`

Append-only record of all governance-significant events. No `UPDATE` or `DELETE` is ever issued against this table by the plugin. `aigis_DB_Audit` will throw a `RuntimeException` if `update()` or `delete()` are called.

```sql
CREATE TABLE {prefix}aigis_audit_trail (
    id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    event_type      VARCHAR(100)        NOT NULL,              -- e.g. 'prompt.published', 'policy.approved'
    object_type     VARCHAR(100)        NOT NULL,              -- e.g. 'prompt', 'policy', 'inventory'
    object_id       VARCHAR(255)        NOT NULL,              -- post ID or table row ID
    actor_user_id   BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    actor_ip        VARCHAR(45)         NOT NULL DEFAULT '',   -- IPv4 or IPv6
    summary         VARCHAR(500)        NOT NULL DEFAULT '',
    before_state    LONGTEXT            NOT NULL DEFAULT '',   -- JSON snapshot of record before change
    after_state     LONGTEXT            NOT NULL DEFAULT '',   -- JSON snapshot of record after change
    occurred_at     DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_event_type (event_type),
    KEY idx_object (object_type, object_id),
    KEY idx_actor (actor_user_id),
    KEY idx_occurred_at (occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.4 `{prefix}aigis_cost_budgets`

Budget limits per team, department, or project.

```sql
CREATE TABLE {prefix}aigis_cost_budgets (
    id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    label           VARCHAR(255)        NOT NULL,              -- e.g. "Engineering Q2 2026"
    scope_type      ENUM('department','project','global') NOT NULL DEFAULT 'department',
    scope_value     VARCHAR(255)        NOT NULL DEFAULT '',   -- department name or project_tag value
    inventory_id    BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,   -- 0 = applies to all models
    period_type     ENUM('monthly','custom') NOT NULL DEFAULT 'monthly',
    period_start    DATE                NOT NULL,
    period_end      DATE                NOT NULL,
    budget_usd      DECIMAL(12,2)       NOT NULL DEFAULT 0.00,
    alert_pct_80    TINYINT(1)          NOT NULL DEFAULT 1,   -- send alert at 80%
    alert_pct_100   TINYINT(1)          NOT NULL DEFAULT 1,   -- send alert at 100%
    created_by      BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_scope (scope_type, scope_value),
    KEY idx_period (period_start, period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.5 `{prefix}aigis_stress_test_variations`

Reusable variation type definitions for the Factorial Stress Testing module.

```sql
CREATE TABLE {prefix}aigis_stress_test_variations (
    id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    name            VARCHAR(255)        NOT NULL,
    slug            VARCHAR(100)        NOT NULL,              -- machine-readable, unique
    category        VARCHAR(100)        NOT NULL DEFAULT '',
    description     TEXT                NOT NULL DEFAULT '',
    parameter_schema LONGTEXT           NOT NULL DEFAULT '',   -- JSON Schema defining configurable params
    created_by      BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.6 `{prefix}aigis_stress_test_runs`

Execution records for stress test scenarios. One row per variation × prompt combination.

```sql
CREATE TABLE {prefix}aigis_stress_test_runs (
    id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    run_batch_id    VARCHAR(36)         NOT NULL,              -- UUID grouping all rows in one test session
    prompt_post_id  BIGINT(20) UNSIGNED NOT NULL,
    variation_id    BIGINT(20) UNSIGNED NOT NULL,
    variation_params LONGTEXT           NOT NULL DEFAULT '',   -- JSON: actual param values used
    modified_prompt LONGTEXT            NOT NULL DEFAULT '',   -- full prompt text after variation applied
    provider        VARCHAR(100)        NOT NULL DEFAULT '',   -- 'openai','anthropic','ollama'
    model_used      VARCHAR(100)        NOT NULL DEFAULT '',
    output          LONGTEXT            NOT NULL DEFAULT '',
    score           DECIMAL(5,2)        NOT NULL DEFAULT 0.00, -- evaluator score 0-100
    flagged         TINYINT(1)          NOT NULL DEFAULT 0,
    flag_reason     VARCHAR(500)        NOT NULL DEFAULT '',
    executed_by     BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    executed_at     DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_batch (run_batch_id),
    KEY idx_prompt (prompt_post_id),
    KEY idx_variation (variation_id),
    KEY idx_flagged (flagged)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.7 `{prefix}aigis_eval_results`

Evaluation run records for the Continuous Evaluation Flywheel.

```sql
CREATE TABLE {prefix}aigis_eval_results (
    id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    agent_id        VARCHAR(255)        NOT NULL,
    inventory_id    BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    input_hash      VARCHAR(64)         NOT NULL DEFAULT '',
    expected_output LONGTEXT            NOT NULL DEFAULT '',
    actual_output   LONGTEXT            NOT NULL DEFAULT '',
    evaluator_version VARCHAR(50)       NOT NULL DEFAULT '',
    pass_fail       ENUM('pass','fail','pending-review') NOT NULL DEFAULT 'pass',
    false_negative  TINYINT(1)          NOT NULL DEFAULT 0,   -- 1 = reviewer marked passed-run as actual failure
    reviewer_id     BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,   -- 0 = not yet reviewed
    reviewed_at     DATETIME            DEFAULT NULL,
    reviewer_notes  LONGTEXT            NOT NULL DEFAULT '',
    rulebook_version VARCHAR(50)        NOT NULL DEFAULT '',
    submitted_at    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_agent_id (agent_id),
    KEY idx_pass_fail (pass_fail),
    KEY idx_false_negative (false_negative),
    KEY idx_submitted_at (submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.8 `{prefix}aigis_guardrail_triggers`

Log of guardrail fire events submitted by integrated agents.

```sql
CREATE TABLE {prefix}aigis_guardrail_triggers (
    id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    agent_id        VARCHAR(255)        NOT NULL,
    inventory_id    BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    guardrail_name  VARCHAR(255)        NOT NULL,
    input_hash      VARCHAR(64)         NOT NULL DEFAULT '',
    matched_rule    VARCHAR(500)        NOT NULL DEFAULT '',
    risk_taxonomy   VARCHAR(255)        NOT NULL DEFAULT '',   -- tag from admin-defined taxonomy
    is_keyword_only TINYINT(1)          NOT NULL DEFAULT 0,   -- heuristic: keyword-only match
    severity        ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    triggered_at    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_agent_id (agent_id),
    KEY idx_guardrail (guardrail_name),
    KEY idx_risk_taxonomy (risk_taxonomy),
    KEY idx_triggered_at (triggered_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.9 Custom Post Types — Schema Summary

CPTs use standard WP post structure. Custom fields are stored as post meta. Key meta fields are listed below.

#### `aigis_prompt`

| Meta Key | Type | Description |
|---|---|---|
| `_aigis_linked_inventory_id` | int | FK to `aigis_ai_inventory.id` |
| `_aigis_data_sensitivity` | string | `none` / `internal` / `confidential` / `restricted` |
| `_aigis_prompt_tags` | array | taxonomy `aigis_prompt_tag` |
| `_aigis_promotion_log` | array | JSON array of `{user_id, from_status, to_status, note, timestamp}` |
| `_aigis_sandbox_results` | array | Last N sandbox test outputs (JSON) |

Custom post statuses for `aigis_prompt`: `draft` (WP default), `aigis-staging`, `publish` (= production). Label "publish" as "Production" in admin UI.

#### `aigis_policy`

| Meta Key | Type | Description |
|---|---|---|
| `_aigis_policy_version` | string | Semantic version string, e.g. `2.1.0` |
| `_aigis_effective_date` | string | `Y-m-d` |
| `_aigis_expiration_date` | string | `Y-m-d` (empty = no expiration) |
| `_aigis_approval_log` | array | JSON array of approvals: `{user_id, action, note, timestamp}` |
| `_aigis_linked_incidents` | array | Post IDs of related incidents |

Custom post statuses for `aigis_policy`: `draft`, `aigis-in-review`, `aigis-approved`, `publish` (= in effect), `aigis-retired`.

#### `aigis_workflow`

| Meta Key | Type | Description |
|---|---|---|
| `_aigis_topology` | string | Mermaid.js flowchart syntax string |
| `_aigis_topology_nodes` | array | JSON array of node objects: `{id, label, type, inventory_id, prompt_post_id}` |

Node `type` values: `human-step`, `ai-step`, `decision`, `data-source`.

#### `aigis_incident`

| Meta Key | Type | Description |
|---|---|---|
| `_aigis_severity` | string | `low` / `medium` / `high` / `critical` |
| `_aigis_incident_status` | string | `open` / `investigating` / `resolved` / `closed` |
| `_aigis_affected_inventory_id` | int | FK to `aigis_ai_inventory.id` |
| `_aigis_affected_prompt_id` | int | Post ID of related `aigis_prompt` |
| `_aigis_linked_policy_id` | int | Post ID of related `aigis_policy` |
| `_aigis_linked_guardrail_trigger_id` | int | FK to `aigis_guardrail_triggers.id` |
| `_aigis_detected_at` | string | ISO 8601 datetime when issue first detected |
| `_aigis_root_cause` | string | Short root cause summary |
| `_aigis_resolution_notes` | string | How it was resolved |
| `_aigis_post_mortem` | string | Full post-mortem (WP editor content stored as meta) |
| `_aigis_assigned_user_id` | int | WP user ID |
| `_aigis_status_log` | array | JSON array: `{status, user_id, note, timestamp}` |

---

## 5. Feature Specifications

---

### Phase 1: Foundation (v1)

---

#### F-01: AI Vendor & Model Inventory

**User Story:**  
As a governance team member, I need a single registry of every AI tool and model our organization uses so I can answer "what AI do we have, who owns it, and what data does it touch?" without hunting through spreadsheets.

**Data Model:**  
Custom table: `{prefix}aigis_ai_inventory` (see Section 4.1).

**UI Description:**

*List View* (`AI Governance → AI Inventory`):
- `WP_List_Table` subclass displaying all inventory records.
- Columns: Vendor, Model Name/Version, Integration Type (badge), Owner (WP user display name), Risk Level (color-coded badge: green/yellow/red), Status (badge), Date Added.
- Bulk actions: Delete.
- Filterable by: Status, Risk Level, Integration Type (as dropdown filters above the table).
- Search box: searches vendor name and model name.

*Add/Edit Form* (`/wp-admin/admin.php?page=aigis-inventory&action=edit`):
- Fields: Vendor Name (text, required), Model Name (text, required), Model Version (text), Integration Type (select: Custom Agent / API Model / On-Premise), API Endpoint (text), Owner (WP user select), Data Categories Touched (multi-checkbox: PII / Financial / Health / Proprietary / Public), Risk Level (select), Status (select), Notes (textarea).
- On save: write to `aigis_ai_inventory` table; write audit trail event `inventory.created` or `inventory.updated`.

*Delete:* Soft/hard decision — **use hard delete** (remove row) since inventory items can be archived via `status = 'deprecated'`. Delete action writes `inventory.deleted` audit event with full `before_state` snapshot before deletion.

**Access Control:**
- List view: `aigis_view_inventory`
- Add/Edit/Delete: `aigis_manage_inventory`

**Dependencies:** F-04 (Audit Log), F-05 (Capabilities)

---

#### F-02: Prompt Store & Version Control

**User Story:**  
As a prompt engineer, I want a versioned, access-controlled repository of our prompts so I can track what changed, who approved a production prompt, roll back if something breaks, and test before promoting.

**Data Model:**  
CPT: `aigis_prompt` with meta fields listed in Section 4.9. Version history stored via WP post revisions (enabled by including `'revisions' => true` in `supports` array when registering CPT).

**UI Description:**

The CPT uses the standard WP edit screen, extended with metaboxes:

*Metabox: Prompt Settings*
- Linked AI Model (select from `aigis_ai_inventory` active records)
- Data Sensitivity (select: None / Internal / Confidential / Restricted)
- Tags (multi-select from taxonomy `aigis_prompt_tag`)

*Metabox: Promotion Status*
- Current status displayed (Draft / Staging / Production) with last-modified date and user.
- "Request Review" button (moves to `aigis-staging`, notifies users with `aigis_review_prompts`).
- For users with `aigis_review_prompts`: "Approve to Production" and "Reject" buttons with required comment field.
- Full promotion log displayed as a timeline (read-only).

*Metabox: Sandbox Test*
- "Run Sandbox Test" button: sends current prompt text to the linked AI model's provider using `aigis_Provider_Abstract::send_prompt()`.
- Provider credentials resolved from plugin Settings (Section 7).
- Output displayed inline; stored in `_aigis_sandbox_results` meta (keep last 5 results).
- Running a sandbox test does not change post status.

*Metabox: Version History*
- Renders WP revisions list with "View Diff" and "Restore" links.
- Each revision augmented with promotion log attribution if applicable.

*List View:*
- Standard WP CPT list table.
- Additional column: Status Badge (color-coded by promotion status).
- Additional column: Sensitivity Level (badge).
- Filter by: status, sensitivity, linked model.

**Access Control:**
- View list + read prompts: `aigis_view_prompts`
- Create/edit prompts: `aigis_manage_prompts`
- Approve to production / reject: `aigis_review_prompts`
- Only users with `aigis_manage_prompts` can set a prompt to `publish`/Production directly (bypasses review).

**Data Privacy:** When a prompt is saved with sensitivity `confidential` or `restricted`, an audit event `prompt.sensitive_access` is written every time the post edit screen loads for that prompt.

**PII Detection Integration:** On save, run `aigis_PII_Detector::scan( $post_content )`. If PII patterns are found and sensitivity is set to `none` or `internal`, display an admin notice: *"Possible PII detected in this prompt. Consider updating the sensitivity classification."* Do not block save.

**Dependencies:** F-01, F-04, F-05, Provider Adapters (Section 2.1 `includes/providers/`)

---

#### F-03: AI Usage Policy Repository

**User Story:**  
As a compliance-minded admin, I need a versioned, centrally accessible repository of our AI usage policies so staff know the current rules, reviewers can approve changes, and I have a clear record of when policies changed and who approved each version.

**Data Model:**  
CPT: `aigis_policy` with meta fields listed in Section 4.9.

**Custom Post Statuses** — register via `register_post_status()`:

| WP Status Key | Label | Description |
|---|---|---|
| `draft` | Draft | In progress |
| `aigis-in-review` | In Review | Awaiting reviewer approval |
| `aigis-approved` | Approved | Approved, not yet in effect |
| `publish` | In Effect | Live policy (label as "In Effect" in admin) |
| `aigis-retired` | Retired | Superseded or expired |

**UI Description:**

*Edit Screen Metaboxes:*

*Policy Details:*
- Version Number (text, e.g. `2.1.0`)
- Effective Date (date picker)
- Expiration Date (date picker; optional)
- Policy Status (custom select reflecting above statuses)

*Approval Workflow:*
- Current status + approver name + approval date (if approved).
- "Submit for Review" button (sets status to `aigis-in-review`; sends `wp_mail` notification to all users with `aigis_review_policies`).
- For users with `aigis_review_policies`: "Approve" / "Reject" buttons with mandatory comment.
- Approval log timeline (read-only).

*Linked Incidents:*
- Read-only list of `aigis_incident` posts that reference this policy.

*List View:*
- Columns: Title, Version, Effective Date, Status (badge), Owner, Last Modified.
- Filter by: status.

**Policy Expiration Alert:** WP-Cron job (`aigis_check_policy_expiry`) runs daily. For any policy with `status = publish` and `_aigis_expiration_date` within the configurable alert window (default: 30 days), send notification per F-12 alert rules.

**Dependencies:** F-04, F-05, F-12 (for expiry alerts; degrade gracefully if F-12 not installed)

---

#### F-04: Audit Trail

**User Story:**  
As an admin or auditor, I need an immutable, searchable record of every significant action in the governance system so I can answer "what happened, when, and who did it?" without depending on human memory.

**Data Model:**  
Custom table: `{prefix}aigis_audit_trail` (see Section 4.3). The DB layer class `aigis_DB_Audit` exposes only `insert()` and query methods. `update()` and `delete()` methods throw `RuntimeException` with message: *"The audit trail is append-only and cannot be modified."*

**Audit Events to Log (minimum):**

| Event Type | Trigger |
|---|---|
| `inventory.created` | New inventory record saved |
| `inventory.updated` | Inventory record edited |
| `inventory.deleted` | Inventory record deleted |
| `prompt.created` | New prompt post saved |
| `prompt.updated` | Prompt post content or meta updated |
| `prompt.status_changed` | Prompt moved to staging or production |
| `prompt.sensitive_accessed` | Confidential/restricted prompt edit screen opened |
| `policy.created` | New policy saved |
| `policy.status_changed` | Policy status transitions |
| `policy.approved` | Policy approved by reviewer |
| `incident.created` | Incident post created |
| `incident.status_changed` | Incident status updated |
| `incident.assigned` | Incident assigned to user |
| `budget.created` | Budget record created |
| `budget.updated` | Budget record updated |
| `settings.updated` | Plugin settings page saved |
| `capability.changed` | Role capability assignment changed |
| `api_key.regenerated` | Plugin API key regenerated |
| `user.login` | WP user login (hook: `wp_login`) |

**UI Description:**

*List View* (`AI Governance → Audit Log`):
- `WP_List_Table` subclass.
- Columns: Date/Time, Event Type, Object Type, Object ID (linked if still exists), Actor (username), IP Address, Summary.
- Filters: Date range (from/to date pickers), Event Type (dropdown), Actor (user search).
- Rows are read-only. No edit or delete UI. No bulk delete.
- "Export CSV" button (requires `aigis_export_audit_log`): exports current filtered view.

**Retention:** Plugin setting `aigis_audit_retention_days` (default: 365). WP-Cron job `aigis_prune_audit_log` runs daily and deletes records older than the retention period. This is the **only** case where `DELETE` is issued against `aigis_audit_trail`.

**IP Address Logging:** Use `$_SERVER['REMOTE_ADDR']`; run through `sanitize_text_field()`. If behind a proxy, check `HTTP_X_FORWARDED_FOR` only if a plugin setting `aigis_trust_proxy_headers` is enabled (disabled by default to prevent IP spoofing).

**Dependencies:** F-05

---

#### F-05: WordPress Capabilities & Role Setup

**User Story:**  
As a WordPress admin, I need to control who in my organization can do what in the governance suite using familiar WP role management.

**Implementation:**

*Activation (`aigis_Activator`):*
1. Register all capabilities from Section 3.1 on the `administrator` WP role.
2. Create four new WP roles: `aigis_admin`, `aigis_analyst`, `aigis_reviewer`, `aigis_viewer` with default capabilities per Section 3.2.

*Deactivation (`aigis_Deactivator`):*
1. Does **not** remove capabilities or roles (data preserved for reactivation).

*Uninstall (`uninstall.php`):*
1. Remove all aigis capabilities from all WP roles.
2. Remove the four custom WP roles.

*Settings Page — Roles & Permissions Tab:*
- Table: rows = aigis capabilities; columns = WP roles present in the system.
- Checkbox grid; save writes capability changes via `$role->add_cap()` / `$role->remove_cap()`.
- Each change triggers audit event `capability.changed`.
- Only WP `administrator` can access this tab.

**Dependencies:** F-04

---

### Phase 2: Analytics & Operations (v2)

---

#### F-06: AI Workflow Documentation & Mapping

**User Story:**  
As a process owner, I want to document workflows where AI is embedded so anyone can see at a glance which steps are automated, which model handles each step, and where human decisions are required.

**Data Model:**  
CPT: `aigis_workflow` with meta fields in Section 4.9. The workflow topology is stored as both a Mermaid.js syntax string (`_aigis_topology`) and a structured JSON array of node objects (`_aigis_topology_nodes`) for programmatic querying.

**UI Description:**

*Edit Screen Metaboxes:*

*Workflow Diagram:*
- Textarea for Mermaid.js flowchart syntax.
- "Preview Diagram" button: renders topology inline using Mermaid.js (loaded only on this page via `wp_enqueue_script`).
- Validation: on save, attempt to parse the Mermaid syntax and return an admin notice if invalid.

*Node Registry:*
- Table of nodes parsed from the topology.
- For each `ai-step` node: link to the corresponding AI Inventory record (select dropdown).
- For each `ai-step` node: optionally link to a Prompt (select from `aigis_prompt` CPT).
- Node metadata is saved to `_aigis_topology_nodes`.

*List View:*
- Columns: Workflow Title, Description, AI Systems Used (count, linked to inventory), Last Modified, Status.

**Dependencies:** F-01, F-02, F-04, F-05

---

#### F-07: Employee AI Usage Analytics

**User Story:**  
As a governance analyst, I want to see how AI tools are being used across teams so I can identify adoption patterns, measure efficiency gains, and surface problem areas.

**Data Model:**  
`{prefix}aigis_usage_logs` (see Section 4.2). Data populated via REST API (see Section 6, endpoint `POST /log`).

**UI Description:**

*Analytics Dashboard* (`AI Governance → Analytics`):

All charts rendered with Chart.js 4.x. Data passed from PHP to JS via `wp_localize_script( 'aigis-charts', 'aigisChartData', $data_array )`.

*Date Range Selector:* "from" and "to" date inputs at top of page. On change, reloads page with query params (`date_from`, `date_to`). Default: last 30 days.

*KPI Cards Row:*
- Total Sessions (period)
- Unique Users (period)
- Total API Cost (period)
- Error Rate (%)
- Avg. Latency (ms)

*Charts:*
1. **Sessions Over Time** — line chart, daily resolution, breakable by department (toggle).
2. **Top Prompts Used** — horizontal bar chart, top 10 prompt post IDs (linked to prompt titles).
3. **Model Usage Breakdown** — doughnut chart by `inventory_id`.
4. **Error Rate by Model** — bar chart: `status = 'error'` count / total count per inventory item.
5. **Cost by Department** — grouped bar chart: `SUM(cost_usd)` grouped by `department`.

*Export:* "Export CSV" button writes filtered `aigis_usage_logs` rows to a CSV download. Requires `aigis_export_audit_log` capability (reuse this capability; do not add a separate one).

**Dependencies:** F-01, F-02, F-05; data from REST API `POST /log`

---

#### F-08: Change Management & Approval Workflows

**User Story:**  
As a governance admin, I need structured review workflows for high-stakes changes — specifically, promoting a prompt to production and publishing a policy — so no change goes live without appropriate sign-off.

**Implementation:**  
Approval workflows are built into F-02 and F-03 as metabox UI. This feature spec defines the **shared workflow engine** that both use.

*Workflow Engine (`aigis_Workflow_Engine` class in `includes/helpers/`):*

```
States: draft → in_review → approved → published
                         → rejected → draft
```

Methods:
- `submit_for_review( $post_id, $post_type, $actor_id, $note )` — validates current state is `draft`, transitions to `in_review`, notifies reviewers, writes audit event.
- `approve( $post_id, $post_type, $actor_id, $note )` — validates actor has review capability, transitions to `approved`, writes audit event.
- `reject( $post_id, $post_type, $actor_id, $note )` — transitions to `draft` (not `rejected` status — content returns to draft for editing), records rejection in approval log, writes audit event.
- `publish( $post_id, $post_type, $actor_id, $note )` — validates state is `approved`, transitions to `publish`, writes audit event. For prompts: sets post status to `publish`. For policies: sets post status to `publish` and sets `_aigis_effective_date` to current date if not already set.

Notifications use `aigis_Notifications::send()` which dispatches `wp_mail` and optionally a webhook.

**Access Control:** Reviewers are determined by the capability required per content type: `aigis_review_prompts` for prompts, `aigis_review_policies` for policies.

**Dependencies:** F-02, F-03, F-04, F-05, F-12

---

#### F-09: Incident Management

**User Story:**  
As a governance team lead, I need a structured place to log, investigate, resolve, and post-mortem AI-related failures so nothing slips through the cracks.

**Data Model:**  
CPT: `aigis_incident` with meta fields in Section 4.9.

**Custom Post Statuses:**

| Status Key | Label |
|---|---|
| `aigis-open` | Open |
| `aigis-investigating` | Investigating |
| `aigis-resolved` | Resolved |
| `publish` | Closed |

**UI Description:**

*Edit Screen Metaboxes:*

*Incident Details:*
- Severity (select: Low / Medium / High / Critical)
- Incident Status (select from above)
- Detected At (datetime)
- Assigned To (WP user select)

*Linked Records:*
- Affected AI System (select from `aigis_ai_inventory`)
- Affected Prompt (select from `aigis_prompt`)
- Linked Policy (select from `aigis_policy`)
- Linked Guardrail Trigger (int field; links to `aigis_guardrail_triggers.id`)

*Investigation:*
- Root Cause (textarea, 500 chars)
- Resolution Notes (textarea)

*Post-Mortem:*
- Full WP editor block (stored in `_aigis_post_mortem` meta, not post content — post content is used for the incident description).

*Status & Assignment Log:*
- Read-only timeline of all status transitions and reassignments with actor names and timestamps.

*List View:*
- Columns: Title, Severity (color badge), Status (badge), Affected System, Assigned To, Detected At.
- Filter by: Severity, Status, Assigned User.

**Auto-creation from Guardrails:** When a guardrail trigger with `severity = 'critical'` is submitted via REST API, the plugin auto-creates an incident post: title = `[AUTO] Critical Guardrail: {guardrail_name}`, severity = `critical`, status = `aigis-open`. Sends alert notification.

**Dependencies:** F-01, F-02, F-03, F-04, F-05, F-12

---

#### F-10: Cost & Budget Governance

**User Story:**  
As a finance-aware admin, I need to track AI API spending against defined budgets by team and project so I can catch runaway costs before they become significant.

**Data Model:**  
`{prefix}aigis_cost_budgets` (Section 4.4). Cost actuals sourced from `SUM(cost_usd)` aggregated from `{prefix}aigis_usage_logs`.

**UI Description:**

*Cost Dashboard* (`AI Governance → Cost & Budget`):

*Budget Summary Cards:* One card per active budget. Displays label, scope, period, `budget_usd`, `actual_spend_usd` (computed), `%_used`, color-coded progress bar (green < 70%, yellow 70-90%, red > 90%).

*Budget Management:*
- "Add Budget" button opens a modal or separate form.
- Fields: Label, Scope Type (Department / Project / Global), Scope Value, AI System (optional, from inventory), Period Type, Period Start, Period End, Budget USD, alert thresholds.
- Edit/Delete existing budgets in list below summary cards.

*Spend by Model chart:* Bar chart of `SUM(cost_usd)` per `inventory_id` for the current period.

*Spend Over Time chart:* Line chart of daily `SUM(cost_usd)` for the selected period.

**Alert Mechanism:** WP-Cron job `aigis_check_budget_alerts` runs daily:
1. For each active budget, compute `SUM(cost_usd)` from `aigis_usage_logs` matching scope/period.
2. If spend crosses 80% and `alert_pct_80 = 1` and no alert sent yet this period: send notification (F-12).
3. If spend crosses 100% and `alert_pct_100 = 1`: send notification.
4. Store alert-sent timestamps in a transient to prevent duplicate alerts: key `aigis_budget_alert_{budget_id}_{pct}`.

**Access Control:** View: `aigis_view_cost`. Create/Edit/Delete budgets: `aigis_manage_cost`.

**Dependencies:** F-01, F-04, F-05, F-07 (usage log data), F-12

---

#### F-11: Data Privacy & Classification Layer

**User Story:**  
As a data protection officer, I need confidence that prompts containing sensitive or regulated data are handled with appropriate controls — not stored in plain text, and not accessible to just anyone.

**Implementation:**  
This feature extends F-02's sensitivity meta and adds enforcement behaviors.

*Sensitivity Levels:*

| Level | Default Storage Behavior | Access |
|---|---|---|
| `none` | Full prompt stored | All with `aigis_view_prompts` |
| `internal` | Full prompt stored | All with `aigis_view_prompts` |
| `confidential` | Full prompt stored + access-logged | Users with `aigis_manage_prompts` + `aigis_review_prompts` only |
| `restricted` | Hash stored only (prompt body replaced with `[REDACTED — RESTRICTED]`) | Users with `aigis_manage_prompts` only |

*Behavior on Save:*
- If sensitivity = `restricted`: overwrite `post_content` with `[REDACTED — RESTRICTED]` before saving. The prompt for actual use must be retrieved from the provider's own prompt library or from a separate secure store outside this plugin. Log an audit event.
- If sensitivity = `confidential`: store full text; gate view access via capability check on the edit screen `load-post.php` hook.

*PII Detection (`aigis_PII_Detector`):*
- Scans prompt text on `save_post` hook (before DB write).
- Patterns to detect: email addresses, US phone numbers, US SSN pattern, credit card number pattern.
- On detection: display admin notice; log `prompt.pii_detected` audit event. Does NOT block save.
- Settings control: `aigis_pii_detection_enabled` (default: true).

*Sandbox Security:* Prompts with sensitivity = `restricted` cannot be sent via the sandbox test feature. Button is disabled and shows tooltip: *"Restricted prompts cannot be sent via sandbox."*

**Dependencies:** F-02, F-04, F-05

---

#### F-12: Alerting & Notifications

**User Story:**  
As a governance admin, I need to know when important events happen — not by checking dashboards manually, but via proactive notifications.

**Data Model:**  
Alert rules stored in WP options as a serialized array under key `aigis_alert_rules`. Each rule:

```json
{
  "id": "uuid",
  "trigger": "budget.threshold_80",
  "condition": {},
  "channels": ["email", "webhook"],
  "email_recipients": ["user_ids or email addresses"],
  "webhook_url": "https://...",
  "enabled": true
}
```

**Built-in Trigger Types:**

| Trigger Key | Description |
|---|---|
| `budget.threshold_80` | Budget usage reaches 80% |
| `budget.threshold_100` | Budget usage reaches 100% |
| `guardrail.critical_triggered` | Critical guardrail fires |
| `incident.critical_opened` | Critical incident created |
| `policy.expiring` | Policy expiring within alert window |
| `prompt.published` | Any prompt promoted to production |
| `eval.false_negative_detected` | Passed eval run marked as false negative |

**Notification Channels:**

*Email:* `aigis_Notifications::send_email( $recipients, $subject, $body )` — wraps `wp_mail()`. Body is plain text formatted by a template.

*Webhook:* `aigis_Notifications::send_webhook( $url, $payload )` — `wp_remote_post()` with JSON payload:
```json
{
  "event": "trigger_key",
  "timestamp": "ISO 8601",
  "plugin": "ai-governance-suite",
  "data": { ... event-specific fields }
}
```
Webhook URL must be stored as a settings value and sanitized (validate it is an HTTPS URL; reject HTTP unless a dev-mode setting is enabled). Do not log webhook response bodies to avoid credential leakage.

*In-App Inbox:* A dedicated admin page (`AI Governance → Notifications`) displays recent notification history, read/unread status. Stored in WP options (ring buffer, capped at 100 entries).

**Settings UI:**  
Alert rules list with Add / Edit / Delete / Enable/Disable toggles. Edit form: trigger type (select), channels (checkboxes), email recipients (user multi-select + raw email input), webhook URL (text, validated). Test button: sends a test payload to the configured channel.

**Dependencies:** F-04, F-05

---

### Phase 3: Advanced Governance (v3)

---

#### F-13: Factorial Stress Testing Module

**User Story:**  
As an AI quality engineer, I want to systematically apply controlled variations to our agent prompts to discover how they fail under unusual-but-realistic conditions before those conditions appear in production.

**Data Model:**  
`{prefix}aigis_stress_test_variations` (Section 4.5), `{prefix}aigis_stress_test_runs` (Section 4.6).

**Built-in Variation Types (seeded on activation):**

| Slug | Name | Parameters |
|---|---|---|
| `social-anchoring` | Social Anchoring | `authority_figure` (string), `confidence_level` (low/medium/high) |
| `time-pressure` | Time Pressure | `urgency_level` (mild/moderate/extreme) |
| `conflicting-instructions` | Conflicting Instructions | `conflict_type` (role/safety/factual) |
| `tool-failure` | Tool Failure Simulation | `failure_mode` (unavailable/slow/wrong-output) |
| `ambiguous-input` | Ambiguous Input | `ambiguity_type` (pronoun/scope/format) |
| `role-escalation` | Role Escalation Attempt | `escalation_vector` (jailbreak/override/persona-shift) |

**UI Description:**

*Variation Types Registry* (`Stress Tests → Variation Types` sub-tab):
- List of registered variation types (built-in + custom).
- Add custom variation type: name, slug (auto-generated, editable), category, description, parameter schema (JSON Schema editor).

*Test Builder* (`Stress Tests → New Test`):
1. Select base prompt (from Prompt Store).
2. Select variation types to apply (multi-select). For each selected, fill in parameter values.
3. Select provider (OpenAI / Anthropic / Ollama) and model.
4. Review generated test matrix (N prompt × M variations = N×M runs).
5. "Run Tests" button: dispatches runs synchronously (for < 20 runs) or via WP-Cron batches (for ≥ 20 runs). Progress shown on page.

*Results View* (`Stress Tests → {batch_id}`):
- Table: rows = runs; columns = Base Prompt, Variation, Parameters, Score, Flagged (✓/✗), Output (truncated, click to expand).
- Visual highlight: rows where `flagged = 1`.
- "Inverted U Pattern" detector: if runs with `urgency_level = moderate` (for time-pressure variations) score higher than both `mild` and `extreme`, display a highlighted callout: *"Inverted U performance curve detected — moderate pressure improves performance, while extreme pressure degrades it."* The precise detection logic is: sort runs by a numeric pressure parameter; flag if middle-range value scores higher than both extremes by > 10 points.
- Export results as CSV.

**Provider Adapters:**  
`aigis_Provider_Abstract` defines:
```php
abstract public function send_prompt( string $prompt, array $options = [] ): array;
// Returns: ['output' => string, 'tokens_in' => int, 'tokens_out' => int, 'latency_ms' => int, 'error' => string|null]

abstract public function list_models(): array;
// Returns: ['model_id' => string, 'label' => string][]
```

Concrete implementations: `aigis_Provider_OpenAI`, `aigis_Provider_Anthropic`, `aigis_Provider_Ollama`. API keys/endpoints configured in plugin Settings.

**Access Control:**
- Run tests: `aigis_run_stress_tests`
- View results: `aigis_view_stress_tests`

**Dependencies:** F-01, F-02, F-04, F-05, Provider Adapters

---

#### F-14: Reasoning Trace Validator

**User Story:**  
As a governance engineer, I want to detect when an agent's stated reasoning contradicts its actual output so I can identify faithfulness failures before they erode trust in our AI systems.

**Data Model:**  
Validation records stored as posts under a dedicated CPT `aigis_trace_validation`, or as rows in `aigis_eval_results` with `evaluator_version = 'trace-validator'`. **Decision: use `aigis_eval_results` table** with a `trace_id` concept: store `reasoning_trace` in `expected_output` column and `actual_output` in `actual_output`. Add a plugin option `aigis_tracev_rules` (JSON) for rule definitions.

**Validation Rules:**

Rules are defined as JSON objects in plugin settings:
```json
{
  "id": "rule_uuid",
  "name": "Safety Override",
  "description": "Trace concludes unsafe, output proceeds anyway",
  "trace_pattern": "unsafe|do not proceed|cannot comply",
  "output_pattern": "(?!.*(?:I cannot|I will not|I'm unable))",
  "match_mode": "regex",
  "severity": "high",
  "enabled": true
}
```

On a validation run, each rule is evaluated against the trace and output pair. If both `trace_pattern` matches the trace AND `output_pattern` matches the output: the rule fires.

**UI Description:**

*Validation Record Submission:*
- Via REST API (see Section 6): `POST /ai-governance/v1/trace-validate` with `agent_id`, `reasoning_trace`, `actual_output`.
- The plugin evaluates all enabled rules against the pair.
- Records result in `aigis_eval_results`.
- If any rule fires: creates an `aigis_incident` post automatically with severity matching the rule's severity, and triggers F-12 alert `eval.false_negative_detected` (reuse trigger).

*Validation Rules Manager* (sub-page under Evaluation):
- List of rules with enable/disable toggle.
- Add/Edit rule form with: name, description, trace pattern (regex), output pattern (regex), match mode, severity.
- "Test Rule" button: enter sample trace + output, see which rules fire.

*Validation History:*
- Filtered view of `aigis_eval_results` where `evaluator_version = 'trace-validator'`.
- Columns: Agent, Date, Pass/Fail, Fired Rules, Linked Incident (if auto-created).

**Access Control:** Manage rules: `aigis_manage_eval`. View history: `aigis_view_eval`.

**Dependencies:** F-01, F-04, F-05, F-09, F-12

---

#### F-15: Progressive Autonomy Router

**User Story:**  
As an AI operations manager, I want to control how much autonomy each agent has based on its demonstrated reliability, so high-stakes actions require human oversight until the agent proves itself.

**Data Model:**  
Autonomy router configuration stored as post meta on `aigis_ai_inventory` records:
- `_aigis_autonomy_mode`: `shadow` / `supervised` / `semi-auto` / `full-auto`
- `_aigis_autonomy_confidence_threshold`: decimal 0.00–1.00
- `_aigis_autonomy_stake_rules`: JSON array of `{stake_level, min_mode}` rules
- `_aigis_autonomy_hitl_triggers`: JSON array of condition strings that force `supervised` mode

**Mode Definitions:**

| Mode | Behavior |
|---|---|
| `shadow` | Agent runs; no action taken; all outputs logged for review |
| `supervised` | Agent proposes action; human must approve before execution |
| `semi-auto` | Auto-execute below stake threshold; supervised above it |
| `full-auto` | Agent executes without human approval |

**Stake Levels:** Admin-configurable labels stored in plugin setting `aigis_stake_levels` (default: `low`, `medium`, `high`, `critical`).

**UI Description:**

*Autonomy Profile Metabox* (on `aigis_ai_inventory` edit screen — note: inventory is a custom-table record, not a CPT, so this becomes a sub-page):

Add a "Autonomy Configuration" section to the Inventory edit form:
- Mode (select)
- Confidence Threshold (number input, 0.00–1.00; shown only for `semi-auto` mode)
- Stake Rules: table of rows `{stake_level → minimum_mode}` with add/remove.
- Human-in-the-Loop Triggers: textarea (one condition string per line).

**REST Endpoint:**  
Agents query `GET /ai-governance/v1/routing/{agent_id}` (see Section 6) to retrieve the current mode configuration. The plugin looks up the inventory record by `agent_id` field and returns the autonomy profile.

**Dependencies:** F-01, F-04, F-05

---

#### F-16: Guardrail Effectiveness Monitor

**User Story:**  
As a safety engineer, I want to know whether our guardrails are catching real threats or just performing safety theater with keyword lists — so I can continuously improve our actual protection, not just the count of blocks.

**Data Model:**  
`{prefix}aigis_guardrail_triggers` (Section 4.8). Populated via REST API `POST /ai-governance/v1/guardrail-trigger`.

**Risk Taxonomy:**  
An admin-defined taxonomy stored in plugin option `aigis_risk_taxonomy` (JSON array of `{id, label, description}`). Default entries:
- `data-exfiltration`
- `prompt-injection`
- `pii-exposure`
- `privilege-escalation`
- `harmful-content`
- `misinformation`

**"Keyword-Only" Detection Heuristic:**  
A guardrail trigger is flagged as `is_keyword_only = 1` if the `matched_rule` value submitted by the agent contains no operator characters beyond simple string matching (i.e., the rule string contains no regex operators: `^$.|?*+()[]{}\\` and no semantic descriptors). This is a heuristic, not ground truth — it serves to surface patterns for human review.

**UI Description:**

*Guardrail Monitor Dashboard* (`AI Governance → Evaluation → Guardrails` tab):

*Summary Cards:*
- Total triggers (period)
- % with risk taxonomy assigned
- % flagged as keyword-only
- Unique guardrails fired

*Charts:*
1. **Triggers by Risk Taxonomy** — bar chart; "untagged" shown prominently.
2. **Top Guardrails by Fire Rate** — horizontal bar chart.
3. **Keyword-Only vs. Semantic** — doughnut chart (keyword-only / other).

*Triggers Log:* Paginated table of `aigis_guardrail_triggers` with all columns visible. Filter by: agent, guardrail name, risk taxonomy, date range, keyword-only flag.

*Vibe-Based Check Alert:* A guardrail is flagged as "potentially vibe-based" if: `is_keyword_only = 1` AND its fire rate on entries with risk taxonomy tags `data-exfiltration`, `prompt-injection`, or `privilege-escalation` is < 5% of total fires. These guardrails are highlighted in the log and listed in a separate callout box.

**Risk Taxonomy Manager:** Settings sub-page to add/edit/delete taxonomy entries.

**Access Control:** View: `aigis_view_eval`. Manage taxonomy: `aigis_manage_eval`.

**Dependencies:** F-04, F-05, F-09 (auto-incident creation for critical triggers), F-12

---

#### F-17: Continuous Evaluation Flywheel

**User Story:**  
As an AI quality lead, I want a system that constantly sanity-checks our own evaluation results — because if our evals have false negatives, every "passed" test is quietly masking a problem.

**Data Model:**  
`{prefix}aigis_eval_results` (Section 4.7). Rulebook versions stored in WP option `aigis_eval_rulebook` (JSON, versioned with semver string).

**Flywheel Loop:**

```
1. Agent submits eval result via REST → stored in aigis_eval_results (pass_fail = 'pass' or 'fail')
2. WP-Cron job samples N% of 'pass' records → sets pass_fail = 'pending-review'
3. Reviewer opens review queue → marks record as actual pass or false_negative = 1
4. On false_negative confirmation: creates rulebook revision proposal (F-08 approval workflow)
5. Approved rulebook update increments version in aigis_eval_rulebook option + logs audit event
6. New evaluator_version tag used on subsequent submissions
```

**WP-Cron Job `aigis_sample_eval_runs`:**
- Runs daily.
- Sample rate configurable: `aigis_eval_sample_rate_pct` (default: 5%).
- Query: `SELECT id FROM aigis_eval_results WHERE pass_fail = 'pass' AND reviewed_at IS NULL ORDER BY RAND() LIMIT N` where `N = CEIL(total_pass_count * sample_rate)`.
- Sets selected records to `pass_fail = 'pending-review'`.

**UI Description:**

*Evaluation Dashboard* (`AI Governance → Evaluation → Flywheel` tab):

*Summary Cards:*
- Total eval submissions (period)
- Pass rate (%)
- False negative rate (%) — `false_negative = 1 / total reviewed`
- Pending review count

*Review Queue:* `WP_List_Table` of records with `pass_fail = 'pending-review'`. Columns: Agent, Date, Expected vs. Actual Output (truncated), Current Verdict. Row actions: "Confirm Pass" / "Mark as False Negative" (with mandatory notes field). Requires `aigis_manage_eval`.

*Trend Charts:*
1. Pass rate over time (line chart, weekly).
2. False negative rate over time (line chart, weekly).

*Rulebook Version History:* Table showing version string, date changed, who approved, and a diff of changes.

*Rulebook Editor:* For users with `aigis_manage_eval`: edit the current rulebook JSON in a `<textarea>` (with basic JSON syntax validation on save). Saving a rulebook change initiates the F-08 approval workflow — it does not take effect immediately.

**Dependencies:** F-04, F-05, F-08, F-12

---

## 6. REST API Reference

Base path: `/wp-json/ai-governance/v1/`

Authentication: All endpoints require authentication. Two methods supported:
1. **WP Application Passwords** — recommended for server-to-server integrations.
2. **Plugin API Key** — a single shared secret generated on the Settings page, stored as option `aigis_api_key` (stored hashed; compared via `hash_equals()`). Passed as header: `X-aigis-API-Key: {key}`.

All responses are JSON. Error responses follow WP REST API convention: `{ "code": "string", "message": "string", "data": { "status": int } }`.

---

### `POST /log`

Log an AI interaction event.

**Auth:** Required (Application Password or API Key)  
**Capability check:** None — any authenticated source can submit logs.

**Request Body:**
```json
{
  "agent_id": "string (required)",
  "inventory_id": "integer (optional, default: 0)",
  "user_id": "integer (optional, default: 0)",
  "prompt_post_id": "integer (optional, default: 0)",
  "session_id": "string (optional)",
  "department": "string (optional)",
  "project_tag": "string (optional)",
  "input_hash": "string — SHA-256 of raw input (optional)",
  "input_tokens": "integer (optional)",
  "output_tokens": "integer (optional)",
  "latency_ms": "integer (optional)",
  "cost_usd": "float (optional)",
  "status": "success|error|timeout|guardrail-blocked (optional, default: success)",
  "error_code": "string (optional)"
}
```

**Response `201 Created`:**
```json
{ "id": 1234, "message": "Log entry created." }
```

---

### `GET /routing/{agent_id}`

Retrieve the current autonomy mode configuration for an agent.

**Auth:** Required  
**Capability check:** None — any authenticated agent can query its own routing config.

**URL Param:** `agent_id` — matches the `agent_id` field on `aigis_ai_inventory` records (searched via `aigis_ai_inventory.api_endpoint` field or a new `agent_identifier` field added to inventory). **Implementation note:** Add varchar column `agent_identifier` (VARCHAR 255, unique) to `aigis_ai_inventory` table for this purpose.

**Response `200 OK`:**
```json
{
  "agent_id": "string",
  "inventory_id": 123,
  "mode": "shadow|supervised|semi-auto|full-auto",
  "confidence_threshold": 0.85,
  "stake_rules": [
    { "stake_level": "high", "min_mode": "supervised" },
    { "stake_level": "critical", "min_mode": "supervised" }
  ],
  "hitl_triggers": [
    "user_data_modification",
    "external_api_call_financial"
  ]
}
```

**Response `404 Not Found`:** Agent ID not found in inventory.

---

### `POST /guardrail-trigger`

Submit a guardrail fire event.

**Auth:** Required  
**Request Body:**
```json
{
  "agent_id": "string (required)",
  "inventory_id": "integer (optional)",
  "guardrail_name": "string (required)",
  "input_hash": "string (optional)",
  "matched_rule": "string (optional)",
  "risk_taxonomy": "string — must match a value in aigis_risk_taxonomy option (optional)",
  "is_keyword_only": "boolean (optional, default: false)",
  "severity": "low|medium|high|critical (optional, default: medium)"
}
```

**Response `201 Created`:**
```json
{ "id": 456, "auto_incident_id": 789, "message": "Guardrail trigger logged." }
```

`auto_incident_id` is non-null only if severity = `critical` (auto-incident created per F-09 spec).

---

### `POST /eval-result`

Submit an evaluation run result.

**Auth:** Required  
**Request Body:**
```json
{
  "agent_id": "string (required)",
  "inventory_id": "integer (optional)",
  "input_hash": "string (optional)",
  "expected_output": "string (required)",
  "actual_output": "string (required)",
  "evaluator_version": "string (required)",
  "pass_fail": "pass|fail (required)",
  "rulebook_version": "string (optional)"
}
```

**Response `201 Created`:**
```json
{ "id": 789, "message": "Eval result recorded." }
```

---

### `POST /trace-validate`

Submit a reasoning trace and actual output for faithfulness validation.

**Auth:** Required  
**Request Body:**
```json
{
  "agent_id": "string (required)",
  "inventory_id": "integer (optional)",
  "reasoning_trace": "string (required)",
  "actual_output": "string (required)"
}
```

**Response `200 OK`:**
```json
{
  "eval_result_id": 912,
  "violations": [
    {
      "rule_id": "uuid",
      "rule_name": "Safety Override",
      "severity": "high"
    }
  ],
  "auto_incident_id": 1011,
  "passed": false
}
```

`violations` is an empty array if no rules fire. `auto_incident_id` is null if no violations.

---

## 7. Plugin Settings Reference

Settings page: `AI Governance → Settings`. Organized into tabs.

### Tab: General

| Setting Key | Type | Default | Description |
|---|---|---|---|
| `aigis_plugin_version` | string | (auto) | Current installed version; do not expose in UI |
| `aigis_audit_retention_days` | integer | 365 | Days to retain audit trail entries |
| `aigis_pii_detection_enabled` | boolean | true | Enable PII heuristic scanning on prompt save |
| `aigis_trust_proxy_headers` | boolean | false | Use X-Forwarded-For for IP logging (only enable behind trusted proxy) |
| `aigis_api_key` | string | (generated) | Plugin API key (stored as bcrypt hash); display masked in UI with "Regenerate" button |
| `aigis_dev_mode` | boolean | false | Allows HTTP (non-HTTPS) webhook URLs; enables verbose error messages |

### Tab: Providers

One sub-section per provider. All API keys stored using `update_option()` with no additional encryption beyond WP's standard. **Implementation note:** Recommend encrypting at-rest using `openssl_encrypt()` with a secret key derived from `AUTH_KEY` from `wp-config.php`.

| Setting Key | Type | Default | Description |
|---|---|---|---|
| `aigis_openai_api_key` | string (encrypted) | — | OpenAI API key |
| `aigis_openai_default_model` | string | `gpt-4o` | Default model for sandbox/stress tests |
| `aigis_anthropic_api_key` | string (encrypted) | — | Anthropic API key |
| `aigis_anthropic_default_model` | string | `claude-opus-4-5` | Default model |
| `aigis_ollama_endpoint` | string | `http://localhost:11434` | Ollama API base URL |
| `aigis_ollama_default_model` | string | `llama3` | Default model |

### Tab: Notifications

| Setting Key | Type | Default | Description |
|---|---|---|---|
| `aigis_alert_rules` | JSON array | [] | Alert rule definitions (see F-12) |
| `aigis_policy_expiry_alert_days` | integer | 30 | Days before expiry to send alert |
| `aigis_notification_inbox_cap` | integer | 100 | Max in-app notifications to retain |

### Tab: Evaluation

| Setting Key | Type | Default | Description |
|---|---|---|---|
| `aigis_eval_sample_rate_pct` | integer | 5 | % of passed eval runs to queue for human review |
| `aigis_eval_rulebook` | JSON | {} | Current versioned rulebook |
| `aigis_tracev_rules` | JSON array | [] | Reasoning trace validation rules |
| `aigis_risk_taxonomy` | JSON array | (defaults) | Guardrail risk taxonomy entries |
| `aigis_stake_levels` | JSON array | (defaults) | Stake level label definitions |

### Tab: Roles & Permissions

Capability grid as described in F-05. Saved per-role to WP's role capabilities system.

---

## 8. Integration Guide

### 8.1 Authentication

Generate an API key in `Settings → General → Regenerate API Key`. Pass it on every request:

```
X-aigis-API-Key: your-api-key-here
```

Alternatively, use WP Application Passwords (`Users → {user} → Application Passwords`). Pass as HTTP Basic Auth: `Authorization: Basic base64(username:app_password)`.

### 8.2 Logging an AI Interaction (Custom Agent)

After every AI model call in your agent, POST to the log endpoint:

```php
// PHP example
$response = wp_remote_post(
    'https://your-wordpress-site.com/wp-json/ai-governance/v1/log',
    [
        'headers' => [
            'Content-Type'    => 'application/json',
            'X-aigis-API-Key'  => 'your-api-key-here',
        ],
        'body' => wp_json_encode([
            'agent_id'      => 'my-customer-service-bot',
            'inventory_id'  => 3,
            'user_id'       => get_current_user_id(),
            'session_id'    => wp_generate_uuid4(),
            'department'    => 'Customer Success',
            'project_tag'   => 'cs-chatbot-v2',
            'input_hash'    => hash( 'sha256', $raw_prompt ),
            'input_tokens'  => $response_data['usage']['prompt_tokens'],
            'output_tokens' => $response_data['usage']['completion_tokens'],
            'latency_ms'    => $elapsed_ms,
            'cost_usd'      => $calculated_cost,
            'status'        => 'success',
        ]),
        'timeout' => 10,
    ]
);
```

```bash
# curl example
curl -X POST https://your-wordpress-site.com/wp-json/ai-governance/v1/log \
  -H "Content-Type: application/json" \
  -H "X-aigis-API-Key: your-api-key-here" \
  -d '{"agent_id":"my-bot","status":"success","latency_ms":342}'
```

### 8.3 Connecting Ollama (On-Premise)

1. Ensure Ollama is running and accessible from the WP server: `curl http://localhost:11434/api/tags`
2. In `Settings → Providers → Ollama`, set the endpoint URL (e.g., `http://localhost:11434` or a LAN IP if Ollama is on a separate machine).
3. The plugin communicates with Ollama via its native REST API. `aigis_Provider_Ollama` uses endpoint `POST /api/generate` with model and prompt parameters.
4. Test the connection using the "Test Connection" button on the Providers settings tab.

### 8.4 Adding a New Provider

1. Create `includes/providers/class-aigis-provider-{name}.php`.
2. Extend `aigis_Provider_Abstract` and implement `send_prompt()` and `list_models()`.
3. Register the provider in `aigis_Plugin::init_providers()` by adding it to the `$providers` array with a slug key.
4. Add provider settings (API key, endpoint, default model) to `Settings → Providers` by following the pattern of existing providers in `admin/views/settings/settings.php`.
5. The new provider will automatically appear in the stress test builder and prompt sandbox provider selects.

### 8.5 Sending Reasoning Traces

```bash
curl -X POST https://your-site.com/wp-json/ai-governance/v1/trace-validate \
  -H "Content-Type: application/json" \
  -H "X-aigis-API-Key: your-key" \
  -d '{
    "agent_id": "finance-bot",
    "reasoning_trace": "The user is asking to transfer funds. This appears unsafe without 2FA verification. I should refuse.",
    "actual_output": "Sure, I have initiated the transfer of $5,000 to the requested account."
  }'
```

---

## 9. Phasing Summary Table

| ID | Feature Name | Phase | Priority | Dependencies |
|---|---|:---:|:---:|---|
| F-01 | AI Vendor & Model Inventory | v1 | P0 | F-04, F-05 |
| F-02 | Prompt Store & Version Control | v1 | P0 | F-01, F-04, F-05 |
| F-03 | AI Usage Policy Repository | v1 | P0 | F-04, F-05 |
| F-04 | Audit Trail | v1 | P0 | F-05 |
| F-05 | WordPress Capabilities & Role Setup | v1 | P0 | — |
| F-06 | AI Workflow Documentation & Mapping | v2 | P1 | F-01, F-02, F-04, F-05 |
| F-07 | Employee AI Usage Analytics | v2 | P1 | F-01, F-02, F-05 |
| F-08 | Change Management & Approval Workflows | v2 | P1 | F-02, F-03, F-04, F-05, F-12 |
| F-09 | Incident Management | v2 | P1 | F-01, F-02, F-03, F-04, F-05, F-12 |
| F-10 | Cost & Budget Governance | v2 | P2 | F-01, F-04, F-05, F-07, F-12 |
| F-11 | Data Privacy & Classification Layer | v2 | P1 | F-02, F-04, F-05 |
| F-12 | Alerting & Notifications | v2 | P1 | F-04, F-05 |
| F-13 | Factorial Stress Testing Module | v3 | P2 | F-01, F-02, F-04, F-05 |
| F-14 | Reasoning Trace Validator | v3 | P2 | F-01, F-04, F-05, F-09, F-12 |
| F-15 | Progressive Autonomy Router | v3 | P2 | F-01, F-04, F-05 |
| F-16 | Guardrail Effectiveness Monitor | v3 | P2 | F-04, F-05, F-09, F-12 |
| F-17 | Continuous Evaluation Flywheel | v3 | P2 | F-04, F-05, F-08, F-12 |

**Priority key:** P0 = must ship in initial release; P1 = high value, ship in v2; P2 = advanced, ship in v3.

---

*End of specification. Last updated: 2026-03-24.*
