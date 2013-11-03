<?php

class ExampleConnection {
  public static $test = "None";
  public function get(){
    $var = self::$test;
    return "$var Boo!";
  }
}

function a_example_extension_function(){
  return "It works!";
}

return ['imported' => function(){
  ExampleConnection::$test = "Loaded";
}];