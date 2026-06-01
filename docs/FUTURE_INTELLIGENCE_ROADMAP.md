# Future Intelligence Roadmap

Phased plan for AI, automation, portal, and payments on top of the existing CA Dashboard (Laravel 12, Blade, policies, WhatsApp service, billing queue, service dues, credentials vault).

**Status today:** **Phases 1–4 are implemented** in the repo (see sections at end of this file). The app has the full **data foundation** plus anomaly alerts, firm-side AI assistant, collections/risk/timeline, UPI links, document review queue, client portal, and WhatsApp inbound auto-reply. **E-invoice / IRN is out of scope.** Optional later: full OCR API, AI-classified WhatsApp intents, payment-gateway webhooks.

---

## How to read this doc

| Column | Meaning |
|--------|---------|
| **Phase** | Suggested delivery order (1 = soonest value) |
| **Effort** | S = weeks, M = 1–2 months, L = quarter+ |
| **Depends on** | External APIs or major new subsystems |

---

## 1. AI assistant (firm-side)

**User stories**

- “Summarize this client” on client show
- “Draft reminder WhatsApp” from overdue dues / outstanding
- “Explain overdue stack” (dues + tasks + invoices in plain language)

**What you already have**

- `ClientController::show` loads dues, tasks, invoices, worksheets
- `WhatsAppService` for outbound messages
- Activity log + credential vault audit for context

**Build**

| Piece | Notes |
|-------|--------|
| `ClientContextBuilder` | Read-only DTO: client profile, open dues, active tasks, open invoices, last payment, manager |
| `AiAssistantService` | Prompt templates per action; calls LLM API; never sends secrets/passwords |
| UI | Panel on client show + optional global “Ask” from Ctrl+K |
| Audit | Log prompt type, user, client_id, token usage (not full prompt if sensitive) |
| Feature flag | `AI_ENABLED=false` default; partner-only at first |

**Config**

```env
AI_ENABLED=false
AI_PROVIDER=openai   # openai | azure
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini
```

**Risks**

- Hallucination on tax advice → system prompt: *operational summary only, not legal advice*
- PII in prompts → strip PAN/GSTIN last digits or hash in logs

**Phase:** 1 · **Effort:** S · **Depends on:** LLM API key

---

## 2. OCR + auto-fill

**User stories**

- Upload notice / 26AS / bank PDF → extract PAN, assessment year, amounts, due dates
- Suggest: new task, service due, or worksheet line

**What you already have**

- `SmartDocumentController`, `ClientDocument`, file storage
- Task / due / worksheet create flows

**Build**

| Piece | Notes |
|-------|--------|
| `DocumentIngestionJob` | Queue upload → OCR → structured JSON |
| OCR provider | Azure Document Intelligence or Google Document AI (better for Indian forms than raw GPT vision alone) |
| Review UI | Partner sees extracted fields → confirm before create |
| Link | Attach source file to created task/due |

**Phase:** 2 · **Effort:** M · **Depends on:** OCR API + queue worker

---

## 3. Predictive compliance

**User stories**

- “3 clients likely to miss GSTR-1 based on history”
- Risk score per client × service

**What you already have**

- `ServiceDue`, `ServiceDueGenerator`, completion history, `due_date`, `status`
- Client × service mapping via `client_services`

**Build (rules-first, ML later)**

| Signal | Rule |
|--------|------|
| Past late completions | Avg days after due_date for same service |
| Missing tasks | Open tasks linked to due past threshold |
| No activity | No task/due movement in N days before due |
| Seasonal | Month-of-year from historical dues |

| Piece | Notes |
|-------|--------|
| `ComplianceRiskScorer` | Nightly artisan command; writes `compliance_risk_scores` table |
| Dashboard widget | Partner dashboard “At risk this week” |
| Phase 2b | Optional ML on features above |

**Phase:** 2 · **Effort:** M (rules) / L (ML) · **Depends on:** 6+ months of due history for accuracy

---

## 4. Smart collections

**User stories**

- “Who to call today” from aging + last reminder + promise-to-pay

**What you already have**

- Invoices, payments, ledger outstanding, renewal reminders (WhatsApp patterns)

**Build**

| Piece | Notes |
|-------|--------|
| `collections` module | Aging buckets (0–30, 31–60, 61–90, 90+) |
| `collection_follow_ups` | last_contact_at, channel, promise_date, next_action |
| Daily list | Sort: highest outstanding × days since last contact |
| AI tie-in | Phase 1 assistant drafts call/WhatsApp script from row context |

**Phase:** 2 · **Effort:** M · **Depends on:** follow-up data entry (or import)

---

## 5. Payment links / QR (no e-invoice)

**Out of scope:** Government **e-invoice / IRN (NIC IRP)** — not planned for this product.

**User stories**

- Invoice PDF or email includes pay link or UPI QR; client pays online; payment auto-marks invoice

**What you already have**

- `Invoice`, PDF generation, `Payment` recording

**Build**

| Piece | Notes |
|-------|--------|
| Provider | Razorpay Payment Links / Stripe India / bank UPI deep link |
| `Invoice::payment_link_id` | Store provider reference |
| Webhook | `POST /webhooks/payments/{provider}` → create `Payment`, update invoice status |
| QR on PDF | Encoded payment URL or UPI string (collection convenience, not statutory e-invoice) |

**Phase:** 3 · **Effort:** M · **Depends on:** payment gateway KYC + webhook HTTPS

---

## 6. Client portal

**User stories**

- Client logs in, sees status, uploads docs, pays invoice

**What you already have**

- Firm-only auth (`User` roles). No `ClientUser` model.

**Build**

| Piece | Notes |
|-------|--------|
| `ClientPortalUser` | email/mobile, belongs to `client_id`, magic link or OTP |
| Routes | `routes/portal.php` — separate guard `portal` |
| Features v1 | Document upload, task/due status (read-only), invoice list + pay link |
| Features v2 | Messages, approval of deliverables |
| Security | No credentials vault, no other clients’ data |

**Phase:** 3 · **Effort:** L · **Depends on:** new auth stack + hosting hardening

---

## 7. Voice / WhatsApp bot

**User stories**

- Client: “Status of my ITR?” → reply from DB
- Firm: internal notifications (already partial via WhatsApp outbound)

**What you already have**

- `WhatsAppService` (outbound)
- Meta Cloud API can receive webhooks (not wired)

**Build**

| Piece | Notes |
|-------|--------|
| Webhook controller | Verify signature, parse message, map phone → `Client` contact |
| Intent router | Rules: status / due date / outstanding / handoff to human |
| AI layer | Optional: classify intent with small model; answer from `ClientContextBuilder` |
| Consent | Opt-in per client; log conversations |

**Phase:** 4 · **Effort:** L · **Depends on:** Meta Business verification, 24h session rules, template messages for outbound

---

## 8. Anomaly alerts

**User stories**

- Unusual ledger movement
- Duplicate PAN across clients
- Credential never used (or accessed after long idle)

**What you already have**

- Ledger, clients (unique PAN), `ClientCredential` + vault audit events

**Build**

| Anomaly | Detection |
|---------|-----------|
| Duplicate PAN | DB query + nightly check |
| Large ledger jump | % change vs 90-day average per client |
| Credential idle | `last_accessed_at` > 90 days or never accessed since create |
| Invoice without tasks | Billing pattern break |

| Piece | Notes |
|-------|--------|
| `anomaly:scan` command | Writes `firm_alerts` table |
| Notify | Dashboard banner + optional email/WhatsApp to partner |

**Phase:** 1–2 · **Effort:** S (rules only) · **Depends on:** nothing external

---

## Recommended implementation order

```text
Phase 1 (8–12 weeks, mostly internal)
  ├── Anomaly alerts (rules)
  ├── AI assistant MVP (summarize + explain overdue + draft WhatsApp)
  └── Extend Ctrl+K with “actions” (create task, log payment)

Phase 2
  ├── Smart collections center
  ├── Predictive compliance (rules-based risk scores)
  └── Client timeline (feeds AI + collections)

Phase 3
  ├── Payment links + QR on invoice (no e-invoice / IRN)
  ├── OCR ingest with human confirm
  └── Client portal v1 (status + upload + pay)

Phase 4
  ├── WhatsApp inbound bot
  └── ML refinement on compliance risk
```

---

## Architecture sketch

```text
                    ┌─────────────────┐
                    │  Blade / API    │
                    └────────┬────────┘
                             │
         ┌───────────────────┼───────────────────┐
         ▼                   ▼                   ▼
 ┌───────────────┐  ┌───────────────┐  ┌───────────────┐
 │ ClientContext │  │ AnomalyScanner│  │ Collections   │
 │ Builder       │  │ (rules)       │  │ Scorer        │
 └───────┬───────┘  └───────────────┘  └───────────────┘
         │
         ▼
 ┌───────────────┐     ┌───────────────┐
 │ AiAssistant   │────►│ LLM provider  │
 │ Service       │     │ (OpenAI/Azure)│
 └───────────────┘     └───────────────┘

 Async: DocumentIngestionJob → OCR → review queue
 Webhooks: Payment provider, WhatsApp inbound
 Portal: separate guard + routes
```

---

## Compliance & ops (India CA context)

- **AI outputs:** Display disclaimer; partner approves WhatsApp drafts before send (reuse existing send flows).
- **Client data:** DPA-style consent for portal and bot; minimal retention on chat logs.
- **E-invoice / IRN:** Explicitly **excluded** from this roadmap.
- **Payment QR / links:** Optional collection UX only; not NIC e-invoicing.
- **Credentials:** Never pass vault passwords into LLM context.

---

## What to build first (if you pick one)

| Priority | Feature | Why |
|----------|---------|-----|
| **A** | AI summarize + overdue explain + draft WhatsApp | Uses existing data; visible on client show immediately |
| **B** | Anomaly alerts | No external API; protects the firm |
| **C** | Smart collections | Direct revenue impact |
| **D** | Payment links / UPI QR (not e-invoice) | Client-facing value; needs gateway setup |

Reply with **A / B / C / D** (or a custom order) to start implementation in the repo.

---

## Phase 1 implemented (2026-05-29)

- **Anomaly scanner** — `anomaly:scan` (daily 6:30 AM), firm alerts on partner dashboard
- **AI assistant** — client show panel (partner/manager): summarize, explain overdue, draft WhatsApp
- Configure: `AI_ENABLED`, `OPENAI_API_KEY` in `.env`
- E-invoice remains out of scope

## Phase 2 implemented (2026-05-29)

- **Collections center** — `/collections` (Finance sidebar): aging buckets, call-today priority, log follow-ups
- **Compliance risk scores** — `compliance:score-risk` (daily 6:45 AM); partner dashboard + client show banner
- **Client timeline** — Timeline tab on client show (tasks, dues, invoices, payments, vault, follow-ups)

## Phase 3 implemented (2026-05-29)

- **UPI payment links + QR** — auto `payment_url` on invoices; QR on invoice show; UPI string on PDF (not e-invoice)
- **Document review queue** — `/document-ingestions` with filename hints; confirm → optional task
- **Client portal** — magic link from client show; client sees dues, pay QR, upload documents
- Configure **Settings → bank_upi** for payment links

## Phase 4 implemented (2026-05-29)

- **WhatsApp inbound bot** — Meta webhook at `/webhooks/whatsapp`; intent router (compliance, invoices, help); message audit log
- **Compliance risk v2** — category weighting, pending velocity, consecutive late streak; `predicted_miss` flag on scores
- Configure: `WHATSAPP_INBOUND_ENABLED`, `WHATSAPP_WEBHOOK_VERIFY_TOKEN`, existing `WHATSAPP_TOKEN` / `WHATSAPP_PHONE_NUMBER_ID`
