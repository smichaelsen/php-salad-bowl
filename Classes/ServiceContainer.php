<?php
namespace Smichaelsen\SaladBowl;

class ServiceContainer
{

    /**
     * @var Bowl
     */
    protected $bowl;

    public function __construct(Bowl $bowl)
    {
        $this->bowl = $bowl;
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
        return new $className($this->bowl, ...$constructorArguments);
    }

}
