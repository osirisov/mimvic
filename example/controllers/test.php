<?php

return [
  'extensions' => [
    'example',
    // Uncomment following to enable 
    // route cache.
    //'route_cache'
  ],
  'composer'  =>  ['webvariants/babelcache'],
  'libraries' =>  ['SystemCacheFactory'],

  // Incase of xcache Installed uncomment following
  //'route_cache' => ['xcache' => ['ttl' => 60], 'prefix' => 'test::'],
  // Incase of apc Installed uncomment following
  //'route_cache' => ['apc' => ['ttl' => 60], 'prefix' => 'test::'],


  '/' => function() {
    $cache = Config::val('cache');
    $cache->flush('visitor');

    $req = Request::data();
    Response::render('jsonp', [ 'Foo' => 'bar' ]);
  },

  '/clear' => function() {
    Controllers::trigger('route_cache', 'clear');
  },

  '/extension_test' => function (){
    echo a_example_extension_function();
    $c = new ExampleConnection();
    echo $c->get();
  },

  '/greet/:name' => function ($name) {
    $cache = Config::val('cache');
    Response::render('php', Config::val('root').'/views/hello_world', ['name' => $name, 'previous_visit' => $cache->get('visitor', $name)]);
    $cache->set('visitor', $name, true);
  },

  'get /do/:foo/then/:bar/till/*' => function ($bar, $foo, $segments) {
    echo "I am about to do $foo, then $bar \n";
    foreach($segments[0] as $act) {
      echo "* then $act\n";
    }
  },

  'post /save/:id' => function($id) {
    echo "It works with $id\n";
  }
];
