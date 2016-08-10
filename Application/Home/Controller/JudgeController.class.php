<?php
namespace Home\Controller;
use Think\Controller;
class JudgeController extends CommonController {
    public function index(){
    	if (session('group_id') > 1) {
    		$this->error('没有权限查看此页面...', U('Index/index'));
    	}
    	$judge = M('judge');
    	$list = $judge->order('datetime DESC')->select();
    	
    	$this->assign('list', $list);
    	$this->display();
    }
    
    public function delete(){
    	$userid = I('get.userid','intval');
    	M('judge')->where("userid='".$userid."'")->delete();
    	$this->success('删除成功', U('judge/index'), 1);
    }
    
    public function add(){
    	if (IS_POST) {
    		$data = array();
    		$data['username'] = I('post.username');
    		$data['userid'] = I('post.userid');
    		$data['datetime'] = date('Y-m-d H:i:s', time());
    		
    		$judge = M('judge');
    		$judge->add($data);
    		$this->success('添加成功', U('judge/index'), 1);
    	}else{
    		$this->display();
    	}
    }
}