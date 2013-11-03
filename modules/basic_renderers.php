<?php

namespace mimvic {
  class RenderPHP {
    function render($____template_name,$_templateData=array()){
      if(stristr($____template_name,'.php')===FALSE)
        $____template_name=$____template_name.'.php';
      
      //Create variables for each of sent data index
      extract($_templateData,EXTR_OVERWRITE);
      
      //Check existance and load file
      if(file_exists($____template_name))
        require($____template_name);
      else
        return NULL;
        
      return TRUE;
    }
  }

  class RenderJSON {
    function render($data){
      header('content-type: application/json; charset=utf-8');
      echo json_encode($data);
    }
  }

  class RenderJSONP {
    function render($data, $callback=null) {
      $req = \Request::data();
      $json = json_encode($data);


      foreach(['callback','jscallback','jsonp','jsoncallback'] as $name) {
        $val = $req->get($name);
        if( !empty($val) ) {
            $callback = $val;
            break;
        }
      }

      if(!$callback){
        header('content-type: application/json; charset=utf-8');
        echo $json;
        return;
      }

      header('content-type: text/javascript; charset=utf-8');
      echo "$callback($json);";
    }
  }

  \Response::register_renderer('php', '\mimvic\RenderPHP');
  \Response::register_renderer('json', '\mimvic\RenderJSON');
  \Response::register_renderer('jsonp', '\mimvic\RenderJSONP');

}
