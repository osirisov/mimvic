<?php

class SystemCacheFactory extends BabelCache_Factory {
  protected function getCacheDirectory() {
    $dir = '/tmp/fscache';
    if (!is_dir($dir)) mkdir($dir, 0777);
    return $dir;
  }

  protected function getSQLiteConnection() {
    $db   = dirname(__FILE__).'/test.sqlite';
    $conn = BabelCache_SQLite::connect($db);

    return $conn;
  }
}