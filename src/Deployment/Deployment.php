<?php
namespace Zend\Deployment;

interface Deployment
{
    const DEPLOYMENTS_DIR='/var/www/apps';
    const DEFAULT_DOCUMENT_ROOT='/var/www/html';
    const TMP_DIR='/tmp';

    public function deploy();
}
