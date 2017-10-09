<?php

namespace app\vehicle\controller;

use think\Db;
use app\vehicle\model\App;
use app\vehicle\model\Driver;
use app\vehicle\model\User as UUser;
use app\index\controller\Index;
use think\Config;
use think\Session;

class User extends Common {
	protected $beforeActionList = [ 
			'ifPwdDefault' 
	];
	protected function ifPwdDefault() {
		if (session ( "user.pwd" ) == $this->pwdRule ( "123456" ) && ! in_array ( request ()->action (), [ 
				"setting",
				'setpwd' 
		] )) {
			return $this->redirect ( "setting", null, 302, [ 
					'foucePwd' => true 
			] );
		}
	}
	public function index() {
		return $this->redirect ( "browser" );
	}
	
	/**
	 * 我的用车
	 *
	 * @return void|string|mixed|string
	 */
	public function browser() {
		// return dump(session("user"));
		$list = App::browser ( session ( "user" ) );
		// return $list ;
		// return dump($list[0]->jsonSerialize()['data']) ;
		$this->assign ( [ 
				'list' => $list [0],
				'title' => '我的用车',
				'msg' => '这里可以查看本部门的用车信息。',
				'page' => $list [1] 
		] );
		return $this->fetch ( 'common/view' );
	}
	/**
	 * 用车单详细信息
	 * 根据内置的appovalText变量判断审批方式（同意/不同意、车辆调度）
	 *
	 * @return mixed|string|void
	 */
	public function detail() {
		if (input ( '?param.d' )) {
			 //$item = App::handleApp(App::get ( input ( 'param.d' ) ));
			$item = App::detail ( session ( "user" ), input ( 'param.d' ) );
			//$app = new App();
			//$item = $app->get(input ( 'param.d' ));
			//return dump($item);
			if ($item != null) {
				$this->assign ( [ 
						'appovalText' => config ( 'role_names' ),
						'item' => $item 
				] );
				return $this->fetch ( 'common/detail' );
			} else {
				return $this->error ( "获取明细为空" );
			}
		}
		return $this->error ( "不可以这样访问哦！", null, null, 0 );
	}
	/**
	 * 申请用车
	 *
	 * @return void|string|mixed|string
	 */
	public function apply() {
		$office = session ( "user.office" );
		// return dump($bb);
		$this->assign ( [ 
				'beginPoint' => $this->getSysInfo ( $office . "-出发点", true ),
				'destination' => $this->getSysInfo ( $office . "-目的地", true ),
				'cuser' => session ( "user" ),
				'title' => '申请用车' 
		] );
		return $this->fetch ( 'user/apply' );
	}
	public function apply_() {
		if (! request ()->isPost ()) {
			return $this->redirect ( "apply" );
		} else {
			$cuser = session ( "user" );
			$data ['toType'] = input ( "param.toType" );
			$data ['useNum'] = input ( "param.useNum" );
			$data ['beginPoint'] = input ( "param.beginPoint" );
			$data ['destination'] = input ( "param.destination" );
			$data ['beginTime'] = input ( "param.beginTime" );
			$data ['returnTime'] = input ( "param.returnTime" );
			$data ['reson'] = input ( "param.reson" );
			$data ['driverPre'] = input ( "param.driverPre" );
			$data ['userId'] = $cuser ['id'];
			$data ['depName'] = $cuser ['office'] . $cuser ['dep2'] . $cuser ['dep'];
			App::create ( $data );
			return $this->redirect ( "browser" );
		}
		return $this->error ( "访问无效。" );
	}
	
	/**
	 * 处理审批操作//未做数据校验
	 */
	public function updateApp() {
		// return input("driverId");
		$str = '';
		$status = input ( 'post.status' );
		if (is_null ( $status )) {
			return $this->error ( "你访问的啥??status is NULL	————Xianda" );
		}
		if ( input ( 'post.driverInfo' ) != null)
			$str .= " ,driverInfo = '" . input ( 'post.driverInfo' ) . "'";
		if (input ( 'post.appRank' ) != null)
			$str .= " ,appRank = '" . input ( 'post.appRank' ) . "'";
		$info = input ( 'post.info' );
		if ($info == null) {
			return $this->error ( "你访问的啥??info is NULL	————Xianda" );
		}
		$info = $info . "，" . (date ( "Y-m-d H:i:s" )) . "；";
		$sql = "update `" . config ( 'database.prefix' ) . "app` set `status` = '" . $status . "'" . $str . ",`logs` = (CASE WHEN `logs` = '' OR `logs` IS NULL THEN '" . $info . "' ELSE CONCAT(`logs`, '" . $info . "') END ) where `id` = " . input ( 'get.id' ) . "";
		// return $sql;
		$int = Db::execute ( $sql );
		if ($int > 0) {
			return $this->success ( '操作成功', null, $int );
		} else {
			return $this->error ( "操作数据库异常", null, $int );
		}
	}
	/**
	 * 个人设置（修改资料）
	 *
	 * @return void|mixed|string
	 */
	public function setting($id = '') {
		if (request ()->isPost ()) {
			if ($id == '')
				$id = session ( "user.id" );
			$data = input ( "post." );
			$uuser = new UUser ();
			$tmpuser = db ( 'user' )->where ( [ 
					'loginName' => $data ['loginName'] 
			] )->find ();
			// return dump($tmpuser);
			if ($tmpuser ['id'] != null && $tmpuser ['id'] != $id) {
				return $this->error ( "登录名已被占用，请重试。" );
			} else {
				$uuser->allowField ( true )->save ( $data, [ 
						'id' => $id 
				] );
				$this->reFreshUser ( session ( "user.id" ) );
				return $this->success ( "操作成功执行！", "setting" );
			}
		} else {
			$this->assign ( [ 
					'msg' => "可以直接修改个人资料/密码",
					'foucePwd' => session ( "foucePwd" ) 
			] );
			return $this->fetch ( "user/setting" );
		}
	}
	
	/**
	 * 修改密码
	 *
	 * @param string $url        	
	 * @param unknown $id        	
	 * @return void|string
	 */
	public function setPwd($id = '') {
		if (request ()->isPut ()) {
			if ($id == null) {
				// 用户修改密码操作
				$id = session ( "user.id" );
				$pwd = input ();
				if ($pwd ['password'] == $pwd ['password_confirm']) {
					$newPwd = $this->pwdRule ( $pwd ['password'] );
				} else {
					Session::flash ( 'foucePwd', true );
					return $this->error ( "两次密码不一致。" );
				}
			} else {
				// 重置密码操作
				$newPwd = $this->pwdRule ( config ( 'default_pwd' ) );
			}
			UUser::where ( 'id', $id )->update ( [ 
					'pwd' => $newPwd 
			] );
			$this->reFreshUser ( session ( "user.id" ) );
			return $this->success ( "修改成功。以后请使用新密码登录", 'setting' );
		} else {
			return $this->error ( "请求类型不对呀:" . request ()->method () );
		}
	}
	
	/*
	 * 密码加密规则
	 */
	protected function pwdRule($pwd = '', $md5 = false) {
		if (! $md5)
			$pwd = md5 ( $pwd );
		return md5 ( config ( "pwd_salt" ) . $pwd );
	}
	
	/**
	 * 刷新user个人信息
	 *
	 * @param unknown $userID        	
	 * @return Model\User
	 */
	protected function reFreshUser($userID) {
		$newUser = UUser::where ( 'id', $userID )->find ();
		session ( "user", null );
		session ( "user", $newUser );
		return $newUser;
	}
	public function driverRank() {
		$list = App::evaluate ( session ( "user" ) );
		$this->assign ( [ 
				'list' => $list [0],
				'page' => $list [1],
				'title' => '用车评价',
				'msg' => '这里可以对自己“已申请的用车” 并且 状态为“派车同意”的申请单做出评价。' 
		] );
		return $this->fetch ( 'common/view' );
	}
	public function stars() {
		return $this->fetch ( "user/stars" );
	}
	
	/**
	 * 获取司机车辆信息-预选和派车时使用
	 *
	 * @return void|string
	 */
	public function getDriverList() {
		$driver = $this->toArray ( Driver::getDriver (), [ ] );
		$result = [ ];
		foreach ( $driver as $d ) {
			$result [] = [ 
					'id' => $d ['id'],
					'label' => $d ['carNo'] . '-' . $d ['carType'] . '-' . $d ['driverName'],
					'value' => $d ['carNo'] . ' ' . $d ['carType'] . ' ' . $d ['driverName'] . ' ' . $d ['driverPhone'] 
			];
		}
		$this->assign ( [ 
				'drivers' => $result,
				'json' => json_encode ( $result, 256 ) 
		] );
		return $this->fetch ( "common/getDriverList" );
	}
}