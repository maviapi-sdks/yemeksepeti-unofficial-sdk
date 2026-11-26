# Yemeksepeti Unofficial SDK

Unofficial **Yemeksepeti API** client for Python, JavaScript/TypeScript, Go, PHP, Ruby, Java and C#. Discover restaurants, browse menus and prices on Yemeksepeti — Turkey's Delivery Hero food-delivery platform.

- **3 endpoints**, each returning JSON, with one method per endpoint in every language.
- **Works with no setup.** With no key, a client uses a shared demo key (30 requests a minute and 300 a day per network).
- **Your own key lifts the limit.** [Create a free key](https://maviapi.com/sign-up?utm_source=github&utm_medium=readme&utm_campaign=sdk-yemeksepeti): a 7-day trial with 5,000 credits, no card.
- **Kept working.** The API is hosted by [maviapi](https://maviapi.com/apis/yemeksepeti?utm_source=github&utm_medium=readme&utm_campaign=sdk-yemeksepeti), which monitors it and repairs it when the website changes.

> This project is not affiliated with, endorsed by or sponsored by Yemeksepeti (yemeksepeti.com). All trademarks belong to their owners.

## Quick start

### Python

```bash
pip install "git+https://github.com/maviapi-sdks/yemeksepeti-unofficial-sdk#subdirectory=python"
```

```python
from yemeksepeti_sdk import YemeksepetiClient

client = YemeksepetiClient()  # or YemeksepetiClient("mvk_live_...")
result = client.restaurants(city="istanbul", cuisine="Burger")
print(result)
```

### JavaScript / TypeScript

```bash
npm install github:maviapi-sdks/yemeksepeti-unofficial-sdk
```

```javascript
import { YemeksepetiClient } from "yemeksepeti-unofficial-sdk";

const client = new YemeksepetiClient(); // or new YemeksepetiClient({ apiKey: "mvk_live_..." })
const result = await client.restaurants({ city: "istanbul", cuisine: "Burger" });
console.log(result);
```

### Go

```bash
go get github.com/maviapi-sdks/yemeksepeti-unofficial-sdk
```

```go
package main

import (
	"context"
	"fmt"
	"log"

	yemeksepeti "github.com/maviapi-sdks/yemeksepeti-unofficial-sdk"
)

func main() {
	client := yemeksepeti.New("") // or yemeksepeti.New("mvk_live_...")
	result, err := client.Restaurants(context.Background(), yemeksepeti.Params{"city": "istanbul", "cuisine": "Burger"})
	if err != nil {
		log.Fatal(err)
	}
	fmt.Println(string(result))
}
```

### PHP

```bash
composer config repositories.yemeksepeti-unofficial-sdk vcs https://github.com/maviapi-sdks/yemeksepeti-unofficial-sdk
composer require maviapi-sdks/yemeksepeti-unofficial-sdk:dev-main
```

```php
<?php

require 'vendor/autoload.php';

use YemeksepetiSdk\YemeksepetiClient;

$client = new YemeksepetiClient(); // or new YemeksepetiClient('mvk_live_...')
$result = $client->restaurants(city: 'istanbul', cuisine: 'Burger');
print_r($result);
```

### Ruby

```ruby
# Gemfile
gem "yemeksepeti_sdk", git: "https://github.com/maviapi-sdks/yemeksepeti-unofficial-sdk", branch: "main"
```

```ruby
require "yemeksepeti_sdk"

client = YemeksepetiSdk::Client.new # or YemeksepetiSdk::Client.new(api_key: "mvk_live_...")
result = client.restaurants(city: "istanbul", cuisine: "Burger")
puts result
```

### Java

```xml
<!-- pom.xml: the JitPack repository, then the dependency -->
<repository>
  <id>jitpack.io</id>
  <url>https://jitpack.io</url>
</repository>

<dependency>
  <groupId>com.github.maviapi-sdks</groupId>
  <artifactId>yemeksepeti-unofficial-sdk</artifactId>
  <version>main-SNAPSHOT</version>
</dependency>
```

The client is one file with no dependencies, so you can also copy [`YemeksepetiClient.java`](java/src/main/java/com/maviapi/yemeksepeti/YemeksepetiClient.java) into your project.

```java
import com.maviapi.yemeksepeti.YemeksepetiClient;
import java.util.Map;

public class Example {
    public static void main(String[] args) {
        YemeksepetiClient client = new YemeksepetiClient(); // or new YemeksepetiClient("mvk_live_...")
        String json = client.restaurants(Map.of("city", "istanbul", "cuisine", "Burger"));
        System.out.println(json);
    }
}
```

### C#

```bash
git clone https://github.com/maviapi-sdks/yemeksepeti-unofficial-sdk
dotnet add reference yemeksepeti-unofficial-sdk/csharp/YemeksepetiSdk.csproj
```

The client is one file with no dependencies, so you can also copy [`YemeksepetiClient.cs`](csharp/YemeksepetiClient.cs) into your project.

```csharp
using YemeksepetiSdk;

var client = new YemeksepetiClient(); // or new YemeksepetiClient("mvk_live_...")
var result = await client.RestaurantsAsync(new Dictionary<string, object?> { ["city"] = "istanbul", ["cuisine"] = "Burger" });
Console.WriteLine(result);
```

## Endpoints

| Endpoint | What it returns | Credits per call |
|---|---|---|
| [`GET /explore`](#get-explore) | Discover restaurants for a location and optionally attach the top results' full menus in one call. | 1 |
| [`GET /restaurant/{code}`](#get-restaurantcode) | Full menu of one restaurant by vendor code: categories, products, descriptions, images and prices (TRY, incl. | 10 |
| [`GET /restaurants`](#get-restaurants) | List and filter restaurants delivering to a location (by city or lat/lng). | 1 |

Try each one in the browser on the [Yemeksepeti API page](https://maviapi.com/apis/yemeksepeti?utm_source=github&utm_medium=readme&utm_campaign=sdk-yemeksepeti).

## Authentication

A client takes the key you give it. With none, it reads the `MAVIAPI_API_KEY` environment variable, and then uses the shared demo key (30 requests a minute and 300 a day per network). To get your own, [sign up](https://maviapi.com/sign-up?utm_source=github&utm_medium=readme&utm_campaign=sdk-yemeksepeti) and create a key on the [keys page](https://maviapi.com/dashboard/keys). A successful request uses the credits its endpoint lists; a failed request is free. Plans are on the [pricing page](https://maviapi.com/pricing?utm_source=github&utm_medium=readme&utm_campaign=sdk-yemeksepeti).

## Errors

A request the API answers with an error status raises an `ApiError` (in Go, returns an `*Error`) with the HTTP status, a machine-readable code and a message. In Java it carries the status and the response body.

| Status | Code | Meaning |
|---|---|---|
| 400 | `bad_request` | A parameter is missing or cannot be used. |
| 401 | `missing_api_key` | No API key was sent, or the key is invalid or revoked (`missing_api_key`, `invalid_api_key`). |
| 402 | `credit_limit_reached` | The plan's credits for this period are used up. |
| 403 | `account_banned` | The account that owns the key is suspended. |
| 429 | `rate_limited` | Too many requests for this key (`rate_limited`), or too many at once for its plan (`concurrency_limited`). Wait the number of seconds in `Retry-After`. |
| 502 | `scrape_failed` | The source site could not be read this time. Retrying later usually works. |

## Endpoint reference

Base URL: `https://api.maviapi.com/v1/sites/yemeksepeti`. API version 2026-09-22. The same contract is in [`openapi.json`](openapi.json).

### `GET /explore`

Discover restaurants for a location and optionally attach the top results' full menus in one call.

Methods: `explore` (Python, JavaScript/TypeScript, PHP, Ruby, Java) · `Explore` (Go) · `ExploreAsync` (C#)

Parameters:

- `city` (string, optional): City to deliver to (ignored if lat/lng are given). One of: `istanbul`, `ankara`, `izmir`, `bursa`, `antalya`, `adana`, `gaziantep`, `konya`. Example: `istanbul`.
- `lat` (number, optional): Latitude (overrides city). Example: `41.0082`.
- `lng` (number, optional): Longitude (overrides city). Example: `28.9784`.
- `cuisine` (string, optional): Filter by cuisine: a numeric cuisine id or a name (e.g. 'Burger', 'Pizza'). See available_cuisines in the response for valid values. Example: `Burger`.
- `sort` (string, optional): Sort order for the results. One of: ``, `rating`, `delivery_time`, `distance`, `minimum_order_amount`. Example: `rating`.
- `menu` (boolean, optional): Attach the full menu to the top results. Example: `true`.
- `menuCount` (integer, optional): How many top restaurants to enrich with their menu (max 3). One of: `1`, `2`, `3`. Example: `2`.
- `limit` (integer, optional): Example: `30`.
- `offset` (integer, optional): Example: `0`.
- `refresh` (boolean, optional): Example: `false`.

<details>
<summary>Example response</summary>

```json
{
  "data": {
    "restaurants": [
      {
        "code": "frtm",
        "id": 272240,
        "name": "Helvacı Ali",
        "rating": 3.9,
        "review_count": 205,
        "cuisines": [
          "Tatlı"
        ],
        "primary_cuisine": "Tatlı",
        "budget": 1,
        "address": "Hasfırın Cd. 13/B",
        "city": "Istanbul",
        "latitude": 41.043,
        "longitude": 29.006,
        "distance_km": 3.862,
        "minimum_order_amount": 0,
        "delivery_fee": 0,
        "delivery_time_minutes": 30,
        "currency": "TRY",
        "is_delivery_available": true,
        "is_pickup_available": false,
        "is_open": true,
        "has_online_payment": false,
        "has_discount": true,
        "logo": null,
        "hero_image": "https://images.deliveryhero.io/image/fd-tr/LH/frtm-listing.jpg",
        "url": "https://www.yemeksepeti.com/restaurant/frtm/helvaci-ali-frtm",
        "menu": {
          "code": "frtm",
          "category_count": 7,
          "product_count": 56,
          "categories": [
            {
              "id": "6033167",
              "name": "Ambalajlı Ürünler",
              "description": null,
              "product_count": 4,
              "products": [
                {
                  "id": "73934913",
                  "name": "Kestane Şekeri (500 gr.)",
                  "description": "500 gr. olarak servis edilir.",
                  "image": "https://images.deliveryhero.io/image/fd-tr/Products/73934913.jpg",
                  "price": 740,
                  "discounted_price": 592,
                  "is_sold_out": false
                }
              ]
            }
          ]
        }
      }
    ],
    "total": 2285,
    "count": 30,
    "location": {
      "latitude": 41.0082,
      "longitude": 28.9784,
      "city": "istanbul"
    },
    "cuisine_filter": {
      "input": "Pizza",
      "resolved_id": "1064"
    },
    "sort": null,
    "menu_enriched": 2
  },
  "cached": false,
  "source": "yemeksepeti:combo",
  "pagination": {
    "total": 60,
    "limit": 30,
    "offset": 0,
    "count": 30,
    "has_more": true
  }
}
```

</details>

### `GET /restaurant/{code}`

Full menu of one restaurant by vendor code: categories, products, descriptions, images and prices (TRY, incl. discounts).

Each successful request uses 10 credits.

Methods: `restaurant_by_code` (Python, Ruby) · `restaurantByCode` (JavaScript/TypeScript, PHP, Java) · `RestaurantByCode` (Go) · `RestaurantByCodeAsync` (C#)

Parameters:

- `code` (string, required, in the path): Restaurant vendor code (the `code` field from /restaurants, e.g. 'frtm'). Path parameter. Example: `frtm`.
- `limit` (integer, optional): Max categories per page. Example: `200`.
- `offset` (integer, optional): Example: `0`.
- `refresh` (boolean, optional): Bypass and repopulate the cache. Example: `false`.

<details>
<summary>Example response</summary>

```json
{
  "data": {
    "code": "frtm",
    "name": "Helvacı Ali",
    "menu_name": "Ana Menü",
    "hours": {
      "start": "10:30:00",
      "end": "23:30:00"
    },
    "currency": "TRY",
    "category_count": 7,
    "product_count": 56,
    "categories": [
      {
        "id": "6033167",
        "name": "Ambalajlı Ürünler",
        "description": null,
        "product_count": 4,
        "products": [
          {
            "id": "73934913",
            "name": "Kestane Şekeri (500 gr.)",
            "description": "500 gr. olarak servis edilir.",
            "image": "https://images.deliveryhero.io/image/fd-tr/Products/73934913.jpg",
            "price": 740,
            "discounted_price": 592,
            "is_sold_out": false
          }
        ]
      }
    ]
  },
  "cached": false,
  "source": "https://www.yemeksepeti.com/restaurant/frtm/x",
  "pagination": {
    "total": 60,
    "limit": 30,
    "offset": 0,
    "count": 30,
    "has_more": true
  }
}
```

</details>

### `GET /restaurants`

List and filter restaurants delivering to a location (by city or lat/lng). Filter by cuisine (id or name) and sort; paginated.

Methods: `restaurants` (Python, JavaScript/TypeScript, PHP, Ruby, Java) · `Restaurants` (Go) · `RestaurantsAsync` (C#)

Parameters:

- `city` (string, optional): City to deliver to (ignored if lat/lng are given). One of: `istanbul`, `ankara`, `izmir`, `bursa`, `antalya`, `adana`, `gaziantep`, `konya`. Example: `istanbul`.
- `lat` (number, optional): Latitude (overrides city). Example: `41.0082`.
- `lng` (number, optional): Longitude (overrides city). Example: `28.9784`.
- `cuisine` (string, optional): Filter by cuisine: a numeric cuisine id or a name (e.g. 'Burger', 'Pizza'). See available_cuisines in the response for valid values. Example: `Burger`.
- `sort` (string, optional): Sort order for the results. One of: ``, `rating`, `delivery_time`, `distance`, `minimum_order_amount`. Example: `rating`.
- `limit` (integer, optional): Page size (max 60). Example: `30`.
- `offset` (integer, optional): Items to skip. Example: `0`.
- `refresh` (boolean, optional): Bypass and repopulate the cache. Example: `false`.

<details>
<summary>Example response</summary>

```json
{
  "data": {
    "restaurants": [
      {
        "code": "frtm",
        "id": 272240,
        "name": "Helvacı Ali",
        "rating": 3.9,
        "review_count": 205,
        "cuisines": [
          "Tatlı"
        ],
        "primary_cuisine": "Tatlı",
        "budget": 1,
        "address": "Hasfırın Cd. 13/B",
        "city": "Istanbul",
        "latitude": 41.043,
        "longitude": 29.006,
        "distance_km": 3.862,
        "minimum_order_amount": 0,
        "delivery_fee": 0,
        "delivery_time_minutes": 30,
        "currency": "TRY",
        "is_delivery_available": true,
        "is_pickup_available": false,
        "is_open": true,
        "has_online_payment": false,
        "has_discount": true,
        "logo": null,
        "hero_image": "https://images.deliveryhero.io/image/fd-tr/LH/frtm-listing.jpg",
        "url": "https://www.yemeksepeti.com/restaurant/frtm/helvaci-ali-frtm"
      }
    ],
    "total": 2285,
    "count": 30,
    "location": {
      "latitude": 41.0082,
      "longitude": 28.9784,
      "city": "istanbul"
    },
    "cuisine_filter": {
      "input": "Burger",
      "resolved_id": "1058"
    },
    "sort": "rating",
    "available_cuisines": [
      null
    ]
  },
  "cached": false,
  "source": "yemeksepeti:disco",
  "pagination": {
    "total": 60,
    "limit": 30,
    "offset": 0,
    "count": 30,
    "has_more": true
  }
}
```

</details>

## More

- [Yemeksepeti API page](https://maviapi.com/apis/yemeksepeti?utm_source=github&utm_medium=readme&utm_campaign=sdk-yemeksepeti): live examples and the changelog.
- [OpenAPI document](https://api.maviapi.com/v1/apis/yemeksepeti/openapi.json) and [Postman collection](https://api.maviapi.com/v1/apis/yemeksepeti/postman.json).
- [maviapi MCP server](https://maviapi.com/docs#mcp): the same API as tools for Claude, Cursor and other AI agents.
- [All maviapi APIs](https://maviapi.com/apis?utm_source=github&utm_medium=readme&utm_campaign=sdk-yemeksepeti). Need a website that is not there? [Ask for it](https://maviapi.com/?utm_source=github&utm_medium=readme&utm_campaign=sdk-yemeksepeti) and get an API for it.

Other unofficial SDKs: [Airbnb](https://github.com/maviapi-sdks/airbnb-unofficial-sdk) · [Booking.com](https://github.com/maviapi-sdks/booking-unofficial-sdk) · [GetYourGuide](https://github.com/maviapi-sdks/getyourguide-unofficial-sdk) · [Google Search](https://github.com/maviapi-sdks/google-search-unofficial-sdk) · [Kiwi.com](https://github.com/maviapi-sdks/kiwi-unofficial-sdk) · [MarineTraffic](https://github.com/maviapi-sdks/marinetraffic-unofficial-sdk) · [Speedtest](https://github.com/maviapi-sdks/speedtest-unofficial-sdk) · [Trainline](https://github.com/maviapi-sdks/trainline-unofficial-sdk) · [Trendyol](https://github.com/maviapi-sdks/trendyol-unofficial-sdk) · [Tripadvisor](https://github.com/maviapi-sdks/tripadvisor-unofficial-sdk)

## License

MIT. See [LICENSE](LICENSE).
