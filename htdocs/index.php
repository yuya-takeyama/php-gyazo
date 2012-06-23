<?php
set_include_path(
    realpath(dirname(__FILE__) . '/../src') .
    PATH_SEPARATOR .
    realpath(dirname(__FILE__) . '/../vendor/slim') .
    PATH_SEPARATOR .
    get_include_path()
);
require_once 'Phat/Application.php';

class GyazoApp extends Phat_Application
{
    public function picture($hash)
    {
        $stmt = $this['db']->prepare('SELECT hash, body FROM pictures WHERE hash = :hash');
        $stmt->bindParam(':hash', $hash);
        $stmt->execute();
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($record) {
            $this->response['Content-type'] = 'image/png';
            $this->response->write($record['body']);
        } else {
            $this->halt(404, 'Picture not found.');
        }
    }
}

$app = new GyazoApp;
$app['db'] = new PDO('mysql:dbname=php_gyazo_dev;host=localhost', 'gyazo', 'gyazo');

$app->get('/:hash.png', array($app, 'picture'));

$app->run();
