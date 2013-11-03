<?php

define('MIMVIC_ROOT', realpath(dirname(__FILE__)));
if( !defined('CONTROLLERS_ROOT') ) {
  define('CONTROLLERS_ROOT', MIMVIC_ROOT.DIRECTORY_SEPARATOR.'controllers');
}

if( !defined('MODULES_ROOT') ) {
  define('MODULES_ROOT', MIMVIC_ROOT.DIRECTORY_SEPARATOR.'modules');
}

class Config {
  public static $Global = [
    'maps' => [],
    'req_data' => [],
    'user_data' => []
  ];

  public static function store($name, &$value){
    self::$Global['user_data'][$name] = $value;
  }

  public static function set($name, $value){
    self::$Global['user_data'][$name] = $value;
  }

  public static function val($name){
    if(!isset(self::$Global['user_data'][$name]))
      return null;
    return self::$Global['user_data'][$name];
  }

}

interface EventHandler {
  const ContinueEvents = 1;
  const PreventAction = 0;
  const PreventAll = -1;
  public function name($name);
  public function handle($params);
}


trait NamedEvent {
  private $name;
  public function name($name) {
    $this->name = $name;
  }
}

trait Eventable {
  private static $EventHooks = [];

  public static function trigger($name, $params=[]) {

    if( !isset(self::$EventHooks[$name]) ) {
      return EventHandler::ContinueEvents;
    }
    
    foreach(self::$EventHooks[$name] as $hook){
      if($hook instanceof EventHandler) {
        $hook->name($name);
        $ret = $hook->handle($params);
        if( isset($ret) && $ret !== EventHandler::ContinueEvents )
          return $ret;
      }
    }

    return EventHandler::ContinueEvents;
  }

  public static function bind($name, $hook) {
    if( !isset(self::$EventHooks[$name]) ) {
      self::$EventHooks[$name] = [];
    }
    self::$EventHooks[$name][] = $hook;
  }
}


trait CoreCommons {
  private static function getReqMethod(){
    if(isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])){
      return strtolower($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
    }

    return strtolower( $_SERVER['REQUEST_METHOD'] );
  }

  private static function getContentType(){
    return strtolower($_SERVER['HTTP_ACCEPT']);
  }
}

trait Importable {

  public static function import_all($names, $params=[]){
    $ret = [];
    foreach( $names as $name ){
      $args = $params;
      if( isset($params[$name]) && is_array($params[$name]) ){
        $args = $params[$name];
      }
      $ret[$name] = self::import( $name, $args );
    }
    return $ret;
  }

  static function import($name, $params=[]){
    if(!file_exists(self::$PathPrefix.DIRECTORY_SEPARATOR.$name)){
      throw new Exception("File does not exist on path ".self::$PathPrefix.DIRECTORY_SEPARATOR.$name);
    }
    $ret = require self::$PathPrefix.DIRECTORY_SEPARATOR.$name;
    //Call imported if defined
    if( isset($ret['imported']) && is_callable($ret['imported']) ){
      call_user_func_array($ret['imported'], $params);
    }
    return $ret;
  }

  public static function at_root($path) {
    self::$PathPrefix = realpath($path);
  }

}

trait Dispatchable {
  private static $Routes = ['get' => [], 'post' => [], 'put' => [], 'delete' => []];

  use CoreCommons;
  use Eventable;

  public static function route($method, $uri, $func){
    $map = &self::$Routes;

    // for all methods *
    if($method == '*')
      $method = array('get','post','put','delete');

    //--If method was array then for all methods register the function
    if( is_array($method) ) {
      foreach($method as $mthd){
        self::route($mthd, $uri, $func);
        return;
      }
    }

    //--If URI was array then for all URIs register the function
    if( is_array($uri) ) {
      foreach($uri as $one_url){
        self::route($methd, $one_url, $func);
        return;
      }
    }
        

    if( isset($map[$method][$uri]) )
      throw new Exception("URI conflict on $uri");

    $map[$method][$uri] = ['method'=> $method, 'func'=> $func];
  }

  public static function call_route($method, $pattern, $params = []) {
    $r = &self::$Routes;
    if( !isset($r[$method][$pattern]) ) {
      throw new Exception(" Calling invalid route for $method $pattern");
    }

    $inf = $r[$method][$pattern];
    return self::call_user_func_named($inf['func'], $params);
  }

  private static function removeGetParams($url){
    $relUrl = explode('?', $url);
    return $relUrl[0];
  }

  private static function escapeForRegex($str){
    $esc_syms = array('^','[','.','$','{','*','(',"\\",'/','+',')','|', '?', '>', '<');
    foreach($esc_syms as $sym)
      $str = str_replace($sym, "\\".$sym, $str);
    return $str;
  }

  private static function compileExpression($elms){
    $namedParamRex = "([^\/]+)";
    $anyParamRex = "([^.]*)";
    $exp = [];
    foreach($elms as $elm){
      //For named parameter
      $v = '';
      if(strlen($elm) && $elm[0] == ':')
        $v = $namedParamRex;
      elseif($elm === '*')
        $v = $anyParamRex;
      else
        $v = self::escapeForRegex($elm);
      $exp[] = $v;
    }

    return "/^".implode("\/", $exp)."$/i";
  }

  private static function parseURIParams($pattern, $ur){
    $psegs = explode('/', $pattern); //Pattern segments

    //Compile Regular expression out of pattern string
    $exp = self::compileExpression($psegs);
    $m = preg_match($exp, $ur, $matches);

    //If matching fails return false
    if(!$m)
      return false;

    //Map and populate values from matched $matches to returnable string
    $ret = ['segments' => []];
    $i = 1;
    foreach($psegs as $pseg){
      if(strlen($pseg) && $pseg[0] == ':')
        $ret[substr($pseg, 1)] = $matches[$i++];
      elseif($pseg === '*')
        $ret['segments'][] = explode('/', $matches[$i++]);
    }
    return $ret;
  }

  private static function getReqMethod(){
    if(isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])){
      return strtolower($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
    }

    return strtolower( $_SERVER['REQUEST_METHOD'] );
  }

  private static function call_user_func_named($method, $arr){
    $ref = new ReflectionFunction($method);
    $params = [];
    foreach( $ref->getParameters() as $p ){
      if( $p->isOptional() ){
        if( isset($arr[$p->name]) ){
          $params[] = $arr[$p->name];
        }else{
          $params[] = $p->getDefaultValue();
        }
      }else if( isset($arr[$p->name]) ){
        $params[] = $arr[$p->name];
      }else{
        throw new Exception("Missing parameter $p->name");
      }
    }
    return $ref->invokeArgs( $params );
  }

  private static function triggerFunction($method, $uri){
    $map = &self::$Routes;
    $patterns = self::$Routes[$method];

    foreach($patterns as $patrn => $info){
      //Try to match
      $cParams = self::parseURIParams($patrn, $uri);
      $ret = false;
      //Catch validity and call
      if( !is_array($cParams) ) {
        continue;
      }

      $ret = self::trigger('dispatch.hasroute', (object)['uri' => $uri, 'method' => $method, 'pattern' => $patrn, 'params' => $cParams]);  
      if( $ret !== EventHandler::PreventAction )
        $ret = self::call_route($method, $patrn, $cParams);  
      return $ret;
    }

    //System failed to find any match
    return NULL;
  }

  private static function getURI(){
    //Seprate Segments
    $req=self::removeGetParams($_SERVER['REQUEST_URI']);
    $page=$_SERVER['SCRIPT_NAME'];

    // Try if its mod_rewrite
    if( stripos($req, $page) === FALSE && isset( $_SERVER['REDIRECT_URL'] ) ){
      $page = explode('/', $page);
      $page = array_slice($page, 0, -1);
      if (count($page) > 1)
        $page = join('/', $page)."/";
      else
        $page = '';
    }

    if(strlen($req)<strlen($page))
      $req=$page;

    //make sure the end part exists...
    $req=str_replace($page,'',$req);

    // if the starting '/' is missing append it
    if(strlen($req)=== 0 || $req[0]!=='/')
      $req = '/'.$req;

    return $req;
  }

  public static function start($method=false, $uri=false){
    self::trigger('dispatch.start');
    if(!$uri) $uri = self::getURI();
    if(!$method) $method = self::getReqMethod();
    try{
      $ret = self::trigger("dispatch.before.action", (object)['uri' => $uri, 'method' => $method]);

      if( $ret !== EventHandler::PreventAction )
        $ret = self::triggerFunction( $method, $uri );
      
      if( $ret !== EventHandler::PreventAll )
        $ret = self::trigger("dispatch.after.action", (object)['uri' => $uri,  'method' => $method, 'return' => $ret]);
    }catch(Exception $e){
      $ret = self::trigger("dispatch.exception", (object)['uri' => $uri,  'method' => $method, 'exception' => $e]);
      if( $ret !== EventHandler::ContinueEvents )
        return $ret;
      $f = Config::val('error_500');
      if( is_callable($f) ) $f($method, $uri, $e);
      else throw $e;
    }
    if( !$ret ) {
      self::trigger("dispatch.noroute", (object)['uri' => $uri,  'method' => $method]);
      $f = Config::val('error_404');
      if( is_callable($f) ) $f($method, $uri);
    }
    self::trigger('dispatch.success');
    return $ret;
  }
}

class Controllers {
  static private $PathPrefix = CONTROLLERS_ROOT;
  use Dispatchable;
  use Importable { at_root as LoadPath; }

  private static function parse_trigger($route, $values){
    $args = explode(' ', $route);
    $name = array_shift($args);
    if( !self::trigger($name, (object)['settings' => $args, 'arguments' => $values]) ){
      self::trigger("system.notrigger", (object)['name' => $name, 'settings'=> $args, 'arguments' => $values]);
    }
  }

  private static function hook_paths($map) {
    $expre = '/^(get\s+|put\s+|post\s+|delete|head\s+)(.+)/i';
    foreach($map as $route => $action ){
      if($route[0] == '/'){
        self::route( '*', $route, $action );
        continue;
      }

      $matched = preg_match($expre, $route, $matches);
      if( $matched )
        self::route( trim($matches[1]), $matches[2], $action );
      else
        self::parse_trigger($route, $action);
    }
  }

  public static function load($files, $params=[]) {
    if( is_string($files) ) $files = [$files];
    $imported = self::import_all($files, $params);
    foreach( $imported as $name => $import ) {
      self::hook_paths( $import );
    }
  }

}

class Request {
  use CoreCommons;

  private static function load_request_data(){
    $content_type = self::getContentType();
    $ret = [];
    if( $content_type == 'application/json' && $content_type == 'text/javascript' ){
      return json_decode(file_get_contents('php://input'));
    }

    switch(self::getReqMethod()) {
      case 'get':
        $ret = $_GET;
        break;
      case 'post':
        $ret = $_POST;
        break;
      case 'put':
      case 'delete':
        parse_str(file_get_contents('php://input'), $ret);
        break;
    }

    return $ret;
  }

  private $Data;

  public function __construct(){
    $this->Data = self::load_request_data();
  }
  
  public function __get($name){
    return $this->get($name);
  }

  public function get($name, $default = null){
    if( isset($this->Data[$name]) ){
      return $this->Data[$name];
    }
    return $default;
  }

  public static $GlobalRequest = NULL;
  public static function data() {
    if( self::$GlobalRequest == NULL ){
      self::$GlobalRequest = new Request();
    }
    return self::$GlobalRequest;
  }
}

class Response {
  private static $Renderers = [];

  public static function render($type) {
    $args = array_slice(func_get_args(), 1);
    $type = strtolower($type);
    if( isset(self::$Renderers[$type]) && class_exists(self::$Renderers[$type]) ){
      $cls = self::$Renderers[$type];
      $inst = new $cls();
      return call_user_func_array(array($inst, "render"), $args);
    }
    return NULL;
  }

  public static function register_renderer($name, $class){
    self::$Renderers[$name] = $class;
  }
}

class Modules {
  static private $PathPrefix = MODULES_ROOT;
  use Importable;
}

Modules::import_all(['basic_renderers.php', 'extension_loader.php']);
