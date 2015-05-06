# Overview

This is a collection of scripts that were developed for AMI with Zend Server. It
gets configuration from user data and configures Zend Server as needed.

# User data

Configuration to this script is passed through user data in JSON format. Inside
of JSON object following fields can be defined:

* __ZEND\_CLUSTER\_DB\_HOST__ (string) - MySQL DB hostname for Zend Cluster
* __ZEND\_CLUSTER\_DB\_USER__ (string) - MySQL DB user for Zend Cluster
* __ZEND\_CLUSTER\_DB\_PASSWORD__ (string) - MySQL DB password for Zend Cluster
* __ZEND\_ADMIN\_PASSWORD__ (string) - Password for admin user of Zend Server,
  if is set without ZEND\_CLUSTER\_* parameters, it will bootstrap Zend Server
  and setup admin password.
* __ZEND\_BOOTSTRAP\_PRODUCTION__ (bool) - whether Zend Server should be
  bootstrapped for production (true) or development (false).
* __ZEND\_GIT\_REPO__ (string) <sub>_[obsolete]_</sub> - URL of git repository which is deployed to /var/www
* __ZEND\_S3\_BUCKET__ (string) <sub>_[obsolete]_</sub> - Name of S3 bucket from which files are
downloaded to /var/www
* __ZEND\_S3\_PREFIX__ (string) <sub>_[obsolete]_</sub> - If set, then only files with that prefix will
  be downloaded to /var/www (take into account that prefix will be present in
  files downloaded, so if you restrict it to some folder then folder will be
  downloaded to document root)
* __ZEND\_ZPK__ (array) <sub>_[obsolete]_</sub> - Details of ZPK that will be deployed on server
  start up. Following are the keys that can be specified in this array:
  * __url__ (string) - URL from which ZPK can be downloaded
  * __name__ (string) - Name of application that will appear in Zend Server UI
    after deployment
  * __params__ (array) - Array of additional parameters that is passed to Zend
    Server during ZPK deployment. Keys of this array are names of parameters and
    respective values are values of parameters.
* __ZEND\_DOCUMENT\_ROOT__ (string) - Document root relative to system default
  document root. Should be used with git or S3 deployment (just set it to path
  relative to your git repository or S3 folder).
* __ZEND\_DEPLOYMENTS__ (array) - array in which each item is an object that
  describes a deployment that will be done after Zend Server start. Following is
  a list of keys of such objects:
  * __type__ (string) - required and must be one of "git", "s3" or "zpk".
  * __path__ (string) - required for all deployments and specifies into which
    path web application should be deployed. To deploy application to root,
    specify "/".
  * __url__ (string) - required for "git" and "zpk" deployments. Specifies URL
    from which git repository or ZPK file should be downloaded.
  * __relativeRoot__ (string) - optional for "git" and "s3" deployments. If
    specified, indicates which folder inside a deployment should be document
    root (or in case application is not deployed to root, which directory should
    be aliased in apache).
  * __buket__ (string) - required for "s3" deployment. Specifies from which S3
    bucket application should be downloaded.
  * __prefix__ (string) - optional for "s3" deployment. Specifies which
    directory in S3 bucket should be downloaded.
  * __name__ (string) - required for "zpk" deployment. Name of application that
    will appear in Zend Server UI after deployment.
  * __params__ (array) - required for "zpk" deployment. Array of additional
    parameters that is passed to Zend Server during ZPK deployment. Keys of this
    array are names of parameters and respective values are values of
    parameters. Parameter values are scanned for string $IP and if it is found,
    then it is replaced with external IP of instance. This is useful, for example,
    when deploying Wordpress application.
* __ZEND\_DEBUG__ (bool) - set to true to start Zend Server in debug mode with maximum
  log verbosity
* __ZEND\_SCRIPT\_URL__ (string) - URL from which custom script must be
  downloaded and executed. Custom script is run after deployment.
* __ZEND\_SCRIPT\_PATH__ (string) - Absolute path to where custom script must be
  placed. If path is a directory, then script is saved in that directory with
  filename extracted from URL. After reboot this file will be overwritten by
  re-downloaded script.
* __ZEND\_PRE\_SCRIPT\_URL__ (string) - URL from which custom preparation script
  must be downloaded and executed. Preparation script is run before any other
  actions of zs-init. It is intended for customization that has to be done
  before deploying applications (for example local MySQL server installation).
* __ZEND\_PRE\_SCRIPT\_PATH__ (string) - Absolute path to where custom
  preparation script must be placed. If path is a directory, then script is
  saved in that directory with filename extracted from URL. After reboot this
  file will be overwritten by re-downloaded script.
* __AWS\_ACCESS\_KEY__ (string) - Access key to use for all AWS S3 operations.
* __AWS\_SECRET\_KEY__ (string) - Secret key to use for all AWS S3 operations.

# Scripts

* __init.php__ - initializes Zend Server on boot of Zend Server.
* __shutdown.php__ - removes Zend Server from cluster, if it has joined cluster
  on boot.
