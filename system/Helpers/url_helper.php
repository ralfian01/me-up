<?php

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\URI;
use Config\App;
use Config\Services;

// CodeIgniter URL Helpers

if (! function_exists('site_url')) {
    /**
     * Returns a site URL as defined by the App config.
     *
     * @param array|string $relativePath URI string or array of URI segments
     * @param string|null  $scheme       URI scheme. E.g., http, ftp
     * @param App|null     $config       Alternate configuration to use
     */
    function site_url($relativePath = '', ?string $scheme = null, ?App $config = null): string
    {
        $currentURI = Services::request()->getUri();

        assert($currentURI instanceof SiteURI);

        return $currentURI->siteUrl($relativePath, $scheme, $config);
    }
}

if (! function_exists('base_url')) {
    /**
     * Returns the base URL as defined by the App config.
     * Base URLs are trimmed site URLs without the index page.
     *
     * @param array|string $relativePath URI string or array of URI segments
     * @param string|null  $scheme       URI scheme. E.g., http, ftp
     */
    function base_url($relativePath = '', ?string $scheme = null): string
    {
        $currentURI = Services::request()->getUri();

        assert($currentURI instanceof SiteURI);

        return $currentURI->baseUrl($relativePath, $scheme);
    }
}

if (! function_exists('current_url')) {
    /**
     * Returns the current full URL based on the Config\App settings and IncomingRequest.
     *
     * @param bool                 $returnObject True to return an object instead of a string
     * @param IncomingRequest|null $request      A request to use when retrieving the path
     *
     * @return string|URI When returning string, the query and fragment parts are removed.
     *                    When returning URI, the query and fragment parts are preserved.
     */
    function current_url(bool $returnObject = false, ?IncomingRequest $request = null)
    {
        $request ??= Services::request();
        /** @var CLIRequest|IncomingRequest $request */
        $uri = $request->getUri();

        return $returnObject ? $uri : URI::createURIString($uri->getScheme(), $uri->getAuthority(), $uri->getPath());
    }
}