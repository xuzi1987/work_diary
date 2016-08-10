<?php
namespace Member\Controller;
use Think\Controller;

/**
 * Class UsersController
 * @package Member\Controller
 */
class GroupController extends CommonController {
    /**
     * get users list
     */
    public function index(){
    	if (session('group_id') > 1) {
    		$this->error('没有权限查看此页面...', U('Index/index'));
    	}
    	$Group = M('group'); 
        $list = $Group->order('id asc')->limit(100)->select();
		$this->assign('list', $list);
        $this->display();
    }
    
    public function edit(){
    	$id = I('get.id','', 'intval');
    	$Group = M('group'); 
    	if (IS_POST) {
    		$title = I('post.title','', 'trim');
    		$description = I('post.description','', 'trim');
    		if($title){
    			$data['title'] = $title;
    		}
    		if($description){
    			$data['description'] = $description;
    		}
    		$Group->where('id='.$id)->save($data);
    		$this->success('编辑成功', U('Group/index'), 2);
    	}else{
	    	$list = $Group->where(array('id'=>$id))->find();
	    	$this->assign('list', $list);
	    	$this->display();
    	}
    }
    
    public function add(){
        if (IS_POST) {
            $Group = M('group'); 
            if (!$data = $Group->create()) {
                header("Content-type: text/html; charset=utf-8");
                exit($Group->getError());
            }

            if ($id = $Group->add($data)) {
                $this->success('添加成功', U('Group/index'), 2);
            } else {
                $this->error('添加失败');
            }
        } else {
            $this->display();
        }
    }
    
    public function delete(){
    	$id = I('get.id','', 'intval');
    	$Group = M('group');
    	$Group->where('id='.$id)->delete();
    	$this->success('删除成功', U('Group/index'), 2);
    }
    
    public function access(){
    	$id = I('get.id','', 'intval');
    	$Group = M('group');
    	if (IS_POST) {
    		$access = array();
    		foreach ($_POST as $key=>$value){
    			if($key != 'submit'){
    				$access[$key] = $value;
    			}
    		}
    		$data['access'] = serialize($access);
    		$Group->where('id='.$id)->save($data);
    		$this->success('成功', U('Group/index'), 2);
    	} else {
    		$list = $Group->where(array('id'=>$id))->find();
    		$list['access'] = unserialize($list['access']);
    		$this->assign('list', $list);
    		$this->display();
    	}
    }
}