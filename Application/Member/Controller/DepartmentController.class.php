<?php
namespace Member\Controller;
use Think\Controller;

/**
 * Class UsersController
 * @package Member\Controller
 */
class DepartmentController extends CommonController {
    public function index(){
    	if (session('group_id') > 1) {
    		$this->error('没有权限查看此页面...', U('Index/index'));
    	}
    	$Department = M('department'); 
        $list = $Department->order('sort asc')->limit(100)->select();
		$this->assign('list', $list);
        $this->display();
    }
    
    public function edit(){
    	$id = I('get.id','', 'intval');
    	$Department = M('department'); 
    	if (IS_POST) {
    		$name = I('post.name','', 'trim');
    		if($name){
    			$data['name'] = $name;
    		}
    		$sort = I('post.sort','', 'intval');
    		if($sort){
    			$data['sort'] = $sort;
    		}
    		$Department->where('id='.$id)->save($data);
    		$this->success('编辑成功', U('Department/index'), 2);
    	}else{
	    	$list = $Department->where(array('id'=>$id))->find();
	    	$this->assign('list', $list);
	    	$this->display();
    	}
    }
    
    public function add(){
        if (IS_POST) {
            $Department = M('department'); 
            if (!$data = $Department->create()) {
                header("Content-type: text/html; charset=utf-8");
                exit($Department->getError());
            }

            if ($id = $Department->add($data)) {
                $this->success('添加成功', U('Department/index'), 2);
            } else {
                $this->error('添加失败');
            }
        } else {
            $this->display();
        }
    }
    
    public function delete(){
    	$id = I('get.id','', 'intval');
    	$Department = M('department');
    	$Department->where('id='.$id)->delete();
    	$this->success('删除成功', U('Department/index'), 2);
    }
}