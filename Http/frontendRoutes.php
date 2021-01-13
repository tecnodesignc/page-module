<?php

use Illuminate\Routing\Router;

/** @var Router $router */

$router->group(['prefix' =>'/inline'], function (Router $router) {

    $router->post('/save', [
        'as' => 'inline.save',
        'uses' => 'PublicController@inlinesave',
        'middleware' => 'can:page.pages.edit',
    ]);
});


$router->get('/', [
    'uses' => 'PublicController@homepage',
    'as' => 'homepage',
    'middleware' => config('encore.page.config.middleware'),
]);
$router->any('{uri}', [
    'uses' => 'PublicController@uri',
    'as' => 'page',
    'middleware' => config('encore.page.config.middleware'),
])->where('uri', '.*');

