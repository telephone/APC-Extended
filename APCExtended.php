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

namespace Telephone\Cache;

/**
 * Extend APC functions
 */
class APC
{
    /**
     * Check whether APC is enabled and greater
     * than version v3.1.1
     *
     * @return boolean
     *   False if APC is disabled
     */
    public static function enabled()
    {
        if (ini_get('apc.enabled') && (phpversion('apc') >= '3.1.1')) {
            return true;
        }
        return false;
    }

    /**
     * Check whether APC key exists
     *
     * @return string
     *   Return true on success
     */
    public static function exists($key)
    {
        if (apc_exists($key)) {
            return true;
        }
        return false;
    }

    /**
     * Return variable stored in APC
     *
     * @param  string $key
     *   Key name
     * @return boolean|object
     *   Data on success
     */
    public static function fetch($key)
    {
        if (self::exists($key)) {
            $apc = json_decode(apc_fetch($key));
            // check for array
            if ($apc->type == 'array') {
                $apc = json_decode(apc_fetch($key), true);
                return $apc['data'];
            }
            return $apc->data;
        }
        return false;
    }

    /**
     * Store variable in APC. If $overwrite is off, then
     * function will perform like apc_add() and only store variable
     * if key doesn't exist
     *
     * @param  string  $key
     *   Key name
     * @param  string|array|object   $value
     *   Variable to store
     * @param  integer $ttl
     *   Seconds until expiry
     * @param  boolean $overwrite
     *   If false and APC key exists, it will not overwrite
     * @return boolean|string
     *   True on success
     */
    public static function store($key, $variable, $ttl = 0, $overwrite = false)
    {
        if (self::exists($key) && !$overwrite) {
            return false;
        }

        $apc = array('data' => $variable);
        ($ttl !== 0) ? $apc['ttl'] = time() + $ttl : $apc['ttl'] = 0;
        $apc['type'] = gettype($variable);
        return apc_store($key, json_encode($apc), $ttl);
    }

    /**
     * Update APC key without changing ttl
     *
     * @param  string $key
     *   Key name
     * @param  array  $variable
     *   New variable to update
     * @return boolean|string
     *   Updated value or true on success
     */
    public static function update($key, $variable)
    {
        if (self::exists($key)) {
            $apc = json_decode(apc_fetch($key));
            // increase/decrease
            if (strpos($variable, ':') !== false) {
                $val = substr($variable, 4);
                if (strpos($variable, 'dec:') !== false) {
                    $apc->data -= $val;
                } else {
                    $apc->data += $val;
                }
                // return new value
                if (self::store($key, $apc->data, self::ttl(false, $apc->ttl), true)) {
                    return $apc->data;
                }
            } else {
                // regular update
                $apc->data = $variable;
                return self::store($key, $apc->data, self::ttl(false, $apc->ttl), true);
            }
        }
        return false;
    }

    /**
     * Delete APC key
     *
     * @param  string $key
     *   Key name
     * @return boolean
     *   True on success
     */
    public static function delete($key)
    {
        if (self::exists($key)) {
            return apc_delete($key);
        }
        return false;
    }

    /**
     * Increase APC key by defined integer (default = 1)
     * Return new value
     *
     * @param  string  $key
     *   Key name
     * @param  integer $increment
     *   Integer to increase variable by
     * @return boolean|string
     *   Return new variable or false
     */
    public static function inc($key, $increment = 1)
    {
        return self::update($key, 'inc:' . $increment);
    }

    /**
     * Decrease APC key by defined integer (default = 1)
     * Return new value
     *
     * @param  string  $key
     *   Key name
     * @param  integer $increment
     *   Integer to decrease variable by
     * @return boolean|string
     *   New variable on success
     */
    public static function dec($key, $increment = 1)
    {
        return self::update($key, 'dec:' . $increment);
    }

    /**
     * Return time left in seconds or Unix expiry time
     * Set $ttl to 'time' to return Unix time
     *
     * @param  string $key
     *   APC key
     * @param  string|integer
     *   Define ttl variable instead of fetching
     * @return boolean|string
     *   TTL in seconds. 0 = no ttl
     */
    public static function ttl($key = false, $ttl = null)
    {
        if ($key !== false) {
            if (self::exists($key)) {
                $apc = json_decode(apc_fetch($key));
                // return unix time
                if ($ttl == 'time') {
                    return $apc->ttl;
                }
                $ttl = $apc->ttl;
            } else {
                return false;
            }
        }
        // return seconds or 0 (integer)
        ((int) $ttl == 0) ? $ttl = (int) 0 : $ttl = ($ttl - time());
        return $ttl;
    }

    /**
     * Fetch APC key/s in 'user' scope based on a regular expression search
     *
     * @param  string  $regex
     *   The pattern to search for, as a regex string: '/^match$/'
     * @param  boolean $returnValue
     *   True to return the matching key/s value/s
     * @return boolean|integer
     *   Object of values for matching keys
     */
    public static function rSearch($regex, $returnValue = false)
    {
        /**
         * PHP memory can easily be eaten up if returning the value of hundreds
         * of keys
         */
        $search = new \stdClass();
        if ($returnValue) {
            $iterator = new \APCIterator('user', $regex, APC_ITER_KEY + APC_ITER_VALUE);
            foreach ($iterator as $key => $val) {
                $apc = json_decode($val['value']);
                $search->$key = $apc->data;
            }
        } else {
            $iterator = new \APCIterator('user', $regex, APC_ITER_KEY);
            foreach ($iterator as $key => $val) {
                $search->$key = $val;
            }
        }

        if ($returnValue && empty($search)) {
            return false;
        }
        return (object) $search;
    }

    /**
     * Delete APC key/s in 'user' scope based on a regular expression search
     *
     * @param  string $regex
     *   The pattern to search for, as a regex string: '/^match$/'
     * @return integer
     *   Number of keys removed
     */
    public static function rDelete($regex)
    {
        $iterator = new \APCIterator('user', $regex, APC_ITER_KEY);
        /**
         * - apc_delete wasn't returning true, so just return total count
         * - getTotalCount() failed when being returned; declared first now
         */
        $count = $iterator->getTotalCount();
        apc_delete($iterator);
        return $count;
    }

    /**
     * Iterate through APC 'user' cache and remove expired entries
     *
     * @return integer
     *   Number of keys removed
     */
    public static function expired()
    {
        $iterator = new \APCIterator('user', null, APC_ITER_CTIME + APC_ITER_TTL);
        $time = time();
        $a = 0;
        foreach ($iterator as $key => $val) {
            if (($val['ttl'] + $val['creation_time']) < $time) {
                if (apc_delete($key)) {
                    $a++;
                }
            }
        }
        return $a;
    }

    /**
     * Iterate through APC and remove unpopular files/keys
     *
     * @param  string $type
     *   Input 'user' or 'opcode' to define which cache to purge
     *   Default = 'user'
     * @param  integer $hits
     *   Number of hits. If the cache item has been served less than $hits,
     *   delete file from cache (remove unpopular items)
     *   Default = 10
     * @return integer
     *   Number of files/keys removed
     */
    public static function purge($type = user, $hits = 10)
    {
        $a = 0;
        // iterate through files
        if ($type == 'opcode') {
            $info = apc_cache_info('opcode');
            foreach ($info['cache_list'] as $i) {
                if ($i['num_hits'] <= $hits) {
                    /**
                     * apc_delete_file will not remove expired files. To
                     * achieve this, you'll have to flush the opcache.
                     *
                     * In apc.ini, set "apc.ttl" to '0' to avoid this problem
                     */
                    if (apc_delete_file($i['filename'])) {
                        $a++;
                    }
                }
            }
        }
        // iterate through user cache
        elseif ($type == 'user') {
            $iterator = new \APCIterator('user', null,  APC_ITER_CTIME + APC_ITER_TTL + APC_ITER_NUM_HITS);
            $time = time();
            foreach ($iterator as $key => $val) {
                if ($val['num_hits'] <= $hits) {
                    if (apc_delete($key)) {
                        $a++;
                    }
                }
            }
        }
        return $a;
    }

    /**
     * Clear APC cache
     *
     * @param  string
     *   Input 'user', 'opcode', or 'all' to define which cache to clear
     *   Default = 'user'
     * @return boolean
     *   True on success
     */
    public static function flush($type = 'user')
    {
        if ($type == 'user' || $type == 'opcode') {
            return apc_clear_cache($type);
        } elseif ($type == 'all') {
            if ((apc_clear_cache('user')) && (apc_clear_cache('opcode'))) {
                return true;
            }
            return false;
        }
    }

    /**
     * Find the percentage of memory free within APC
     *
     * @return float
     *   Percentage of memory free (to one decimal point)
     */
    public static function freeMemory()
    {
        $info = apc_sma_info();
        return round((($info['avail_mem'] / ($info['num_seg'] * $info['seg_size'])) * 100), 1);
    }
}