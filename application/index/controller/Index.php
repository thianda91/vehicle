<?php

namespace app\index\controller;

use think\Controller;
use think\Request;

class Index extends Controller {
	public function index() {
		if (request ()->isGet ()) {
			return $this->redirect ( "default0" );
		} else {
			return $this->login ();
		}
	}
	
	public function _empty() {
		$request = Request::instance ();
		$dir = APP_PATH . $request->module () . DS . "view" . DS . $request->controller () . DS . $request->action () . "." . config ( 'template.view_suffix' );
		if (file_exists ( $dir ))
			return $this->fetch ( $request->action () );
		else {
			return $this->error ( "请求未找到，将返回上一页...(vehicle/controller/Common.php->_empty())" );
		}
	}
}
