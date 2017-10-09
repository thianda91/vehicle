<?php

namespace app\vehicle\model;

use think\Model;

class Driver extends Model {
	public static function getDriver($where = []) {
		$where += [ 
				'office' => session ( 'user' ) ['office'] 
		];
		$driversObj = Driver::where ( $where )->select ();
		return $driversObj;
	}
}