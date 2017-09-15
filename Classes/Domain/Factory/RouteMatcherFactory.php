<?php
namespace Smichaelsen\SaladBowl\Domain\Factory;

use Aura\Router\Matcher;
use Aura\Router\RouterContainer;
use Smichaelsen\SaladBowl\Bowl;
use Smichaelsen\SaladBowl\RoutesClassInterface;
use Smichaelsen\SaladBowl\Service\SignalSlotService;
use Smichaelsen\SaladBowl\ServiceContainer;

class RouteMatcherFactory
{

    const SIGNAL_CONFIGURE_MAP = self::class . '::SIGNAL_CONFIGURE_MAP';

    public function create(): Matcher
    {
        $routerContainer = ServiceContainer::getSingleton(RouterContainer::class);
        $map = $routerContainer->getMap();
        // First register routes from plugins
        $signalSlotService = ServiceContainer::getSingleton(SignalSlotService::class);
        $signalSlotService->dispatchSignal(self::SIGNAL_CONFIGURE_MAP, $map);
        // Then register routes from application
        $routesClassName = Bowl::getConfiguration('routesClass');
        if (!class_exists($routesClassName)) {
            throw new \Exception('Routes class could not be loaded', 1454173497);
        }
        $routesClass = new $routesClassName;
        if (!$routesClass instanceof RoutesClassInterface) {
            throw new \Exception('Routes class must implement RoutesClassInterface', 1454173584);
        }
        $routesClass->configure($map);
        return $routerContainer->getMatcher();
    }
}