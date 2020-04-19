<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Instantiate App
$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

define('JWP_APP_ID', 'dev');
define('JWP_API_KEY', 'meXxp1xABjiy5skBF9ecnwDBePPqMeIL80hBgHaiHT54yroKKyVZFffb459jLFyi');



function getJwpClient()
{
    $auth = new Jwp\Auth(JWP_APP_ID, JWP_API_KEY);
    return new Jwp\Client($auth);
}

// Add routes
$app->get('/', function (Request $request, Response $response) {
    return $response
        ->withHeader('Location', '/' . uniqid() . '/0')
        ->withStatus(302);
});

$app->get('/{usename}/{stealth}', function (Request $request, Response $response, $args) {
    $username = $args['usename'];
    $stealth = $args['stealth'] === '1';
    $jwp = getJwpClient();
    $connParams = json_encode($jwp->connect([
        'socket_id' => $username,
        'channels' =>  [
            'general' => [
                'presence_track' => !$stealth,
                'presence_diffs' => true,
                'webhook_join' => true,
                'webhook_leave' => true,
                'meta' => ['username' => $username]
            ]
        ]
    ]));

    $html = <<<HTML
        <!DOCTYPE html>
        <title>Example App</title>
        <meta charset="utf-8" />
        <link rel="stylesheet" href="/main.css" /> 
        <div class="container">
            <h2>Chat example</h2>
            <div id="messages-list"></div>
            <input type="text" id="msg-body" value="Hello !" />
            <button id="msg-send">Send</button>
        </div>
        <script>
            window.jwpParams = $connParams;
        </script>
        <script src="/jwp-js/jwp.umd.js"></script>
        <script src="/main.js"></script>
HTML;

    $response->getBody()->write($html);
    return $response;
});

$app->post('/chat', function (Request $request, Response $response, $args) {
    $contents = json_decode(file_get_contents('php://input'), true);
    $message = $contents['message'];
    $request =  $request->withParsedBody($contents);
    $jwp = getJwpClient();
    $jwp->push('general', 'chat_msg', ['message' => $message]);
    $response = $response->withHeader('Content-type', 'application/json');
    $response->getBody()->write(json_encode(['status' => 'ok']));
    return $response;
});

$app->run();
