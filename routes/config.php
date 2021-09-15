<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/** @var \Laravel\Lumen\Routing\Router $router */
$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/test/index', ['uses' => 'TestController@index']);
$router->post('/auth/login', ['uses' => 'AuthController@login']);
$router->post('/auth/logout', ['uses' => 'AuthController@logout']);

$router->get('/user/info', ['uses' => 'UserController@info']);
$router->post('/user/resetPwd', ['uses' => 'UserController@resetPwd']);

$router->post('/order/applyWithdraw', ['uses' => 'OrderController@applyWithdraw']);
$router->get('/order/incomeStatInfo', ['uses' => 'OrderController@incomeStatInfo']);
$router->get('/order/incomeLists', ['uses' => 'OrderController@incomeLists']);

$router->get('/common/appProducts', ['uses' => 'CommonController@appProducts']);
$router->get('/common/paymentChannels', ['uses' => 'CommonController@paymentChannels']);
