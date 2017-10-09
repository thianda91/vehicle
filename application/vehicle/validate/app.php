<?php

namespace app\vehicle\validate;

use think\Validate;

class App extends Validate {
	//	本 php 未使用
	protected $rule = [ 
			'name' => 'require|max:25',
			'email' => 'email' 
	];
}