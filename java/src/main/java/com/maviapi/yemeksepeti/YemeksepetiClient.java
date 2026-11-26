// Unofficial Yemeksepeti API client for Java 11+.
// Not affiliated with Yemeksepeti. Endpoint reference: https://maviapi.com/apis/yemeksepeti

package com.maviapi.yemeksepeti;

import java.io.IOException;
import java.io.UncheckedIOException;
import java.net.URI;
import java.net.URLEncoder;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.nio.charset.StandardCharsets;
import java.time.Duration;
import java.util.Map;

/** Yemeksepeti API client. Every method returns the response body: a JSON document. */
public class YemeksepetiClient {
    public static final String DEMO_KEY = "mvk_test_playground_5f3c1a8d";

    /** An error status answered by the API. Its body is a JSON object with "error" and "message". */
    public static class ApiError extends RuntimeException {
        private final int status;
        private final String body;

        public ApiError(int status, String body) {
            super(status + ": " + body);
            this.status = status;
            this.body = body;
        }

        public int getStatus() {
            return status;
        }

        public String getBody() {
            return body;
        }
    }

    private final String apiKey;
    private final String baseUrl;
    private final HttpClient http = HttpClient.newHttpClient();

    /** Uses the MAVIAPI_API_KEY environment variable, then the shared demo key. */
    public YemeksepetiClient() {
        this(null, null);
    }

    public YemeksepetiClient(String apiKey) {
        this(apiKey, null);
    }

    public YemeksepetiClient(String apiKey, String baseUrl) {
        this.apiKey = first(apiKey, System.getenv("MAVIAPI_API_KEY"), DEMO_KEY);
        String root = first(baseUrl, System.getenv("MAVIAPI_BASE_URL"), "https://api.maviapi.com");
        while (root.endsWith("/")) {
            root = root.substring(0, root.length() - 1);
        }
        this.baseUrl = root + "/v1/sites/yemeksepeti";
    }

    /**
     * Discover restaurants for a location and optionally attach the top results' full
     * menus in one call.
     *
     * Parameters:
     *   city (string): City to deliver to (ignored if lat/lng are given). One of:
     *     istanbul, ankara, izmir, bursa, antalya, adana, gaziantep, konya
     *   lat (number): Latitude (overrides city)
     *   lng (number): Longitude (overrides city)
     *   cuisine (string): Filter by cuisine: a numeric cuisine id or a name (e.g.
     *     'Burger', 'Pizza'). See available_cuisines in the response for valid values
     *   sort (string): Sort order for the results. One of: , rating, delivery_time,
     *     distance, minimum_order_amount
     *   menu (boolean): Attach the full menu to the top results
     *   menuCount (integer): How many top restaurants to enrich with their menu (max 3).
     *     One of: 1, 2, 3
     *   limit (integer)
     *   offset (integer)
     *   refresh (boolean)
     */
    public String explore(Map<String, Object> params) {
        return get("/explore", params);
    }

    /**
     * Full menu of one restaurant by vendor code: categories, products, descriptions,
     * images and prices (TRY, incl. discounts).
     *
     * Each successful request uses 10 credits.
     *
     * Parameters:
     *   code (string, required): Restaurant vendor code (the `code` field from
     *     /restaurants, e.g. 'frtm'). Path parameter
     *   limit (integer): Max categories per page
     *   offset (integer)
     *   refresh (boolean): Bypass and repopulate the cache
     */
    public String restaurantByCode(Map<String, Object> params) {
        return get("/restaurant/{code}", params, "code");
    }

    /**
     * List and filter restaurants delivering to a location (by city or lat/lng). Filter by
     * cuisine (id or name) and sort; paginated.
     *
     * Parameters:
     *   city (string): City to deliver to (ignored if lat/lng are given). One of:
     *     istanbul, ankara, izmir, bursa, antalya, adana, gaziantep, konya
     *   lat (number): Latitude (overrides city)
     *   lng (number): Longitude (overrides city)
     *   cuisine (string): Filter by cuisine: a numeric cuisine id or a name (e.g.
     *     'Burger', 'Pizza'). See available_cuisines in the response for valid values
     *   sort (string): Sort order for the results. One of: , rating, delivery_time,
     *     distance, minimum_order_amount
     *   limit (integer): Page size (max 60)
     *   offset (integer): Items to skip
     *   refresh (boolean): Bypass and repopulate the cache
     */
    public String restaurants(Map<String, Object> params) {
        return get("/restaurants", params);
    }

    private static String first(String... values) {
        for (String value : values) {
            if (value != null && !value.isEmpty()) {
                return value;
            }
        }
        return "";
    }

    private String get(String path, Map<String, Object> params, String... required) {
        for (String name : required) {
            if (params.get(name) == null) {
                throw new IllegalArgumentException("Missing required parameter: " + name);
            }
        }
        StringBuilder query = new StringBuilder();
        for (Map.Entry<String, Object> entry : params.entrySet()) {
            if (entry.getValue() == null) {
                continue;
            }
            String token = "{" + entry.getKey() + "}";
            String text = URLEncoder.encode(String.valueOf(entry.getValue()), StandardCharsets.UTF_8);
            if (path.contains(token)) {
                path = path.replace(token, text.replace("+", "%20"));
            } else {
                query.append(query.length() == 0 ? "?" : "&")
                    .append(URLEncoder.encode(entry.getKey(), StandardCharsets.UTF_8))
                    .append("=")
                    .append(text);
            }
        }
        HttpRequest request = HttpRequest.newBuilder(URI.create(baseUrl + path + query))
            .timeout(Duration.ofSeconds(120))
            .header("Authorization", "Bearer " + apiKey)
            .header("Accept", "application/json")
            .header("User-Agent", "yemeksepeti-unofficial-sdk/0.1.0 (java)")
            .GET()
            .build();
        try {
            HttpResponse<String> response = http.send(request, HttpResponse.BodyHandlers.ofString());
            if (response.statusCode() >= 400) {
                throw new ApiError(response.statusCode(), response.body());
            }
            return response.body();
        } catch (IOException e) {
            throw new UncheckedIOException(e);
        } catch (InterruptedException e) {
            Thread.currentThread().interrupt();
            throw new IllegalStateException(e);
        }
    }
}
