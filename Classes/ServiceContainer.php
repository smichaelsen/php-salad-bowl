<?php
namespace Smichaelsen\SaladBowl;

class ServiceContainer
{

    /**
     * @param string $className
     * @return object
     */
    public static function getSingleton($className)
    {
        static $instances = [];
        if (!isset($instances[$className])) {
            $instances[$className] = self::instantiate($className, array_slice(func_get_args(), 1));
        }
        return $instances[$className];
    }

    /**
     * @param string $className
     * @param array $constructorArguments
     * @return object
     */
    protected static function instantiate($className, array $constructorArguments)
    {
        return new $className(...$constructorArguments);
    }
}
