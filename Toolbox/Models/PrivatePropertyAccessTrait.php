<?php /** @noinspection PhpUnhandledExceptionInspection */


namespace MxcCommons\Toolbox\Models;

use ReflectionClass;
use ReflectionProperty;

/**
 * @ORM\MappedSuperclass
 */
trait PrivatePropertyAccessTrait
{
    public function getPrivatePropertyNames(): array
    {
        $r = new ReflectionClass($this);
        $properties = $r->getProperties(ReflectionProperty::IS_PRIVATE);
        $names = [];
        foreach ($properties as $property) {
            $name = $property->getName();
            $names[] = $name;
        }
        return $names;
    }
}