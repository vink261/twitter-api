<?php
/**
 * Created by IntelliJ IDEA.
 * User: vinh
 * Date: 2016/08/25
 * Time: 18:27
 */

use Phalcon\Tag;
use Phalcon\Cache\Backend\File as BackFile;
use Phalcon\Cache\Frontend\Output as FrontOutput;


$di->set(
    'viewCache', function () use ($config) {
        // Create an Output frontend. Cache the files for 2 days
        $frontCache = new FrontOutput(
            array(
                "lifetime" => 172800
            )
        );
        //Set file cache
        $cache = new BackFile($frontCache, array(
            "cacheDir" => $config->application->cacheDir
        ));

        return $cache;
    }
);
