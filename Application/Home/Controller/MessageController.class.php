<?php
namespace Home\Controller;
use Think\Controller;
class MessageController extends CommonController {
    public function index(){
    	$map = array();
    	$userid = I('get.userid');
    	if($userid && $userid == session('uid')){
    		$map['to_username'] = session('nickname');
    		//$map['is_alert'] = 1;
    	}
    	
    	$message = M('message');
    	$count = $list = $message->where($map)->count();
    	$Page = new \Think\Page($count,25);
    	$Page->setConfig('prev','上一页');
    	$Page->setConfig('next','下一页');
    	$Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
    	$show = $Page->show();
    	
    	$list = $message->where($map)->order('datetime DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
    	$this->assign('list', $list);
    	$this->assign('page', $show);
    	$this->display();
    }
    
    public function delete(){
    	if(session('group_id') != 1 && session('group_id') != 4){
    		exit('Access deny');
    	}
    	$id = I('get.id','intval');
    	M('message')->where('id='.$id)->delete();
    	$this->success('删除成功', U('Message/index'), 1);
    }
    
    public function edit(){
    	if(session('group_id') != 1 && session('group_id') != 4){
    		exit('Access deny');
    	}
    	$id = I('get.id','intval');
    	if (IS_POST) {
    		$data = array();
    		$data['alert_datetime'] = $this->getDatetime($_POST['alert_datetime']);
    		$data['msg'] = $_POST['msg'];
    		M('message')->where('id='.$id)->save($data);
    		$this->success('编辑成功', U('Message/index'), 1);
    	}else{
    		$list = M('message')->where('id='.$id)->find();
    		$this->assign('list', $list);
    		$this->display();
    	}
    }
    
    public function add(){
    	if(session('group_id') != 1 && session('group_id') != 4){
    		exit('Access deny');
    	}
    	if (IS_POST) {
    		if($_POST['to_username']){
    			foreach ($_POST['to_username'] as $key=>$usernames){
    				foreach ($usernames as $username){
    					$data = array();
    					$data['from_username'] = session('nickname');
    					$data['code'] = I('get.code');
    					$data['to_username'] = $username;
    					$data['datetime'] = date('Y-m-d H:i:s', time());
    					$data['msg'] = $_POST['msg'][$key];
    					$data['alert_datetime'] = $this->getDatetime($_POST['alert_datetime'][$key]);
    					$data['status'] = 1;
    					M('message')->add($data);
    				}
    			}
    		}
    		$this->success('添加成功', U('Message/index'), 1);
    	}else{
    		$this->assign('list', $this->getIplist());
    		$this->display();
    	}
    }
    
    public function sendMessage(){
    	$id = I('get.id','intval');
    	$this->sendMessageByPython($id);
    	$this->success('发送成功', U('Message/index'), 1);
    }
    
    public function sendMessageByPython($id){
    	$data = array ('id' => $id);
    	sendHttpSocket($data, 'sendMessageByPython');
    }
    
    public function getProject($code){
    	return $code ? M('project')->where("code='{$code}'")->find() : M('project')->select();
    }
    
    public function getIplist(){
    	$code = I('get.code');
    	$list = M('project')->where("code='{$code}'")->find();
    	$list['iplist'] = M('iplists')->where("project_id='{$list['id']}'")->select();
    	
    	$users = array();
    	foreach ($list['iplist'] as $key=>$value){
    		foreach (explode(',', $value['moni_headers']) as $v){
    			if($v) $users[] = $v;
    		}
    		foreach (explode(',', $value['shuzi_headers']) as $v){
    			if($v) $users[] = $v;
    		}
    		foreach (explode(',', $value['bantu_headers']) as $v){
    			if($v) $users[] = $v;
    		}
    		
    		$list['iplist'][$key]['official_datetime'] = date('Y-m-d', strtotime($value['official_datetime']));
    		$list['iplist'][$key]['interal_datetime'] = date('Y-m-d', strtotime($value['interal_datetime']));
    		$list['iplist'][$key]['first_datetime'] = date('Y-m-d', strtotime($value['first_datetime']));
    		$list['iplist'][$key]['middle_datetime'] = date('Y-m-d', strtotime($value['middle_datetime']));
    		$list['iplist'][$key]['final_datetime'] = date('Y-m-d', strtotime($value['final_datetime']));
    	}
    	$list['users'] = array_unique($users);
    	return $list;
    }
    
    function getDatetime($str){
    	if($str){
    		return date('Y-m-d 00:00:00', strtotime($str));
    	}else{
    		return date('Y-m-d 00:00:00', time());
    	}
    }
}