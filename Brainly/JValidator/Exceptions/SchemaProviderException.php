<?php

/*
 * This file is part of the JValidator library.
 *
 * (c) Łukasz Lalik <lukasz.lalik@brainly.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brainly\JValidator\Exceptions;

class SchemaProviderException extends \Exception
{
    const SCHEMA_NOT_FOUND = 1;
    const UNPARSABLE_JSON = 2;
    const BROKEN_CACHE = 3;
    const CACHE_WRITE_ERROR = 4;
}
