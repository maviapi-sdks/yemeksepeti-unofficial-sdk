"""Unofficial Yemeksepeti API client for Python.

Discover restaurants, browse menus and prices on Yemeksepeti — Turkey's Delivery Hero food-delivery platform.

Not affiliated with Yemeksepeti. Endpoint reference: https://maviapi.com/apis/yemeksepeti
"""

from __future__ import annotations

import json
import os
import urllib.error
import urllib.parse
import urllib.request
from typing import Any, Dict, Optional

__version__ = "0.1.0"
__all__ = ["YemeksepetiClient", "ApiError", "DEMO_KEY"]

DEMO_KEY = "mvk_test_playground_5f3c1a8d"


class ApiError(Exception):
    """An error status answered by the API."""

    def __init__(self, status: int, code: str, message: str) -> None:
        super().__init__(f"{status} {code}: {message}")
        self.status = status
        self.code = code
        self.message = message


class YemeksepetiClient:
    """Yemeksepeti API client.

    With no api_key it uses the MAVIAPI_API_KEY environment variable, then the
    shared demo key.
    """

    def __init__(
        self,
        api_key: Optional[str] = None,
        *,
        base_url: Optional[str] = None,
        timeout: float = 120.0,
    ) -> None:
        self.api_key = api_key or os.environ.get("MAVIAPI_API_KEY") or DEMO_KEY
        root = base_url or os.environ.get("MAVIAPI_BASE_URL") or "https://api.maviapi.com"
        self.base_url = root.rstrip("/") + "/v1/sites/yemeksepeti"
        self.timeout = timeout

    def explore(self, *, city: Optional[str] = None, lat: Optional[float] = None, lng: Optional[float] = None, cuisine: Optional[str] = None, sort: Optional[str] = None, menu: Optional[bool] = None, menuCount: Optional[int] = None, limit: Optional[int] = None, offset: Optional[int] = None, refresh: Optional[bool] = None) -> Dict[str, Any]:
        """
        Discover restaurants for a location and optionally attach the top results' full
        menus in one call.

        Parameters:
          city (string): City to deliver to (ignored if lat/lng are given). One of:
            istanbul, ankara, izmir, bursa, antalya, adana, gaziantep, konya
          lat (number): Latitude (overrides city)
          lng (number): Longitude (overrides city)
          cuisine (string): Filter by cuisine: a numeric cuisine id or a name (e.g.
            'Burger', 'Pizza'). See available_cuisines in the response for valid values
          sort (string): Sort order for the results. One of: , rating, delivery_time,
            distance, minimum_order_amount
          menu (boolean): Attach the full menu to the top results
          menuCount (integer): How many top restaurants to enrich with their menu (max 3).
            One of: 1, 2, 3
          limit (integer)
          offset (integer)
          refresh (boolean)
        """
        return self._get("/explore", {"city": city, "lat": lat, "lng": lng, "cuisine": cuisine, "sort": sort, "menu": menu, "menuCount": menuCount, "limit": limit, "offset": offset, "refresh": refresh})

    def restaurant_by_code(self, *, code: str, limit: Optional[int] = None, offset: Optional[int] = None, refresh: Optional[bool] = None) -> Dict[str, Any]:
        """
        Full menu of one restaurant by vendor code: categories, products, descriptions,
        images and prices (TRY, incl. discounts).

        Each successful request uses 10 credits.

        Parameters:
          code (string, required): Restaurant vendor code (the `code` field from
            /restaurants, e.g. 'frtm'). Path parameter
          limit (integer): Max categories per page
          offset (integer)
          refresh (boolean): Bypass and repopulate the cache
        """
        return self._get("/restaurant/{code}", {"code": code, "limit": limit, "offset": offset, "refresh": refresh})

    def restaurants(self, *, city: Optional[str] = None, lat: Optional[float] = None, lng: Optional[float] = None, cuisine: Optional[str] = None, sort: Optional[str] = None, limit: Optional[int] = None, offset: Optional[int] = None, refresh: Optional[bool] = None) -> Dict[str, Any]:
        """
        List and filter restaurants delivering to a location (by city or lat/lng). Filter by
        cuisine (id or name) and sort; paginated.

        Parameters:
          city (string): City to deliver to (ignored if lat/lng are given). One of:
            istanbul, ankara, izmir, bursa, antalya, adana, gaziantep, konya
          lat (number): Latitude (overrides city)
          lng (number): Longitude (overrides city)
          cuisine (string): Filter by cuisine: a numeric cuisine id or a name (e.g.
            'Burger', 'Pizza'). See available_cuisines in the response for valid values
          sort (string): Sort order for the results. One of: , rating, delivery_time,
            distance, minimum_order_amount
          limit (integer): Page size (max 60)
          offset (integer): Items to skip
          refresh (boolean): Bypass and repopulate the cache
        """
        return self._get("/restaurants", {"city": city, "lat": lat, "lng": lng, "cuisine": cuisine, "sort": sort, "limit": limit, "offset": offset, "refresh": refresh})

    def _get(self, path: str, params: Dict[str, Any]) -> Dict[str, Any]:
        query = {}
        for name, value in params.items():
            if value is None:
                continue
            text = str(value).lower() if isinstance(value, bool) else str(value)
            token = "{" + name + "}"
            if token in path:
                path = path.replace(token, urllib.parse.quote(text, safe=""))
            else:
                query[name] = text
        url = self.base_url + path + ("?" + urllib.parse.urlencode(query) if query else "")
        request = urllib.request.Request(
            url,
            headers={
                "Authorization": "Bearer " + self.api_key,
                "Accept": "application/json",
                "User-Agent": "yemeksepeti-unofficial-sdk/0.1.0 (python)",
            },
        )
        try:
            with urllib.request.urlopen(request, timeout=self.timeout) as response:
                return json.loads(response.read())
        except urllib.error.HTTPError as error:
            body = error.read().decode("utf-8", "replace")
            try:
                detail = json.loads(body)
            except ValueError:
                detail = None
            if not isinstance(detail, dict):
                detail = {}
            raise ApiError(
                error.code,
                str(detail.get("error", "http_error")),
                str(detail.get("message", body[:200])),
            ) from None
