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
        ->withHeader('Location', '/room/general/' . uniqid())
        ->withStatus(302);
});

$app->get('/room/{room}/{username}', function (Request $request, Response $response, $args) {
    $username = $args['username'];
    $room = $args['room'];

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
        <script src="/jwp-js/jwp.umd.js"></script>
        <script>
            window.jwpSocketParams = jwp.fetchParams('/auth/$username');
            window.jwpChannelParams = jwp.fetchParams('/auth/$username');
            window.jwpChannelName = '$room';
        </script>
        <script src="/main.js"></script>
HTML;

    $response->getBody()->write($html);
    return $response;
});

$app->post('/send/{room}', function (Request $request, Response $response, $args) {
    $room = $args['room'];
    $contents = json_decode(file_get_contents('php://input'), true);
    $message = $contents['message'];
    $request =  $request->withParsedBody($contents);
    $jwp = getJwpClient();
    $jwp->push($room, 'chat_msg', ['message' => $message]);
    $response = $response->withHeader('Content-type', 'application/json');
    $response->getBody()->write(json_encode(['status' => 'ok']));
    return $response;
});

$app->post('/auth/{username}', function (Request $request, Response $response, $args) {
    $username = $args['username'];
    $contents = json_decode(file_get_contents('php://input'), true);
    $socketID = $username;
    $jwp = getJwpClient();
    $channelOptions = [
        'presence_track' => true,
        'presence_diffs' => true,
        'notify_joins' => true,
        'notify_leaves' => true,
    ];

    try {
        switch ($contents['auth_type']) {
            case 'socket':
                $socketToken = $jwp->authenticateSocket($socketID, 60);
                $data = [
                    'status' => 'ok',
                    'data' => [
                        'auth' => $socketToken,
                        'app_id' => 'dev'
                    ]
                ];
                break;

            case 'channel':
                $channelMeta = ['username' => $username];
                $channel = $contents['channel_name'];
                $data =
                    $data = [
                        'status' => 'ok',
                        'data' => $jwp->authenticateChannel($socketID, $channel, $channelMeta, $channelOptions)
                    ];
                break;

            default:
                throw new \Exception("Incorrect auth type");
                # code...
                break;
        }
    } catch (\Exception $e) {
        $data = ['status' => 'error', 'error' => $e->getMessage()];
    }

    $response = $response->withHeader('Content-type', 'application/json');
    $response->getBody()->write(json_encode($data));
    return $response;
});

$app->run();
