<?php

namespace Smichaelsen\SaladBowl\ControllerTraits;

use Aura\Router\Generator;
use Doctrine\ORM\EntityManager;
use Smichaelsen\SaladBowl\ForwardException;
use Smichaelsen\SaladBowl\View;

trait UrlGeneratorEnabledControllerTrait
{

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Generator
     */
    protected $urlGenerator;

    /**
     * @param Generator $generator
     */
    public function setUrlGenerator(Generator $generator)
    {
        $this->urlGenerator = $generator;
    }

    /**
     * @param View $view
     */
    protected function registerTwigFunctions_urlGenerator(View $view)
    {
        static $added = false;
        if ($added) {
            return;
        }
        $added = true;
        $urlGenerator = $this->urlGenerator;
        $view->addFunction('path', function ($routeName, $arguments = []) use ($urlGenerator) {
            return $urlGenerator->generate($routeName, $arguments);
        });
    }

    /**
     * @param string $routeName
     * @param array $arguments
     * @throws ForwardException
     */
    public function forwardToRoute($routeName, array $arguments = [])
    {
        $forwardException = new ForwardException();
        $forwardException->setPath($this->urlGenerator->generate($routeName, $arguments));
        throw $forwardException;
    }

    /**
     * @param $routeName
     * @param array $arguments
     */
    public function redirectToRoute($routeName, array $arguments = [])
    {
        header('Location: ' . '//' . $_SERVER['HTTP_HOST'] . '/' . trim($this->urlGenerator->generate($routeName, $arguments), '/'));
        $this->entityManager->flush();
        die();
    }

}