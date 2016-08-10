<?php
namespace Member\Controller;
use Think\Controller;
class TeamController extends CommonController {
    public function index(){
    	$userid = I('get.userid','', 'intval');
    	if(session('group_id') > 1){
    		$userid = session('uid');
    	}
    	if(!$userid){
    		$Team = M('team');
    		$list = $Team->order('id desc')->limit(100)->select();
    	}else{
    		$Teamusers = M('teamusers');
    		$list = $Teamusers->where(array('userid'=>$userid))->order('teamid desc')->limit(100)->select();
    		foreach ($list as $key=>$value){
    			$Teaminfo = M('team')->where('id='.$value['teamid'])->find();
    			$userinfo = M('users')->where('userid='.$value['userid'])->find();
    			$list[$key]['nickname'] = $userinfo['nickname'];
    			$list[$key]['name'] = $Teaminfo['name'];
    			$list[$key]['id'] = $Teaminfo['id'];
    			$list[$key]['author'] = $Teaminfo['author'];
    			$list[$key]['datetime'] = $Teaminfo['datetime'];
    			$list[$key]['description'] = $Teaminfo['description'];
    			if($value['type'] == 1){
    				$list[$key]['type'] = '组长';
    			}else{
    				$list[$key]['type'] = '成员';
    			}
    		}
    		$this->assign('userid', $userid);
    	}
    	foreach ($list as $key=>$value){
    		$userinfo = M('users')->where('userid='.$value['author'])->find();
    		$list[$key]['authorname'] = $userinfo['nickname'];
    		
    		$leader = array();
    		$leaderidinfo = M('teamusers')->where(array('teamid'=>$value['id'],'type'=>1))->order('userid desc')->limit(100)->select();
    		foreach ($leaderidinfo as $v){
    			$leaderinfo = M('users')->where('userid='.$v['userid'])->find();
    			$leader[] = $leaderinfo['nickname'];
    		}
    		$list[$key]['leader'] = implode(', ', $leader);
    	}
    	$this->assign('list', $list);
    	$this->display();
    }
    
    public function edit(){
    	$id = I('get.id','', 'intval');
    	$Team = M('team');
    	if (IS_POST) {
    		$name = I('post.name','', 'trim');
    		if($name){
    			$data['name'] = $name;
    		}
    		$description = I('post.description','', 'trim');
    		$data['description'] = $description;
    		$Team->where('id='.$id)->save($data);
    		$this->success('编辑成功', U('Team/index'), 2);
    	}else{
    		$list = $Team->where(array('id'=>$id))->find();
    		$this->assign('list', $list);
    		$this->display();
    	}
    }
    
    public function add(){
    	if (IS_POST) {
    		$Team = M('team');
    		if (!$data = $Team->create()) {
    			header("Content-type: text/html; charset=utf-8");
    			exit($Team->getError());
    		}
    
    		if ($id = $Team->add($data)) {
    			$data['teamid'] = $id;
    			$data['userid'] = I('post.author','','intval');
    			$data['type'] = 1;
    			$Teamusers = M('teamusers');
    			$Teamusers->add($data);
    			$this->success('添加成功', U('Team/index'), 2);
    		} else {
    			$this->error('添加失败');
    		}
    	} else {
    		$this->display();
    	}
    }
    
    public function delete(){
    	$id = I('get.id','', 'intval');
    	M('team')->where('id='.$id)->delete();
    	M('teamusers')->where('teamid='.$id)->delete();
    	$this->success('删除成功', U('Team/index'), 2);
    }
    
    public function members(){
    	$id = I('get.id','', 'intval');
    	$Team = M('team');
    	$team_list = $Team->where(array('id'=>$id))->find();
    	
    	$Teamusers = M('teamusers');
    	$list = $Teamusers->where(array('teamid'=>$id))->order('type asc')->limit(100)->select();
    	foreach ($list as $key=>$value){
    		$userinfo = M('users')->where('userid='.$value['userid'])->find();
    		$list[$key]['nickname'] = $userinfo['nickname'];
    		$list[$key]['username'] = $userinfo['username'];
    		if($value['type'] == 1){
    			$list[$key]['type'] = '组长';
    		}else{
    			$list[$key]['type'] = '成员';
    		}
    		if($value['userid'] == session('uid')){
    			$type = $value['type'];
    		}
    	}
    	$this->assign('type', $type);
    	$this->assign('team', $team_list);
    	$this->assign('list', $list);
    	$this->display();
    }
    
    public function addmember(){
    	if (IS_POST) {
    		$Teamusers = M('teamusers');
    		$data = array();
    		$data['type'] = I('post.type');
    		$data['teamid'] = I('post.teamid');
    		$userinfo = M('users')->where("nickname='".I('post.username','','trim')."'")->find();
    		$data['userid'] = $userinfo['userid'];
    		if ($data['userid'] && $id = $Teamusers->add($data)) {
    			$this->success('添加成功', U('Team/members?id='.I('post.teamid')), 2);
    		} else {
    			$this->error('添加失败');
    		}
    	} else {
    		$id = I('get.id','', 'intval');
    		$Team = M('team');
    		$team_list = $Team->where(array('id'=>$id))->find();
    		$User = M('users');
    		$list = $User->field('userid,nickname')->where("status=1")->order('regdate desc')->limit(500)->select();
    		$this->assign('list', $list);
    		$this->assign('team', $team_list);
    		$this->display();
    	}
    }
    
    public function editmember(){
    	if (IS_POST) {
    		$data['type'] = I('post.type','','intval');
    		$userid = I('post.userid','','intval');
    		$teamid = I('post.teamid','','intval');
    		$Teamusers = M('teamusers');
    		$Teamusers->where(array('userid'=>$userid,'teamid'=>$teamid))->save($data);
    		$this->success('编辑成功', U('Team/members?id='.I('post.teamid')), 2);
    	} else {
    		$teamid = I('get.teamid','', 'intval');
    		$Team = M('team');
    		$team_list = $Team->where(array('id'=>$teamid))->find();
    		
    		$userid = I('get.userid','', 'intval');
    		$Teamusers = M('teamusers');
    		$list = $Teamusers->where(array('userid'=>$userid,'teamid'=>$teamid))->find();
    		$userinfo = M('users')->where('userid='.$list['userid'])->find();
    		$list['nickname'] = $userinfo['nickname'];
    		$this->assign('list', $list);
    		$this->assign('team', $team_list);
    		$this->display();
    	}
    }
    
    public function deletemember(){
    	$userid = I('get.userid','','intval');
    	$teamid = I('get.teamid','','intval');
    	$Teamusers = M('teamusers');
    	$Teamusers->where(array('userid'=>$userid,'teamid'=>$teamid))->delete();
    	$this->success('删除成功', U('Team/members?id='.$teamid), 2);
    }
    
    public function getmembers(){
    	$nickname = I('get.filter_name','', 'trim');
    	$map = array();
    	$map['nickname'] = array('LIKE','%'.$nickname.'%');
    	$map['username'] = array('LIKE','%'.$nickname.'%');
    	$map['_logic'] = 'OR';
    	$User = M('users');
    	$list = $User->field('userid,nickname')->where($map)->order('regdate desc')->limit(500)->select();
    	echo json_encode($list);
    }
}