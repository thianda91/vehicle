<?php
// 配置文件
return [ 
		'view_replace_str' => [ 
				'__ROOT__' => '/vehicle' ,
				'__PUBLIC__'=>''
		],
		'cookie'                 => [
				// cookie 名称前缀
				'prefix'    => '',
				// cookie 保存时间
				'expire'    => 0,
				// cookie 保存路径
				'path'      => '/',
				// cookie 有效域名
				'domain'    => '',
				//  cookie 启用安全传输
				'secure'    => false,
				// httponly设置
				'httponly'  => '',
				// 是否使用 setcookie
				'setcookie' => true,
		],
		'session' => [ 
				'id' => '',
				'var_session_id' => '',
				'prefix' => 'think',
				// 驱动方式 支持redis memcache memcached
				'type' => '',
				'auto_start' => true 
		],
		// 'expire' => 1800
		'paginate' => [ 
				'type' => 'bootstrap',
				'var_page' => 'page' 
		],
		// 'list_rows' => 5,
		
		'pwd_salt' => 'tlvehicle',
		'role_names' => [ 
				"车辆调度",
				"副总",
				"行政事务",
				"经理",
				"主任",
				"用户" 
		],
		'default_pwd'=>'123456',
		
		'office_names'=>[
				"tlyd市公司",
				"tlyd银州区分公司",
				"tlyd铁岭县分公司",
				"tlyd开原分公司",
				"铁岭移清河分公司",
				"tlyd西丰分公司",
				"tlyd昌图分公司",
				"tlyd调兵山分公司",
		],
		
];