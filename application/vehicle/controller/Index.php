<?php

namespace app\vehicle\controller;

use app\vehicle\model\User;
use think\Session;
use think\Controller;
use think\Request;

class Index extends Controller {
	public function index() {
		if (request ()->isGet ()) {
			return $this->redirect ( "default" );
		} else {
			return $this->login ();
		}
	}
	protected function login() {
		$input = input ( "post." );
		if ($input ['username'] == null || $input ['password'] == null)
			return $this->redirect ( "default" );
		$userModel = new User ();
		$userModel->save ( [ 
				'pwd' => $this->pwdRule ( $input ['password'] ) 
		], [ 
				'loginName' => $input ['username'] 
		] );
		$user = $userModel->where ( [ 
				"loginName" => $input ['username'] 
		] )->find ();
		if ($user == null) {
			$this->error ( "账户不存在，请重试。" );
		} else {
			$user = $user->toArray ();
			if ($user ['pwd'] == $this->pwdRule ( $input ['password'] )) {
				foreach ( $user as $k => $u ) {
					Session::set ( "user." . $k, $u );
				}
				$index = array_search ( $user ['role'], config ( 'role_names' ) );
				if ($index < 5) {
					return $this->success ( "登录成功，" . $user ['name'] . "，正在跳转", "Manage/todo", "", 1 );
				} else if (strpos ( $user ['role'], "用户" ) !== false) {
					return $this->success ( "登录成功，" . $user ['name'] . "，正在跳转", "User/browser", "", 1 );
				} else {
					return $this->error ( "权限获取异常，请联系管理员！", "", "", 10 );
				}
			} else {
				return $this->error ( "账户存在，密码错误，请重试！" );
			}
		}
	}
	public function loginout() {
		Session::delete ( "user" );
		return $this->success ( "已注销登录", "index/index", "", 1 );
	}
	/**
	 * 获取dhtmlx form 中 select/combo的options
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
	 * 密码加密规则、与Controller\User的同名函数一样。
	 *
	 * @param string $pwd        	
	 * @param string $md5        	
	 * @return string
	 */
	protected function pwdRule($pwd = '', $md5 = false) {
		if (! $md5)
			$pwd = md5 ( $pwd );
		return md5 ( config ( "pwd_salt" ) . $pwd );
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
	/**
	 * 用户注册
	 *
	 * @return void|mixed|string
	 */
	public function register() {
		if (request ()->isPut ()) {
			if ($this->checkLoginName ( input ( 'loginName' ) )) {
				return $this->success ( "登录名可用。" );
			} else {
				return $this->error ( "登录名已被占用，请重试。" );
			}
		} else if (request ()->isPost ()) {
			$loginName = input ( "loginName" );
			$name = input ( "name" );
			if ($loginName != null && $this->checkLoginName ( $loginName ) && $name != null) {
				$user = new User ( input ( 'post.' ) );
				$user ['pwd'] = $this->pwdRule ( "123456" );
				$user ['role'] = '用户';
				$user->allowField ( true )->save ();
				return $this->success ( "注册成功，前往登陆", "default", 3 );
			} else {
				return $this->error ( "登录名已被占用!!!!!!!" );
			}
			// return $this->fetch ();
		} else {
			$this->assign ( [ 
					'office_names' => json_encode ( config ( 'office_names' ), 256 ) 
			] );
			return $this->fetch ();
		}
	}
	protected function checkLoginName($loginName) {
		$tmpuser = db ( 'user' )->where ( [ 
				'loginName' => $loginName 
		] )->find ();
		if ($tmpuser != null) {
			return false;
		} else {
			return true;
		}
	}
}
