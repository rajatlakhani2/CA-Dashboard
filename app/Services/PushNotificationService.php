<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Sends push notifications to the Android app through Firebase Cloud Messaging (HTTP v1).
 *
 * Stays silent until FIREBASE_CREDENTIALS is set in .env, so nothing breaks before Firebase is set up.
 */
class PushNotificationService
{
    /** Notification types a user can switch on or off in Settings → Notifications. */
    public const TYPES = [
        'task_assigned' => 'A task is assigned to me',
        'due_tomorrow' => 'Morning reminder of what is due tomorrow',
        'payment_received' => 'A client payment is recorded (partners and managers)',
    ];

    private ?array $credentials = null;

    public function isConfigured(): bool
    {
        return $this->credentials() !== null;
    }

    /**
     * Send one notification to every phone of this user that has the app.
     *
     * @return int number of phones it was delivered to
     */
    public function sendToUser(User $user, string $type, string $title, string $body, ?string $url = null): int
    {
        if (! $this->isConfigured() || ! $user->wantsPush($type) || ! Schema::hasTable('user_devices')) {
            return 0;
        }

        $sent = 0;
        foreach (UserDevice::where('user_id', $user->id)->get() as $device) {
            if ($this->sendToToken($device, $title, $body, $url, $type)) {
                $sent++;
            }
        }

        return $sent;
    }

    private function sendToToken(UserDevice $device, string $title, string $body, ?string $url, string $type): bool
    {
        $creds = $this->credentials();
        $accessToken = $this->accessToken();
        if (! $creds || ! $accessToken) {
            return false;
        }

        $data = ['type' => $type];
        if ($url) {
            $data['url'] = $url;
        }

        try {
            $response = Http::withToken($accessToken)
                ->timeout(8)
                ->post("https://fcm.googleapis.com/v1/projects/{$creds['project_id']}/messages:send", [
                    'message' => [
                        'token' => $device->token,
                        'notification' => [
                            'title' => mb_substr($title, 0, 120),
                            'body' => mb_substr($body, 0, 400),
                        ],
                        'data' => $data,
                        'android' => [
                            'priority' => 'high',
                            'notification' => ['sound' => 'default'],
                        ],
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::warning('Push send failed: ' . $e->getMessage());

            return false;
        }

        if ($response->successful()) {
            return true;
        }

        // The app was uninstalled or the token expired: forget this phone.
        $status = (string) data_get($response->json(), 'error.status', '');
        $code = (string) data_get($response->json(), 'error.details.0.errorCode', '');
        if ($response->status() === 404 || $status === 'NOT_FOUND' || $code === 'UNREGISTERED') {
            $device->delete();
        } else {
            Log::warning('Push send rejected', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 300)]);
        }

        return false;
    }

    /** OAuth access token for FCM, cached for 50 minutes. */
    private function accessToken(): ?string
    {
        $creds = $this->credentials();
        if (! $creds) {
            return null;
        }

        return Cache::remember('fcm_access_token_' . md5($creds['client_email']), now()->addMinutes(50), function () use ($creds) {
            $now = time();
            $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->base64Url(json_encode([
                'iss' => $creds['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $creds['token_uri'],
                'iat' => $now,
                'exp' => $now + 3600,
            ]));
            $signature = '';
            if (! openssl_sign("{$header}.{$claims}", $signature, $creds['private_key'], OPENSSL_ALGO_SHA256)) {
                Log::warning('Push: could not sign the Firebase token request. Check FIREBASE_CREDENTIALS.');

                return null;
            }
            $jwt = "{$header}.{$claims}." . $this->base64Url($signature);

            try {
                $response = Http::asForm()->timeout(8)->post($creds['token_uri'], [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Push: Firebase token request failed: ' . $e->getMessage());

                return null;
            }

            return $response->successful() ? $response->json('access_token') : null;
        }) ?: null;
    }

    /** Service-account details from FIREBASE_CREDENTIALS (a file path or the JSON itself). */
    private function credentials(): ?array
    {
        if ($this->credentials !== null) {
            return $this->credentials ?: null;
        }

        $raw = (string) config('services.fcm.credentials');
        if ($raw !== '' && ! str_starts_with(ltrim($raw), '{')) {
            $path = str_starts_with($raw, '/') ? $raw : base_path($raw);
            $raw = is_readable($path) ? (string) file_get_contents($path) : '';
        }

        $json = $raw !== '' ? json_decode($raw, true) : null;
        if (! is_array($json) || empty($json['client_email']) || empty($json['private_key'])) {
            $this->credentials = [];

            return null;
        }

        $this->credentials = [
            'client_email' => $json['client_email'],
            'private_key' => $json['private_key'],
            'project_id' => config('services.fcm.project_id') ?: ($json['project_id'] ?? ''),
            'token_uri' => $json['token_uri'] ?? 'https://oauth2.googleapis.com/token',
        ];

        return $this->credentials;
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
