# cPanel deploy (no Git on server)

Your server folder is **not a git clone** (`fatal: not a git repository`).  
Use **curl from GitHub** instead of `git pull`.

## One-time: copy all of this into cPanel Terminal

```bash
cd ~/app.kuhu.org.in || cd ~/public_html/app.kuhu.org.in
mkdir -p scripts
curl -fsSL -o scripts/sync-saas-dashboard.sh \
  https://raw.githubusercontent.com/rajatlakhani2/CA-Dashboard/master/scripts/sync-saas-dashboard.sh
bash scripts/sync-saas-dashboard.sh
```

## What you should see

- Many lines: `ok resources/views/...`
- `SUCCESS: SaaS dashboard is on disk.`
- `Nothing to migrate` is **OK** (database already updated)

## Browser

1. Incognito window  
2. https://app.kuhu.org.in/dashboard  
3. **Ctrl+F5**

Look for **SaaS Workspace** banner and **Dashboard SaaS v1**.

## If curl fails

Check internet from server:

```bash
curl -I https://raw.githubusercontent.com/rajatlakhani2/CA-Dashboard/master/artisan
```

## Check after deploy

```bash
curl -fsSL -o scripts/check-saas-deploy.sh \
  https://raw.githubusercontent.com/rajatlakhani2/CA-Dashboard/master/scripts/check-saas-deploy.sh
bash scripts/check-saas-deploy.sh
```
