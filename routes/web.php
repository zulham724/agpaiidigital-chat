<?php

/** @var \Laravel\Lumen\Routing\Router $router */

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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->post('/chat', ['middleware' => 'auth', function () use ($router) {
    $now = date("Y-m-d h:i:s");
    $conversation_id = app('request')->input('conversation_id');
    $value = app('request')->input('value');
    $sender_id = $router->app['decoded_array']['sub'];
    $id =  app('db')->connection('mysql')->table('chats')
    ->insertGetId(['sender_id'=>$sender_id, 'conversation_id'=>$conversation_id, 'value'=>$value,'created_at'=>$now,'updated_at'=>$now]); //->insert('insert into chats(`sender_id`,`conversation_id`, `value`, created_at, updated_at) values (?,?,?,?,?)', [$sender_id, $conversation_id,$value,$now,$now]);
    return $id;
}]);
$router->post('/getlatestchat', 'ChatController@getLatestChat');
$router->get('/getunreadcount', 'ChatController@getUnreadCount');
$router->post('/conversations', 'ChatController@getMyConversations');
$router->put('/readconversation/{id}','ChatController@readConversation');
// $router->get('/')
$router->get('/getunreadconversationbymessages','ChatController@getUnreadConversationCountByMessage');
$router->post('/removechats','ChatController@removeChats');
