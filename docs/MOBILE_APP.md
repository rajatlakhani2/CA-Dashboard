# CA Dashboard mobile app

## How it works

- `mobile/` is a Capacitor 8 Android app. It opens `https://app.kuhu.org.in/home`, so **every website
  change reaches the app immediately** – no new app build needed.
- A new build is only needed when something inside `mobile/` changes (icon, name, a new native plugin).
  Pushing such a change to `master` runs `.github/workflows/android-app.yml`, which builds the APK and the
  Play Store bundle (AAB), and uploads the APK to `https://app.kuhu.org.in/downloads/ca-dashboard.apk`.
  It can also be started by hand: GitHub → Actions → Android App → Run workflow.
- Staff download it from **https://app.kuhu.org.in/get-app** (also linked on the sign-in page and in
  Settings → Notifications). iPhone users get "Add to Home Screen" steps on the same page.

| Piece | Where |
|---|---|
| Phone start page (staff → My Day, others → dashboard) | `routes/web.php` → `/home` |
| Push, fingerprint lock, camera scan helpers | `public/js/native-app.js` |
| Push sender (Firebase HTTP v1) | `app/Services/PushNotificationService.php` |
| Push triggers | `app/Observers/PushTaskObserver.php`, `PushPaymentObserver.php`, `php artisan push:due-tomorrow` (08:30 daily) |
| Phones registered for push | table `user_devices`, `app/Http/Controllers/AppDeviceController.php` |
| Each user's alert choices | `users.notification_preferences`, Settings → Notifications |
| No-internet screen | `mobile/www/error.html` |
| Download page / privacy policy | `/get-app`, `/privacy` |

## One-time setup (owner)

### 1. Release signing key (needed before the Play Store)
Add these four repository secrets (GitHub → Settings → Secrets and variables → Actions → New repository secret):

- `ANDROID_KEYSTORE_BASE64` – contents of `keystore-base64.txt`
- `ANDROID_KEYSTORE_PASSWORD` – the keystore password
- `ANDROID_KEY_ALIAS` – `cadashboard`
- `ANDROID_KEY_PASSWORD` – same as the keystore password

Keep `ca-dashboard-release.jks` and its password safe offline. Until the secrets exist, builds are signed with
`mobile/android/app/fallback-signing.jks` (fine for direct installs, not for the Play Store). Switching keys
means people with the fallback build must uninstall it once and install the new one.

### 2. Push notifications (Firebase, free)
1. https://console.firebase.google.com → Add project (e.g. "CA Dashboard").
2. Add app → Android → package name `in.org.kuhu.cadashboard` → download `google-services.json`.
   Add its full contents as the repository secret `GOOGLE_SERVICES_JSON`, then re-run the Android App workflow.
3. Project settings → Service accounts → Generate new private key (a JSON file).
   Upload it to the server outside `public_html` (e.g. `/home/kuhuorgi/firebase-ca-dashboard.json`) and add to
   the server `.env`:
   ```
   FIREBASE_CREDENTIALS=/home/kuhuorgi/firebase-ca-dashboard.json
   ```
   then `php artisan config:clear`.

Until both parts exist the site behaves exactly as before and the app simply doesn't ask for notification permission.

### 3. Google Play (optional)
1. Play Console account ($25 one-time) → Create app "CA Dashboard", free, App.
2. Upload `ca-dashboard.aab` from the latest Android App workflow run (Artifacts) – must be signed with the
   release key from step 1.
3. Store listing (text below), privacy policy URL `https://app.kuhu.org.in/privacy`, Data safety form:
   collects name, email, phone number, files/photos (user-chosen uploads), device ID (push token);
   all encrypted in transit; not shared for ads; users can request deletion.
4. For staff only: publish on the **Internal testing** or **Closed testing** track and add their emails.
   Note: new personal Play accounts must first run a closed test with a minimum number of testers for some
   days before going public – check the current rule in Play Console.

**Short description (80 chars):** Clients, filings, tasks and billing for your CA firm – on your phone.

**Full description:**
CA Dashboard is the practice-management app for chartered accountancy firms using app.kuhu.org.in.
- My Day: today's and overdue tasks with one-tap Done, notes and time logging
- Action inbox for partners: filings due, unpaid invoices, documents pending
- Alerts when a task is assigned to you, what is due tomorrow, and payments received
- Scan client documents with the camera straight into the right client
- Fingerprint or face lock, automatic sign-out on inactivity
Sign-in is with your firm workspace ID, email and password from your firm administrator.

## Not included yet
- **iPhone app in the App Store** – needs an Apple Developer account ($99/year) and a Mac build step.
  iPhones use Add to Home Screen meanwhile.
- **Offline data** – without internet the app shows a "No internet" screen and reloads by itself when back online.
