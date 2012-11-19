<?php
/**
 * MIT License
 * ===========
 *
 * Copyright (c) 2012 Nick Adams nick89@zoho.com
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
 * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @package     APC Extended
 * @author      Nick Adams nick89@zoho.com
 * @copyright   2012 Nick Adams.
 * @license     http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link        http://github.com/telephone
 * @version     0.1
 */

// include cache class
include '../APCExtended.php';

// namespace
use Telephone\Cache\APC as APC;

// end script if APC is not enabled
if (!APC::enabled()) {
    exit('APC is not enabled, or APC version is below 3.1.1');
}

// user set variables
$apiLimit = 100;        // API call limit per TTL
$apiTTL   = 3600;       // TTL of API Calls

/**
 * Call rate limit function. If sucessful, return string
 */
if (rateLimit($apiLimit, $apiTTL)) {
    echo 'Check headers for rate limit';
}


/**
 * Check APC for rate limit
 *
 * @param  integer $limit
 *   API call limit
 * @param  integer $ttl
 *   API TTL
 * @return boolean
 *   Return true if not rate limited
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
    }
    // create a new key
    else {
        APC::store($key, 0, $ttl);
    }

    /**
     * Increase API calls by 1, and set headers
     */
    headers($limit, ($limit - APC::inc($key)), APC::ttl($key, 'time'));
    return true;
}

/**
 * Set rate limit headers
 *
 * @param  string $limit
 *   API limit
 * @param  string $remain
 *   API calls remaining
 * @param  string $reset
 *   Timestamp of expiry
 */
function headers($limit, $remain, $reset)
{
    header('X-RateLimit-Limit: '     . $limit);
    header('X-RateLimit-Remaining: ' . $remain);
    header('X-RateLimit-Reset: '     . $reset);
}