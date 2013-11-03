<?php 

namespace mimvic {

  if( !defined('EXTENSIONS_ROOT') ) {
    define( 'EXTENSIONS_ROOT', MIMVIC_ROOT.DIRECTORY_SEPARATOR.'modules/extensions');
  }

  class ExtensionLoadException extends \Exception {}

  class Extensions {
    static private $PathPrefix = EXTENSIONS_ROOT;
    use \Importable;
  }

  class ComposerExtensions {
    static private $PathPrefix = MIMVIC_ROOT;
    use \Importable { at_root as public Path; }
  }

  class UserExtensions {
    static private $PathPrefix = MIMVIC_ROOT;
    use \Importable { at_root as public Path; }
  }

  trait LoaderEvent {

    use \NamedEvent;

    protected function load($name, $arguments){
      throw new Exception("Not implemented");
    }

    private function parse_and_load($extension) {
      $name = $extension;
      $arguments = [];
      if(is_array($extension))
        $name = array_shift($arguments);
      try{
        $this->load($name, $arguments);
      }catch(\Exception $e){
        throw new ExtensionLoadException($e);
      }
    }

    public function handle($args) {
      if(is_string($args->arguments)) {
        $args->arguments = explode(',', $args->arguments);
      }
      foreach($args->arguments as $extension) {
        $this->parse_and_load($extension);
      }
      return \EventHandler::ContinueEvents;
    }
  }


  class ExtensionLoadEvent implements \EventHandler {
    use LoaderEvent;
    protected function load($name, $arguments) {
      return Extensions::import($name.'.php', $arguments);
    }
  }

  class ComposerLoadEvent implements \EventHandler {
    use LoaderEvent;
    protected function load($name, $arguments) {
      try{
        return ComposerExtensions::import($name.'/Autoload.php', $arguments);
      }catch(Exception $e){
        return ComposerExtensions::import($name.'.php', $arguments);
      }
    }
  }

  class UserLoadEvent implements \EventHandler {
    use LoaderEvent;
    protected function load($name, $arguments) {
      return UserExtensions::import($name.'.php', $arguments);
    }
  }

  return ['imported' => function(){
    \Controllers::bind('extensions', new ExtensionLoadEvent);
    \Controllers::bind('composer', new ComposerLoadEvent);
    \Controllers::bind('libraries', new UserLoadEvent);
  }];

}
