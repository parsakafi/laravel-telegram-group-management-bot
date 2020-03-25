<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/set_webhook',
    ['as' => 'set_webhook', 'uses' => 'BotController@setWebhook']
);
$router->get('/debug',
    ['as' => 'debug', 'uses' => 'BotController@debug']
);
$router->get('/remove_message',
    ['as' => 'remove_message', 'uses' => 'BotController@removeMessage']
);
$router->post('/',
    ['as' => 'start', 'uses' => 'BotController@start']
);
