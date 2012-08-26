<?php
set_include_path(
    realpath(dirname(__FILE__) . '/../src') .
    PATH_SEPARATOR .
    realpath(dirname(__FILE__) . '/../vendor/codeguy/Slim') .
    PATH_SEPARATOR .
    realpath(dirname(__FILE__) . '/../vendor/yuya-takeyama/acne/src') .
    PATH_SEPARATOR .
    realpath(dirname(__FILE__) . '/../vendor/yuya-takeyama/edps/src') .
    PATH_SEPARATOR .
    realpath(dirname(__FILE__) . '/../vendor/yuya-takeyama/sumile/src') .
    PATH_SEPARATOR .
    get_include_path()
);

require_once 'PhpGyazo/Application.php';
require_once 'Sumile/WebTestCase.php';
