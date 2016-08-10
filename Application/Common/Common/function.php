<?php
/**
 * 邮件发送函数
 */
function sendMail($to, $title, $content) {
	Vendor('PHPMailer.class#phpmailer');
	$mail = new PHPMailer(); //实例化
	$mail->IsSMTP(); // 启用SMTP
	$mail->Host=C('MAIL_HOST'); //smtp服务器的名称（这里以QQ邮箱为例）
	$mail->SMTPAuth = C('MAIL_SMTPAUTH'); //启用smtp认证
	$mail->Username = C('MAIL_USERNAME'); //你的邮箱名
	$mail->Password = C('MAIL_PASSWORD') ; //邮箱密码
	$mail->From = C('MAIL_FROM'); //发件人地址（也就是你的邮箱地址）
	$mail->FromName = C('MAIL_FROMNAME'); //发件人姓名
	$to_emails = explode(',', $to);
	foreach ($to_emails as $v){
		$mail->AddAddress($v, $v);
	}
	$mail->WordWrap = 50; //设置每行字符长度
	$mail->IsHTML(C('MAIL_ISHTML')); // 是否HTML格式邮件
	$mail->CharSet = C('MAIL_CHARSET'); //设置邮件编码
	//$mail->SMTPDebug = true;
	//$mail->Timeout = 60;
	$mail->Subject = $title; //邮件主题
	$mail->Body = $content; //邮件内容
	$mail->AltBody = "这是一个纯文本的身体在非营利的HTML电子邮件客户端"; //邮件正文不支持HTML的备用显示
	return($mail->Send());
}

function getQuarterByMonth($date){
	$month = substr($date,-2);
	$Q = ceil($month/3);
	return $Q;
}

function readExcel($file){
	Vendor('Excel.reader');
	$data = new Spreadsheet_Excel_Reader();
	//$data->setOutputEncoding('CP936');
	$data->setOutputEncoding('UTF-8');
	$data->read($file);
	return $data;
}

function ftp_upload($local, $ftp_path){
	$results = M('ftpsettings')->select();
	if($results){
		$ftpSettings = unserialize(innoDecode($results[0]['settings']));
	}else{
		return;
	}
	
	Vendor('ftp.ftp');
	$ftps = new ftps();
	$ftps->connect($ftpSettings['ip'], $ftpSettings['username'], $ftpSettings['password'], $ftpSettings['port'], false, false, 3600);
	 
	//$local = iconv('utf-8', 'gbk', $local);
	$path = explode('/', $local);
	$remote = '/'.$path[count($path)-1];
	if($ftp_path){
		//$ftps->mkdir('/public_html/statics/'.$ftp_path);
		$remote = '/'.$ftp_path.'/'.$path[count($path)-1];
	}
	
	$ftps->put($remote,$local);
	 
	if(!$ftps->get_error()){
		$ftps->close();
		return 'success';
	}else{
		$ftps->close();
		return 'fail:'.$ftps->get_error();
	}
}

function sftp_download($host, $remote, $local, $filesize){
	if($host == '192.168.212.66'){
		$username = 'root';
		$password = 'wh70ll';
	}
	if($host == '192.168.212.67'){
		$username = 'root';
		$password = 'wh70ll';
	}
	if($host == '192.168.212.68'){
		$username = 'root';
		$password = 'wh70ll';
	}
	if($host == '192.168.212.69'){
		$username = 'root';
		$password = 'wh70ll';
	}
	if($host == '192.168.212.20'){
		$username = 'root';
		$password = 'wh70ll';
		$remote = str_replace('ftproot', 'Innosilicon/Old_Ftproot', $remote);
	}
	if($host && $username && $password){
		set_include_path(get_include_path() . PATH_SEPARATOR . 'phpseclib');
		Vendor('phpseclib.Net.SFTP');
		$sftp = new Net_SFTP($host);
		if (!$sftp->login($username, $password)) {
			exit('Login Failed');
		}
		if($filesize){
			return $sftp->size($remote);
		}
		$sftp->get($remote, $local);
	}
}

function checkFileName($filename){
	//$filename = iconv('utf-8', 'gb2312', $filename);
	$file_split = explode(".", $filename);
	$ext = end($file_split);
	$filename = substr($filename, 0, -(strlen($ext)+1));
	return str_replace(' ', '-', $filename);
}

function getRandChar($length){
	$str = null;
	$strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
	$max = strlen($strPol)-1;

	for($i=0;$i<$length;$i++){
		//rand($min,$max)生成介于min和max两个数之间的一个随机整数
		$str.=$strPol[rand(0,$max)];
	}
	return $str;
}

function encrytByGpg($key, $local){
	shell_exec("python3 ./encrypt.py /home/www/Public/keys/{$key} /Storage/Upload/{$local}");
}

function senMessage($ip, $msg){
	$msg = base64_encode($msg);
	$ip = '192.168.233.139';
	shell_exec("python3 ./PythonMessages/MsgRouter.py {$ip} {$msg}");
}

function sendHttpSocket($data, $method){
	$referrer = "";
	$url_info = parse_url('http://'.$_SERVER['HTTP_HOST'].'/Task/Index/'.$method.'.html');
	if($referrer == "") $referrer = $_SERVER["SCRIPT_URI"];
	foreach($data as $key=>$value) {
		$values[] = "$key=".urlencode($value);
	}
	$data_string = implode("&",$values);
	if(!isset($url_info["port"])) $url_info["port"]=80;
	$header .= "POST ".$url_info["path"]." HTTP/1.1\n";
	$header .= "Host: ".$url_info["host"]."\n";
	$header .= "Referer: $referrer\n";
	$header .= "Content-type: application/x-www-form-urlencoded\n";
	$header .= "Content-length: ".strlen($data_string)."\n";
	$header .= "Connection: close\n";
	$header .= "\n";
	$header .= $data_string."\n";
	$fp = fsockopen($url_info["host"],$url_info["port"]);
	fputs($fp, $header);
}

function innoEncode($string = '', $skey = 'inno_2016_wh') {
	$strArr = str_split(base64_encode($string));
	$strCount = count($strArr);
	foreach (str_split($skey) as $key => $value)
		$key < $strCount && $strArr[$key].=$value;
	return str_replace(array('=', '+', '/'), array('O0O0O', 'o000o', 'oo00o'), join('', $strArr));
}

function innoDecode($string = '', $skey = 'inno_2016_wh') {
	$strArr = str_split(str_replace(array('O0O0O', 'o000o', 'oo00o'), array('=', '+', '/'), $string), 2);
	$strCount = count($strArr);
	foreach (str_split($skey) as $key => $value)
		$key <= $strCount  && isset($strArr[$key]) && $strArr[$key][1] === $value && $strArr[$key] = $strArr[$key][0];
	return base64_decode(join('', $strArr));
}

function prDates($start, $end) {
	$results = array();
	$dt_start = strtotime($start);
	$dt_end   = strtotime($end);
	do {
		$results[] = date('Y-m-d', $dt_start);
	} while (($dt_start += 86400) <= $dt_end);

	return $results;
}

function getPostData($post_data, $name){
	$results = array();
	foreach ($post_data as $key=>$value){
		if(preg_match("/{$name}/", $key)){
			$results[] = $value;
		}
	}
	return $results;
}

function getCurrentNav(){
	$parent = 1;
	$sub = 1;
	$title = '';
	switch (CONTROLLER_NAME){
		case 'Diary':
			if((ACTION_NAME == 'index' && I('view') == 'self') || ACTION_NAME == 'edit'){
				$title = '我的日志';
			}
			if(ACTION_NAME == 'index' && I('view') == 'development'){
				$title = '研发日志';
				$parent = 3;
				if(I('srchregdatestart') == date('Y-m-d', time()) && I('srchregdateend') == date('Y-m-d', time())){
					$parent = 2;
				}
			}
			if(ACTION_NAME == 'index' && I('view') == 'administration'){
				$sub = 2;
				$title = '行政日志';
				$parent = 3;
				if(I('srchregdatestart') == date('Y-m-d', time()) && I('srchregdateend') == date('Y-m-d', time())){
					$parent = 2;
				}
			}
			if(ACTION_NAME == 'noneDiary'){
				$title = '未写日报';
				$parent = 2;
				$sub =3;
			}
			if(ACTION_NAME == 'add'){
				$title = '添加日志';
				$sub = 2;
				if(I('type') == 2){
					$title = '添加周报';
					$sub = 3;
				}
			}
			break;
		case 'Users':
			if(ACTION_NAME == 'password'){
				$title = '修改密码';
				$sub = 4;
			}else{
				$parent = 9;
				$title = '用户管理';
				$sub = 2;
			}
			break;
		case 'Team':
			if(I('userid') == session('uid')){
				$title = '我的团队';
				$sub = 5;
			}else{
				$parent = 9;
				$title = '团队管理';
				$sub = 4;
			}
			break;
		case 'Project':
			if(ACTION_NAME == 'index' && I('userid') == session('uid')){
				$title = '我的任务';
				$sub = 6;
			}else{
				$title = '项目管理';
				$parent = 8;
				$sub = 1;
			}
			break;
		case 'Article':
			$parent = 4;
			if(I('type') == 3){
				$title = '近期项目公告';
				$sub = 1;
			}else if(I('type') == 4){
				$title = '近期测试公告';
				$sub = 2;
			}else if(I('type') == 2){
				$title = '管理制度';
				$sub = 4;
			}else{
				$title = '项目管理';
				$sub = 3;
			}
			break;	
		case 'Group':
			$title = '用户组管理';
			$parent = 9;
			$sub = 1;
			break;
		case 'Department':
			$title = '部门管理';
			$parent = 9;
			$sub = 3;
			break;
		case 'Message':
			if(I('userid') == session('uid')){
				$title = '我的提醒';
				$sub = 7;
			}else{
				$parent = 8;
				$title = '提醒管理';
				$sub = 2;
			}
			break;
		case 'Judge':
			$title = '评委设置';
			$parent = 7;
			$sub = 1;
			break;
		case 'Medal':
			$title = '奖项设置';
			$parent = 7;
			$sub = 2;
			break;
		case 'Nominate':
			$title = '提名总览';
			$parent = 7;
			$sub = 5;
			if(ACTION_NAME == 'selfview'){
				$title = '我的提名';
				$sub = 4;
			}else if(ACTION_NAME == 'add'){
				$title = '我要提名';
				$sub = 3;
			}
			break;
		case 'Package':
			$title = '数据包管理';
			$parent = 5;
			$sub = 2;
			if(ACTION_NAME == 'add'){
				$sub = 1;
			}
			break;
		case 'File':
			$title = '文件传输';
			$parent = 6;
			$sub = 1;
			if(I('get.type') == 'fromMe'){
				$sub = 2;
			}
			if(I('get.type') == 'toMe'){
				$sub = 3;
			}
			break;
		case 'Material':
			$title = '元器件管理';
			$parent = 10;
			$sub = 2;
			if(preg_match('/category/', ACTION_NAME)){
				$sub = 1;
			}
			if(preg_match('/finalBuyApply/', ACTION_NAME)){
				$sub = 6;
			}
			if(preg_match('/buyApply/', ACTION_NAME)){
				$sub = 5;
			}
			if(preg_match('/buyConfirm/', ACTION_NAME)){
				$sub = 7;
			}
			if(preg_match('/buyCheckout/', ACTION_NAME)){
				$sub = 8;
			}
			if(preg_match('/receiveAdd/', ACTION_NAME)){
				$sub = 9;
			}
			if(preg_match('/receiveIndex/', ACTION_NAME)){
				$sub = 3;
			}
			if(preg_match('/uses/', ACTION_NAME)){
				$sub = 4;
			}
			break;
	}
	
	return array('parent'=>$parent, 'sub'=>$sub, 'title'=>$title);
}
