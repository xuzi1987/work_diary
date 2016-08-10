<?php
return array(
    /* 数据库设置 */
    'DB_TYPE'               =>  'mysql',     // 数据库类型
    'DB_HOST'               =>  'localhost', // 服务器地址
    'DB_NAME'               =>  'diary',  // 数据库名
    'DB_USER'               =>  'root',      // 用户名
    'DB_PWD'                =>  '123456',          // 密码
    'DB_PREFIX'             =>  'diary_',    // 数据库表前缀
    'DB_CHARSET'            =>  'utf8',      // 数据库编码默认采用utf8
	'URL_MODEL'             =>  '2',
		
	// 配置邮件发送服务器
	'MAIL_HOST'             =>  'smtp.163.com',//smtp服务器的名称
	'MAIL_SMTPAUTH'         =>  TRUE, //启用smtp认证
	'MAIL_USERNAME'         =>  'wyp_sea@163.com',//你的邮箱名
	'MAIL_FROM'             =>  'wyp_sea@163.com',//发件人地址
	'MAIL_FROMNAME'         =>  '芯动科技',//发件人姓名
	'MAIL_PASSWORD'         =>  'lhzlovewyp',//邮箱密码
	'MAIL_CHARSET'          =>  'utf-8',//设置邮件编码
	'MAIL_ISHTML'           =>  TRUE, // 是否HTML格式邮件
);