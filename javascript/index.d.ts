export declare const DEMO_KEY: string;

export interface ClientOptions {
  /** Your maviapi API key. Default: the MAVIAPI_API_KEY environment variable, then the shared demo key. */
  apiKey?: string;
  baseUrl?: string;
  /** Milliseconds. Default 120000. */
  timeout?: number;
  fetch?: typeof fetch;
}

/** An error status answered by the API. */
export declare class ApiError extends Error {
  status: number;
  code: string;
  constructor(status: number, code: string, message: string);
}

export interface ExploreParams {
  /**
   * City to deliver to (ignored if lat/lng are given). One of: istanbul, ankara, izmir,
   * bursa, antalya, adana, gaziantep, konya
   */
  "city"?: string;
  /**
   * Latitude (overrides city)
   */
  "lat"?: number;
  /**
   * Longitude (overrides city)
   */
  "lng"?: number;
  /**
   * Filter by cuisine: a numeric cuisine id or a name (e.g. 'Burger', 'Pizza'). See
   * available_cuisines in the response for valid values
   */
  "cuisine"?: string;
  /**
   * Sort order for the results. One of: , rating, delivery_time, distance,
   * minimum_order_amount
   */
  "sort"?: string;
  /**
   * Attach the full menu to the top results
   */
  "menu"?: boolean;
  /**
   * How many top restaurants to enrich with their menu (max 3). One of: 1, 2, 3
   */
  "menuCount"?: number;
  "limit"?: number;
  "offset"?: number;
  "refresh"?: boolean;
}

export interface RestaurantByCodeParams {
  /**
   * Restaurant vendor code (the `code` field from /restaurants, e.g. 'frtm'). Path
   * parameter
   */
  "code": string;
  /**
   * Max categories per page
   */
  "limit"?: number;
  "offset"?: number;
  /**
   * Bypass and repopulate the cache
   */
  "refresh"?: boolean;
}

export interface RestaurantsParams {
  /**
   * City to deliver to (ignored if lat/lng are given). One of: istanbul, ankara, izmir,
   * bursa, antalya, adana, gaziantep, konya
   */
  "city"?: string;
  /**
   * Latitude (overrides city)
   */
  "lat"?: number;
  /**
   * Longitude (overrides city)
   */
  "lng"?: number;
  /**
   * Filter by cuisine: a numeric cuisine id or a name (e.g. 'Burger', 'Pizza'). See
   * available_cuisines in the response for valid values
   */
  "cuisine"?: string;
  /**
   * Sort order for the results. One of: , rating, delivery_time, distance,
   * minimum_order_amount
   */
  "sort"?: string;
  /**
   * Page size (max 60)
   */
  "limit"?: number;
  /**
   * Items to skip
   */
  "offset"?: number;
  /**
   * Bypass and repopulate the cache
   */
  "refresh"?: boolean;
}

export declare class YemeksepetiClient {
  constructor(options?: ClientOptions);
  request(path: string, params?: Record<string, unknown>, required?: string[]): Promise<Record<string, any>>;
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
  explore(params?: ExploreParams): Promise<Record<string, any>>;
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
  restaurantByCode(params: RestaurantByCodeParams): Promise<Record<string, any>>;
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
  restaurants(params?: RestaurantsParams): Promise<Record<string, any>>;
}
