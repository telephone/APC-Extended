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
$freeMemory = 20;       // minimum percentage of free memory
$fileHits   = 10;       // minimum file hits
$userHits   = 10;       // minimum 'user' hits


/**
 * Iterate through cache cleaning techniques
 * Only attempt to clean cache if free memory is below $freeMemory
 */
for ($i=0; $i < 4; $i++) {
    // check free memory
    if (rtrim(APC::freeMemory(true), '%') < $freeMemory) {
        if ($i === 0) {
            // preserve initial memory
            $memory = APC::freeMemory();
        }

        if ($i === 1) {
            // set file hits for purge
            cleanCache($i, $fileHits);
        } elseif ($i === 2) {
            // set user hits for purge
            cleanCache($i, $userHits);
        } else {
            // perform function without $hits
            cleanCache($i);
        }
    } else {
        // break loop if available memory is greater than $freeMemory
        break;
    }
}

/**
 * Return free memory
 */
if (isset($memory)) {
    echo 'Script cleaned: ', (APC::freeMemory() - $memory), ' MB ', "<br>\n";
}
echo 'Free memory: ', APC::freeMemory(), ' / ', APC::totalMemory(), ' = ',
    APC::freeMemory(true);

/**
 * Attempt to clean cache systematically
 *
 * @param  integer         $attempt  Attempt number
 * @return boolean|integer           True on success, or number of files/keys
 *                                   removed
 */
function cleanCache($attempt, $hits = 10) {
    switch ($attempt) {
        case '0':
            // remove keys that are expired
            return APC::expired();
        case '1':
            // remove files that have under $hits hits
            return APC::purge('files', $hits);
        case '2':
            // remove 'user' keys that have under $hits hits
            return APC::purge('user', $hits);
        case '3':
            // as a last resort, clear opcode (files) from cache
            return APC::flush('opcode');
        default:
            return APC::expired();
    }
}