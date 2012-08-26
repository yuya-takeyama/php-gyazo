#!/usr/bin/env php
<?php
class Venom_Application
{
    const VENOM_DIR = './.venom';
    const TMP_DIR   = './.venom/tmp';

    private $repos = array();

    private $autoload = array();

    private $vendor = './vendor';

    public static function run()
    {
        $app = new self;
        try {
            $app->initialize();
            $app->loadVenomfile();
            $app->download();
            $app->generateAutoloader();
        } catch (Exception $e) {
            echo get_class($e) . ": {$e->getMessage()}", PHP_EOL;
        }
    }

    public function initialize()
    {
        if (!file_exists(self::VENOM_DIR)) {
            mkdir(self::VENOM_DIR);
        }
        if (file_exists(self::TMP_DIR)) {
            $this->cmd('rm', '-rf', self::TMP_DIR);
        }
        mkdir(self::TMP_DIR);
        if (!file_exists($this->vendor)) {
            mkdir($this->vendor);
        }
        if (!file_exists($this->vendor('SplClassLoader'))) {
            mkdir($this->vendor('SplClassLoader'));
        }
        if (!file_exists($this->vendor('SplClassLoader/SplClassLoader.php'))) {
            $this->cmd('wget', '--quiet', '-O', $this->vendor('SplClassLoader/SplClassLoader.php'), 'https://raw.github.com/gist/221634/SplClassLoader.php');
        }
    }

    public function loadVenomfile()
    {
        if (file_exists('./Venomfile')) {
            $GLOBALS['venom'] = $this;
            include './Venomfile';
        } else {
            throw new RuntimeException('Venomfile not found');
        }
    }

    public function github($repo, $options = array())
    {
        $this->repos[] = new Venom_Repository_Github($repo, $options);
    }

    public function download()
    {
        foreach ($this->repos as $repo) {
            $this->cmd('wget', '--quiet', '-O', $this->tmp($repo->getTarGzFilename()), $repo->getTarGzUrl());
            mkdir($this->getTmpDir($repo));
            $this->cmd('tar', 'xzf', $this->tmp($repo->getTarGzFilename()), '--strip-components', '1', '-C', $this->tmp($repo->getHash()));
            $this->cmd('rm', '-rf', $this->getTargetDir($repo));
            $this->cmd('mkdir', '-p', dirname($this->getTargetDir($repo)));
            $this->cmd('cp', '-pr', $this->getTmpDir($repo), $this->getTargetDir($repo));
            $config = $this->getAutoloadConfig($repo);
            if ($config && is_array($config)) {
                foreach ($config as $key => $value) {
                    $this->autoload[$key] = $this->getTargetDir($repo) . DIRECTORY_SEPARATOR . $value;
                }
            }
        }
    }

    private function cmd()
    {
        $args = func_get_args();
        $cmd = join(' ', array_map('escapeshellarg', $args));
        echo $cmd, PHP_EOL;
        echo `$cmd`;
    }

    private function getTmpDir(Venom_RepositoryInterface $repo)
    {
        return $this->tmp($repo->getHash());
    }

    private function getTargetDir(Venom_RepositoryInterface $repo)
    {
        return $this->vendor($repo->getUser() . DIRECTORY_SEPARATOR . $repo->getProject());
    }

    private function getComposerJson(Venom_RepositoryInterface $repo)
    {
        return $this->getTargetDir($repo) . DIRECTORY_SEPARATOR . 'composer.json';
    }

    private function hasComposerJson(Venom_RepositoryInterface $repo)
    {
        return file_exists($this->getComposerJson($repo));
    }

    public function getAutoloadConfig(Venom_RepositoryInterface $repo)
    {
        if ($this->hasComposerJson($repo)) {
            $json = json_decode(file_get_contents($this->getComposerJson($repo)), true);
            if (isset($json['autoload']) && isset($json['autoload']['psr-0'])) {
                $config = $json['autoload']['psr-0'];
                return $config;
            }
        }
    }

    private function tmp($file)
    {
        return self::TMP_DIR . DIRECTORY_SEPARATOR . $file;
    }

    private function vendor($file)
    {
        return $this->vendor . DIRECTORY_SEPARATOR . $file;
    }

    private function generateAutoloader()
    {
        $fp = fopen($this->vendor('autoload.php'), 'w');
        fputs($fp, '<?php' . PHP_EOL);
        fputs($fp, 'require_once \'./vendor/SplClassLoader/SplClassLoader.php\';' . PHP_EOL);
        foreach ($this->autoload as $namespace => $dir) {
            fputs($fp, '$loader = new SplClassLoader(' . var_export($namespace, true) . ', ' . var_export($dir, true) . ');' . PHP_EOL);
            fputs($fp, '$loader->register();' . PHP_EOL);
        }
        fclose($fp);
    }
}

interface Venom_RepositoryInterface
{
    function getHash();
    function getUser();
    function getProject();
    function getTarGzFilename();
    function getTarGzUrl();
}

class Venom_Repository_Github implements Venom_RepositoryInterface
{
    private $url;

    private $user;

    private $project;

    private $branch;

    private $tag;

    public function __construct($url, $options = array())
    {
        $this->setUrl($url);
        $this->tag = isset($options['tag']) ? $options['tag'] : NULL;
        $this->branch = isset($options['branch']) ? $options['branch'] : NULL;
    }

    public function setUrl($url)
    {
        $this->url = $url;
        if (preg_match('#^https://github.com/([^/]+)/([^/]+)#', $url, $matches)) {
            $this->user    = $matches[1];
            $this->project = $matches[2];
        } else {
            throw new RuntimeException('Invalid GitHub URL specified');
        }
    }

    public function getHash()
    {
        return md5($this->url);
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getProject()
    {
        return $this->project;
    }

    public function getTarGzFilename()
    {
        return "{$this->project}.tar.gz";
    }

    public function getTarGzUrl()
    {
        if (isset($this->tag)) {
            return "https://github.com/{$this->user}/{$this->project}/tarball/{$this->tag}";
        } else if (isset($this->branch)) {
            return "https://github.com/{$this->user}/{$this->project}/tarball/{$this->branch}";
        } else {
            return "https://github.com/{$this->user}/{$this->project}/tarball/master";
        }
    }
}

function github($url, $options = array()) {
    global $venom;
    $venom->github($url, $options);
}

Venom_Application::run();
