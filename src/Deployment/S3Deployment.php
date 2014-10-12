<?php
namespace Zend\Deployment;

use Aws\S3\S3Client;

class S3Deployment extends AbstractDeployment
{
    protected $s3;

    public function __construct($bucket,$prefix,$defaultDocRoot,$awsAccessKey = null,$awsSecretKey = null)
    {
        parent::__construct(['bucket' => $bucket, 'prefix' => $prefix],$defaultDocRoot);
        date_default_timezone_set("UTC");
        $region = substr(file_get_contents("http://169.254.169.254/latest/meta-data/placement/availability-zone"),0,-1);
        $options = ['region' => $region];
        if($awsAccessKey !== null && $awsSecretKey !== null) {
            $options['key'] = $awsAccessKey;
            $options['secret'] = $awsSecretKey;
        }
        $this->s3 = S3Client::factory($options);
    }

    public function deploy()
    {
        exec("rm -rf {$this->defaultDocRoot}/*");
        $this->s3->downloadBucket($this->defaultDocRoot,$this->repo['bucket'],$this->repo['prefix']);
        symlink('/usr/local/zend/share/dist/dummy.php',"{$this->defaultDocRoot}/dummy.php");
    }
}
