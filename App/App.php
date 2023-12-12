<?php

namespace App;

use AltoRouter;

class App
{

    private $dtDiff = 3 * 3600; // 3 hrs

    public function init(string $url, string $inputFileName)
    {
        $io = new FileIO();
        $cache = new Cache();

        $cache->setCachePath(APP . '/cache/');

        $io->init();
        $io->setUseragent();
        $io->setUrl($url);

        if ($io->isFile($inputFileName)) {
            if (time() - $io->createdAt($inputFileName) > $this->dtDiff) {
                $io->getFileFromUrl($inputFileName);
            }
        } else {
            $io->getFileFromUrl($inputFileName);
        }

    }

    public function afterRoute(AltoRouter $router) {
        $match = [];

        $match = $router->match();

        // call closure or throw 404 status
        if( is_array($match) && is_callable( $match['target'] ) ) {
           call_user_func_array( $match['target'], $match['params'] );
        } else {
            // no route was matched
            header( $_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
        }

    }

}