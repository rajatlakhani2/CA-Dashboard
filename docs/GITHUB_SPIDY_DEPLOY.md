# Deploy on Spidy using GitHub (no large zip upload)

**Track what is live:** keep [DEPLOY_TRACKER.md](./DEPLOY_TRACKER.md) updated after each task (Local → GitHub → Server).

## 1. cPanel settings

- Subdomain **app.kuhu.org.in** → document root: `public_html/app.kuhu.org.in/public`
- PHP **8.2** or **8.3**
- Create **MySQL** database + user → link with **ALL PRIVILEGES**

## 2. Download app from GitHub (Terminal)

cPanel → **Terminal** → paste (moves old folder to `app.kuhu.org.in.old`, fixes permissions):

```bash
curl -fsSL https://raw.githubusercontent.com/rajatlakhani2/CA-Dashboard/master/scripts/spidy-download-on-server.sh | bash
```

If you see `$'\r': command not found`, fix scripts then re-run install:

```bash
cd ~/public_html/app.kuhu.org.in
sed -i 's/\r$//' scripts/*.sh
chmod +x scripts/*.sh
bash scripts/spidy-install.sh
```

## 3. Edit `.env`

File Manager → `app.kuhu.org.in` → `.env`:

```ini
APP_URL=https://app.kuhu.org.in
APP_DEBUG=false
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=your_database_name
DB_USERNAME=your_db_user
DB_PASSWORD="your_db_password"
SESSION_DRIVER=file
```

Save.

## 4. Upload UI build (small file ~40 KB)

On your PC the file is:

`D:\New folder\Dashboard\CA Dashboard\cpanel-build-upload.zip`

(or run `npm run build` then zip the `public/build` folder)

cPanel File Manager → `app.kuhu.org.in/public` → **Upload** → extract zip  
You must have: `public/build/manifest.json`

## 5. Finish install (Terminal)

```bash
cd ~/public_html/app.kuhu.org.in
bash scripts/spidy-install.sh
```

## 6. Login

1. https://app.kuhu.org.in/clear-app-cache  
2. Incognito → https://app.kuhu.org.in/login  
3. `rajat@rlassociates.in` / `password`

## Cron (optional)

```cron
* * * * * cd ~/public_html/app.kuhu.org.in && php artisan schedule:run >> /dev/null 2>&1
```
