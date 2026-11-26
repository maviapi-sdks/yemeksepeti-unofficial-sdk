<?php

// Unofficial Yemeksepeti API client for PHP.
// Not affiliated with Yemeksepeti. Endpoint reference: https://maviapi.com/apis/yemeksepeti

declare(strict_types=1);

namespace YemeksepetiSdk;

/** An error status answered by the API. */
class ApiError extends \RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
    ) {
        parent::__construct("$status $errorCode: $message", $status);
    }
}

class YemeksepetiClient
{
    public const DEMO_KEY = 'mvk_test_playground_5f3c1a8d';

    private string $apiKey;
    private string $baseUrl;

    /**
     * With no $apiKey it uses the MAVIAPI_API_KEY environment variable, then the
     * shared demo key.
     */
    public function __construct(?string $apiKey = null, ?string $baseUrl = null, private int $timeout = 120)
    {
        $this->apiKey = $apiKey ?: (getenv('MAVIAPI_API_KEY') ?: self::DEMO_KEY);
        $root = $baseUrl ?: (getenv('MAVIAPI_BASE_URL') ?: 'https://api.maviapi.com');
        $this->baseUrl = rtrim($root, '/') . '/v1/sites/yemeksepeti';
    }

    /**
     * Discover restaurants for a location and optionally attach the top results' full
     * menus in one call.
     *
     * Parameters:
     *   $city (string): City to deliver to (ignored if lat/lng are given). One of:
     *     istanbul, ankara, izmir, bursa, antalya, adana, gaziantep, konya
     *   $lat (number): Latitude (overrides city)
     *   $lng (number): Longitude (overrides city)
     *   $cuisine (string): Filter by cuisine: a numeric cuisine id or a name (e.g.
     *     'Burger', 'Pizza'). See available_cuisines in the response for valid values
     *   $sort (string): Sort order for the results. One of: , rating, delivery_time,
     *     distance, minimum_order_amount
     *   $menu (boolean): Attach the full menu to the top results
     *   $menuCount (integer): How many top restaurants to enrich with their menu (max
     *     3). One of: 1, 2, 3
     *   $limit (integer)
     *   $offset (integer)
     *   $refresh (boolean)
     *
     * @return array<string, mixed>
     */
    public function explore(?string $city = null, ?float $lat = null, ?float $lng = null, ?string $cuisine = null, ?string $sort = null, ?bool $menu = null, ?int $menuCount = null, ?int $limit = null, ?int $offset = null, ?bool $refresh = null): array
    {
        return $this->get('/explore', ['city' => $city, 'lat' => $lat, 'lng' => $lng, 'cuisine' => $cuisine, 'sort' => $sort, 'menu' => $menu, 'menuCount' => $menuCount, 'limit' => $limit, 'offset' => $offset, 'refresh' => $refresh]);
    }

    /**
     * Full menu of one restaurant by vendor code: categories, products, descriptions,
     * images and prices (TRY, incl. discounts).
     *
     * Each successful request uses 10 credits.
     *
     * Parameters:
     *   $code (string, required): Restaurant vendor code (the `code` field from
     *     /restaurants, e.g. 'frtm'). Path parameter
     *   $limit (integer): Max categories per page
     *   $offset (integer)
     *   $refresh (boolean): Bypass and repopulate the cache
     *
     * @return array<string, mixed>
     */
    public function restaurantByCode(string $code, ?int $limit = null, ?int $offset = null, ?bool $refresh = null): array
    {
        return $this->get('/restaurant/{code}', ['code' => $code, 'limit' => $limit, 'offset' => $offset, 'refresh' => $refresh]);
    }

    /**
     * List and filter restaurants delivering to a location (by city or lat/lng). Filter by
     * cuisine (id or name) and sort; paginated.
     *
     * Parameters:
     *   $city (string): City to deliver to (ignored if lat/lng are given). One of:
     *     istanbul, ankara, izmir, bursa, antalya, adana, gaziantep, konya
     *   $lat (number): Latitude (overrides city)
     *   $lng (number): Longitude (overrides city)
     *   $cuisine (string): Filter by cuisine: a numeric cuisine id or a name (e.g.
     *     'Burger', 'Pizza'). See available_cuisines in the response for valid values
     *   $sort (string): Sort order for the results. One of: , rating, delivery_time,
     *     distance, minimum_order_amount
     *   $limit (integer): Page size (max 60)
     *   $offset (integer): Items to skip
     *   $refresh (boolean): Bypass and repopulate the cache
     *
     * @return array<string, mixed>
     */
    public function restaurants(?string $city = null, ?float $lat = null, ?float $lng = null, ?string $cuisine = null, ?string $sort = null, ?int $limit = null, ?int $offset = null, ?bool $refresh = null): array
    {
        return $this->get('/restaurants', ['city' => $city, 'lat' => $lat, 'lng' => $lng, 'cuisine' => $cuisine, 'sort' => $sort, 'limit' => $limit, 'offset' => $offset, 'refresh' => $refresh]);
    }

    /**
     * @param array<string, scalar|null> $params
     * @return array<string, mixed>
     */
    private function get(string $path, array $params): array
    {
        $query = [];
        foreach ($params as $name => $value) {
            if ($value === null) {
                continue;
            }
            $text = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
            $token = '{' . $name . '}';
            if (str_contains($path, $token)) {
                $path = str_replace($token, rawurlencode($text), $path);
            } else {
                $query[$name] = $text;
            }
        }
        $curl = curl_init($this->baseUrl . $path . ($query ? '?' . http_build_query($query) : ''));
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => 'yemeksepeti-unofficial-sdk/0.1.0 (php)',
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->apiKey, 'Accept: application/json'],
        ]);
        $body = curl_exec($curl);
        if ($body === false) {
            throw new ApiError(0, 'network_error', curl_error($curl));
        }
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $data = json_decode($body, true);
        if ($status >= 400) {
            $detail = is_array($data) ? $data : [];
            throw new ApiError(
                $status,
                (string) ($detail['error'] ?? 'http_error'),
                (string) ($detail['message'] ?? substr($body, 0, 200)),
            );
        }
        return is_array($data) ? $data : [];
    }
}
