<?php
namespace Home\Controller;
use Think\Controller;
class MedalController extends CommonController {
    public function index(){
    	if (session('group_id') > 1) {
    		$this->error('没有权限查看此页面...', U('Index/index'));
    	}
    	$medal = M('medal');
    	$list = $medal->order('datetime DESC')->select();
    	
    	$this->assign('list', $list);
    	$this->display();
    }
    
    public function delete(){
    	$id = I('get.id','intval');
    	M('medal')->where("id='".$id."'")->delete();
    	$this->success('删除成功', U('Medal/index'), 1);
    }
    
    public function edit(){
    	$id = I('get.id','intval');
    	$medal = M('medal');
    	if (IS_POST) {
    		$data['name'] = I('post.name');
    		$data['places'] = I('post.places');
    		$data['nominate_places'] = I('post.nominate_places');
    		$data['status'] = I('post.status');
    		$medal->where('id='.$id)->save($data);
    		$this->success('编辑成功', U('Medal/index'), 1);
    	}else{
    		$list = $medal->where('id='.$id)->find();
    		$this->assign('list', $list);
    		$this->display();
    	}
    }
    
    public function add(){
    	if (IS_POST) {
    		$data = array();
    		$data['name'] = I('post.name');
    		$data['places'] = I('post.places');
    		$data['nominate_places'] = I('post.nominate_places');
    		$data['status'] = I('post.status');
    		$data['datetime'] = date('Y-m-d H:i:s', time());
    		
    		$medal = M('medal');
    		$medal->add($data);
    		$this->success('添加成功', U('Medal/index'), 1);
    	}else{
    		$this->display();
    	}
    }
}