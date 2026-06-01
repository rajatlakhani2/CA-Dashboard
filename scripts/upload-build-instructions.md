# Upload frontend build (fixes 500 on CSS/JS)

On your PC (project folder):

```bash
npm ci
npm run build
```

Upload the folder `public/build` to the server at:

`~/public_html/app.kuhu.org.in/public/build`

Then on server:

```bash
cd ~/public_html/app.kuhu.org.in
php artisan view:clear
```
