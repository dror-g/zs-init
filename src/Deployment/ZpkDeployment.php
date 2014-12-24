<?php
namespace Zend\Deployment;

use Zend\ZSWebApiClient;

class ZpkDeployment extends AbstractDeployment
{
    public function __construct($url, $name, $params, $defaultDocRoot, $webApiKeyName, $webApiKeyHash)
    {
        parent::__construct($url, $defaultDocRoot);
        date_default_timezone_set("UTC");
        $parts = parse_url($url);
        if(!is_array($params)) {
            $params = array();
        }
        $this->name = $name;
        $this->params = $params;
        $this->filename = basename($parts['path']);
        $this->data = file_get_contents($url);
        $this->webApiKeyName = $webApiKeyName;
        $this->webApiKeyHash = $webApiKeyHash;
    }

    public function deploy()
    {
        $client = new ZSWebApiClient($this->webApiKeyName, $this->webApiKeyHash);
        $response = $client->applicationDeploy([
            'baseUrl' => "http://localhost/",
            'defaultServer' => true,
            'userAppName' => $this->name,
            'userParams' => $this->params,
        ], [
            $this->filename => [
                'formname' => 'appPackage',
                'filename' => $this->filename,
                'ctype' => 'application/vnd.zend.applicationpackage',
                'data' => $this->data,
            ],
        ]);
    }
}
