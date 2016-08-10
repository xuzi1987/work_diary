<?php
namespace Task\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index(){
	    $package = M('package');
		$results = $package->where('status=2 AND project_header_is_check is null')->select();
		foreach ($results as $data){
			if($data){
				$this->sendEmail('check', $data['id'], true);
			}
		}
		echo date('Y-m-d H:i:s', time())."/n";
    }
    
    public function alertMessage(){
    	$message = M('message');
    	$results = $message->where("status=1 AND alert_datetime='".date('Y-m-d 00:00:00', time())."'")->select();
    	foreach ($results as $list){
    		if($list){
    			$userinfo = M('users')->where("nickname='{$list['to_username']}'")->find();
    			senMessage($userinfo['ip'], $list['msg']);
    			$data = array();
    			$data['is_alert'] = $list['is_alert'] + 1;
    			M('message')->where('id='.$list['id'])->save($data);
    		}
    	}
    	echo date('Y-m-d H:i:s', time())."/n";
    }
    
	public function sendMessageByPython(){
		$id = I('post.id');
    	$list = M('message')->where('id='.$id)->find();
    	if($list){
    		$userinfo = M('users')->where("nickname='{$list['to_username']}'")->find();
    		senMessage($userinfo['ip'], $list['msg']);
    		$data = array();
    		$data['is_alert'] = $list['is_alert'] + 1;
    		M('message')->where('id='.$id)->save($data);
    	}	
    }
    
    public function checkEmail(){
    	$id = I('post.id');
    	$type = I('post.type');
    	$this->sendEmail($type, $id);
    }
    
    public function gpgEncryt(){
    	set_time_limit(0);
    	ignore_user_abort(true);
    	$id = I('post.id');
    	$key = I('post.key');
    	$local = I('post.local');
    	$package = M('package');
    	$data = $package->where('id='.$id)->find();
    	encrytByGpg($key, $local);
    	//获取加密后的Cksum值
    	if(file_exists("/Storage/Upload/{$data['file_path']}.asc")){
    		$cksum = exec("cksum /Storage/Upload/{$data['file_path']}.asc");
    		$current_cksum = explode(' ', $cksum);
    		$filename = explode('/', $data['file_path']);
    		$data['encrypt_checksum'] = $current_cksum[0].' '.$current_cksum[1].' '.$filename[1].'.asc';
    		$package->where('id='.$id)->save($data);
    	}
    	$data['status'] = 5;
    	$package->where('id='.$id)->save($data);
    	 
    	//通知项目负责人文件已加密完成
    	//$this->sendEmail('encrypt', $id);
    	
    	$project = M('project')->where("code='{$data['project_code']}'")->find();
    	preg_match_all('/PRJ(.*?)C/is', $project['code'], $matches);
    	$ftp_path = '/Storage/Customers/'.$project['customer'].'/S'.$matches[1][0].'_'.$project['technology'].'/from_inno';
    	$local = '/Storage/Upload/'.$data['file_path'].'.asc';
    	if($data['status'] == 5 && $data['it_header_is_check'] == 1 && file_exists($local)){
    		shell_exec("cp {$local} {$ftp_path}");
    	}
    	$data['status'] = 2;
    	$data['ftp_path'] = $ftp_path;
    	$package->where('id='.$id)->save($data);
    	$this->sendEmail('alert', $id);
    }
    
    public function getFromSftp(){
    	set_time_limit(0);
    	ignore_user_abort(true);
    	$id = I('post.id');
    	$package = M('package');
    	$data = $package->where('id='.$id)->find();
    	$remote = explode('/', $data['host_path']);
    	$dir = './Uploads/'.date('Y-m-d', time());
    	if(!is_dir($dir)){
    		mkdir($dir);
    	}
    	$local = $dir.'/'.$remote[count($remote)-1];
    	if($data['host'] && $data['host_path']){
    		sftp_download($data['host'], $data['host_path'], $local);
    	}
    	$this->sendEmail('check', $id);
    }
    
    public function checkCksum($id){
    	set_time_limit(0);
    	$package = M('package');
    	$data = $package->where('id='.$id)->find();
    	$cksum = exec("cksum /Storage/Upload/{$data['file_path']}");
    	 
    	$oring_cksum = explode(' ', $data['checksum']);
    	$current_cksum = explode(' ', $cksum);
    	 
    	if($oring_cksum[0] == $current_cksum[0] && $oring_cksum[1] == $current_cksum[1]){
    		return 'success';
    	}
    	 
    	return $cksum;
    }
    
    public function publish(){
    	set_time_limit(0);
    	ignore_user_abort(true);
    	$id = I('post.id');
    	$package = M('package');
    	$data = $package->where('id='.$id)->find();
    	$data['ftp_path'] = I('post.ftp_path');
    	$local = './Uploads/'.$data['file_path'].'.asc';
    	if($data['status'] == 5 && $data['it_header_is_check'] == 1 && file_exists($local)){
    		ftp_upload($local, $data['ftp_path']);
    	}
    	
    	$data['status'] = 2;
    	$package->where('id='.$id)->save($data);
    	
    	$this->sendEmail('alert', $id);
    }
    
    public function sendEmail($type, $id, $task=false){
    	$package = M('package');
    	$data = $package->where("id='$id'")->find();
    	 
    	if(($type == 'check' && $data['check_email'] != 1 && $data['id']) || ($type == 'alert' && $data['alert_email'] != 1) || $type == 'encrypt' || ($type == 'check' && $task)){
    		if($type == 'check'){
    			$to = $data['it_header'].','.$data['project_header'];
    			$title = '有一个数据包需要审核 ';
    			if(!$task){
    				$cksum = $this->checkCksum($data['id']);
    				if($cksum != 'success'){
    					$data['status'] = 3;
    					$package->where('id='.$data['id'])->save($data);
    				
    					$current_cksum = explode(' ', $cksum);
    					$file = explode('/', $data['file_path']);
    					$userinfo = M('users')->where("userid='{$data['userid']}'")->find();
    					$to = $userinfo['email'];//申请人的邮箱
    					$title = '错误提醒：数据包Cksum值不一致';
    				}else{
	    				$data['status'] = 1;
	    				$package->where('id='.$id)->save($data);
	    			}
    			}
    		}else{
    			$to = $data['it_header'].','.$data['project_header'];
    			if($type == 'alert'){
    				$title = '数据包上传完成提醒 ';
    			}
    			if($type == 'encrypt'){
    				$title = '文件加密已完成 ';
    			}
    		}
    
    		$path = explode('/', $data['file_path']);
    		$title .= ' ID:'.$data['id'];
    		$content = '申请人：'.$data['username'].'<br/>';
    		$content .= '项目Code：'.$data['project_code'].'<br/>';
    		$content .= '相关描述：'.$data['description'].'<br/>';
    		$content .= '数据路径：'.$data['file_path'].'<br/>';
    		$content .= '原始的Cksum：'.$data['checksum'].'<br/>';
    
    		if($type == 'check' && $cksum != 'success' && !$task){
    			$content .= '上传文件的Cksum：'.$current_cksum[0].' '.$current_cksum[1].' '.$file[1].'<br/>';
    		}
    		if($type == 'alert' || ($type == 'check' && $task)){
    			$content .= '加密后的Cksum：'.$data['encrypt_checksum'].'<br/>';
    			$content .= '上传路径：'.$data['ftp_path'].'/'.$path[count($path)-1].'.asc<br/>';
    		}
    		if($type == 'encrypt'){
    			$content .= '加密后的Cksum：'.$data['encrypt_checksum'].'<br/>';
    		}
    		
    		$to = 'wangyp@innosilicon.com.cn';//测试邮箱
    		//$send = sendMail($to, $title, $content);
    		
    		if(($type == 'check' && $cksum == 'success') || $type == 'alert'){
    			$data[$type.'_email'] = 1;
    			$package->where('id='.$data['id'])->save($data);
    		}
    		
    		$userinfo = M('users')->where("email='{$to}'")->find();
    		$message = str_replace('<br/>', "\n", $title."\n".$content);
    		senMessage($userinfo['ip'], $message);
    	}
    }
    
    public function diaryAlert(){
    	$map = array();
    	$map['status'] = 1;
    	$map['department_id'] = array('not in', '5,7');
    	$map['userid'] = array('not in', '6,55,56');
    	$results = M('users')->where($map)->select();
    	foreach ($results as $result){
    		$d_map = array();
    		$list = array();
    		$d_map['userid'] = $result['userid'];
    		$d_map['datetime'] = array(array('egt',date('Y-m-d 00:00:00',time())),array('elt',date('Y-m-d 23:59:59',time())),'AND');
    		$list = M('diary')->where($d_map)->select();
    		if(!$list){
    			print_r($result);
    			$msg = $result['nickname']."亲，已经10:30啦！\n记得写日报哦！          ";
    			//senMessage($result['ip'], $msg);
    			//exit();
    		}
    	}
    }
}
