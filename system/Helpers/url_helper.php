<?php

use MVCME\Request\HTTPRequest;
use MVCME\URI\SiteURI;
use MVCME\URI\URI;
use MVCME\Config\App;
use MVCME\Service\Services;

// MVCME URL Helpers
if (!function_exists('site_url')) {

    /**
     * Returns a site URL as defined by the App config.
     * @param array|string $relativePath URI string or array of URI segments
     * @param string|null  $scheme URI scheme. E.g., http, ftp
     * @param App|null $config Alternate configuration to use
     * @return string
     */
    function site_url($relativePath = '', ?string $scheme = null, ?App $config = null)
    {
        $currentURI = Services::request()->getUri();

        assert($currentURI instanceof SiteURI);

        return $currentURI->siteUrl($relativePath, $scheme, $config);
    }
}

if (!function_exists('base_url')) {

    /**
     * Returns the base URL as defined by the App config.
     * Base URLs are trimmed site URLs without the index page.
     * @param array|string $relativePath URI string or array of URI segments
     * @param string|null $scheme URI scheme. E.g., http, ftp
     * @return string
     */
    function base_url($relativePath = '', ?string $scheme = null)
    {
        $currentURI = Services::request()->getUri();

        assert($currentURI instanceof SiteURI);

        return $currentURI->baseUrl($relativePath, $scheme);
    }
}

if (!function_exists('asset_url')) {

    /**
     * Returns the asset URL as defined by the App config.
     * Base URLs are trimmed site URLs without the index page.
     * @param array|string $relativePath URI string or array of URI segments
     * @param string|null $scheme URI scheme. E.g., http, ftp
     * @return string
     */
    function asset_url($relativePath = '', ?string $scheme = null)
    {
        $config = Services::appConfig();
        $baseUrlConfig = $config->baseURL;
        $assetUrlConfig = $config->assetURL;

        $currentURI = Services::request()->getUri();

        assert($currentURI instanceof SiteURI);

        return str_replace(
            $baseUrlConfig,
            $assetUrlConfig,
            $currentURI->baseUrl($relativePath, $scheme)
        );
    }
}

if (!function_exists('api_url')) {

    /**
     * Returns the API URL as defined by the App config.
     * Base URLs are trimmed site URLs without the index page.
     * @param array|string $relativePath URI string or array of URI segments
     * @param string|null $scheme URI scheme. E.g., http, ftp
     * @return string
     */
    function api_url($relativePath = '', ?string $scheme = null)
    {
        $config = Services::appConfig();
        $baseUrlConfig = $config->baseURL;
        $apiUrlConfig = $config->apiURL;

        $currentURI = Services::request()->getUri();

        assert($currentURI instanceof SiteURI);

        return str_replace(
            $baseUrlConfig,
            $apiUrlConfig,
            $currentURI->baseUrl($relativePath, $scheme)
        );
    }
}

if (!function_exists('current_url')) {

    /**
     * Returns the current full URL based on the Config\App settings and HTTPRequest.
     * @param bool $returnObject True to return an object instead of a string
     * @param HTTPRequest|null $request A request to use when retrieving the path
     * @return string|URI When returning string, the query and fragment parts are removed.
     *                    When returning URI, the query and fragment parts are preserved.
     */
    function current_url(bool $returnObject = false, ?HTTPRequest $request = null)
    {
        $request ??= Services::request();
        /** @var HTTPRequest $request */
        $uri = $request->getUri();

        return $returnObject ? $uri : URI::createURIString($uri->getScheme(), $uri->getAuthority(), $uri->getPath());
    }
}
