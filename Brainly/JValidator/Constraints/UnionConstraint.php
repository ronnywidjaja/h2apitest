<?php

/*
 * This file is part of the JValidator library.
 *
 * (c) Łukasz Lalik <lukasz.lalik@brainly.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brainly\JValidator\Constraints;

use Brainly\JValidator\Validator;
use Brainly\JValidator\Constraints\Constraint;

/**
 * Constraint for validating union of types
 */
class UnionConstraint implements Constraint
{
    /**
     * Performs validation of given element.
     *
     * @param Validator $validator  Instance of Validator to perform recursive validation
     * @param mixed     $element    Element to validate against schema
     * @param object    $schema     Part of Schema that validates given element
     * @param string    $myName     Name of validated element
     * @param array     $errors     Array of currently gathered errors
     * @return array    Currently gathered errors
     */
    public function check(Validator $validator, $element, $schema, $myName, array $errors)
    {
        $types = $schema->type;
        $allResults[$myName] = array();

        foreach ($types as $t) {
            $schema->type = $t;
            $result = $validator->check($element, $schema, $myName, array());
            if (!count($result)) {
                $schema->type = $types;
                return $errors;
            }
            $allResults[$myName][$t] = $result[$myName];
        }

        $schema->type = $types;
        return array_merge($errors, $allResults);
    }
}
