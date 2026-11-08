# Zaytrix

Laravel 13 crypto dashboard — rebuild from scratch.
Stack: Laravel 13 + Inertia/React + Vite + Bun + SQLite + Laravel Reverb (WebSocket)

---

## Progress Fase

- [x] **Fase 1 — Scaffold & Auth**
  - Laravel 13 scaffold dari nol
  - 17 migrasi SQLite (users, cache, jobs, portfolio, transactions, alert_configs, knowledge_documents, knowledge_chunks, ai_usage_logs, upstream_healths, vault_secrets, audit_logs, webauthn_credentials, totp_secrets, backup_codes, paper_trades, strategies, watchlists, settings)
  - Auth: email/password + JWT + TOTP 2FA + WebAuthn
  - Inertia/React baseline (createRoot + createElement), Vite build

- [x] **Fase 2 — Market Dashboard**
  - `MarketDataService.php` — Top 500 CoinGecko (paginated 250/page), `Cache::remember` 60s, global stats 120s
  - `PollMarketData` job + `market:poll` command, schedule `everyMinute`
  - `MarketController.php` → Inertia `Market/Dashboard`
  - UI: 4 KPI cards + tabel paginated 25 baris/page (20 halaman)
  - `ExampleTest` diperbaiki (redirect `/` → `/dashboard`, auth redirect)
  - Commit: `9668a81`

- [x] **Fase 3 — On-Chain + Whale Radar + WebSocket**
  - `composer require laravel/reverb:^1.12` + `reverb:install`
  - Config: `config/reverb.php`, `config/broadcasting.php`, `routes/channels.php`
  - `.env` / `.env.example` diupdate dengan Reverb vars
  - `OnChainService.php` — BTC on-chain metrics dari `mempool.space/api/v1/*`:
    - price, difficulty-adjustment, blocks, fees/recommended, fees/mempool-blocks
    - mining/pools/1w, mining/hashrate/3d, mining/difficulty-adjustments/1d
    - Cache 60–300s
  - `WhaleRadarService.php` — bounded Binance WebSocket (8s window, connect → collect → disconnect)
    - **Fallback**: Binance WS blok di Termux (timeout) → gunakan `mempool.space/api/v1/fees/mempool-blocks` (urut totalFees tertinggi)
    - Cache `whale:latest` 15s
  - `PollWhales` job + `whale:poll` command (`--window`, `--threshold`), schedule `everyTwoMinutes`
  - `OnChainController.php` → Inertia `OnChain/Dashboard` (baca whales dari cache, tidak blocking)
  - UI On-Chain: KPI cards (price, difficulty change, fastest fee, blocks in mempool) + tabel blocks/pools/hashrate + whale radar table
  - Frontend build: `bun node_modules/.bin/vite build` → 588 modules, 371KB bundle, 1.14s
  - Test: 3/3 passed, 0 failures
  - Commit: `4490a03`

- [ ] **Fase 4 — Portfolio & Trading**
  - Portfolio tracking (real + paper trades)
  - Strategy engine (scaffold)
  - Watchlist management
  - Alert configs (price, on-chain, whale)
  - Transaction CRUD

- [ ] **Fase 5 — AI & Knowledge**
  - 9router AI integration (localhost:20128 → OpenRouter → Gemini)
  - Knowledge documents + chunks (RAG)
  - AI usage logs
  - Upstream health monitoring

- [ ] **Fase 6 — Security Hardening & Production**
  - Security audit (25+ bugs fixed in Fase 1–3)
  - HTTPS + PWA
  - CI/CD pipeline (GitHub Actions)
  - Rate limiting, CSRF, session hardening
  - Audit logs

- [ ] **Fase 7 — Polish & Deploy**
  - UI polish (dark mode, responsive)
  - Performance optimization
  - Deploy ke VPS/hosting

---

## Catatan Pengembangan

- **Repo**: https://github.com/Zayidan123/Zaytrix.git (branch `main`)
- **Lingkungan**: Termux on Android, PHP 8.5, Bun (bukan npm), SQLite
- **API bebas kunci**: mempool.space/api/v1/* (on-chain), CoinGecko (market)
- **API terblok di Termux**: Binance WebSocket (timeout), CoinGecko onchain (butuh API key)
- **Token GitHub**: PAT untuk push saja, tidak di-commit (disimpan di memory agent)
