mimvic
======

Minimal View Contoller in PHP (from BitBucket) (MIT license)

Introducing MiMViC that embraces the PHP 5.3 and takes a step into future by utilizing the namespaces, lambda functions
and minimality. MiMViC is supposed to be super-light weight and programmer friendly framework, thus giving programmer only
the most essential tools for programming. Practically speaking MiMViC follows the "KISS Rule" for real! 

Why?

If you are a developer who wants:

    To glue up functions on friendly URL.
    Really really zero-configuration.
    No frustrating XML or YAML shit!
    Optimum performance.
    Perfect for shared hosting servers.
    Framework that melts into your code like ice-flake.
    No interference with PHP's variables or settings or whatever. 

Then MiMViC is the right choice for you. Remember *its not intended to be a full fledged MVC framework*, its a whatever framework.
Getting Started

There are two ways to get started using composer or just downloading the source.
Composer

1) Add mimvic to your composer.json

{
  "require": {
    "mxp/mimvic": "dev-default"
  }
}

2) Install mimvic composer.phar install

3) Use it

<?php
require 'vendor/mxp/mimvic/uvic.php';

use MiMViC as app;

app\get('/', function(){
  print "Hello World!";
});

app\post('/', function(){
  print "Hello World, ".(empty($_POST['name'])?'unknown person':$_POST['name'])."!";
});

app\start();

Download source

1) Download the source: uvic.php

2) Use it

<?php
require 'uvic.php';

use MiMViC as app;

app\get('/', function(){
  print "Hello World!";
});

app\post('/', function(){
  print "Hello World, ".(empty($_POST['name'])?'unknown person':$_POST['name'])."!";
});

app\start();

Examples

For more examples see the Examples page.
