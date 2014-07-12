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
 * Constraint for validating objects
 */
class ObjectConstraint implements Constraint
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
        if (!is_object($element)) {
            $errors[$myName][] = 'must be an object';
            return $errors;
        }
                
        $properties = $schema->properties;
        
        $possibleProps = get_object_vars($properties);
        foreach ($possibleProps as $name => $details) {
            $errors = $this->checkProperties(
                $validator,
                $element,
                $name,
                $details,
                $myName.'.'.$name,
                $errors
            );
        }

        if (!isset($schema->additionalProperties) &&
           !$validator->allowAdditionalFields()) {

            $schema->additionalProperties = false;
        }

        if (isset($schema->additionalProperties) && !$schema->additionalProperties) {
            foreach (get_object_vars($element) as $name => $details) {
                if (!array_key_exists($name, $possibleProps)) {
                    $errors[$myName.'.'.$name][] = 'this property is not listed in SCHEMA';
                }
            }
        }

        return $errors;
    }
    
    /**
     * Validate single property
     */
    private function checkProperties(Validator $validator, $object, $name, $details, $myName, $errors)
    {
        $hasProperty = property_exists($object, $name);
        $required = isset($details->required) && $details->required;
        
        if ($hasProperty) {
            $errors = $validator->check($object->$name, $details, $myName, $errors);
        } elseif (!$hasProperty && $required) {
            $errors[$myName][] = 'is not defined';
        }
        return $errors;
    }
}
