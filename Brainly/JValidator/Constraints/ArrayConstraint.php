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
 * Constraint for validating arrays
 */
class ArrayConstraint implements Constraint
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
        if (!is_array($element)) {
            $errors[$myName][] = 'is not an array';
            return $errors;
        }
                
        $itemsSchema = $schema->items;
        
        if (isset($schema->minItems)) {
            $minItems = $schema->minItems;
        } else {
            $minItems = 0;
        }

        if (isset($schema->maxItems)) {
            $maxItems = $schema->maxItems;
        } else {
            $maxItems = PHP_INT_MAX;
        }
        
        if (count($element) < $minItems) {
            $errors[$myName][] = 'must have at last '.$minItems.' items';
            return $errors;
        }

        if (count($element) > $maxItems) {
            $errors[$myName][] = 'must have at most '.$maxItems.' items';
            return $errors;
        }

        if (isset($schema->uniqueItems) && $schema->uniqueItems) {
            $count1 = count($element);
            $count2 = count(array_unique($element, SORT_REGULAR));
            if ($count1 != $count2) {
                $errors[$myName][] = 'items must be unique';
            }
        }
        
        $i = 0;
        foreach ($element as $item) {
            $errors = $this->checkArrayItems($validator, $item, $itemsSchema, $myName.'.'.$i, $errors);
            $i++;
        }
        return $errors;
    }
    
    /**
     * Validate single element from array
     */
    private function checkArrayItems(Validator $validator, $item, $schema, $myName, $errors)
    {
        return $validator->check($item, $schema, $myName, $errors);
    }
}
