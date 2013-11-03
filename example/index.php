<?php
require '../mimvic.php';

Config::set('root', realpath(dirname(__FILE__)));

\mimvic\UserExtensions::Path( 'libs' );
\mimvic\ComposerExtensions::Path( 'vendor' );

$st = microtime();
Controllers::LoadPath( 'controllers' );
Controllers::load(['test.php']);

$factory = new SystemCacheFactory();
$cache   = $factory->getCache('BabelCache_Filesystem');
Config::store('cache', $cache);

Controllers::start();
echo " \n Time taken ".(microtime() - $st);