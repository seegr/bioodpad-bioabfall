<?php

declare(strict_types=1);

namespace App\Router;

use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;


final class RouterFactory
{
	use Nette\StaticClass;

	public static function createRouter(): RouteList
	{
		$router = new RouteList;

		////////////////////////////////////////////////////////////
		//--------------------- ADMIN ROUTES ---------------------//
		////////////////////////////////////////////////////////////
        $router->withModule('Admin')
            ->addRoute('[<locale=cs>/]admin/<presenter>/<action>[/<id>]', 'Dashboard:default');

		////////////////////////////////////////////////////////////
		//--------------------- FRONT ROUTES ---------------------//
		////////////////////////////////////////////////////////////
		$router->withModule('Front')
            ->addRoute('<slug>', [
                'presenter' => 'Category',
                'action' => 'articles',
                'locale' => 'cs'
            ])

		// SITEMAP
		->addRoute('sitemap.xml', 'Sitemap:default')
		->addRoute('sitemap', 'Sitemap:default')

        // MOST GENERAL ROUTE
		->addRoute('<locale=cs cs|en>/<presenter>/<action>[/<id>][/<slug>]', 'Homepage:default');

		return $router;
	}

}