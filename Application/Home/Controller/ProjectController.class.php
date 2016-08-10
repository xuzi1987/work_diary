<?php
namespace Home\Controller;
use Think\Controller;
class ProjectController extends CommonController {
    public function index(){
    	$userid = I('get.userid');
    	if($userid && $userid == session('uid')){
    		$iplists = M('iplists');
    		$nickname = session('nickname');
    		$count = $iplists->where("header LIKE '%".$nickname."%' OR moni_headers LIKE '%".$nickname."%' OR shuzi_headers LIKE '%".$nickname."%' OR bantu_headers LIKE '%".$nickname."%'")->count();
    		
    		$Page = new \Think\Page($count,25);
    		$Page->setConfig('prev','上一页');
    		$Page->setConfig('next','下一页');
    		$Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
    		$show = $Page->show();
    		
    		$results = $iplists->where("header LIKE '%".$nickname."%' OR moni_headers LIKE '%".$nickname."%' OR shuzi_headers LIKE '%".$nickname."%' OR bantu_headers LIKE '%".$nickname."%'")->order('id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
    		foreach ($results as $result){
    			$list[$result['project_id']] = M('project')->where("id='{$result['project_id']}'")->find();
    		}
    		
    		foreach ($results as $result){
    			$list[$result['project_id']]['iplist'][] = $result;
    		}
    		
    	}else{
	    	$project = M('project');
	    	$count = $project->count();
	    	$Page = new \Think\Page($count,25);
	    	$Page->setConfig('prev','上一页');
	    	$Page->setConfig('next','下一页');
	    	$Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
	    	$show = $Page->show();
	    	$list = $project->order('datetime DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
	    	foreach ($list as $key=>$project){
	    		$list[$key]['iplist'] = M('iplists')->where("project_id='{$project['id']}'")->select();
	    	}
    	}
    	
    	$this->assign('list', $list);
    	$this->assign('page', $show);
    	$this->display();
    }
    
    public function delete(){
    	if(session('group_id') != 1 && session('group_id') != 4){
    		exit('Access deny');
    	}
    	$id = I('get.id','intval');
    	M('project')->where("id='".$id."'")->delete();
    	M('iplists')->where("project_id='".$id."'")->delete();
    	$this->success('删除成功', U('Project/index'), 1);
    }
    
    public function edit(){
    	if(session('group_id') != 1 && session('group_id') != 4){
    		exit('Access deny');
    	}
    	$id = I('get.id','intval');
    	$project = M('project');
    	if (IS_POST) {
    		$moni_headers_array = getPostData($_POST, 'moni_headers');
    		$shuzi_headers_array = getPostData($_POST, 'shuzi_headers');
    		$bantu_headers_array = getPostData($_POST, 'bantu_headers');
    		$data = array();
    		$data['code'] = I('post.code', 'trim');
    		$data['keys'] = I('post.keys', 'trim');
    		$data['customer'] = I('post.customer');
    		$data['technology'] = I('post.technology');
    		$data['mpw_fm'] = I('post.mpw_fm');
    		$data['project_header_text'] = I('post.project_header_text', 'trim');
    		if($data['project_header_text']){
    			$userinfo = M('users')->where("nickname='{$data['project_header_text']}'")->find();
    			$data['project_header'] = $userinfo['email'];
    		}
    		$project = M('project');
    		$project->where("id='$id'")->save($data);
    		
    		M('iplists')->where("project_id='".$id."'")->delete();
    		if(!empty($_POST['ip'])){
    			foreach ($_POST['ip'] as $key=>$ip){
    				$ip_data = array();
    				$ip_data['project_id'] = $id;
    				$ip_data['ip'] = $ip;
    				$ip_data['official_datetime'] = $this->getDatetime($_POST['official_datetime'][$key]);
    				$ip_data['interal_datetime'] = $this->getDatetime($_POST['interal_datetime'][$key]);
    				$ip_data['comment'] = $_POST['comment'][$key];
    				$ip_data['shuzi_headers'] = implode($shuzi_headers_array[$key], ',');
    				$ip_data['bantu_headers'] = implode($bantu_headers_array[$key], ',');
    				$ip_data['moni_headers'] = implode($moni_headers_array[$key], ',');
    				$ip_data['header'] = $_POST['header'][$key];
    				$ip_data['first_datetime'] = $this->getDatetime($_POST['first_datetime'][$key]);
    				$ip_data['middle_datetime'] = $this->getDatetime($_POST['middle_datetime'][$key]);
    				$ip_data['final_datetime'] = $this->getDatetime($_POST['final_datetime'][$key]);
    				M('iplists')->add($ip_data);
    			}
    		}
    		
    		$this->success('编辑成功', U('Project/index'), 1);
    	}else{
    		$list = $project->where('id='.$id)->find();
    		$list['iplist'] = M('iplists')->where("project_id='{$list['id']}'")->select();
    		$this->assign('list', $list);
    		$this->assign('count', (count($list['iplist'])+1));
    		
    		$User = M('users');
    		$moni_users = $User->field('userid,nickname')->where("department_id=1 AND status=1")->order('usort desc')->limit(500)->select();
    		$this->assign('moni_users', $moni_users);
    		$bantu_users = $User->field('userid,nickname')->where("department_id=3 AND status=1")->order('usort desc')->limit(500)->select();
    		$this->assign('bantu_users', $bantu_users);
    		$shuzi_users = $User->field('userid,nickname')->where("department_id=2 AND status=1")->order('usort desc')->limit(500)->select();
    		$this->assign('shuzi_users', $shuzi_users);
    		$this->display();
    	}
    }
    
    public function add(){
    	if(session('group_id') != 1 && session('group_id') != 4){
    		exit('Access deny');
    	}
    	if (IS_POST) {
    		$moni_headers_array = getPostData($_POST, 'moni_headers');
    		$shuzi_headers_array = getPostData($_POST, 'shuzi_headers');
    		$bantu_headers_array = getPostData($_POST, 'bantu_headers');
    		$data = array();
    		$data['code'] = I('post.code', 'trim');
    		$data['keys'] = I('post.keys', 'trim');
    		$data['customer'] = I('post.customer');
    		$data['technology'] = I('post.technology');
    		$data['mpw_fm'] = I('post.mpw_fm');
    		$data['datetime'] = date('Y-m-d H:i:s', time());
    		$data['project_header_text'] = I('post.project_header_text', 'trim');
    		if($data['project_header_text']){
	    		$userinfo = M('users')->where("nickname='{$data['project_header_text']}'")->find();
	    		$data['project_header'] = $userinfo['email'];
    		}
    		if(!$data['code'] || !$data['keys']){
    			
    		}
    		$project = M('project');
    		$insertId = $project->add($data);
    		
    		if(!empty($_POST['ip']) && $insertId){
    			foreach ($_POST['ip'] as $key=>$ip){
    				$ip_data = array();
    				$ip_data['project_id'] = $insertId;
    				$ip_data['ip'] = $ip;
    				$ip_data['official_datetime'] = $this->getDatetime($_POST['official_datetime'][$key]);
    				$ip_data['interal_datetime'] = $this->getDatetime($_POST['interal_datetime'][$key]);
    				$ip_data['comment'] = $_POST['comment'][$key];
    				$ip_data['shuzi_headers'] = implode($shuzi_headers_array[$key], ',');
    				$ip_data['bantu_headers'] = implode($bantu_headers_array[$key], ',');
    				$ip_data['moni_headers'] = implode($moni_headers_array[$key], ',');
    				$ip_data['header'] = $_POST['header'][$key];
    				$ip_data['first_datetime'] = $this->getDatetime($_POST['first_datetime'][$key]);
    				$ip_data['middle_datetime'] = $this->getDatetime($_POST['middle_datetime'][$key]);
    				$ip_data['final_datetime'] = $this->getDatetime($_POST['final_datetime'][$key]);
    				M('iplists')->add($ip_data);
    			}
    		}
    		$this->success('添加成功', U('Project/index'), 1);
    	}else{
    		$User = M('users');
    		$moni_users = $User->field('userid,nickname')->where("department_id=1 AND status=1")->order('usort desc')->limit(500)->select();
    		$this->assign('moni_users', $moni_users);
    		$bantu_users = $User->field('userid,nickname')->where("department_id=3 AND status=1")->order('usort desc')->limit(500)->select();
    		$this->assign('bantu_users', $bantu_users);
    		$shuzi_users = $User->field('userid,nickname')->where("department_id=2 AND status=1")->order('usort desc')->limit(500)->select();
    		$this->assign('shuzi_users', $shuzi_users);
    		$this->display();
    	}
    }
    
    function getDatetime($str){
    	if($str){
    		return date('Y-m-d 00:00:00', strtotime($str));
    	}else{
    		return date('Y-m-d 00:00:00', time());
    	}
    }
}