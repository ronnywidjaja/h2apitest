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

/**
 * Interface for schema resolver - mechanism that resolves paths
 * from schema "extends" field.
 */
interface IResolver
{
    /**
     * Resolve path from extend field to real path.
     * @param string $extend
     * @param string 
     */
    public static function resolveExtend($extend, $dirname);
}
