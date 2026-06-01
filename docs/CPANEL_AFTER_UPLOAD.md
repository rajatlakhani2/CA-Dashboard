# cPanel — deploy to app.kuhu.org.in

## 1. Subdomain + document root

**Subdomains** → `app` on `kuhu.org.in`:

**Document Root:** `public_html/app.kuhu.org.in/public`

## 2. Upload zip

Extract into **`public_html/app.kuhu.org.in/`** so `artisan` is at:

`public_html/app.kuhu.org.in/artisan`

Upload **`public/build/`** from local `npm run build` if missing.

## 3. `.env`

In `app.kuhu.org.in/.env`:

```ini
APP_URL=https://app.kuhu.org.in
```

## 4. Terminal

```bash
cd ~/public_html/app.kuhu.org.in
bash deploy-cpanel.sh
```

Or run the commands in [PRODUCTION_SETUP.md](./PRODUCTION_SETUP.md).

## 5. Test

https://app.kuhu.org.in/login
