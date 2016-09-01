<?php
/**
 * Created by IntelliJ IDEA.
 * User: vinh
 * Date: 2016/08/25
 * Time: 18:32
 */
use Phalcon\Loader;
use Phalcon\Mvc\Application;

try {

    // Register an autoloader
    $loader = new Loader();
    $loader->registerDirs(
        array(
            '../app/controllers/',
            '../app/models/'
        )
    )->register();

    // Handle the request
    $application = new Application($di);

    echo $application->handle()->getContent();

} catch (Exception $e) {
    echo "Exception: ", $e->getMessage();
}