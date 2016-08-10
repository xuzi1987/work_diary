<?php
namespace Member\Controller;
use Think\Controller;

/**
 * Class UsersController
 * @package Member\Controller
 */
class UsersController extends CommonController {
    /**
     * get users list
     */
    public function userlist(){
    	if (session('group_id') > 1) {
    		$this->error('没有权限查看此页面...', U('Index/index'));
    	}
    	$map = array();
    	$srchname = I('get.srchname','', 'trim');
    	$srchregdatestart = I('get.srchregdatestart','', 'trim');
    	$srchregdateend = I('get.srchregdateend','', 'trim');
    	$srchuid = I('get.srchuid','', 'intval');
    	$srchemail = I('get.srchemail','', 'trim');
    	$srchgroupid = I('get.srchgroupid','', 'intval');
    	$srchdepartmentid = I('get.srchdepartmentid','', 'intval');
    	
    	if($srchname) {
    		$map['nickname'] = array('LIKE','%'.$srchname.'%');
    		$this->assign('srchname', $srchname);
    	}
    	if($srchuid) {
    		$map['userid'] = $srchuid;
    		$this->assign('srchuid', $srchuid);
    	}
    	if($srchemail) {
    		$map['email'] = array('LIKE','%'.$srchemail.'%');
    		$this->assign('srchemail', $srchemail);
    	}
    	if($srchuid) {
    		$map['userid'] = $srchuid;
    		$this->assign('srchuid', $srchuid);
    	}
    	if($srchgroupid) {
    		$map['group_id'] = $srchgroupid;
    		$this->assign('srchgroupid', $srchgroupid);
    	}
    	if($srchdepartmentid) {
    		$map['department_id'] = $srchdepartmentid;
    		$this->assign('srchdepartmentid', $srchdepartmentid);
    	}
    	if($srchregdatestart && $srchregdateend){
    		$this->assign('srchregdatestart', $srchregdatestart);
    		$this->assign('srchregdateend', $srchregdateend);
    		$map['regdate'] = array(array('egt',date('Y-m-d 00:00:00',strtotime($srchregdatestart))),array('elt',date('Y-m-d 23:59:59',strtotime($srchregdateend))),'AND');
    	}else{
    		if($srchregdatestart) {
    			$map['regdate'] = array('egt',date('Y-m-d 00:00:00',strtotime($srchregdatestart)));
    			$this->assign('srchregdatestart', $srchregdatestart);
    		}
    		if($srchregdateend) {
    			$map['regdate'] = array('elt',date('Y-m-d 23:59:59',strtotime($srchregdateend)));
    			$this->assign('srchregdateend', $srchregdateend);
    		}
    	}
    	
    	$User = M('users');   	
    	$count = $User->where($map)->count();
    	
    	$Page = new \Think\Page($count,25);
    	foreach($map as $key=>$val) {
    		$Page->parameter[$key] = urlencode($val);
    	}
    	$Page->setConfig('prev','上一页');
    	$Page->setConfig('next','下一页');
    	$Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
    	$show = $Page->show();
        $list = $User->where($map)->order('regdate desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach ($list as $key=>$value) {
        	$department = M('Department')->where(array('id'=>$value['department_id']))->find();
        	$group = M('Group')->where(array('id'=>$value['group_id']))->find();
        	$list[$key]['department'] = $department['name'];
        	$list[$key]['group'] = $group['title'];
        }
        
        $department_list = M('Department')->order('sort asc')->limit(50)->select();
        $this->assign('department_list', $department_list);
        $group_list = M('Group')->order('id asc')->limit(50)->select();
        $this->assign('group_list', $group_list);
        
		$this->assign('list', $list);
		$this->assign('page', $show);
        $this->display();
    }
    
    public function useredit(){
    	$uid = I('get.uid','', 'intval');
    	$User = M('users');
    	if (IS_POST) {
    		$nickname = I('post.nickname','', 'trim');
    		$password = I('post.password','', 'trim');
    		$email = I('post.email','', 'trim');
    		$mobile = I('post.mobile','', 'trim');
    		$department_id = I('post.department_id','', 'intval');
    		$group_id = I('post.group_id','', 'intval');
    		$ip = I('post.ip','', 'trim');
    		$usort = I('post.usort','', 'intval');
    		$data['status'] = I('post.status','', 'intval');
    		if($nickname){
    			$data['nickname'] = $nickname;
    		}
    		if($password){
    			$data['password'] = md5($password);
    		}
    		if($email){
    			$data['email'] = $email;
    		}
    		if($mobile){
    			$data['mobile'] = $mobile;
    		}
    		if($department_id){
    			$data['department_id'] = $department_id;
    		}
    		if($group_id){
    			$data['group_id'] = $group_id;
    		}
    		if($ip){
    			$data['ip'] = $ip;
    		}
    		if($usort){
    			$data['usort'] = $usort;
    		}
    		$User->where('userid='.$uid)->save($data);
    		$this->success('编辑成功', U('Users/userlist'), 2);
    	}else{
	    	$list = $User->where(array('userid'=>$uid))->find();
	    	$department_list = M('Department')->order('sort asc')->limit(50)->select();
        	$this->assign('department_list', $department_list);
        	$group_list = M('Group')->order('id asc')->limit(50)->select();
        	$this->assign('group_list', $group_list);
	    	$this->assign('list', $list);
	    	$this->display();
    	}
    }
    
    public function password(){
    	if (IS_POST) {
    		$uid = session('uid');
    		$User = M('users');
    		$password = I('post.password','', 'trim');
    		if($password){
    			$data['password'] = md5($password);
    		}
    		$User->where('userid='.$uid)->save($data);
    		$this->success('编辑成功', U('Home/Diary/index?view=self'), 2);
    	}else{
    		$this->display();
    	}
    }
    
    public function useradd(){
        if (IS_POST) {
            $user = D('users');
            if (!$data = $user->create()) {
                header("Content-type: text/html; charset=utf-8");
                exit($user->getError());
            }

            if ($id = $user->add($data)) {
                $this->success('添加成功', U('Users/userlist'), 2);
            } else {
                $this->error('添加失败');
            }
        } else {
        	$department_list = M('Department')->order('sort asc')->limit(50)->select();
	        $this->assign('department_list', $department_list);
	        $group_list = M('Group')->order('id asc')->limit(50)->select();
	        $this->assign('group_list', $group_list);
            $this->display();
        }
    }
    
    public function userdelete(){
    	if (IS_POST) {
    		$delete = I('post.delete');
    		$userids = implode(',', $delete);
    		if($userids){
    			$user = M('users');
    			$user->where('userid IN('.$userids.')')->delete();
    		}
    	}else{
	    	$userid = I('get.uid','', 'intval');
	    	if($userid){
		    	$user = M('users');
		    	$user->where('userid='.$userid)->delete();
	    	}
    	}
    	$this->success('删除成功', U('Users/userlist'), 2);
    }
    
    public function useraccess(){
    	$uid = I('get.uid','', 'intval');
    	$User = M('users');
    	if (IS_POST) {
    		$access = array();
    		foreach ($_POST as $key=>$value){
    			if($key != 'submit'){
    				$access[$key] = $value;
    			}
    		}
    		$data['access'] = serialize($access);
    		$User->where('userid='.$uid)->save($data);
    		$this->success('成功', U('Users/userlist'), 2);
    	} else {
    		$list = $User->where('userid='.$uid)->find();
    		$list['access'] = unserialize($list['access']);
    		$this->assign('list', $list);
    		$this->display();
    	}
    }
}