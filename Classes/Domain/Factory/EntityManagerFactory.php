<?php

namespace Smichaelsen\SaladBowl\Domain\Factory;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Smichaelsen\SaladBowl\Bowl;
use Smichaelsen\SaladBowl\Service\SignalSlotService;
use Smichaelsen\SaladBowl\ServiceContainer;

class EntityManagerFactory
{

    const SIGNAL_ENTITY_PATHS = self::class . '::SIGNAL_ENTITY_PATHS';

    public function create(): EntityManager
    {
        $entityManagerConfiguration = Bowl::getConfiguration('entity');
        $applicationEntityPath = BOWL_ROOT_PATH . '/' . ($entityManagerConfiguration['entityDirectory'] ?? 'src/Domain/Entity');
        $entityPaths = new \ArrayObject([$applicationEntityPath]);
        // add entity paths from plugins
        $signalSlotService = ServiceContainer::getSingleton(SignalSlotService::class);
        $signalSlotService->dispatchSignal(self::SIGNAL_ENTITY_PATHS, $entityPaths);
        return EntityManager::create(
            Bowl::getConfiguration('database'),
            Setup::createAnnotationMetadataConfiguration($entityPaths->getArrayCopy(), true)
        );
    }

}