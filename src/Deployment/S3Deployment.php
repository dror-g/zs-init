<?php
namespace Zend\Deployment;

use Aws\S3\S3Client;
use Zend\Log;

class S3Deployment extends AbstractDeployment
{
    private $s3;
    private $bucket;
    private $prefix;
    private $leavePrefix;

    public function __construct($path, Log $log, $relativeRoot, $bucket, $prefix, $leavePrefix, $awsAccessKey = null, $awsSecretKey = null)
    {
        parent::__construct($path, $log, true, $relativeRoot);
        $this->bucket = $bucket;
        $this->prefix = $prefix;
        $this->leavePrefix = (bool) $leavePrefix;
        date_default_timezone_set("UTC");
        $region = substr(file_get_contents("http://169.254.169.254/latest/meta-data/placement/availability-zone"), 0, -1);
        $options = ['region' => $region];
        if ($awsAccessKey !== null && $awsSecretKey !== null) {
            $options['key'] = $awsAccessKey;
            $options['secret'] = $awsSecretKey;
        }
        $this->s3 = S3Client::factory($options);
    }

    public function deploy()
    {
        $this->cleanDeploymentDir();
        $this->fixDummyPhp();
        $tmpDir = self::createTmpDir();
        $this->s3->downloadBucket($tmpDir, $this->bucket, $this->prefix);
        $srcDir = $tmpDir;
        if (!$this->leavePrefix && $this->prefix !== null && $this->prefix !== "") {
            $srcDir .= "/{$this->prefix}";
        }
        self::moveDirContent($srcDir, $this->deploymentDir);
        self::removeTmpDir($tmpDir);
        $this->runComposer();
        $this->updateApacheConfig();
        return true;
    }

    /**
     * Create temporary directory and return it's full name
     * @return string temporary directory name
     */
    public static function createTmpDir()
    {
        $dir = Deployment::TMP_DIR . '/zs-init-s3-tmp';
        if (is_dir($dir)) {
            exec("rm -rf {$dir}");
        }
        mkdir($dir, 0755);
        return $dir;
    }

    /**
     * Delete temporary directory
     * @param string $dir temporary directory to delete
     * @return null nothing
     */
    public static function removeTmpDir($dir)
    {
        exec("rm -rf {$dir}");
    }

    /**
     * Move directory content to another directory
     * @param string $srcDir directory to move content from
     * @param string $dstDir directory to move content to
     * @return bool true on success, false if one of directories does not exist
     * or could not be opened
     */
    public static function moveDirContent($srcDir, $dstDir)
    {
        $filter = ['.', '..'];
        if (!is_dir($srcDir) || !is_dir($dstDir)) {
            return false;
        }
        if ($handle = opendir($srcDir)) {
            while (($file = readdir($handle)) !== false) {
                if (!in_array($file, $filter)) {
                    rename("{$srcDir}/{$file}", "{$dstDir}/{$file}");
                }
            }
            closedir($handle);
            return true;
        }
        return false;
    }
}
