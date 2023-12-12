<?php

// map homepage
$router->map('GET', '/', function() {
    require_once APP . '/Views/home.php';
}, 'home');

$router->map('GET', '/ajax/[a:action]/[*:params]?', function($action, $params=[]) {
    header('Content-Type: application/json; charset=utf-8');
    require_once APP . '/Controller/ApiController.php';

    $api = new \App\Controller\ApiController($action, $params);
    echo $api->runAction($action, $params)->asJson();
}, 'api');
