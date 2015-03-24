<?php
namespace Zend\Deployment;

use Exception;
use Zend\Log;

abstract class AbstractDeployment implements Deployment
{
    private $error;
    protected $log;
    protected $path;
    protected $deploymentDir;
    private $relativeRoot;

    public function __construct($path, Log $log, $createDeploymentDir = true, $relativeRoot = "")
    {
        $this->error = null;
        $this->log = $log;
        $this->relativeRoot = trim($relativeRoot, '/');
        $this->path = $path;
        $this->deploymentDir = self::getDeploymentDir($path);
        if (!is_dir(Deployment::DEPLOYMENTS_DIR)) {
            $this->log->log(Log::INFO, "Creating deployments directory " . Deployment::DEPLOYMENTS_DIR);
            if (!mkdir(Deployment::DEPLOYMENTS_DIR, 0775)) {
                throw new Exception("Failed creating directory " . Deployment::DEPLOYMENTS_DIR);
            }
        }
        if ($createDeploymentDir && $this->deploymentDir !== null && !is_dir($this->deploymentDir)) {
            $this->log->log(Log::INFO, "Creating deployment directory {$this->deploymentDir}");
            if (!mkdir($this->deploymentDir, 0775, true)) {
                throw new Exception("Failed creating directory {$this->deploymentDir}");
            }
        }
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getError()
    {
        return $this->error;
    }

    protected function setError($error)
    {
        $this->error = $error;
    }

    public function runComposer()
    {
        $this->log->log(Log::INFO, "Running composer for directory {$this->deploymentDir}");
        if(is_file("{$this->deploymentDir}/composer.json")) {
            exec("/usr/local/zend/bin/php /usr/local/zend/bin/composer.phar update -d {$this->deploymentDir}/ -o --no-progress --no-ansi -n");
        }
    }

    abstract public function deploy();

    protected static function pregReplaceFile($pattern, $replacement, $filename)
    {
        $text = file_get_contents($filename);
        $text = preg_replace($pattern, $replacement, $text);
        file_put_contents($filename, $text);
    }

    protected static function strposFile($string, $filename)
    {
        $text = file_get_contents($filename);
        return strpos($text, $string) !== false;
    }

    protected function fixDummyPhp()
    {
        if ($this->deploymentDir != Deployment::DEFAULT_DOCUMENT_ROOT) {
            symlink('/usr/local/zend/share/dist/dummy.php', "{$this->deploymentDir}/dummy.php");
        }
    }

    protected function cleanDeploymentDir()
    {
        if (is_dir($this->deploymentDir)) {
            $this->log->log(Log::INFO, "Cleaning directory {$this->deploymentDir}");
            exec("rm -rf {$this->deploymentDir}/*");
        }
    }

    protected function updateApacheConfig()
    {
        $aliasDir = $this->deploymentDir;
        if ($this->relativeRoot != "") {
            $aliasDir .= "/{$this->relativeRoot}";
        }
        $this->log->log(Log::INFO, "Updating apache config for {$this->path}");
        self::addApacheDirectory($aliasDir);
        if ($this->deploymentDir != Deployment::DEFAULT_DOCUMENT_ROOT) {
            self::addApacheAlias($aliasDir, $this->path);
        }
    }

    private static function getDeploymentDir($path)
    {
        $path = rtrim($path, '/');
        if ($path === null || $path === "") {
            return Deployment::DEFAULT_DOCUMENT_ROOT;
        }
        return rtrim(Deployment::DEPLOYMENTS_DIR . '/' . trim($path, '/'), '/');
    }

    private static function addApacheDirectory($dir)
    {
        return self::addToConfig(self::apacheDirectory($dir));
    }

    private static function addApacheAlias($dir, $path)
    {
        return self::addToConfig(self::apacheAlias($dir, $path));
    }

    private static function addToConfig($string)
    {
        if (is_dir("/etc/apache2") && !self::strposFile($string, "/etc/apache2/sites-available/000-default.conf")) {
            self::pregReplaceFile("|\\<\\/VirtualHost\\>|", "{$string}\n</VirtualHost>", "/etc/apache2/sites-available/000-default.conf");
        } else if (is_dir("/etc/httpd") && !self::strposFile($string, "/etc/httpd/conf/httpd.conf")) {
            self::pregReplaceFile("|\\<\\/VirtualHost\\>|", "{$string}\n</VirtualHost>", "/etc/httpd/conf/httpd.conf");
        } else {
            return false;
        }
        return true;
    }

    private static function apacheAlias($dir, $path)
    {
        return "\nAlias {$path} \"{$dir}\"\n";
    }

    private static function apacheDirectory($dir)
    {
        return "\n<Directory \"{$dir}\">\n    AllowOverride All\n    Options +Indexes +FollowSymLinks\n    DirectoryIndex index.php\n    Order allow,deny\n    Allow from all\n    Require all granted\n</Directory>\n";
    }
}
