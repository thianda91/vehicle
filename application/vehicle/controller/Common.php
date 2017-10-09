<?php

namespace app\vehicle\controller;

use think\Controller;
use think\Request;
use think\Session;
use think\Db;
use app\vehicle\model\Sysinfo;

class Common extends Controller {
	
	/**
	 * 判断是否已登录--(初始化函数优先于 $beforeActionList 配置)
	 */
	public function _initialize() {
		$request = Request::instance ();
		if ($request->controller () == 'common') {
			return $this->error ( "非法访问！你很6啊。然而我会带你回去。" );
		}
		$suser = input ( 'session.user/a' );
		if (! $suser) {
			return $this->error ( '您未登录或登录超时，请先登录！', 'index/index' );
		} else {
			foreach ( $suser as $k => $u ) {
				Session::set ( "user." . $k, $u );
			}
		}
		$this->assign ( 'version', $this->getSysInfo ( "version" ) );
	}
	public function bugReport() {
		if (Request::instance ()->isGet ()) {
			return $this->display ( "<h3>??????</h3>" );
		} else {
			$data = input ( "post." );
			// return dump($data);
			return Db::name ( 'bugreport' )->insert ( $data );
			// return $this->success("");
		}
	}
	/**
	 * 获取dhtmlx form 中 select/combo的options
	 * @return void|unknown[][]|mixed[][]|void[][]|boolean[][]|NULL[][]
	 */
	public function getOptions() {
		if (! input ( "?param.optionName" ))
			return $this->error ( "请求无效" );
			$name = input ( 'param.optionName' );
			$res = config ( $name );
			// $res = Sysinfo::get(['label'=>$name]);
			$options = [ ];
			foreach ( $res as $r ) {
				$options [] = [
						'text' => $r,
						'value' => $r
				];
			}
			return $options; // 自动处理成json()
	}
	/**
	 * 获取系统属性
	 *
	 * @param unknown $where        	
	 * @return unknown
	 */
	public function getSysInfo($label, $isJson = false) {
		// return $this->toArray ( Sysinfo::where ( "label", $label )->select () ) [0] ['value'];
		$sysinfo = Sysinfo::where ( "label", $label )->value ( "value" );
		if ($isJson) {
			return json_decode ( $sysinfo );
		} else
			return $sysinfo;
	}
	/**
	 * 含Model类的数组转成含数组对象的数组
	 *
	 * @param unknown $modelArray        	
	 * @param array $hidden        	
	 */
	public function toArray($modelArray, $hidden = []) {
		if ($modelArray == null)
			return [ ];
		$array = [ ];
		foreach ( $modelArray as $m ) {
			$array [] = $m->hidden ( $hidden )->toArray ();
		}
		return $array;
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