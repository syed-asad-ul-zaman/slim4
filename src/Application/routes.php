<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

// Group different routes under the same path
return function (App $app) {
    // App routes
    $app->get('/',         'App\Application\Controllers\HomeController:index')->setName('root');

    // API routes
    $app->group('/api', function (Group $group) {
        $group->post('/login', 'App\Application\Controllers\LoginController:doLogin')->setName('apiLogin');
        $group->post('/convert', 'App\Application\Controllers\FileConversionController:convertFile')->setName('apiConvertFile');
    });
};