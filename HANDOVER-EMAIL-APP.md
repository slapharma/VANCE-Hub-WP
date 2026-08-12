# Handover — Standalone Email App (email.vancemedicalfoods.co.uk)

**Written:** 2026-08-12, from the HealthHub-WP session that built the email programme.
**For:** a NEW session in a NEW project/repo. This doc is self-contained — read it top to bottom before writing any code. Copy it into the new project root.

## 1. Mission

Build a standalone web app at **`email.vancemedicalfoods.co.uk`** that manages the Vance Medical Hub email programme end-to-end, wired into the FluentCRM instance running on the live WordPress site (`vancehealthhub.co.uk`). The app must let the team:

1. **Edit content** — campaign copy and the four silo templates, without touching wp-admin.
2. **Set up campaigns** — create drafts from the silo templates, fill placeholders, set recipients, preview, test-send.
3. **Activate** — schedule/send campaigns and activate the two nurture funnels, with compliance guards (see §7).

A 6-month content calendar already exists and is the app's natural data spine (see §4).

## 2. The estate (all verified live 2026-08-12)

| Thing | Value |
|---|---|
| WordPress site | `https://vancehealthhub.co.uk` — Hostinger, SSH `u767439438@82.29.185.3` port `65002`, key `~/.ssh/hostinger_sla` |
| WP path on server | `~/domains/vancehealthhub.co.uk/public_html` (wp-cli available) |
| FluentCRM | v3.1.10 (free), active. Whole admin runs on its own REST API — see §5 |
| Mail transport | WP Mail SMTP v4.9.0 → **Resend** SMTP, sends as `team@vancemedicalfoods.co.uk` |
| DNS for `vancemedicalfoods.co.uk` | **Vercel** (NOT Hostinger) — the `email.` subdomain gets created there; deploying the app itself on Vercel makes this a one-step domain attach |
| Theme repo (context only) | `https://github.com/slapharma/VANCE-Hub-WP` — new app repo should live under `slapharma/` too (`gh auth switch --user slapharma` before pushing; a 403 always means wrong active account, never missing access) |
| Vercel CLI gotcha | `npx vercel` fails inside sandboxed shells (`api.vercel.com` unreachable) — deploys need `dangerouslyDisableSandbox: true` |

## 3. FluentCRM inventory (verified 2026-08-12 — re-verify, other sessions write to this DB)

- **List:** `vance-health-hub-members` (id 1). **Subscribers: 2** (both `subscribed`) — tiny until marketing opt-in capture ships (§7).
- **Tags (ids 1–8):** `member`, `patient-caregiver`, `hcp-practitioner`, `researcher`, `marketing-opt-in`, `signup-google`, `signup-form`, `new-member-nurture-sent`.
- **Templates — the four silos** (`fc_template` posts; HTML source of truth is in the theme repo at `email-templates/*.html`, server backups at `~/fluentcrm-template-backups/`):
  - 3232 `[Template] Newsletters` · 3233 `[Template] HealthTips` · 3234 `[Template] Promos` · 3235 `[Template] Informational`
  - All use FluentCRM's `simple` design mode → the compliant unsubscribe footer is **auto-appended**; templates must never carry their own.
  - Placeholders are square-bracketed `[like this]`; image slots point at a branded placeholder PNG (attachment 3246) with an HTML comment telling the editor to swap or delete. The app's content editor should treat `[...]` tokens and those image slots as its form fields.
- **Campaigns:** ids 1–9 are funnel child emails (status `published` = attached to a funnel, NOT sent); 10–13 reusable one-off drafts; 14 archived; **15–18 are the four September 2026 sends, drafted with real copy and verified links, recipients deliberately unset**.
- **Funnels (both still `draft`):** 2 = New Member Nurture (5 emails, day 0/2/5/9/14, trigger: tags 1/2/4 added); 3 = HCP Nurture (4 emails, day 0/3/7/12, trigger: tag 3).
- **Global settings:** from `Vance Health Hub <team@vancemedicalfoods.co.uk>`; footer carries `{{crm.business_name}}`, address (Vance Medical Foods Ltd, 3a Chestnut House, Farm Close, Shenley, WD7 9AD) and `##crm.unsubscribe_url##`. **Per-campaign from name/email is blank** — the app should set it explicitly on each campaign it creates.

## 4. Existing assets to build on (in the HealthHub-WP repo)

| Asset | Where |
|---|---|
| 6-month content calendar (Sep 2026–Feb 2027, all 24 sends, silo keys NL/HT/PR/IN) | `EMAIL-CONTENT-CALENDAR.md` |
| Design system + template/silo mapping + workflow | `EMAIL-DESIGN-TEMPLATES.md` |
| Deployed template HTML (edit here, push via `wp post update <id> <file>`) | `email-templates/{newsletters,healthtips,promos,informational}.html` |
| Editable calendar artifact (localStorage planner, exports .md) | https://claude.ai/code/artifact/d9db5daa-b94d-4c99-b0d9-6bdc4b76c0a0 |
| Canva visual masters | Folder https://www.canva.com/folder/FAHR_yPnljQ — designs `DAHR_-JUmTY` (Newsletters), `DAHR_-oTaSg` (HealthTips), `DAHR_8mm-uk` (Promos), `DAHR_wtBFhs` (Informational). ⚠️ Still purple `#632c94` — manual recolour to `#008080` pending (Canva API can't edit colours on email designs); FluentCRM templates are already teal. |

The app could subsume the artifact planner: same calendar data, but each row gains "Create draft in FluentCRM" / status pulled live from the API.

## 5. Integration surface — FluentCRM REST API (verified live)

Namespace **`/wp-json/fluent-crm/v2/`** is registered and serves the entire FluentCRM admin. Routes confirmed present include everything the app needs:

- Campaigns: `GET/POST /campaigns`, `GET/PUT /campaigns/{id}`, `/campaigns/{id}/title`, `/campaigns/{id}/status`, `/campaigns/{id}/schedule` + `/un-schedule`, `/campaigns/{id}/pause` + `/resume`, `/campaigns/{id}/duplicate`, `/campaigns/{id}/draft-recipients`, `/campaigns/{id}/estimated-recipients-count`, `/campaigns/send-test-email`, `/campaigns/email-preview-html`, `/campaigns/{id}/overview_stats`, `/campaigns/{id}/link-report`
- Templates: `GET/POST /templates`, `GET/PUT /templates/{id}`, `/templates/duplicate/{id}`, `/templates/smartcodes`
- Funnels: `GET /funnels`, `GET/PUT /funnels/{id}`, `/funnels/{id}/sequences`, `.../save-email-action`, `/funnels/{id}/report` (funnel activation = updating funnel status)
- Contacts/segments: `/subscribers`, `/tags`, `/lists`, `/reports/*`

**Auth:** WP **Application Passwords** are enabled on the site (verified) — HTTPS Basic auth per request. FluentCRM authorises via its own permission layer: the acting WP user must be an **administrator or a FluentCRM Manager** (FluentCRM → Settings → Managers).

- A bot user already exists: **`claude-bot` (ID 15, role `editor`)** — credentials note in the theme repo at `docs/username claude-bot.txt` (untracked, never commit it). Its existing application password belongs to the annotation skill — **create a separate application password named `email-app`**, and grant the user FluentCRM Manager permission (or full campaign permissions) before expecting `fluent-crm/v2` calls to succeed. Verify with a `GET /campaigns` smoke call before building on it.
- `jwt-authentication-for-wp-rest-api` is also active if JWT is preferred, but Basic + app password is simpler and already proven on this site.
- Official API docs: https://rest-api.fluentcrm.com (payload shapes for campaign create/schedule).

**Architecture rule:** credentials live server-side only (env vars in Vercel; all FluentCRM calls proxied through the app's backend). The app itself needs its own login — simplest consistent-with-estate option is Google sign-in restricted to `@slapharmagroup.com` (that domain already auto-admins the WP site's Google OAuth; do NOT try to reuse the separate V-Net "Vance Passport" SSO — see the WP repo's memory note on why they're incompatible).

**Escape hatch:** anything the REST API can't do (it covers ~everything, but e.g. bulk template seeding was done this way) can run over SSH + `wp eval-file`. Gotcha learned twice: `wp eval-file` runs the file in *function scope* — top-level variables aren't globals; `global $x` inside helpers refers to true globals, not the file's top level.

## 6. House email rules the app must encode (not just document)

1. **Silo model:** every send is one of Newsletters / HealthTips / Promos / Informational — create campaigns by duplicating templates 3232–3235, never from blank.
2. **`simple` design mode always** — it's what auto-appends the legal footer. Do not offer `raw_html`.
3. **Design tokens** (for the app's own preview rendering): teal `#008080`, headings `#0F172A`, body `#334155`, muted `#64748B`, callout `#F0FDFA` bordered `#99F6E4`, dividers `#E2E8F0`, Arial, 600px single column, one primary CTA.
4. Consumer emails end with the 999/NHS 111 disclaimer line; Informational ends with "For healthcare professionals. Not intended for patients."
5. UK send window: Tue/Thu 09:00–10:30 UK time.

## 7. Compliance guards — build these as hard constraints in the app

1. **Recipient guard:** a campaign cannot be scheduled unless its segment includes the `marketing-opt-in` tag intersected with the audience tag (Members: `patient-caregiver`; HCPs: `hcp-practitioner`). Estimated-recipients endpoint lets the app show the real count pre-send.
2. **Test-send gate:** block "schedule" until at least one test email has been sent for that campaign (`/campaigns/send-test-email`) — to `team@vancemedicalfoods.co.uk`.
3. **No-claims reminders** in the editor UI: no medicinal claims for foods; FSMP → "under medical supervision"; identify Vance product mentions.
4. **Bigger blocker upstream:** marketing opt-in capture at registration isn't live on the WP site yet (ENG-04 in the theme repo's `IMPLEMENTATION-PLAN.md`) — until it ships, the mailable audience is 2 people. The app can launch before that, but activation of the calendar depends on it.

## 8. Suggested first-session order

1. Scaffold repo under `slapharma/`, Next.js on Vercel, attach `email.vancemedicalfoods.co.uk` (DNS is already at Vercel).
2. Grant `claude-bot` FluentCRM Manager permission + new `email-app` application password; prove auth with `GET /wp-json/fluent-crm/v2/campaigns` (expect the 18 campaigns in §3 — that's the fresh-state check too).
3. Read-only dashboard first: campaigns + statuses + calendar view seeded from `EMAIL-CONTENT-CALENDAR.md`.
4. Then the write path: duplicate-template → placeholder-form editor → draft-recipients → test-send → schedule, with §7 guards.
5. Funnel activation screen last (it's one status flip, but gate it behind the same review UX).

*Verification habit carried over from the parent project: never report a step done on a check that cannot fail — prove auth with a real API call, prove sends with a real test email, and re-verify FluentCRM state at session start because parallel sessions write to the same DB.*
