# AIGIS: The AI Governance and Infrastructure Suite

**⚠️ Beta — Active Development — Testing Phase**  
This plugin is in early beta. APIs, database schemas, and admin UI may change between versions without notice. Not recommended for production use yet. Feedback, bug reports, and pull requests are very welcome.

---

## Overview

AIGIS is a comprehensive WordPress plugin that brings enterprise-grade AI governance capabilities to any WordPress site. It gives teams the tools to **manage, audit, control, and continuously improve** how AI systems are used across their organisation — all from within the WordPress admin.

### What it does

| Capability | Details |
|---|---|
| **AI Inventory** | Central registry of every AI model, vendor, and integration in use |
| **Prompt Library** | Version-controlled prompts with sandbox testing and one-click promotion |
| **Policy Management** | Formal AI-use policies with approval workflows and expiry tracking |
| **Workflow Designer** | Visual Mermaid-powered diagrams for documenting AI workflows |
| **Incident Tracking** | CPT-based incident log with status transitions and linked policies |
| **Analytics** | Usage trends, token consumption, model breakdown, and department cost |
| **Cost & Budgets** | Per-scope budgets with 80%/100% alert thresholds |
| **Evaluation** | Automated and human-reviewed AI output quality scoring |
| **Guardrail API** | REST endpoint for real-time PII detection and content filtering |
| **Audit Trail** | Immutable, append-only log of every significant plugin action |
| **Notifications** | Admin-inbox notification system with unread badge |
| **REST API** | Authenticated API for external systems to log usage and trigger guardrails |

---

## Requirements

- **WordPress** 6.4 or later
- **PHP** 8.1 or later (tested on 8.3)
- **MySQL** 5.7+ / MariaDB 10.4+
- **Chart.js 4.x** — **UMD build** (`chart.umd.min.js`) placed at `admin/js/vendor/chart.umd.min.js`  
  Download: `https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js`
- **Mermaid.js 10.x** — IIFE build placed at `admin/js/vendor/mermaid.min.js`  
  Download: `https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js`

> **Note:** Vendor JS files are not bundled in the repository. Download them separately and place them in `admin/js/vendor/` before activating. The plugin will fall back to the jsDelivr CDN for Chart.js if the local UMD file is absent.

---

## Installation

1. Clone or download this repository into `wp-content/plugins/ai-governance-suite/`
2. Download the vendor JS files (see Requirements above)
3. In WordPress admin go to **Plugins → Installed Plugins** and activate **AI Governance & Infrastructure Suite**
4. On first activation a REST API key is generated and displayed once — copy it immediately
5. Navigate to **AI Governance** in the admin sidebar to get started

---

## Repository Structure

```
ai-governance-suite/
├── admin/
│   ├── css/                    # Admin stylesheet
│   ├── js/                     # Admin JS (aigis-admin.js, aigis-charts.js, aigis-workflow-diagram.js)
│   │   └── vendor/             # chart.umd.min.js, mermaid.min.js (not in repo — see Requirements)
│   └── views/                  # PHP view templates (one folder per admin area)
├── docs/
│   ├── development/            # Dev journal and implementation notes
│   ├── reference/              # Repo-only reference material
│   └── specifications/         # Project specification and planning docs
├── includes/
│   ├── core/                   # Bootstrap, loader, activator, deactivator, orchestrator
│   ├── admin/                  # Admin page controllers (AIGIS_Page_*)
│   ├── api/                    # REST API controllers (AIGIS_REST_*)
│   ├── cpt/                    # Custom Post Type classes (AIGIS_CPT_*)
│   ├── db/                     # Database abstraction layer (AIGIS_DB_*)
│   ├── helpers/                # Capabilities, PII detector, notifications, cron, test data
│   └── providers/              # AI provider adapters (OpenAI, Anthropic, Ollama)
├── tests/                      # Placeholder for automated test coverage
├── ai-governance-suite.php     # Plugin bootstrap
└── uninstall.php               # Clean uninstall (drops all DB tables and options)
```

### Layout notes

- Runtime plugin code stays in `admin/` and `includes/` so the WordPress load path remains simple.
- Repository-only material lives in `docs/` instead of a hidden scratch directory.
- Core bootstrapping classes are grouped in `includes/core/` to separate framework wiring from feature code.

---

## REST API

All endpoints live under `/wp-json/ai-governance/v1/` and require an `X-AIGIS-API-Key` header.

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/log` | Log an AI usage event |
| `GET` | `/routing` | Get model routing rules |
| `POST` | `/guardrail/check` | Check a prompt against guardrails (PII detection etc.) |
| `POST` | `/eval` | Submit an evaluation result |

---

## Roles & Capabilities

AIGIS adds a dedicated set of capabilities and assigns them to WordPress roles on activation:

| Capability | Administrator | Editor |
|---|---|---|
| `aigis_view_ai_inventory` | ✅ | ✅ |
| `aigis_manage_ai_inventory` | ✅ | — |
| `aigis_view_analytics` | ✅ | ✅ |
| `aigis_view_costs` | ✅ | — |
| `aigis_manage_budgets` | ✅ | — |
| `aigis_manage_policies` | ✅ | — |
| `aigis_manage_workflows` | ✅ | ✅ |
| `aigis_manage_incidents` | ✅ | ✅ |
| `aigis_view_eval` | ✅ | ✅ |
| `aigis_manage_eval` | ✅ | — |
| `aigis_manage_api_keys` | ✅ | — |
| `aigis_manage_settings` | ✅ | — |

---

## Development

### Generating test data

In WordPress admin go to **AI Governance → Settings → Developer Tools** and click **Generate Test Data**. This populates every section of the plugin with realistic sample records. Use **Purge Test Data** to remove them cleanly.

### Running a PHP lint check inside Docker

```bash
docker exec <wordpress-container> bash -c \
  'find /var/www/html/wp-content/plugins/ai-governance-suite -name "*.php" \
   | xargs -I{} php -l {}'
```

---

## Beta Status & Known Limitations

- **In active development** — features and database schemas may change
- **Vendor assets not bundled** — Chart.js and Mermaid.js must be supplied separately
- **Provider sandbox** — the OpenAI/Anthropic/Ollama adapters are scaffolded; live API calls require valid API keys configured in Settings
- **No automated test suite yet** — unit and integration tests are a planned next step
- **Cron jobs** — `aigis_prune_usage_logs` and `aigis_check_budget_alerts` are scheduled on activation; verify they run in your environment (some managed hosts disable WP-Cron)

---

## License

MIT License — see [LICENSE](LICENSE) for full text.

This plugin is provided as-is, with no warranty. Use in production at your own risk during the beta period.

---

## Contributing

Issues, feature requests, and pull requests are welcome. Please open an issue first to discuss significant changes before submitting a PR.
