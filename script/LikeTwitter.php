<?php

/**
 * Created by IntelliJ IDEA.
 * User: vinh
 * Date: 2016/09/12
 * Time: 21:44
 */
use Phalcon\Di\FactoryDefault\Cli as CliDI;
use Phalcon\Cli\Console as ConsoleApp;
use Phalcon\Loader;

require '../app/controllers/TwitterController.php';

class LikeTwitter extends Phalcon\DI\Injectable
{
    public function run()
    {
        $twitter = new TwitterController();
        $twitter->likeAction();
    }
}

try {
    $task = new LikeTwitter();
    $task->run();
} catch (Exception $e) {
    echo $e->getMessage(), PHP_EOL;
    echo $e->getTraceAsString();
}