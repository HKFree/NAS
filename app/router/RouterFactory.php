<?php

namespace App;

use Nette\Application\Routers\RouteList;

final class RouterFactory
{
    public static function createRouter(): RouteList
    {
        $router = new RouteList;
        $router->addRoute('http://%host%/api/<action>', 'Api:default');
        $router->addRoute('https://%host%/<presenter>/<action>[/<id>]', 'Homepage:default');
        return $router;
    }
}
