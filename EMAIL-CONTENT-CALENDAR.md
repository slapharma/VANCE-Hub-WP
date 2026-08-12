# Vance Medical Hub — Email Content Calendar (Sep 2026 – Feb 2027)

**Owner:** Content / Marketing
**Platform:** FluentCRM (live on vancehealthhub.co.uk) + WP Mail SMTP → Resend (`team@vancemedicalfoods.co.uk`)
**Companion doc:** [EMAIL-DESIGN-TEMPLATES.md](EMAIL-DESIGN-TEMPLATES.md) — the four template silos (FluentCRM + Canva) every send below maps to: **Newsletters**, **HealthTips**, **Promos**, **Informational**.

---

## 1. Audience segments (as configured in FluentCRM today)

| Segment | FluentCRM targeting | Who |
|---|---|---|
| Members | List `vance-health-hub-members` + tag `patient-caregiver` | Patients & caregivers |
| HCPs | Tag `hcp-practitioner` | Clinicians, dietitians, pharmacists |
| Researchers | Tag `researcher` | Low volume — fold into HCP sends unless content diverges |
| Marketing-consented | Tag `marketing-opt-in` | **The only people who may receive the sends in this calendar** |

### Compliance guardrails (apply to every send — no exceptions)

1. **PECR/UK GDPR:** every campaign in this calendar is marketing. Target `marketing-opt-in` (intersected with the relevant audience tag) — never the whole list. The two nurture funnels ride on legitimate-interest service messaging only as long as their content stays functional/onboarding; anything promotional goes through opt-in.
2. **Unsubscribe:** the global footer (`##crm.unsubscribe_url##` + manage-subscription link + business address) is already configured in FluentCRM settings — never override it in a template.
3. **Medical disclaimer:** every email carries the one-liner: *"General information only, not medical advice. In an emergency call 999 or NHS 111."* (baked into the templates).
4. **CAP/claims:** no medicinal claims for foods; FSMP references must say "under medical supervision"; identify Vance product mentions clearly (per IMPLEMENTATION-PLAN.md §6.4).
5. **Send-time:** UK audience — schedule 9:00–10:30 GMT/BST midweek.

---

## 2. Always-on flows (already built — run underneath the calendar)

| Flow | Trigger | Cadence | Status / action needed |
|---|---|---|---|
| New Member Nurture (5 emails) | Tag `member`/`patient-caregiver` added | Day 0, 2, 5, 9, 14 | Funnel drafted — **activate in Sep** after copy review |
| HCP Nurture (4 emails) | Tag `hcp-practitioner` added | Day 0, 3, 7, 12 | Funnel drafted — **activate in Sep** |
| Refer a Friend | Manual/one-off | Quarterly re-send (Oct, Jan) | Draft exists (campaign 10) |
| Talk to Your Doctor | Manual/one-off | Slot in Nov | Draft exists (campaign 13) |
| Join the Professional Portal | Manual to non-HCP-tagged clinicians | Slot in Oct | Draft exists (campaign 14) |

**Rhythm for one-off sends:** Members get a **Newsletters** issue (1st Tuesday) and a **HealthTips** send (3rd Tuesday) each month; HCPs get one **Informational** round-up (2nd Thursday) each month. One **Promos** send per month max — protect list health.

---

## 3. Monthly calendar

Silo key: **NL** = Newsletters · **HT** = HealthTips · **PR** = Promos · **IN** = Informational (see EMAIL-DESIGN-TEMPLATES.md).

### September 2026 — "Back to routine" / programme launch

| Date | Audience | Silo | Subject (working) | Content | CTA |
|---|---|---|---|---|---|
| Tue 1 Sep | Members | NL | Your September issue: gut health, back to routine | Launch issue: what's new on the Hub, featured article, tool of the month (Gastro Health Survey), community highlight | Read on the Hub |
| Thu 10 Sep | HCPs | IN | New on the Professional Portal this month | Evidence round-up, one clinical-nutrition article, portal feature (consultation requests) | Visit the portal |
| Tue 15 Sep | Members | HT | One small change for calmer digestion | Single practical tip (meal pacing / fibre re-introduction), link to full article | Read more |
| Tue 22 Sep | Members | PR | Meet VANCE-ai: answers about your condition, anytime | Feature spotlight on Ask AI with worked example questions + disclaimer framing | Try Ask AI |

*Also in Sep: activate both nurture funnels; verify opt-in counts; baseline open/click benchmarks.*

### October 2026 — Malnutrition awareness

| Date | Audience | Silo | Subject (working) | Content | CTA |
|---|---|---|---|---|---|
| Tue 6 Oct | Members | NL | Your October issue: spotting malnutrition early | Issue themed on nutrition risk; featured article + malnutrition risk checker walkthrough | Check your risk |
| w/c 12 Oct* | Members | PR | UK Malnutrition Awareness Week: 3 signs not to ignore | Tied to BAPEN's UK Malnutrition Awareness Week (*confirm 2026 dates — typically Oct*); unintentional weight-loss red flags; signpost to GP/dietitian | Use the free checker |
| Thu 8 Oct | HCPs | IN | Malnutrition screening resources for your practice | MUST-style screening refresher article, printable patient signposting, portal invite re-push to lapsed | Get the resources |
| Tue 20 Oct | Members | HT | Protein at breakfast: the easiest win | Practical fortified-breakfast tip; recipe link from IBD Recipes | See the recipe |
| Tue 27 Oct | Members | PR | Know someone this might help? | Quarterly referral push (existing Refer a Friend draft, restyle onto the Promos template) | Share your link |

### November 2026 — Self-care & preparing for appointments

| Date | Audience | Silo | Subject (working) | Content | CTA |
|---|---|---|---|---|---|
| Tue 3 Nov | Members | NL | Your November issue: making appointments count | Issue on self-advocacy; symptom-diary article; Gastro Health Survey as prep tool | Take the survey |
| Thu 12 Nov | HCPs | IN | What your patients are asking VANCE-ai | Anonymised/aggregate theme round-up from Ask AI + how to use it for patient education | Read the round-up |
| Mon 16 Nov | Members | PR | Self Care Week: your 5-minute gut check-in | UK Self Care Week (mid-Nov) tie-in; checklist content; "Talk to Your Doctor" draft folds in here | Download the checklist |
| Tue 24 Nov | Members | HT | Worth mentioning at your next appointment | Existing "Talk to Your Doctor" draft — one tip on raising symptoms early | Read how |

### December 2026 — Crohn's & Colitis Awareness Week

| Date | Audience | Silo | Subject (working) | Content | CTA |
|---|---|---|---|---|---|
| Tue 1 Dec | Members | PR | Crohn's & Colitis Awareness Week starts today | Awareness Week (1–7 Dec): living-well stories, IBD article cluster, community invite | Explore IBD resources |
| Thu 10 Dec | HCPs | IN | IBD in primary care: December round-up | IBD-focused evidence digest; flag awareness week patient materials | View resources |
| Tue 15 Dec | Members | NL | Your December issue: eating well over the holidays | Festive IBD-friendly eating; IBD Recipes meal-plan feature; managing flares over holidays | Browse recipes |
| **No send week of 22–29 Dec** | — | — | — | Quiet period — low engagement, respect inboxes | — |

### January 2027 — Fresh start, sustainable habits

| Date | Audience | Silo | Subject (working) | Content | CTA |
|---|---|---|---|---|---|
| Tue 5 Jan | Members | NL | Your January issue: small habits, real change | Anti-fad framing: evidence-based habit change vs. crash diets; hydration; fibre diversity | Read the guide |
| Thu 14 Jan | HCPs | IN | 2027 planning: nutrition-first conversations | New-year behaviour-change evidence; motivational interviewing resource | Get the toolkit |
| Tue 19 Jan | Members | HT | The 30-plants myth — what the evidence says | Plant-diversity tip with realistic targets (Veganuary adjacent, no fad endorsement) | Read more |
| Tue 26 Jan | Members | PR | Know someone starting their gut-health journey? | Quarterly referral push #2 | Share the Hub |

### February 2027 — Gut–brain connection

| Date | Audience | Silo | Subject (working) | Content | CTA |
|---|---|---|---|---|---|
| Tue 2 Feb | Members | NL | Your February issue: the gut–brain connection | Stress & symptoms article; Time to Talk Day (early Feb) mental-health tie-in | Read the issue |
| Thu 11 Feb | HCPs | IN | Gut–brain axis: what to tell patients | Evidence summary + communication guide | Read the summary |
| Tue 16 Feb | Members | HT | A 2-minute wind-down before meals | Practical stress-and-digestion tip | Try it tonight |
| Tue 23 Feb | Members | PR | 6 months of the Hub: what you've told us | Programme milestone: survey + feedback ask; tease spring content; Rare Disease Day (28 Feb) nod if relevant | Give feedback |

---

## 4. Production workflow (per send)

1. Duplicate the matching silo's FluentCRM template (Campaigns → create from template, or Templates screen).
2. Replace every square-bracketed *[placeholder]*; keep one primary CTA; keep the disclaimer + auto-footer. Merge tags (`{{contact.first_name}}`) are already in place.
3. Images: each image slot ships with a branded placeholder graphic. Either swap its `src` for a WP Media URL or delete the block — never send the placeholder. For **Promos**, produce the banner from the Canva Promos master (swap the headline, export 1200×400 PNG, upload to WP Media). **Newsletters** takes one hero, **Informational** takes three 120px article thumbnails, **HealthTips** has no image slot.
4. Recipient = segment tag **AND** `marketing-opt-in`.
5. Test-send to `team@vancemedicalfoods.co.uk`; check Gmail + Outlook rendering, all links, unsubscribe.
6. Schedule Tue/Thu 09:30 UK; review opens/clicks after 72h and log in the tracking sheet below.

## 5. KPI baseline to track monthly

| Metric | Target (6-mo) |
|---|---|
| Open rate | ≥ 35% (small consented list should run high) |
| Click rate | ≥ 4% |
| Unsubscribe per send | < 0.5% |
| List growth (opt-ins/mo) | Track from Sep baseline |
| Funnel completion (nurture) | ≥ 60% reach email 5 |

**Note on list size:** there are currently only 2 subscribers in FluentCRM. The first job of this calendar (Sep) is switching on the nurture funnels and the marketing opt-in capture at registration (IMPLEMENTATION-PLAN.md Phase 2 / ENG-04) — the calendar assumes that plumbing lands first.

*Awareness dates marked "confirm" should be verified against the organiser (BAPEN, Crohn's & Colitis UK, Self Care Forum) when scheduling — exact weeks move year to year.*
