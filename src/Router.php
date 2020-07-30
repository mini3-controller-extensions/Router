<?php


namespace Mini3ControllerExtensions\Router;


use crystlbrd\Values\ArrVal;

/**
 * Trait Router
 * Handles relocating the client
 * @package Mini3ControllerExtensions\Router
 */
trait Router
{
    /// SETTINGS

    /**
     * @var int Defines how many URL should be cached per session
     */
    protected $_SETTING_Router_maxCachedLocations = 5;


    /// METHODS

    /**
     * Translates an array to the GET string syntax
     * @param array $get
     * @param bool $encodeParameterValues
     * @param bool $encodeParameterKeys
     * @return string
     */
    protected function buildGetString(array $get, bool $encodeParameterValues = true,  bool $encodeParameterKeys = true): string
    {
        $str = '';
        $i = 0;
        foreach ($get as $k => $v) {
            $str .= ($i ? '&' : '?') . (!is_int($k) ? ($encodeParameterKeys ? urlencode($k) : $k) . '=' : '') . ($encodeParameterValues ? urlencode($v) : $v);
            $i++;
        }

        return $str;
    }

    /**
     * Caches the current URL
     */
    protected function cacheCurrentLocation(): void
    {
        $this->cacheLocation($_GET['url']);
    }

    /**
     * Saves an URL to the cache
     * @param string $url
     */
    protected function cacheLocation(string $url): void
    {
        // Don't cache the same location twice
        if ($url != $this->getLastCachedLocation()) {
            // Get the current cache
            $cache = $this->getCache();

            // Delete oldest entries, if cache is full
            if (count($cache) >= $this->_SETTING_Router_maxCachedLocations) {
                unset($cache[0]);
            }

            // Save URL to cache
            $cache[] = $url;

            // Save cache to session
            $this->setCache(array_values($cache));
        }
    }

    /**
     * Returns the current cache
     * @return array
     */
    protected function getCache(): array
    {
        return (isset($_SESSION['mini']['router']['cache']) ? $_SESSION['mini']['router']['cache'] : []);
    }

    /**
     * Returns the last cached URL
     * @param bool $skipCurrent If the last cached location is the current location, it will be skipped
     * @param int $offset Cache offset
     * @return string
     */
    protected function getLastCachedLocation(bool $skipCurrent = false, int $offset = 0): string
    {
        $cache = array_reverse($this->getCache());
        if (isset($cache[$offset])) {
            if ($skipCurrent && $cache[$offset] == $_GET['url']) {
                return $this->getLastCachedLocation($skipCurrent, $offset + 1);
            } else {
                return $cache[$offset];
            }
        } else {
            return '';
        }
    }

    /**
     * Relocates to the last cached location
     * @param array $get additional GET parameter
     * @param array $options additional options
     */
    protected function goBack(array $get = [], array $options = []): void
    {
        $this->relocateTo($this->getLastCachedLocation(true) ?: '', $get, $options);
    }

    /**
     * Relocates the client and stops the script
     * @param string $url
     * @param int $status
     */
    protected function relocate(string $url, int $status = 302): void
    {
        header('Location: ' . $url, true, $status);
        exit;
    }

    /**
     * Relocates the client to a specific page
     * @param string $url the URL to relocate
     * @param array $get additional GET parameters
     * @param array $options additional options
     */
    protected function relocateTo(string $url, array $get = [], array $options = []): void
    {
        // OPTIONS

        $opt = ArrVal::merge([
            'base' => URL,                      // Baselink
            'anchor' => null,                   // URL anchor (/some/url#anchor)
            'status' => 302,                    // HTTP Status for relocating (default: 302),
            'encode_parameter_keys' => true,    // Encode GET-Parameters in the redirect URL
            'encode_parameter_values' => true   // Encode GET-Parameters in the redirect URL
        ], $options);


        // LOGIC

        // base url
        $url = $opt['base'] . $url;

        // add GET parameters
        $url .= $this->buildGetString($get, $opt['encode_parameter_values'], $opt['encode_parameter_keys']);

        // add anchor if required
        if ($opt['anchor']) {
            $url .= '#' . $opt['anchor'];
        }

        // relocate
        $this->relocate($url, $opt['status']);
    }

    /**
     * Relocates to home/index
     * @param array $get
     * @param string|null $anchor
     */
    protected function relocateToHome(array $get = [], string $anchor = null): void
    {
        $this->relocateTo('', $get, ['anchor' => $anchor]);
    }

    /**
     * Sets the cache
     * @param array $cache
     */
    protected function setCache(array $cache): void
    {
        $_SESSION['mini']['router']['cache'] = $cache;
    }
}