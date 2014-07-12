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

class SchemaBuilderException extends \Exception
{
    const UNKNOWN = 1;
    const NO_EXTEND_FILE = 2;
    const BROKEN_EXTEND = 3;
    const INVALID_TYPE = 4;
    const INVALID_PROPERTY = 5;
    const UNPARSABLE_JSON = 6;
}
