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
* __ZEND\_GIT\_REPO__ (string) - URL of git repository which is deployed to /var/www
* __ZEND\_S3\_BUCKET__ (string) - Name of S3 bucket from which files are
downloaded to /var/www
* __ZEND\_S3\_PREFIX__ (string) - If set, then only files with that prefix will
  be downloaded to /var/www (take into account that prefix will be present in
  files downloaded, so if you restrict it to some folder then folder will be
  downloaded to document root)
* __ZEND\_DEBUG__ (bool) - set to true to start Zend Server in debug mode with maximum
  log verbosity
* __ZEND\_DOCUMENT\_ROOT__ (string) - Document root relative to system default
  document root. Should be used with git or S3 deployment (just set it to path
  relative to your git repository or S3 folder).
* __ZEND\_SCRIPT\_URL__ (string) - URL from which custom script must be
  downloaded and executed.
* __ZEND\_SCRIPT\_PATH__ (string) - Absolute path including filename where
  custom script must be placed. After reboot this file will be overwritten by
  redownloaded script.
* __AWS\_ACCESS\_KEY__ (string) - Access key to use for all AWS S3 operations.
* __AWS\_SECRET\_KEY__ (string) - Secret key to use for all AWS S3 operations.

# Scripts

* __init.php__ - initializes Zend Server on boot of Zend Server.
* __shutdown.php__ - removes Zend Server from cluster, if it has joined cluster
  on boot.
