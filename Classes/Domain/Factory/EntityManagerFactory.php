<?php

namespace Smichaelsen\SaladBowl\Domain\Factory;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Smichaelsen\SaladBowl\Bowl;
use Smichaelsen\SaladBowl\Service\SignalSlotService;

class EntityManagerFactory
{

    const SIGNAL_ENTITY_PATHS = self::class . '::SIGNAL_ENTITY_PATHS';

    /**
     * @var Bowl
     */
    protected $bowl;

    public function __construct(Bowl $bowl)
    {
        $this->bowl = $bowl;
    }

    public function create(): EntityManager
    {
        $entityManagerConfiguration = $this->bowl->getConfiguration()['entity'];
        $applicationEntityPath = $this->bowl->getRootPath() . '/' . ($entityManagerConfiguration['entityDirectory'] ?? 'src/Domain/Entity');
        $entityPaths = new \ArrayObject([$applicationEntityPath]);
        // add entity paths from plugins
        $signalSlotService = $this->bowl->getServiceContainer()->getSingleton(SignalSlotService::class);
        $signalSlotService->dispatchSignal(self::SIGNAL_ENTITY_PATHS, $entityPaths);
        return EntityManager::create(
            $this->bowl->getConfiguration()->get('database'),
            Setup::createAnnotationMetadataConfiguration($entityPaths->getArrayCopy(), true)
        );
    }

}