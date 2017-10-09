<?php

namespace app\vehicle\model;

use think\Model;

class App extends Model {
	protected $autoWriteTimestamp = 'datetime';
	protected $createTime = 'creTime';
	protected $updateTime = false;
	protected $type = [ 
			'status' => 'integer',
			'userId' => 'integer',
			'useNum' => 'integer',
			'toType' => 'integer',
			'appRank' => 'integer',
			'beginTime' => 'datetime:Y-m-d H:m',
			'returnTime' => 'datetime:Y-m-d H:m' 
	];
	protected $insert = [ 
			'status' => 0 
	];
	
	public function getAppRankAttr($value, $data){
		if($value==0)
			return "未评价";
		else 
			return $value."分";
	}
	
	/**
	 * 定义数据表中不存在的字段statusText
	 */
	public function getStatusTextAttr($value, $data) {
		$status = [ 
				0 => '待审批',
				1 => '中心/部门拒绝',
				2 => '中心/部门同意',
				3 => '行政/副总拒绝',
				4 => '行政/副总同意',
				5 => '派车拒绝',
				6 => '派车同意',
				7 => '已评价',
				11 => '用户撤销' 
		];
		
		return array_key_exists($data ['status'],$status)?$status [$data ['status']]:"[状!态!异!常!]";
	}
	/**
	 * 定义数据表中不存在的字段toTypeText
	 */
	public function getToTypeTextAttr($value, $data) {
		$toType = [ 
				0 => '市内',
				1 => '市外',
				2 => '节假日用车' 
		];
		return $toType [$data ['toType']];
	}
	/**
	 * 查询详细信息
	 * @param unknown $user
	 * @param unknown $id
	 * @return unknown[]
	 */
	public static function detail($user,$id){
		$where = App::whereDep ( $user );
		$where +=['aa.id'=>$id];
		$field = "aa.id as id,aa.status as status,depName,creTime,`name` as userName,beginPoint,toType,destination,reson,beginTime,returnTime,logs,driverPre,driverInfo,appRank";
		$item =  App::alias ( "aa" )->join ( '__USER__ uu', 'uu.id = aa.userId' )->field ( $field )->where ( $where )->order ( 'creTime desc,id desc' )->find();
		//return $item;
		$item = App::handleApp ($item);
		return $item;
	}
	/**
	 * 我的用车
	 */
	public static function browser($user, $limit = 20) {
		$where = App::whereDep ( $user );
		return App::queryTodo ( $where, $limit );
	}
	
	/**
	 * 主任/经理待审批
	 */
	public static function leaderTodo($user, $f = false) {
		$where = App::whereDep ( $user );
		$where += $f ? [ 
				'aa.status' => 0 
		] : [ 
				'logs' => [ 
						'like',
						'%' . $user ['name'] . '%' 
				] 
		];
		
		return App::queryTodo ( $where );
	}
	/**
	 * 行政事务审批
	 */
	public static function xzswTodo($user, $f = false) {
		$where = App::whereDep ( $user );
		$where += $f ? [ 
				'toType' => 1,
				'aa.status' => 2 
		] : [ 
				'logs' => [ 
						'like',
						'%' . $user ['name'] . '%' 
				] 
		];
		return App::queryTodo ( $where );
	}
	
	/**
	 * 副总管理审批
	 */
	public static function managerTodo($user, $f = false) {
		$where = App::whereDep ( $user );
		$where += $f ? [ 
				'toType' => 2,
				'aa.status' => 2 
		] : [ 
				'logs' => [ 
						'like',
						'%' . $user ['name'] . '%' 
				] 
		];
		return App::queryTodo ( $where );
	}
	/**
	 * 车辆管理审批
	 */
	public static function dispatchTodo($user, $f = false) {
		if ($f) {
			//$where = "`depName` like '" . $user ['office'] . "%' AND ((`toType` = 0 AND `aa`.`status` = 2) or (`toType` > 0 AND `aa`.`status` = 4))";
			$where = "`depName` like '" . $user ['office'] . "%' AND `aa`.`status` < 5";
		} else {
			$where = App::whereDep ( $user );
			$where += [ 
					'logs' => [ 
							'like',
							'%' . $user ['name'] . '%' 
					] 
			];
		}
		return App::queryTodo ( $where );
	}
	
	/**
	 * 用车后待评价
	 *
	 * @param unknown $user        	
	 * @param string $f        	
	 */
	public static function evaluate($user) {
		$where = [ 
				'userId' => $user ['id'],
				'aa.status' => 6 
		];
		
		return App::queryTodo ( $where );
	}
	
	protected static function whereDep($user) {
		$dep = App::getDepFullName ( $user );
		return [ 
				'depName' => [ 
						'like',
						$dep . '%' 
				] 
		];
	}
	protected static function getDepFullName($user) {
		return $user ['office'] . $user ['dep2'] . $user ['dep'];
	}
	/**
	 * 查询 
	 */
	protected static function queryTodo($where = '', $limit = 20) {
		// return dump ( $where );
		$field = "aa.id as id,aa.status as status,destination,reson,`name` as userName,creTime";
		// $list = App::handleApp ( App::alias ( "aa" )->join ( '__USER__ uu', 'uu.id = aa.userId' )->field ( $field )->where ( $where )->limit ( $limit )->order ( 'creTime desc,id desc' )->paginate ( 5 ) );
		$list = App::alias ( "aa" )->join ( '__USER__ uu', 'uu.id = aa.userId' )->field ( $field )->where ( $where )->order ( 'creTime desc,id desc' )->paginate ( $limit );
		$page = $list->render ();
		// $list->jsonSerialize();
		// session ( 'list', $list );
		/*
		 * foreach ($list as $l){
		 * $result[] +=[$l['id']=>$l];
		 * }
		 */
		return [ 
				$list,
				$page 
		];
	}
	/**
	 * 处理一个包含模型对象的二维数组 into包含模型数据数组的二维数组
	 * Xianda自定义函数
	 *
	 * @param array $app        	
	 * @return array
	 */
	public static function handleApp($data) {
		if ($data == null) {
			return [ ];
		} else if (is_array ( $data )) {
			foreach ( $data as $a ) {
				$ans = App::handle ( $a );
				$result [] = $ans;
			}
			return $result;
		} else
			return App::handle ( $data );
	}
	protected static function handle($a) {
		$ans = $a->toArray ();
		$ans ['statusText'] = $a->statusText;
		$ans ['toTypeText'] = $a->toTypeText;
		if ($ans ['appRank'] == 0)
			$ans ['appRank'] = "未评价";
		return $ans;
	}
	public static function select($where = '', $limit = '') {
		return App::queryTodo ( $where, $limit );
	}
}