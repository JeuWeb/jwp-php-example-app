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
define('JWP_SECRET', '9rpajQOrCCdZrVY80uOtU');



function getJwpClient()
{
    $auth = new Jwp\Auth(JWP_APP_ID, JWP_API_KEY, JWP_SECRET);
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
    $socketID = $username;
    $stealth = $args['stealth'] === '1';
    $jwp = getJwpClient();
    $channelMeta = ['username' => $username];
    $channelOptions = [
        'presence_track' => !$stealth,
        'presence_diffs' => true,
        'notify_joins' => true,
        'notify_leaves' => true,
    ];

    $socketToken = $jwp->authenticateSocket($socketID, 1000);
    $socketParams = json_encode([
        'auth' => $socketToken,
        'app_id' => 'dev'
    ]);
    $channParams = json_encode($jwp->authenticateChannel($socketID, 'general', $channelMeta, $channelOptions));

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
            window.jwpSocketParams = $socketParams;
            window.jwpChannelParams = $channParams;
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
