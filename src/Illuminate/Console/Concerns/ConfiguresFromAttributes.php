<?php

namespace Illuminate\Console\Concerns;

use Illuminate\Console\Attributes\Alias;
use Illuminate\Console\Attributes\Hidden;
use Illuminate\Console\Attributes\Isolated;
use ReflectionClass;

trait ConfiguresFromAttributes
{
    /**
     * Configure the command using PHP attributes.
     *
     * @return bool Whether attributes were found and used
     */
    protected function configureUsingAttributes(): bool
    {
        $reflection = new ReflectionClass($this);

        return $this->configureClassAttributes($reflection);
    }

    /**
     * Configure class-level attributes (Hidden, Isolated, Alias).
     *
     * @param  ReflectionClass  $reflection
     * @return bool
     */
    protected function configureClassAttributes(ReflectionClass $reflection): bool
    {
        $hasAttributes = false;

        // Handle Hidden attribute
        $hiddenAttrs = $reflection->getAttributes(Hidden::class);
        if (! empty($hiddenAttrs)) {
            $this->setHidden(true);
            $hasAttributes = true;
        }

        // Handle Isolated attribute
        $isolatedAttrs = $reflection->getAttributes(Isolated::class);
        if (! empty($isolatedAttrs)) {
            $isolated = $isolatedAttrs[0]->newInstance();
            $this->isolated = true;
            $this->isolatedExitCode = $isolated->exitCode;
            $hasAttributes = true;
        }

        // Handle Alias attributes (repeatable)
        $aliasAttrs = $reflection->getAttributes(Alias::class);
        if (! empty($aliasAttrs)) {
            $aliases = [];
            foreach ($aliasAttrs as $attr) {
                $alias = $attr->newInstance();
                $aliases[] = $alias->name;
            }
            $this->setAliases($aliases);
            $hasAttributes = true;
        }

        return $hasAttributes;
    }
}
