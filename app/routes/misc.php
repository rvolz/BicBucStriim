<?php

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

$app->get('/login/', function (ServerRequestInterface $request, $response, $args) use ($app) {
    $logger = $app->getContainer()->get(LoggerInterface::class);
    if (is_authenticated()) {
        // TODO Where do we get auth info from?
        //$logger->info('user is already logged in : ' . $app->auth->getUserName());
        $home = $request->getUri()->withPath('/');
        $app->redirect($request->getUri(), $home);
    } else {
        return $this->get('view')->render(
            $response,
            'login.html', array(
                'page' => mkPage(getMessageString('login'))
            )
        );
    }
});

$app->post('/login/', 'perform_login');
$app->get('/logout/', 'logout');
