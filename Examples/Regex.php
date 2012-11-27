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
 * User set PCRE pattern to match
 */
$regexSearch = '/^myvar_(.*)$/';


// create 100 dummy keys/values
createKeys();

// search for keys based on regex
$keys = APC::rSearch($regexSearch);

// return number of matches
echo count((array) $keys), " keys were matched. <br>\n <br>\n";

// delete keys based on regex
$keys = APC::rDelete($regexSearch);

// return number of keys deleted
echo $keys . ' keys were matched and deleted.';

/**
 * Create 100 random 'user' keys with no TTL
 *
 * @return boolean  True on success
 */
function createKeys()
{
    // create 100 non-expiring dummy keys
    $a = 0;
    for ($i=0; $i < 100; $i++) {
        do {
            // attempt to create key until successful
            $bool = APC::store(
                'myvar_' . substr(md5(uniqid(rand(), true)), mt_rand(0, 25), mt_rand(3, 7)),
                mt_rand(2, 10000)
            );
        } while (!$bool);
    }
}