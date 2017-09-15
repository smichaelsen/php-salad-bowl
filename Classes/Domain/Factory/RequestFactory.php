<?php

namespace Smichaelsen\SaladBowl\Domain\Factory;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequestFactory;

class RequestFactory
{

    public static function create(string $uriPath = null, string $method = null): ServerRequestInterface
    {
        $server = $_SERVER;
        if ($uriPath !== null) {
            $server['REQUEST_URI'] = $uriPath;
        }
        if ($method !== null) {
            $server['REQUEST_METHOD'] = strtoupper($method);
        }
        return ServerRequestFactory::fromGlobals($server);
    }
}