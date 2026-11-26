// Unofficial Yemeksepeti API client for .NET.
// Not affiliated with Yemeksepeti. Endpoint reference: https://maviapi.com/apis/yemeksepeti

using System.Globalization;
using System.Net.Http.Headers;
using System.Text.Json;

namespace YemeksepetiSdk;

/// <summary>An error status answered by the API.</summary>
public class ApiError : Exception
{
    public int Status { get; }
    public string Code { get; }

    public ApiError(int status, string code, string message) : base($"{status} {code}: {message}")
    {
        Status = status;
        Code = code;
    }
}

public class YemeksepetiClient
{
    public const string DemoKey = "mvk_test_playground_5f3c1a8d";

    private readonly string _apiKey;
    private readonly string _baseUrl;
    private readonly HttpClient _http;

    /// <summary>
    /// With no apiKey it uses the MAVIAPI_API_KEY environment variable, then the
    /// shared demo key.
    /// </summary>
    public YemeksepetiClient(string? apiKey = null, string? baseUrl = null, HttpClient? http = null)
    {
        _apiKey = First(apiKey, Environment.GetEnvironmentVariable("MAVIAPI_API_KEY"), DemoKey);
        var root = First(baseUrl, Environment.GetEnvironmentVariable("MAVIAPI_BASE_URL"), "https://api.maviapi.com");
        _baseUrl = root.TrimEnd('/') + "/v1/sites/yemeksepeti";
        _http = http ?? new HttpClient { Timeout = TimeSpan.FromSeconds(120) };
    }

    // Discover restaurants for a location and optionally attach the top results' full
    // menus in one call.
    //
    // Parameters:
    //   city (string): City to deliver to (ignored if lat/lng are given). One of:
    //     istanbul, ankara, izmir, bursa, antalya, adana, gaziantep, konya
    //   lat (number): Latitude (overrides city)
    //   lng (number): Longitude (overrides city)
    //   cuisine (string): Filter by cuisine: a numeric cuisine id or a name (e.g.
    //     'Burger', 'Pizza'). See available_cuisines in the response for valid values
    //   sort (string): Sort order for the results. One of: , rating, delivery_time,
    //     distance, minimum_order_amount
    //   menu (boolean): Attach the full menu to the top results
    //   menuCount (integer): How many top restaurants to enrich with their menu (max 3).
    //     One of: 1, 2, 3
    //   limit (integer)
    //   offset (integer)
    //   refresh (boolean)
    public Task<JsonElement> ExploreAsync(IDictionary<string, object?> parameters, CancellationToken cancellationToken = default) =>
        GetAsync("/explore", parameters, cancellationToken);

    // Full menu of one restaurant by vendor code: categories, products, descriptions,
    // images and prices (TRY, incl. discounts).
    //
    // Each successful request uses 10 credits.
    //
    // Parameters:
    //   code (string, required): Restaurant vendor code (the `code` field from
    //     /restaurants, e.g. 'frtm'). Path parameter
    //   limit (integer): Max categories per page
    //   offset (integer)
    //   refresh (boolean): Bypass and repopulate the cache
    public Task<JsonElement> RestaurantByCodeAsync(IDictionary<string, object?> parameters, CancellationToken cancellationToken = default) =>
        GetAsync("/restaurant/{code}", parameters, cancellationToken, "code");

    // List and filter restaurants delivering to a location (by city or lat/lng). Filter by
    // cuisine (id or name) and sort; paginated.
    //
    // Parameters:
    //   city (string): City to deliver to (ignored if lat/lng are given). One of:
    //     istanbul, ankara, izmir, bursa, antalya, adana, gaziantep, konya
    //   lat (number): Latitude (overrides city)
    //   lng (number): Longitude (overrides city)
    //   cuisine (string): Filter by cuisine: a numeric cuisine id or a name (e.g.
    //     'Burger', 'Pizza'). See available_cuisines in the response for valid values
    //   sort (string): Sort order for the results. One of: , rating, delivery_time,
    //     distance, minimum_order_amount
    //   limit (integer): Page size (max 60)
    //   offset (integer): Items to skip
    //   refresh (boolean): Bypass and repopulate the cache
    public Task<JsonElement> RestaurantsAsync(IDictionary<string, object?> parameters, CancellationToken cancellationToken = default) =>
        GetAsync("/restaurants", parameters, cancellationToken);

    private static string First(params string?[] values) => values.First(value => !string.IsNullOrEmpty(value))!;

    private async Task<JsonElement> GetAsync(string path, IDictionary<string, object?> parameters, CancellationToken cancellationToken, params string[] required)
    {
        foreach (var name in required)
        {
            if (!parameters.TryGetValue(name, out var given) || given is null)
            {
                throw new ArgumentException($"Missing required parameter: {name}");
            }
        }
        var query = new List<string>();
        foreach (var (name, value) in parameters)
        {
            if (value is null)
            {
                continue;
            }
            var text = value switch
            {
                bool flag => flag ? "true" : "false",
                IFormattable number => number.ToString(null, CultureInfo.InvariantCulture),
                _ => value.ToString() ?? "",
            };
            var token = "{" + name + "}";
            if (path.Contains(token))
            {
                path = path.Replace(token, Uri.EscapeDataString(text));
            }
            else
            {
                query.Add(Uri.EscapeDataString(name) + "=" + Uri.EscapeDataString(text));
            }
        }
        var url = _baseUrl + path + (query.Count > 0 ? "?" + string.Join("&", query) : "");
        using var request = new HttpRequestMessage(HttpMethod.Get, url);
        request.Headers.Authorization = new AuthenticationHeaderValue("Bearer", _apiKey);
        request.Headers.Accept.ParseAdd("application/json");
        request.Headers.UserAgent.ParseAdd("yemeksepeti-unofficial-sdk/0.1.0 (csharp)");
        using var response = await _http.SendAsync(request, cancellationToken);
        var body = await response.Content.ReadAsStringAsync(cancellationToken);
        JsonElement? data = null;
        try
        {
            using var document = JsonDocument.Parse(body);
            data = document.RootElement.Clone();
        }
        catch (JsonException)
        {
        }
        if (response.IsSuccessStatusCode && data is JsonElement result)
        {
            return result;
        }
        string Field(string name, string fallback) =>
            data is { ValueKind: JsonValueKind.Object } detail && detail.TryGetProperty(name, out var field) && field.ValueKind == JsonValueKind.String
                ? field.GetString()!
                : fallback;
        throw new ApiError((int)response.StatusCode, Field("error", "http_error"), Field("message", body.Length > 200 ? body[..200] : body));
    }
}
