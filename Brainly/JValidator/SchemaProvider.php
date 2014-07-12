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

use Brainly\JValidator\BasicResolver;
use Brainly\JValidator\Exceptions\InvalidSchemaException;
use Brainly\JValidator\Exceptions\SchemaBuilderException;
use Brainly\JValidator\Exceptions\SchemaProviderException;

/**
 * Provides complete schema files (raw or builded with extends)
 * Includes caching mechanism with file storage.
 */
class SchemaProvider
{
    private $useCache  = false;
    private $schemaDir = '/schemas';
    private $cacheDir  = '/cache';
    private $resolver = '\Brainly\JValidator\BasicResolver';

    public function __construct(
        $schemaDir,
        $useCache = false,
        $cacheDir = '/cache',
        $resolver = '\Brainly\JValidator\BasicResolver')
    {
        $this->schemaDir = $schemaDir;
        $this->useCache = $useCache;
        $this->cacheDir = $cacheDir;
        $this->resolver = $resolver;
    }

    public function setCustomResolver($resolver)
    {
        $this->resolver = $resolver;
    }

    public function setUseCache($useCache)
    {
        $this->useCache = $useCache;
    }

    public function setSchemaDir($schemaDir)
    {
        $this->schemaDir = $schemaDir;
    }

    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    public function resolveExtend($extend, $dirname)
    {
        $resolver = $this->resolver;
        return $resolver::resolveExtend($extend, $dirname);
    }

    /**
     * Returns completely builded JSON Schema
     * @param string $fName Schema file name
     * @param bool $forceNoCache Don't use cache
     * @return string JSON encoded schema
     * @throws SchemaProviderException
     * @throws SchemaBuilderException
     */
    public function getSchema($fName, $forceNoCache = false)
    {
        if ($this->useCache && !$forceNoCache) {
            $cached = $this->getFromCache($fName);

            if ($cached !== false) {
                return $cached;
            }
        }

        // Not from cache, so build schema
        $rawSchema = $this->getRawSchema($fName);
        $dirname = dirname($fName);

        try {
            $builder = new Builder();
            $builded = $builder->buildSchema($this, $rawSchema, $dirname);
        } catch (SchemaBuilderException $e) {
            switch ($e->getCode()) {
                case SchemaBuilderException::NO_EXTEND_FILE:
                case SchemaBuilderException::BROKEN_EXTEND:
                case SchemaBuilderException::INVALID_TYPE:
                case SchemaBuilderException::INVALID_PROPERTY:
                    throw new InvalidSchemaException($e->getMessage());
                    break;

                default:
                    throw new Exception($e->getMessage());
            }
        }

        if ($this->useCache && !$forceNoCache) {
            $this->putToCache($fName, $builded);
        }

        return $builded;
    }

    /**
     * Returns raw JSON Schema
     * @param string $fName Schema file name
     * @return string JSON encoded schema
     * @throws SchemaProviderException when schema file doesn't exists or can't be parsed
     */
    public function getRawSchema($fName)
    {
        $fName = $this->schemaDir . '/' . $fName;

        if (!file_exists($fName)) {
            $msg = sprintf("Schema file '%s' not found", $fName);
            $code = SchemaProviderException::SCHEMA_NOT_FOUND;
            throw new SchemaProviderException($msg, $code);
        }

        $schema = file_get_contents($fName);
        $decoded = json_decode($schema);

        if (is_null($decoded)) {
            $msg = sprintf("Unable to decode file '%s' as JSON", $fName);
            $code = SchemaProviderException::UNPARSABLE_JSON;
            throw new SchemaProviderException($msg, $code);
        }

        return $schema;
    }

    /**
     * Reads schema from cache
     * @param $fName Schema file name used to compute cache key
     * @return false | string JSON encoded schema or false if there is no schema in cache
     * @throws SchemaProviderException when can not decode cached JSON
     */
    private function getFromCache($fName)
    {
        $cacheFile = $this->cacheDir . '/' . md5($fName);

        if (!file_exists($cacheFile)) {
            return false;
        }

        $schema = file_get_contents($cacheFile);
        $decoded = json_decode($schema);

        if (is_null($decoded)) {
            $msg = sprintf(
                "Broken cache for schema '%s', cache file '%s'",
                $fName,
                $cacheFile
            );
            $code = SchemaProviderException::BROKEN_CACHE;
            throw new SchemaProviderException($msg, $code);
        }

        return $schema;
    }

    /**
     * Puts schema into cache
     * @param string $fName Schema file name used to compute destination cache key
     * @param string $schema JSON encoded schema
     * @throws SchemaProviderException when unable to write to cache
     */
    private function putToCache($fName, $schema)
    {
        $cacheFile = $this->cacheDir . '/' . md5($fName);

        $result = file_put_contents($cacheFile, $schema);

        if (is_null($result)) {
            $msg = sprintf(
                "Unable to write schema '%s' to cache '%s'",
                $fName,
                $cacheFile
            );
            $code = SchemaProviderException::BROKEN_CACHE;
            throw new SchemaProviderException($msg, $code);
        }
    }
}
