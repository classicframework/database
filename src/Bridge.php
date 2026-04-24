<?php

namespace classicframework\database;

use classicframework\core\App;
use classicframework\core\Config;
use classicframework\core\BridgeInterface;

class Bridge implements BridgeInterface
{
  public static function register(App $app)
  {
    $config = Config::extract('database');
    $database = new Database($config);

    $app->set_service('db', $database);
    $app->set_service('database', $database);
  }
}