<?php

namespace app\vehicle\model;

use think\Model;

class Sysinfo extends Model {
	protected $pk = 'id';
	protected $type = [ 
			'value' => 'array' 
	];
	protected $auto = [ 
			'value' 
	];
	public function setValueAttr($value) {
		return json_encode ( $value, JSON_UNESCAPED_UNICODE );
	}
}