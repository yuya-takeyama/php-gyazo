<?php
require_once 'Sumile/Application.php';
require_once 'PhpGyazo/Provider/PdoServiceProvider.php';

class PhpGyazo_Application extends Sumile_Application
{
    const MD5_LENGTH = 32;

    /**
     * Project root directory
     *
     * @var string
     */
    private $rootDir;

    public function __construct()
    {
        parent::__construct();

        $this->configure();

        $this->initialize();
    }

    public function initialize()
    {
        $this->get('/:hash.png', array($this, 'picture'));
        $this->get('/:hash', array($this, 'picturePage'));
        $this->post('/upload.cgi', array($this, 'upload'));
    }

    public function configure()
    {
        $this->configureMode('production', array($this, 'configureProduction'));
        $this->configureMode('development', array($this, 'configureDevelopment'));

        $configFile = $this->getConfigFile();

        if ($configFile) {
            $this->config(require $configFile);
        } else {
            throw new RuntimeException("Config file for {$this->getMode()} mode is not found");
        }

        $this->register(new PhpGyazo_Provider_PdoServiceProvider, array(
            'db.host'     => $this->config('db.host'),
            'db.user'     => $this->config('db.user'),
            'db.password' => $this->config('db.password'),
            'db.database' => $this->config('db.database'),
        ));
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
        if (isset($_FILES['imagedata']) && isset($_FILES['imagedata']['tmp_name'])) {
            $tmpfile = $_FILES['imagedata']['tmp_name'];
        } else {
            $this->halt(400, "Failed to upload: No image file is attached");
        }

        $file = fopen($tmpfile, 'r');
        $hash = md5_file($tmpfile);
        $userId = $this->request()->post('id');
        $stmt = $this['db']->prepare(
            'INSERT INTO pictures (`hash`, `user_id`, `body`, `created_at`, `updated_at`) ' .
            'VALUES (?, ?, ?, NOW(), NOW())'
        );
        $stmt->bindParam(1, $hash, PDO::PARAM_STR, self::MD5_LENGTH);
        $stmt->bindParam(2, $userId, PDO::PARAM_STR, strlen($userId));
        $stmt->bindParam(3, $file, PDO::PARAM_LOB);
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
            $this->rootDir = realpath(dirname(__FILE__) . '/../../');
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
