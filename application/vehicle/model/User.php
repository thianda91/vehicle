<?php

namespace app\vehicle\model;

use think\Model;

class User extends Model {
	
	public function getPhoneAttr($value, $data) {
		if ($value == 0)
			$value = '';
		return $value;
	}
}