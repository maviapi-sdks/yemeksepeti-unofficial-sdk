// Package yemeksepeti is an unofficial Yemeksepeti API client.
//
// Discover restaurants, browse menus and prices on Yemeksepeti — Turkey's Delivery
// Hero food-delivery platform.
//
// Not affiliated with Yemeksepeti. Endpoint reference: https://maviapi.com/apis/yemeksepeti
package yemeksepeti

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"os"
	"strings"
	"time"
)

const (
	Version = "0.1.0"
	// DemoKey is the shared demo key a client uses when it is given no key.
	DemoKey = "mvk_test_playground_5f3c1a8d"
)

// Params are an endpoint's parameters by name. Those named in the endpoint's
// path go there, the rest in the query string.
type Params map[string]any

// Error is an error status answered by the API.
type Error struct {
	Status  int
	Code    string
	Message string
}

func (e *Error) Error() string { return fmt.Sprintf("%d %s: %s", e.Status, e.Code, e.Message) }

// Client calls the Yemeksepeti API. Its fields may be changed after New.
type Client struct {
	APIKey     string
	BaseURL    string
	HTTPClient *http.Client
}

// New returns a client. With an empty apiKey it uses the MAVIAPI_API_KEY
// environment variable, then the shared demo key.
func New(apiKey string) *Client {
	if apiKey == "" {
		apiKey = os.Getenv("MAVIAPI_API_KEY")
	}
	if apiKey == "" {
		apiKey = DemoKey
	}
	root := os.Getenv("MAVIAPI_BASE_URL")
	if root == "" {
		root = "https://api.maviapi.com"
	}
	return &Client{
		APIKey:     apiKey,
		BaseURL:    strings.TrimRight(root, "/") + "/v1/sites/yemeksepeti",
		HTTPClient: &http.Client{Timeout: 120 * time.Second},
	}
}

// Explore: Discover restaurants for a location and optionally attach the top results' full
// menus in one call.
//
// Parameters:
//
//   - city (string): City to deliver to (ignored if lat/lng are given). One of:
//     istanbul, ankara, izmir, bursa, antalya, adana, gaziantep, konya
//   - lat (number): Latitude (overrides city)
//   - lng (number): Longitude (overrides city)
//   - cuisine (string): Filter by cuisine: a numeric cuisine id or a name (e.g.
//     'Burger', 'Pizza'). See available_cuisines in the response for valid values
//   - sort (string): Sort order for the results. One of: , rating, delivery_time,
//     distance, minimum_order_amount
//   - menu (boolean): Attach the full menu to the top results
//   - menuCount (integer): How many top restaurants to enrich with their menu (max 3).
//     One of: 1, 2, 3
//   - limit (integer)
//   - offset (integer)
//   - refresh (boolean)
func (c *Client) Explore(ctx context.Context, params Params) (json.RawMessage, error) {
	return c.get(ctx, "/explore", params)
}

// RestaurantByCode: Full menu of one restaurant by vendor code: categories, products, descriptions,
// images and prices (TRY, incl. discounts).
//
// Each successful request uses 10 credits.
//
// Parameters:
//
//   - code (string, required): Restaurant vendor code (the `code` field from
//     /restaurants, e.g. 'frtm'). Path parameter
//   - limit (integer): Max categories per page
//   - offset (integer)
//   - refresh (boolean): Bypass and repopulate the cache
func (c *Client) RestaurantByCode(ctx context.Context, params Params) (json.RawMessage, error) {
	return c.get(ctx, "/restaurant/{code}", params, "code")
}

// Restaurants: List and filter restaurants delivering to a location (by city or lat/lng). Filter by
// cuisine (id or name) and sort; paginated.
//
// Parameters:
//
//   - city (string): City to deliver to (ignored if lat/lng are given). One of:
//     istanbul, ankara, izmir, bursa, antalya, adana, gaziantep, konya
//   - lat (number): Latitude (overrides city)
//   - lng (number): Longitude (overrides city)
//   - cuisine (string): Filter by cuisine: a numeric cuisine id or a name (e.g.
//     'Burger', 'Pizza'). See available_cuisines in the response for valid values
//   - sort (string): Sort order for the results. One of: , rating, delivery_time,
//     distance, minimum_order_amount
//   - limit (integer): Page size (max 60)
//   - offset (integer): Items to skip
//   - refresh (boolean): Bypass and repopulate the cache
func (c *Client) Restaurants(ctx context.Context, params Params) (json.RawMessage, error) {
	return c.get(ctx, "/restaurants", params)
}

func (c *Client) get(ctx context.Context, path string, params Params, required ...string) (json.RawMessage, error) {
	for _, name := range required {
		if params[name] == nil {
			return nil, fmt.Errorf("missing required parameter: %s", name)
		}
	}
	query := url.Values{}
	for name, value := range params {
		if value == nil {
			continue
		}
		text := fmt.Sprint(value)
		if token := "{" + name + "}"; strings.Contains(path, token) {
			path = strings.ReplaceAll(path, token, url.PathEscape(text))
		} else {
			query.Set(name, text)
		}
	}
	target := c.BaseURL + path
	if len(query) > 0 {
		target += "?" + query.Encode()
	}
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, target, nil)
	if err != nil {
		return nil, err
	}
	req.Header.Set("Authorization", "Bearer "+c.APIKey)
	req.Header.Set("Accept", "application/json")
	req.Header.Set("User-Agent", "yemeksepeti-unofficial-sdk/0.1.0 (go)")
	res, err := c.HTTPClient.Do(req)
	if err != nil {
		return nil, err
	}
	defer res.Body.Close()
	body, err := io.ReadAll(res.Body)
	if err != nil {
		return nil, err
	}
	if res.StatusCode >= 400 {
		failure := &Error{Status: res.StatusCode, Code: "http_error", Message: string(body)}
		var detail struct{ Error, Message string }
		if json.Unmarshal(body, &detail) == nil && detail.Error != "" {
			failure.Code, failure.Message = detail.Error, detail.Message
		}
		return nil, failure
	}
	return body, nil
}
