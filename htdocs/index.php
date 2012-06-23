<?php
require_once dirname(__FILE__) . '/../vendor/slim/Slim/Slim.php'; 

$app = new Slim;

$app->get('/hello/:name', 'hello');
function hello($name) {
    echo "Hello, {$name}";
}

$app->run();
