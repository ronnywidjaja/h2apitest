<?php

/*
 * This file is part of the JValidator library.
 *
 * (c) Łukasz Lalik <lukasz.lalik@brainly.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brainly\JValidator;

use Brainly\JValidator\IResolver;

class BasicResolver implements IResolver
{
    /**
     * Resolve path from extend field to real path.
     * @param string $extend
     * @param string 
     */
    public static function resolveExtend($extend, $dirname)
    {
        return $dirname . "/" . $extend;
    }
}
