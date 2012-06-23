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
    const MD5_LENGTH = 32;

    /**
     * Project root directory
     *
     * @var string
     */
    private $rootDir;

    public function __construct(array $settings = array())
    {
        parent::__construct($settings);
        $this->configure();
    }

    public function configure()
    {
        $this->configureMode('production', array($this, 'configureProduction'));
        $this->configureMode('development', array($this, 'configureDevelopment'));
        $configFile = $this->getConfigFile();
        if ($configFile) {
            $this->config(require $configFile);
        }
        $this['db'] = new PDO(
            "mysql:dbname={$this->config('db.database')};host={$this->config('db.host')}",
            $this->config('db.user'),
            $this->config('db.password'),
            array(
                PDO::MYSQL_ATTR_DIRECT_QUERY => true,
            )
        );
    }

    public function configureProduction()
    {
        $this->config(array(
            'log.enable' => true,
            'debug'      => false,
        ));
    }

    public function configureDevelopment()
    {
        $this->config(array(
            'log.enable' => false,
            'debug'      => true,
        ));
    }

    public function picture($hash)
    {
        $record = $this->getRecordByHash($hash);
        if ($record) {
            $this->response['Content-type'] = 'image/png';
            $this->response->write($record['body']);
        } else {
            $this->halt(404, 'Picture not found.');
        }
    }

    public function picturePage($hash)
    {
        $record = $this->getRecordByHash($hash);
        if ($record) {
            $this->response->write("<html><head><title>{$record['hash']}.png</title></head><body><img src=\"/{$record['hash']}.png\" alt=\"Picture\" /></body></html>");
        } else {
            $this->halt(404, 'Picture not found.');
        }
    }

    public function upload()
    {
        $tmpfile = $_FILES['imagedata']['tmp_name'];
        $file = fopen($tmpfile, 'r');
        $hash = md5_file($tmpfile);
        $stmt = $this['db']->prepare(
            'INSERT INTO pictures (`hash`, `body`, `created_at`, `updated_at`) ' .
            'VALUES (?, ?, NOW(), NOW())'
        );
        $stmt->bindParam(1, $hash, PDO::PARAM_STR, self::MD5_LENGTH);
        $stmt->bindParam(2, $file, PDO::PARAM_LOB);
        if ($stmt->execute()) {
            $this->response->write($this->getImageUrl($hash));
        } else {
            $this->halt(500, "Failed to upload " . json_encode($stmt->errorInfo()));
        }
    }

    private function getRootDir()
    {
        return isset($this->rootDir) ?
            $this->rootDir :
            $this->rootDir = realpath(dirname(__FILE__) . '/..');
    }

    public function getConfigFile()
    {
        $configFile = "{$this->getRootDir()}/config/{$this->getMode()}.php";
        return file_exists($configFile) ? $configFile : NULL;
    }

    private function getRecordByHash($hash)
    {
        $stmt = $this['db']->prepare(
            'SELECT hash, body, created_at, updated_at FROM pictures WHERE hash = ?'
        );
        $stmt->bindParam(1, $hash);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getImageUrl($hash)
    {
        $env = $this->environment;
        $scheme = isset($env['HTTPS']) && $env['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $env['SERVER_NAME'];
        return "{$scheme}://{$host}/{$hash}";
    }
}

$app = new GyazoApp;

$app->get('/:hash.png', array($app, 'picture'));
$app->get('/:hash', array($app, 'picturePage'));
$app->post('/upload.cgi', array($app, 'upload'));

$app->run();
