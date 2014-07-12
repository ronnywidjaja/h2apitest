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

use \Exception as Exception;
use Brainly\JValidator\Exceptions\SchemaBuilderException;
use Brainly\JValidator\SchemaSpec;
use Brainly\JValidator\SchemaProvider;

/**
 * Tool for building schema with extends
 */
class Builder
{
    private $dirname;

    /**
     * Builder interface. Builds schema extends and validates schema syntax.
     * @param string $schema JSON encoded schema
     * @param string $dirname Directory where given schema lives
     * @return string JSON encoded schema
     * @throws SchemaBuilderException
     */
    public function buildSchema(SchemaProvider $provider, $schema, $dirname)
    {
        $schema = json_decode($schema);
        $this->dirname = $dirname;

        $builded = $this->build($provider, $schema);

        return json_encode($builded);
    }

    /**
     * Validates property type
     * @param string | array $type Single type or array of types
     * @throws SchemaBuilderException
     */
    private function validateType($type)
    {
        if (is_array($type)) {
            foreach ($type as $t) {
                $this->validateType($t);
            }
        } else {
            if (!in_array($type, SchemaSpec::getAllowedTypes())) {
                $msg = sprintf("Property type '%s' is not allowed", $type);
                throw new SchemaBuilderException($msg, SchemaBuilderException::INVALID_TYPE);
            }
        }
    }

    private function validateProperties($type, $props)
    {
        if (is_array($type)) {
            $typesCount = count($type);
            foreach ($props as $name => $data) {
                $errors = 0;
                $lastError = null;

                // If schema has more than one type property must validate
                // for at last one type
                foreach ($type as $t) {
                    $allowedProps = SchemaSpec::getAllowedProperties($t);
                    if (!in_array($name, $allowedProps)) {
                        $lastError = sprintf(
                            "Property '%s' is not allowed for any of types '%s'",
                            $name,
                            implode(", ", $type)
                        );
                        $errors++;
                    }
                }

                if ($errors == $typesCount) {
                    throw new SchemaBuilderException($lastError, SchemaBuilderException::INVALID_PROPERTY);
                }
            }
        } else {
            foreach ($props as $name => $data) {
                $allowedProps = SchemaSpec::getAllowedProperties($type);
                if (!in_array($name, $allowedProps)) {
                    $msg = sprintf(
                        "Property '%s' is not allowed for type '%s'",
                        $name,
                        $type
                    );
                    throw new SchemaBuilderException($msg, SchemaBuilderException::INVALID_PROPERTY);
                }
            }
        }
    }

    /**
     * Validates schema syntax and looks around for extends. Invoked recursively.
     * @param stdClass $schema Schema decoded to stdClass
     * @return stdClass builded schema
     * @throws SchemaBuilderException
     */
    private function build(SchemaProvider $provider, $schema)
    {
        // If encounter 'extends' property fetch given schema properties
        if (isset($schema->extends)) {
            $schema = $this->extend($provider, $schema, $schema->extends);
            unset($schema->extends);
        }

        // Set default type if not set
        if (!isset($schema->type)) {
            $schema->type = SchemaSpec::getDefault("type");
        }

        // Validate property type
        $this->validateType($schema->type);

        // Validate property keys for this property type
        $this->validateProperties($schema->type, get_object_vars($schema));
        
        // If this property is an object or array process children
        if (isset($schema->properties)) {
            $schema->properties = $this->processObjectProperties($provider, $schema);
        }
                
        if (isset($schema->items)) {
            $schema->items = $this->processArrayItems($provider, $schema->items);
        }

        return $schema;
    }
    
    /**
     * Fetch extending schema and merge it with current schema
     * @param stdClass $schema Schema that invokes extend
     * @param string $extend URL of extending schema
     * @return stdClass Schema merged with extending schema
     * @throws SchemaBuilderException
     */
    private function extend(SchemaProvider $provider, $schema, $extendSchema)
    {
        // If extending more than one schema
        if (is_array($extendSchema)) {
            foreach ($extendSchema as $extendSchemaItem) {
                $schema = $this->extend($provider, $schema, $extendSchemaItem);
            }
            return $schema;
        }

        // Resolve extend path
        $extendPath = $provider->resolveExtend($extendSchema, $this->dirname);

        $extend = $provider->getSchema($extendPath);
        $extend = json_decode($extend);

        //
        // Extend common fields
        //
        foreach (get_object_vars($extend) as $name => $data) {
            if ($name != "properties") {
                if (!isset($schema->$name)) {
                    $schema->$name = $data;
                }
            }
        }
                
        //
        // Extend object properties
        //
        if (isset($schema->properties) && isset($extend->properties)) {
            foreach (get_object_vars($extend->properties) as $name => $data) {
                if (isset($schema->properties->$name)) {
                    $schema->properties->$name = $this->extendProps(
                        $schema->properties->$name,
                        $data
                    );
                } else {
                    $schema->properties->$name = $data;
                }
            }
        } elseif (isset($extend->properties)) {
            $schema->properties = $extend->properties;
        }
        
        return $schema;
    }

    private function extendProps ($props, $extend)
    {
        foreach (get_object_vars($extend) as $name => $data) {
            if (!isset($props->$name)) {
                $props->$name = $data;
            }
        }
        return $props;
    }
    
    private function processObjectProperties(SchemaProvider $provider, $schema)
    {
        $properties = $schema->properties;
                
        foreach (get_object_vars($properties) as $name => $details) {
            $newProps = $this->build($provider, $details);
            $properties->$name = $newProps;
        }
        
        return $properties;
    }
    
    private function processArrayItems(SchemaProvider $provider, $schema)
    {
        if (is_array($schema)) {
            $newSchema = array();
            foreach ($schema as $item) {
                $newSchema[] = $this->build($provider, $item);
            }
            return $newSchema;
        } else {
            return $this->build($provider, $schema);
        }
    }
}
