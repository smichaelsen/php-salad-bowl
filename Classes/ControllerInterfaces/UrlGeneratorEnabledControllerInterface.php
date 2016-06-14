<?php
namespace Smichaelsen\SaladBowl\ControllerInterfaces;

use Aura\Router\Generator;

interface UrlGeneratorEnabledControllerInterface
{

    public function setUrlGenerator(Generator $generator);

}
