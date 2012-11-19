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
$freeMemory = 20;       // minimum percentage of free memory
$fileHits   = 20;       // minimum file hits
$userHits   = 10;       // minimum 'user' hits

/**
 * Iterate through cache cleaning techniques.
 * Only attempt to clean cache if free memory is below $freeMemory.
 */
for ($i=0; $i < 4; $i++) {
    // check free memory
    if (APC::freeMemory() < $freeMemory) {
        if ($i === 0) {
            // set memory only once
            $memory = APC::freeMemory();
        }

        if ($i === 1) {
            // set file hits for purge
            cleanCache($i, $fileHits);
        }
        elseif ($i === 2) {
            // set user hits for purge
            cleanCache($i, $userHits);
        }
        else {
            // perform function without $hits
            cleanCache($i);
        }
    }
    // break loop if available memory is above $freeMemory
    else {
        break;
    }
}

/**
 * Return free memory
 */
if (isset($memory)) {
    echo 'Script cleaned free memory from: ' . $memory . '% to: ' . APC::freeMemory() . '%';
} else {
    echo 'Free memory: ' . APC::freeMemory() . '%';
}


/**
 * Attempt to clean cache systematically
 *
 * @param  integer $attempt
 *   Attempt number
 * @return boolean|integer
 *   True on success, or number of files/keys removed
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