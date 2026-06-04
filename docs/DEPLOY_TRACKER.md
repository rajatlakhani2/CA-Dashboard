# Deploy tracker — Local → GitHub → Server

**Production:** https://app.kuhu.org.in  
**Server path:** `~/public_html/app.kuhu.org.in`  
**GitHub:** https://github.com/rajatlakhani2/CA-Dashboard (`master`)

Update this file **after every task** (feature, fix, or doc change) so you always know what is on your PC, what was pushed, and what actually runs on Spidy/cPanel.

Related: [GITHUB_SPIDY_DEPLOY.md](./GITHUB_SPIDY_DEPLOY.md) · [GITHUB_DEPLOY.md](./GITHUB_DEPLOY.md) · [DEPLOY_CHECKLIST.md](./DEPLOY_CHECKLIST.md)

---

## Status legend

| Symbol | Meaning |
|--------|---------|
| ✅ | Confirmed present / verified |
| 🟡 | Built locally or on branch; not on server yet |
| ⬜ | Not started |
| ❓ | On server but not verified this session |

---

## Task log (copy row per task)

| Date | Task / feature | Local branch @ commit | GitHub `master` | Server (live) | Verified live? | Notes |
|------|----------------|----------------------|-----------------|---------------|----------------|-------|
| _YYYY-MM-DD_ | _e.g. Command Centre UI polish_ | _branch @ `abc1234`_ | _⬜ / 🟡 / ✅ `abc1234`_ | _⬜ / 🟡 / ✅ / ❓_ | _Yes / No_ | _sync script, smoke URL_ |

**After each task, fill one row and refresh the snapshot below.**

---

## Current snapshot

_Last updated: 2026-06-03 (Command Centre UI polish + deploy tracker doc)_

### Git branches (local)

| Branch | Tip commit | vs `origin/master` | Pushed to GitHub? |
|--------|------------|--------------------|-------------------|
| `master` | `50d15d9` — Show all three create-task steps expanded at once | In sync (0 ahead / 0 behind) | ✅ Yes (`origin/master`) |
| `cursor/command-centre-ui-polish` | `e9e1ddd` — Command Centre dashboard UI polish | **+2** commits (not on `master`) | 🟡 No — local only |
| `cursor/task-create-unbilled-billing-ui` | `050dd94` — Unbilled / FOC / create-task / sidebar squash | Diverged (squash not merged to `master`; overlapping fixes already on `master` via other commits) | 🟡 No — local only |

### What is where (summary)

| Layer | State |
|-------|--------|
| **Local (active work)** | `cursor/command-centre-ui-polish` @ `e9e1ddd` — welcome banner, KPIs, pill tabs, priority queue, empty states |
| **GitHub** | `master` @ `50d15d9` — includes expanded create-task (3 steps), sync-ui script, unbilled/FOC, sidebar fix (via separate commits) |
| **Server** | ❓ Assumed ~`origin/master` unless you ran a one-off sync; **Command Centre polish (`e9e1ddd`) not deployed** until merged + pull/sync |

### Recent task rows

| Date | Task / feature | Local branch @ commit | GitHub `master` | Server (live) | Verified live? | Notes |
|------|----------------|----------------------|-----------------|---------------|----------------|-------|
| 2026-06-03 | Command Centre UI polish | `cursor/command-centre-ui-polish` @ `e9e1ddd` | 🟡 Not merged | 🟡 Not deployed | No | Merge to `master` + deploy when ready |
| 2026-06-03 | Unbilled / FOC / create-task / sidebar (squash) | `cursor/task-create-unbilled-billing-ui` @ `050dd94` | 🟡 Overlap on `master` (`50d15d9` line) | ❓ Partial overlap likely | No | Squash branch optional; `master` already has related fixes |
| 2026-06-03 | Create task 3 steps + searchable UI | `master` @ `50d15d9` | ✅ `50d15d9` | ❓ Pull/sync dependent | No | See verify steps below |

---

## Verify on server (after deploy or sync)

Run on **cPanel Terminal** (`~/public_html/app.kuhu.org.in`):

```bash
cd ~/public_html/app.kuhu.org.in
git rev-parse --short HEAD
grep -l "Task UI v2" resources/views/tasks/create.blade.php 2>/dev/null && echo "create-task UI: v2 marker OK"
curl -fsSL -o /tmp/sync-ui-now.sh https://raw.githubusercontent.com/rajatlakhani2/CA-Dashboard/master/scripts/sync-ui-now.sh
bash /tmp/sync-ui-now.sh
php artisan optimize:clear
```

**Browser checks**

1. https://app.kuhu.org.in/clear-app-cache  
2. Incognito → login  
3. **Create Task** — title “Create a new task”, **three sections visible at once**, badge `Task UI v2 · all steps` (bottom-right)  
4. **Command Centre** — indigo welcome banner + pill tabs (after `e9e1ddd` is deployed)  
5. **System Health** (partner) — deploy ref / create-task version shows `v2-searchable (latest)` when applicable  

**If login shows 419 Page Expired:** new Incognito tab, hit clear-app-cache, run `php artisan optimize:clear` on server; do not run `key:generate` on a working site.

---

## Deploy actions (quick reference)

| Goal | Action |
|------|--------|
| Push local `master` to GitHub | `git push origin master` (only when ready) |
| Update server from GitHub | `git pull origin master` + `bash deploy-cpanel.sh` or [GITHUB_SPIDY_DEPLOY.md](./GITHUB_SPIDY_DEPLOY.md) |
| Hotfix UI files without full pull | `bash /tmp/sync-ui-now.sh` (from `master` raw script) + `php artisan optimize:clear` |
| Full Spidy reinstall | `curl … spidy-download-on-server.sh` (see GITHUB_SPIDY_DEPLOY) |

---

## Changelog (tracker doc only)

| Date | Change |
|------|--------|
| 2026-06-03 | Initial tracker; snapshot for `e9e1ddd`, `050dd94`, `50d15d9` |
