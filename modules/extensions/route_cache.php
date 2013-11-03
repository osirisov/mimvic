<?php 

namespace mimvic\extensions\route_cache {

  if( !defined('EXTENSIONS_ROOT') ) {
    define( 'EXTENSIONS_ROOT', MIMVIC_ROOT.DIRECTORY_SEPARATOR.'modules/extensions');
  }

  class RouteCacheException extends \Exception {}

  trait RouteCacheSystem {
    protected $prefix = '';
    protected $ttl = 0;
    protected $cache_type = '';
    
    protected function configureCache($config) {
      $args = $config->arguments;
      $def_opts = ['ttl' => 0, 'prefix' => 0];

      foreach(['apc', 'xcache']  as $cache_type){
        if( isset($args[$cache_type]) ){
          $this->cache_type = $cache_type;
          break;          
        }
      }

      $opts = array_merge($def_opts, $args[$cache_type]);
      $this->prefix = $opts['prefix'];
      $this->ttl = $opts['ttl'];
    }

    public function set($key, $val) {
      $ttl = $this->ttl;
      switch ($this->cache_type) {
        case 'apc':
          return apc_add($key, $val, $ttl);
        case 'xcache':
          return xcache_set($key, $val, $ttl);
        default:
          break;
      }
      return NULL;
    }

    public function get($key) {
      switch ($this->cache_type) {
        case 'apc':
          return apc_fetch($key);
        case 'xcache':
          return xcache_get($key);
        default:
          break;
      }
      return NULL;
    }

    public function del($key) {
      switch ($this->cache_type) {
        case 'apc':
          return apc_delete($key);
        case 'xcache':
          return xcache_unset($key);
        default:
          break;
      }
      return NULL;
    }

    public function flush() {
      switch ($this->cache_type) {
        case 'apc':
          return apc_clear_cache("user");
        case 'xcache':
          return xcache_clear_cache(XC_TYPE_VAR, 0);
        default:
          break;
      }
      return NULL;
    }
  }

  class TriggerFromCacheEvent implements \EventHandler {
    use \NamedEvent;

    public function __construct($rc) {
      $this->cache = $rc;
    }

    public function handle($args) {
      $key = $this->prefix.$args->method.' '.$args->uri;
      $inf = $this->cache->get($key);
      if(isset($inf) && is_array($inf)){
        \Controllers::call_route($inf['method'], $inf['pattern'], $inf['params']);
        return \EventHandler::PreventAction;
      }
      return \EventHandler::ContinueEvents;
    }
  }

  class SaveToCacheEvent implements \EventHandler {
    use \NamedEvent;

    public function __construct($rc) {
      $this->cache = $rc;
    }

    public function handle($args) {
      $key = $this->prefix.$args->method.' '.$args->uri;
      if( !is_array($this->cache->get($key)) ){
        $this->cache->set($key, [
          'method'  => $args->method,
          'pattern' => $args->pattern,
          'params'  => $args->params
        ]);
      }
      return \EventHandler::ContinueEvents;
    }
  }

  class RouteCacheEvent implements \EventHandler {
    use RouteCacheSystem;
    use \NamedEvent;

    public function handle($args) {
      if( is_string($args) ){
        switch ($args) {
          case 'clear':
              echo "Flushing cache...";
              $c = $this->flush();
              var_dump($c);
              throw new RouteCacheException("Cache cleared");
            break;
          default:
            break;
        }
        
      }

      $this->configureCache($args);
      \Controllers::bind('dispatch.hasroute', new SaveToCacheEvent($this));
      \Controllers::bind('dispatch.before.action', new TriggerFromCacheEvent($this));
      return \EventHandler::ContinueEvents;
    }
  }

  \Controllers::bind('route_cache', new RouteCacheEvent);

}
