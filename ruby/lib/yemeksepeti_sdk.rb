# frozen_string_literal: true

# Unofficial Yemeksepeti API client for Ruby.
# Not affiliated with Yemeksepeti. Endpoint reference: https://maviapi.com/apis/yemeksepeti

require "json"
require "net/http"
require "uri"

module YemeksepetiSdk
  VERSION = "0.1.0"
  DEMO_KEY = "mvk_test_playground_5f3c1a8d"

  # An error status answered by the API.
  class ApiError < StandardError
    attr_reader :status, :code

    def initialize(status, code, message)
      super("#{status} #{code}: #{message}")
      @status = status
      @code = code
    end
  end

  class Client
    # With no api_key it uses the MAVIAPI_API_KEY environment variable, then the
    # shared demo key.
    def initialize(api_key: nil, base_url: nil, timeout: 120)
      given = ->(*values) { values.find { |value| value && !value.empty? } }
      @api_key = given.call(api_key, ENV["MAVIAPI_API_KEY"], DEMO_KEY)
      root = given.call(base_url, ENV["MAVIAPI_BASE_URL"], "https://api.maviapi.com")
      @base_url = root.sub(%r{/+\z}, "") + "/v1/sites/yemeksepeti"
      @timeout = timeout
    end

    # Discover restaurants for a location and optionally attach the top results' full
    # menus in one call.
    #
    # Parameters:
    #   city (string): City to deliver to (ignored if lat/lng are given). One of:
    #     istanbul, ankara, izmir, bursa, antalya, adana, gaziantep, konya
    #   lat (number): Latitude (overrides city)
    #   lng (number): Longitude (overrides city)
    #   cuisine (string): Filter by cuisine: a numeric cuisine id or a name (e.g.
    #     'Burger', 'Pizza'). See available_cuisines in the response for valid values
    #   sort (string): Sort order for the results. One of: , rating, delivery_time,
    #     distance, minimum_order_amount
    #   menu (boolean): Attach the full menu to the top results
    #   menuCount (integer): How many top restaurants to enrich with their menu (max 3).
    #     One of: 1, 2, 3
    #   limit (integer)
    #   offset (integer)
    #   refresh (boolean)
    def explore(**params)
      get("/explore", params, %w[])
    end

    # Full menu of one restaurant by vendor code: categories, products, descriptions,
    # images and prices (TRY, incl. discounts).
    #
    # Each successful request uses 10 credits.
    #
    # Parameters:
    #   code (string, required): Restaurant vendor code (the `code` field from
    #     /restaurants, e.g. 'frtm'). Path parameter
    #   limit (integer): Max categories per page
    #   offset (integer)
    #   refresh (boolean): Bypass and repopulate the cache
    def restaurant_code(**params)
      get("/restaurant/{code}", params, %w[code])
    end

    # List and filter restaurants delivering to a location (by city or lat/lng). Filter by
    # cuisine (id or name) and sort; paginated.
    #
    # Parameters:
    #   city (string): City to deliver to (ignored if lat/lng are given). One of:
    #     istanbul, ankara, izmir, bursa, antalya, adana, gaziantep, konya
    #   lat (number): Latitude (overrides city)
    #   lng (number): Longitude (overrides city)
    #   cuisine (string): Filter by cuisine: a numeric cuisine id or a name (e.g.
    #     'Burger', 'Pizza'). See available_cuisines in the response for valid values
    #   sort (string): Sort order for the results. One of: , rating, delivery_time,
    #     distance, minimum_order_amount
    #   limit (integer): Page size (max 60)
    #   offset (integer): Items to skip
    #   refresh (boolean): Bypass and repopulate the cache
    def restaurants(**params)
      get("/restaurants", params, %w[])
    end

    private

    def get(path, params, required)
      params = params.transform_keys(&:to_s)
      missing = required.find { |name| params[name].nil? }
      raise ArgumentError, "missing required parameter: #{missing}" if missing

      query = {}
      params.each do |name, value|
        next if value.nil?

        token = "{#{name}}"
        if path.include?(token)
          path = path.sub(token, URI.encode_www_form_component(value.to_s).gsub("+", "%20"))
        else
          query[name] = value.to_s
        end
      end
      uri = URI(@base_url + path)
      uri.query = URI.encode_www_form(query) unless query.empty?
      request = Net::HTTP::Get.new(uri)
      request["Authorization"] = "Bearer #{@api_key}"
      request["Accept"] = "application/json"
      request["User-Agent"] = "yemeksepeti-unofficial-sdk/0.1.0 (ruby)"
      response = Net::HTTP.start(uri.hostname, uri.port, use_ssl: uri.scheme == "https", read_timeout: @timeout) do |http|
        http.request(request)
      end
      data = begin
        JSON.parse(response.body.to_s)
      rescue JSON::ParserError
        nil
      end
      return data if response.is_a?(Net::HTTPSuccess)

      detail = data.is_a?(Hash) ? data : {}
      raise ApiError.new(response.code.to_i, detail["error"] || "http_error", detail["message"] || response.body.to_s[0, 200])
    end
  end
end
