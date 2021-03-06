<?php

namespace App;

use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;


class RouterFactory
{

	/**
	 * @return Nette\Application\IRouter
	 */
	public static function createRouter()
	{
		$router = new RouteList;
        $router[] = new Route('http://%host%/api/<action>', 'Api:default');
		$router[] = new Route('https://%host%/<presenter>/<action>[/<id>]', 'Homepage:default');
		return $router;
	}

}
