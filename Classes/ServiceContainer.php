<?php
namespace Smichaelsen\SaladBowl;

use Noodlehaus\Config;

class ServiceContainer
{

    /**
     * @var Config
     */
    protected $configuration;

    /**
     * @param Config $configuration
     */
    public function __construct(Config $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param string $className
     * @return object
     */
    public function getSingleton($className)
    {
        static $instances = [];
        if (!isset($instances[$className])) {
            $instances[$className] = $this->instantiate($className, array_slice(func_get_args(), 1));
        }
        return $instances[$className];
    }

    /**
     * @param string $className
     * @param array $constructorArguments
     * @return object
     */
    protected function instantiate($className, array $constructorArguments)
    {
        return new $className(...$constructorArguments);
    }

}
