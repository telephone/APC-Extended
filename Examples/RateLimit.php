<?php
/**
 * APC Extended - Static class to extend APC functions
 *
 * @package     APC Extended
 * @author      Nick Adams <nick89@zoho.com>
 * @copyright   2012 Nick Adams.
 * @link        http://iamtelephone.com
 * @license     http://opensource.org/licenses/MIT MIT License
 * @version     1.0.0
 */
include '../APCExtended.php';
use Telephone\Cache\APC as APC;

// end script if APC is not enabled
if (!APC::enabled()) {
    exit('APC is not enabled, or APC version is below 3.1.1');
}

/**
 * User set variables
 */
$apiLimit = 100;        // API call limit per TTL
$apiTTL   = 3600;       // TTL of API Calls


// Call rate limit function. If sucessful, return string
if (rateLimit($apiLimit, $apiTTL)) {
    echo 'Check headers for rate limit';
}

/**
 * Check APC for rate limit
 *
 * @param  integer $limit  API call limit
 * @param  integer $ttl    API TTL
 * @return boolean         True if not rate limited
 */
function rateLimit($limit, $ttl)
{
    // set APC key as users public IP
    $key = $_SERVER['REMOTE_ADDR'];

    // check if IP exists
    if (APC::exists($key)) {
        // check whether IP is over API limit
        if (APC::fetch($key) >= $limit) {
            // set rate limit headers
            headers($limit, 0, APC::ttl($key, 'time'));
            exit('Rate limit exceeded');
        }
    } else {
        // create a new key
        APC::store($key, 0, $ttl);
    }

    // increase API calls by 1, and set headers
    headers($limit, ($limit - APC::inc($key)), APC::ttl($key, 'time'));
    return true;
}

/**
 * Set rate limit headers
 *
 * @param  string $limit   API limit
 * @param  string $remain  API calls remaining
 * @param  string $reset   Timestamp of expiry
 */
function headers($limit, $remain, $reset)
{
    header('X-RateLimit-Limit: '     . $limit);
    header('X-RateLimit-Remaining: ' . $remain);
    header('X-RateLimit-Reset: '     . $reset);
}