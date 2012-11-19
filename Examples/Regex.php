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
$regexSearch = '/^myvar_(.*)$/';

// create dummy keys/values
createKeys();

// search for keys based on regex
$keys = APC::rSearch($regexSearch);

// return number of matches
echo count((array) $keys) . ' keys were matched.' . " <br>\n <br>\n";
/*echo "<b>Two matching keys:</b> <br>\n";
// return first two keys/values
$a = 0;
foreach ($keys as $key => $val) {
    echo $key . ' = ' . $val . " <br>\n";
    if ($a >= 1) {
        echo "\n";
        break;
    }
    $a++;
}
echo "<br>\n";*/

// delete keys based on regex
$keys = APC::rDelete($regexSearch);
// return number of keys deleted
echo $keys . ' keys were matched and deleted.';

/**
 * Create 100 random 'user' keys
 *
 * @return boolean
 *   Return true on success
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