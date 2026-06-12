<?php

namespace App\Support;

use App\Models\ClientCredential;
use InvalidArgumentException;

class GovernmentPortals
{
    public const PORTAL_GST = 'gst';

    public const PORTAL_INCOME_TAX = 'income_tax';

    public const PORTAL_TRACES = 'traces';

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            self::PORTAL_GST => [
                'id' => self::PORTAL_GST,
                'label' => 'GST',
                'logo' => '/images/gov-portals/gst.svg',
                'login_url' => 'https://services.gst.gov.in/services/login',
                'launch_mode' => 'open_autofill',
                'categories' => [ClientCredential::CATEGORY_GST],
                'keywords' => ['gst', 'goods and services'],
                'autofill_hint' => 'User ID and password are copied — enter CAPTCHA on the GST portal.',
            ],
            self::PORTAL_INCOME_TAX => [
                'id' => self::PORTAL_INCOME_TAX,
                'label' => 'Income Tax',
                'logo' => '/images/gov-portals/income-tax.svg',
                'login_url' => 'https://eportal.incometax.gov.in/iec/foservices/#/login',
                'launch_mode' => 'auto_submit',
                'form_action' => 'https://eportal.incometax.gov.in/iec/loginapi/login',
                'form_method' => 'post',
                'form_enctype' => 'application/x-www-form-urlencoded',
                'fields' => [
                    'entity' => 'A',
                    'userId' => '{username}',
                    'password' => '{password}',
                ],
                'categories' => [ClientCredential::CATEGORY_IT],
                'keywords' => ['income tax', 'it portal', 'e-filing', 'efiling'],
                'autofill_hint' => 'Attempting direct login — if it fails, use the copied credentials on the login page.',
            ],
            self::PORTAL_TRACES => [
                'id' => self::PORTAL_TRACES,
                'label' => 'TRACES',
                'logo' => '/images/gov-portals/traces.svg',
                'login_url' => 'https://www.tdscpc.gov.in/app/login.xhtml',
                'launch_mode' => 'auto_submit',
                'form_action' => 'https://www.tdscpc.gov.in/app/login.xhtml',
                'form_method' => 'post',
                'fields' => [
                    'loginForm:userid' => '{username}',
                    'loginForm:password' => '{password}',
                    'loginForm:tanpan' => '{tan}',
                ],
                'categories' => [ClientCredential::CATEGORY_TRACES, ClientCredential::CATEGORY_TAN],
                'keywords' => ['traces', 'tds', 'tdscpc', 'tan'],
                'autofill_hint' => 'Attempting direct login — enter verification code if prompted.',
            ],
        ];
    }

    public static function find(string $portalId): array
    {
        $portals = self::all();

        if (! isset($portals[$portalId])) {
            throw new InvalidArgumentException("Unknown government portal [{$portalId}].");
        }

        return $portals[$portalId];
    }

    /**
     * @return list<string>
     */
    public static function ids(): array
    {
        return array_keys(self::all());
    }

    public static function matchesCredential(array $portal, ClientCredential $credential): bool
    {
        $category = $credential->category ?? ClientCredential::CATEGORY_OTHER;

        if (in_array($category, $portal['categories'], true)) {
            return true;
        }

        $haystack = strtolower(trim(($credential->portal_name ?? '').' '.($credential->notes ?? '')));

        foreach ($portal['keywords'] as $keyword) {
            if ($keyword !== '' && str_contains($haystack, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }
}
