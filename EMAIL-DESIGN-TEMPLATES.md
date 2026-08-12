# Vance Medical Hub — Email Design Templates

**Companion to:** [EMAIL-CONTENT-CALENDAR.md](EMAIL-CONTENT-CALENDAR.md)
**Created:** 2026-08-11 · **Rebuilt on the four-silo model:** 2026-08-12

Every send belongs to one of **four silos**. Each silo has one visual master in Canva (art direction, banner imagery, social crossposts) and one production HTML template in FluentCRM (what actually gets sent).

| Silo | Audience | What it carries |
|---|---|---|
| **Newsletters** | Members | The monthly issue: featured article, tool of the month, community spotlight |
| **HealthTips** | Members | One practical tip + a "why it matters" reason |
| **Promos** | Members | Awareness weeks, referral pushes, feature launches, specials (teal hero band) |
| **Informational** | HCPs | Professional round-up: evidence articles + practice resources |

## Design system (email)

| Token | Value | Use |
|---|---|---|
| Primary / CTA | `#008080` | Buttons, top bar, section labels, hero bands |
| Heading text | `#0F172A` | H1/H2 |
| Body text | `#334155` | Paragraphs (≥15px, 1.6 line-height) |
| Muted text | `#64748B` | Disclaimer, footer |
| Callout background | `#F0FDFA` | "Why it matters" / "Tool of the month" / "Key facts" boxes |
| Callout border / accent | `#99F6E4` | Callout borders, kicker text on teal |
| Divider | `#E2E8F0` | Informational article-list separators |
| Font | Arial/Helvetica | Email-safe; matches existing sends |
| Width | 600px single column | 4px teal top bar, logo at 32px height |

Rules: one primary CTA per email; high contrast (WCAG AA min); no images-only content (all copy is live text); every consumer email ends with *"General information only, not medical advice. In an emergency call 999 or NHS 111."*; Informational ends with *"For healthcare professionals. Not intended for patients."*; the unsubscribe/business-address footer is appended automatically by FluentCRM (`simple` design mode) — do not add a second one.

## FluentCRM templates (live on vancehealthhub.co.uk)

Rebuilt 2026-08-12 from the four new Canva layouts, replacing the templates created 2026-08-11 **in place** — the post IDs are unchanged, so anything referencing them still resolves. Nothing sends automatically; use them via Campaigns → "Create from template".

| ID | Template | Silo | Image slots |
|---|---|---|---|
| 3232 | `[Template] Newsletters` | NL | 1 hero (1200×400) |
| 3233 | `[Template] HealthTips` | HT | none — fully text |
| 3234 | `[Template] Promos` | PR | 1 optional banner (1200×400) below the teal hero band |
| 3235 | `[Template] Informational` | IN | 3 article thumbnails (120px wide) |

**Placeholder convention.** Every editable slot is a square-bracketed *[placeholder]*; `{{contact.first_name}}` merge tags are already in place. Image slots point at a branded placeholder graphic — `https://vancehealthhub.co.uk/wp-content/uploads/2026/08/vance-email-image-placeholder.png` (attachment 3246) — and each is preceded by an HTML comment telling the editor to swap the `src` for a WP Media URL or delete the block. A forgotten image therefore renders as an obvious empty placeholder band, never a broken image.

**Source of truth in this repo:** [email-templates/](email-templates/) holds the exact HTML deployed to each template — `newsletters.html`, `healthtips.html`, `promos.html`, `informational.html`. Edit there, then push with `wp post update <id> <file>` over SSH. Pre-rebuild backups of the 2026-08-11 versions live on the server at `~/fluentcrm-template-backups/<id>-2026-08-12.html`.

## Canva design masters

Folder: **Vance Hub — Email Templates** — https://www.canva.com/folder/FAHR_yPnljQ (built on the *VANCE-Social Media Kit* brand kit)

| Silo | Canva design title | Design ID |
|---|---|---|
| Newsletters | `[VANCE] Newsletters — Email Template` | `DAHR_-JUmTY` |
| HealthTips | `[VANCE] HealthTips — Email Template` | `DAHR_-oTaSg` |
| Promos | `[VANCE] Promos — Email Template` | `DAHR_8mm-uk` |
| Informational | `[VANCE] Informational — Email Template` | `DAHR_wtBFhs` |

All four had their sample copy replaced with the same bracketed placeholders and merge tags as the FluentCRM templates, so the two sets read identically slot for slot.

### ⚠️ Outstanding manual step in Canva — recolour purple → teal

The four designs were generated on a **purple `#632c94`** accent. Canva's API exposes only text edits on responsive email designs — colour, image fill and layout are not reachable — so the recolour could not be automated. **This affects the Canva masters only; the FluentCRM templates that actually send are already fully teal.**

In each of the four designs (2 minutes total):
1. Open the design → click the purple header wordmark and each purple heading.
2. Set the colour to `#008080` (it is in the *VANCE-Social Media Kit* brand kit palette).
3. Replace the generic header mark with the VANCE logo — Uploads → `logojpg.jpg`, already in the account.

## Before the first real send (blockers from the compliance plan)

1. Marketing opt-in capture at registration is not live yet (IMPLEMENTATION-PLAN.md ENG-04) — until it is, only the 2 existing consented contacts are mailable.
2. Both nurture funnels are still `draft` — review copy, then activate.
3. Campaign "from" name/email is blank in FluentCRM campaign settings (global from is `Vance Health Hub <team@vancemedicalfoods.co.uk>` — confirm and set per-campaign).
4. Test-send each of the four templates to team@vancemedicalfoods.co.uk and check Gmail + Outlook before September's first issue — in particular the Informational three-column article rows, which are the most fragile in Outlook.
