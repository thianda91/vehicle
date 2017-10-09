<?php

namespace app\vehicle\controller;

use app\vehicle\model\App;
use app\vehicle\model\User;
use app\vehicle\model\Driver;
use think\Db;
use think\Cookie;

class Manage extends \app\vehicle\controller\User {
	protected $adminPage = [ 
			'settinguser',
			'settingdriver' 
	];
	protected $beforeActionList = [ 
			'checkAuth',
			'ifPwdDefault' 
	];
	protected function checkAuth() {
		$cuser = session ( "user" );
		$index = array_search ( $cuser ['role'], config ( 'role_names' ) );
		if ($index > 4) {
			return $this->error ( "您的权限不足，无法访问。", "user/index" );
		} else {
			if (in_array ( request ()->action (), $this->adminPage )) {
				if ($index > 0) {
					return $this->error ( "您的权限不够高哦。", "user/index" );
				}
			}
			return $index;
		}
	}
	public function index() {
		return $this->redirect ( "todo" );
	}
	public function todo($f = true, $msg = '这里可以对本部门申请单进行审批') {
		$cuser = session ( "user" );
		$index = $this->checkAuth ();
		if ($index == 4 || $index == 3) {
			$list = App::leaderTodo ( $cuser, $f );
		} else if ($index == 2) {
			$list = App::xzswTodo ( $cuser, $f );
		} else if ($index == 1) {
			$list = App::managerTodo ( $cuser, $f );
		} else if ($index == 0) {
			$list = App::dispatchTodo ( $cuser, $f );
		} else {
			$list = [ ];
		}
		// return dump($list);
		$this->assign ( [ 
				'title' => '用车审批',
				'list' => $list [0],
				'page' => $list [1],
				'msg' => $msg,
				'istodo' => $f ? "yes" : null 
		] );
		return $this->fetch ( "common/view" );
	}
	public function done() {
		return $this->todo ( false, '这里显示自己已审批的申请单。' );
	}
	/**
	 * 报表统计
	 */
	public function tabulation($limit = 10) {
		$whereTime = '';
		$data = Db::view ( "app", "id,creTime,depName,destination,beginTime,reson,driverInfo" )->view ( "user", "name", "user.id=app.userId", 'LEFT' )->where ( 'depName', 'like', session ( "user.office" ) . '%' );
		// return dump ( $data );
		if (request ()->isPut ()) {
			if (input ( "param.startTime" ) == '') { // 输入起始条件为空，默认查询本月数据。
				$data->whereTime ( "creTime", "y" );
			} else {
				if (input ( "param.stopTime" ) != '') { // 根据输入的起止时间段查询数据。
					$data->whereTime ( "creTime", "between", [ 
							input ( "param.startTime" ),
							input ( "param.stopTime" ) 
					] );
				} else { // 有起始，无截至，默认查询起始至今的数据。
					$data->whereTime ( "creTime", ">=", input ( "param.startTime" ) );
				}
			}
			//return dump(input ( "param.startTime" ));
			if(input('param.filterInput') != ''){
				$data->where('driverInfo',input('param.filterInput'));
			}
		} else {	//	非put请求
			cookie ( 'startTime', null );
			cookie ( 'stopTime', null );
			cookie ( 'filterInput', null );
		}
		cookie ( 'startTime', input ( "param.startTime" ) );
		cookie ( 'stopTime', input ( "param.stopTime" ) );
		cookie ( 'filterInput', input ( "param.filterInput" ) );
		//return $data->select(false) ;
		$data = $data->order ( 'creTime,id' )->paginate ( $limit );
		// $data = $data->whereTime ( "creTime", "y" )->order ( 'creTime,id' )->paginate ( $limit );
		// return dump($data) ;
		$page = $data->render ();
		$json = json_encode($data->items(),256);
		$this->assign ( [ 
				'title' => "报表统计",
				//'data' => $data,
				'page' => $page,
				'json' => $json
		] );
		return $this->fetch ();
	}
	public function settingDriver() {
		if (request ()->isPost ()) { // 修改
			$data = input ( "param." );
			$driver = new Driver ();
			$driver->allowField ( true )->save ( $data, [ 
					'id' => input ( "param.id" ) 
			] );
			return Driver::getDriver ();
		} else if (request ()->isPut ()) { // 新增
			$data = input ( "param." );
			$driver = new Driver ();
			$driver->allowField ( true )->save ( $data );
			return $this->success ( "新增成功" );
		} else if (request ()->isDelete ()) { // 删除
			$id = input ( "param.id" );
			db ( 'driver' )->delete ( $id );
			return Driver::getDriver ();
		} else {
			$result = Driver::getDriver ();
			$this->assign ( [ 
					'list' => json_encode ( $result, 256 ),
					'office_names' => json_encode ( config ( 'office_names' ), 256 ) 
			] );
			return $this->fetch ();
		}
	}
	public function settingUser() {
		if (request ()->isPost ()) {
			return $this->setting ( input ( "id" ) );
		} else {
			$result = $this->reFreshUsers ();
			$this->assign ( [ 
					'list' => $result,
					'default_pwd' => config ( 'default_pwd' ) 
			] );
			return $this->fetch ();
		}
	}
	/**
	 * 重置密码
	 *
	 * @param string $url        	
	 * @param string $id        	
	 */
	public function resetPwd() {
		if (request ()->isPut ()) {
			return $this->setPwd ( input ( "param.id" ) );
		}
	}
	/**
	 * 查询用户列表
	 *
	 * @param unknown $userID        	
	 */
	public function reFreshUsers() {
		$where = [ 
				'office' => session ( "user.office" ) 
		];
		if (input ( "?param.arg" ))
			$where += [ 
					'loginName' => [ 
							'like',
							input ( "param.arg" ) 
					] 
			];
		$list = User::where ( $where )->field ( "id,loginName,name,dep,dep2,office,phone,role" )->select ();
		$result = [ ];
		if ($list != null) {
			foreach ( $list as $l ) {
				$result [] = $l->toArray ();
			}
		}
		return json_encode ( $result, 256 );
	}
	public function systemsetting() {
		return $this->fetch ();
	}
}
