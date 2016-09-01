<?php
/**
 * Created by IntelliJ IDEA.
 * User: vinh
 * Date: 2016/08/25
 * Time: 18:27
 */

use Phalcon\Tag;
use Phalcon\Mvc\Url;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Application;
use Phalcon\Cache\Multiple;
use Phalcon\Cache\Backend\Libmemcached;
use Phalcon\Cache\Backend\File as BackFile;
use Phalcon\Cache\Frontend\Data as FrontData;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;


$di->set(
    'cache', function ()  {
    $config = $this->getConfig();
    // Create an Output frontend. Cache the files for 2 days
    $frontCache = new FrontData(
        array(
            "lifetime" => 172800
        )
    );
    //Set file cache
    $cache = new Multiple(
        array(
            new Libmemcached($frontCache, array(
               'servers' => [
                    [
                        'host' => 'localhost',
                        'port' => 11211,
                        'weight' => 1
                    ],
                ],
                'client' => [
                    \Memcached::OPT_HASH => \Memcached::HASH_MD5,
                    \Memcached::OPT_PREFIX_KEY => 'prefix.',
                ]
            )),
            new BackFile($frontCache, array(
                'cacheDir' => $config->application->cacheDir
            ))
        )
    );

    return $cache;
});

// Set the database service
$di->set('db', function() {
    $config = $this->getConfig();

    return new DbAdapter(array(
        "host"     => $config->database->host,
        "username" => $config->database->username,
        "password" => $config->database->password,
        "dbname"   => $config->database->dbname
    ));
});

// Setting up the view component
$di->set('view',  function() {
    $config = $this->getConfig();
    $view = new View();
    $view->setViewsDir($config->application->viewsDir);
    return $view;
});

// Setup a base URI so that all generated URIs include the "tutorial" folder
$di->set('url', function() {
    $config = $this->getConfig();
    $url = new Url();
    $url->setBaseUri($config->application->baseUri);
    return $url;
});

$di->set('router', function() {
    $router = new \Phalcon\Mvc\Router();
    $router->setUriSource(Phalcon\Mvc\Router::URI_SOURCE_SERVER_REQUEST_URI);
    return $router;
});

//set config to use in controller
$di->set('config', function () {
    $configData = require '../app/config/config.php';
    return $configData;
});

// Setup the tag helpers
$di->set('tag', function() {
    return new Tag();
});