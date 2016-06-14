<?php
namespace Smichaelsen\SaladBowl\ControllerTraits;

use Aura\Router\Generator;
use Smichaelsen\SaladBowl\View;

trait UrlGeneratorEnabledControllerTrait
{

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
    protected function registerTwigFunctions_urlGenerator(View $view) {
        $urlGenerator = $this->urlGenerator;
        $view->addFunction('path', function ($routeName, $arguments = []) use ($urlGenerator) {
            return $urlGenerator->generate($routeName, $arguments);
        });
    }

}