<?php

namespace Illuminate\Support;

use Illuminate\Support\EnvProcessors\ArrayProcessor;
use Illuminate\Support\EnvProcessors\Base64Processor;
use Illuminate\Support\EnvProcessors\BooleanProcessor;
use Illuminate\Support\EnvProcessors\ChainProcessor;
use Illuminate\Support\EnvProcessors\EnvProcessorInterface;
use Illuminate\Support\EnvProcessors\FileProcessor;
use Illuminate\Support\EnvProcessors\FloatProcessor;
use Illuminate\Support\EnvProcessors\IntegerProcessor;
use Illuminate\Support\EnvProcessors\JsonProcessor;
use Illuminate\Support\EnvProcessors\StringProcessor;
use Illuminate\Support\EnvProcessors\TrimProcessor;

final class EnvProcessorFactory
{
    /**
     * Gets the array processor.
     *
     * @param  string  $delimiter
     * @return \Illuminate\Support\EnvProcessors\ArrayProcessor
     */
    public static function arrayProcessor(string $delimiter = ','): ArrayProcessor
    {
        return new ArrayProcessor($delimiter);
    }

    /**
     * Gets the string processor.
     *
     * @return \Illuminate\Support\EnvProcessors\StringProcessor
     */
    public static function stringProcessor(): StringProcessor
    {
        return new StringProcessor();
    }

    /**
     * Gets the integer processor.
     *
     * @return \Illuminate\Support\EnvProcessors\IntegerProcessor
     */
    public static function integerProcessor(): IntegerProcessor
    {
        return new IntegerProcessor();
    }

    /**
     * Gets the float processor.
     *
     * @return \Illuminate\Support\EnvProcessors\FloatProcessor
     */
    public static function floatProcessor(): FloatProcessor
    {
        return new FloatProcessor();
    }

    /**
     * Gets the boolean processor.
     *
     * @param  bool  $not
     * @return \Illuminate\Support\EnvProcessors\BooleanProcessor
     */
    public static function booleanProcessor(bool $not = false): BooleanProcessor
    {
        return new BooleanProcessor($not);
    }

    /**
     * Gets the trim processor.
     *
     * @param  string|null  $charactersToTrim
     * @return \Illuminate\Support\EnvProcessors\TrimProcessor
     */
    public static function trimProcessor(?string $charactersToTrim = null): TrimProcessor
    {
        return new TrimProcessor($charactersToTrim);
    }

    /**
     * Gets the file processor.
     *
     * @return \Illuminate\Support\EnvProcessors\FileProcessor
     */
    public static function fileProcessor(): FileProcessor
    {
        return new FileProcessor();
    }

    /**
     * Gets the JSON processor.
     *
     * @return \Illuminate\Support\EnvProcessors\JsonProcessor
     */
    public static function jsonProcessor(): JsonProcessor
    {
        return new JsonProcessor();
    }

    /**
     * Gets the base64 processor.
     *
     * @return \Illuminate\Support\EnvProcessors\Base64Processor
     */
    public static function base64Processor(): Base64Processor
    {
        return new Base64Processor();
    }

    /**
     * Gets the chain processor.
     *
     * @param  \Illuminate\Support\EnvProcessors\EnvProcessorInterface  ...$processors
     * @return \Illuminate\Support\EnvProcessors\ChainProcessor
     */
    public static function chainProcessor(EnvProcessorInterface ...$processors): ChainProcessor
    {
        return new ChainProcessor(...$processors);
    }
}
