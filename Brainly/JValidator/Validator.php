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

use Brainly\JValidator\Constraints\ObjectConstraint;
use Brainly\JValidator\Constraints\ArrayConstraint;
use Brainly\JValidator\Constraints\StringConstraint;
use Brainly\JValidator\Constraints\NumberConstraint;
use Brainly\JValidator\Constraints\BooleanConstraint;
use Brainly\JValidator\Constraints\IntegerConstraint;
use Brainly\JValidator\Constraints\NullConstraint;
use Brainly\JValidator\Constraints\UnionConstraint;

/**
 * Core validation class.
 * Uses constraints to perform recursive validation on each JSON element.
 */
class Validator
{
    // Possible validation results
    const VALID         = 0;
    const MODIFIED      = 1;
    const INVALID       = 2;
    const NOT_PERFORMED = 3;

    private $allowAdditionalFields = false;

    private $resultCode = Validator::NOT_PERFORMED;
    private $validationErrors = array();
    private $resultJson = "";

    public function setAllowAdditionalFields($allow)
    {
        $this->allowAdditionalFields = $allow;
    }

    public function allowAdditionalFields()
    {
        return $this->allowAdditionalFields;
    }

    /**
     * Validates JSON against given schema. Schema should be builded
     * and checked against syntax errors before.
     *
     * @param string $json JSON encoded string to validate
     * @param string $schema JSON encoded schema
     * @throws InvalidSchemaException
     */
    public function validate($json, $schema)
    {
        $this->validationErrors = array();
        $this->resultCode = Validator::NOT_PERFORMED;
        $this->resultJson = new \stdClass;

        $json = json_decode($json);
        $schema = json_decode($schema);

        if (is_null($schema)) {
            throw new InvalidSchemaException();
        }

        if (is_null($json)) {
            $this->addError('$', 'Is not a valid JSON');
            return;
        }
        $this->resultCode = Validator::VALID;

        $errors = $this->check($json, $schema, "$", array());
        $this->setErrors($errors);

        $this->resultJson = json_encode($json);
    }
    
    /**
     * Validates single property of JSON Schema.
     *
     * @param stdClass $json Property to validate
     * @param stdClass $schema Schema for property
     * @param string $name Name of currently validating property
     */
    public function check($json, $schema, $name, $errors)
    {
        if (is_array($schema->type)) {
            $unionConstraint = new UnionConstraint();
            return $unionConstraint->check($this, $json, $schema, $name, $errors);
        } else {
            switch ($schema->type) {
                case 'object':
                    $objectConstraint = new ObjectConstraint();
                    return $objectConstraint->check($this, $json, $schema, $name, $errors);
                    
                case 'array':
                    $arrayConstraint = new ArrayConstraint();
                    return $arrayConstraint->check($this, $json, $schema, $name, $errors);
                    
                case 'string':
                    $stringConstraint = new StringConstraint();
                    return $stringConstraint->check($this, $json, $schema, $name, $errors);
                    
                case 'number':
                    $numberConstraint = new NumberConstraint();
                    return $numberConstraint->check($this, $json, $schema, $name, $errors);
                    
                case 'boolean':
                    $booleanConstraint = new BooleanConstraint();
                    return $booleanConstraint->check($this, $json, $schema, $name, $errors);
                    
                case 'integer':
                    $integerConstraint = new IntegerConstraint();
                    return $integerConstraint->check($this, $json, $schema, $name, $errors);

                case 'null':
                    $nullConstraint = new NullConstraint();
                    return $nullConstraint->check($this, $json, $schema, $name, $errors);
                    
                default:
                    return $errors;
            }
        }
    }

    /**
     * Appends new error to errors array.
     *
     * @param string $property Property that error concerns
     * @param string $message Content of validation error
     */
    public function addError($property, $message)
    {
        $this->validationErrors[$property] = $message;
        $this->resultCode = Validator::INVALID;
    }

    public function setErrors($errors)
    {
        $this->validationErrors = $errors;
        if (count($errors)) {
            $this->resultCode = Validator::INVALID;
        }
    }

    /**
     * Returns result code of last performed validation
     * @return integer Validation code
     */
    public function getResultCode()
    {
        return $this->resultCode;
    }

    /**
     * Returns array of errors from last performed validation
     * @return array Validation errors
     */
    public function getValidationErrors()
    {
        return $this->validationErrors;
    }

    /**
     * Returns result JSON after validation performed on it
     * @return string JSON
     */
    public function getResultJson()
    {
        return $this->resultJson;
    }
}
