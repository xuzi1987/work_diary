<?php
namespace Home\Controller;
use Think\Controller;
class FileController extends CommonController {
    public function index(){
    	$map = array();
    	$type = I('get.type','','trim');
    	if($type == 'fromMe'){
    		$map['userid'] = session('uid');
    	}else if($type == 'toMe'){
    		$receiver_data = M('filereceiver')->where("username='".session('nickname')."'")->select();
    		$file_ids = array();
    		foreach ($receiver_data as $value){
    			$file_ids[] = $value['file_id'];
    		}
    		$map['id'] = array('IN', implode(',', $file_ids));
    	}
    	$count = $list = M('file')->where($map)->count();
    	$Page = new \Think\Page($count,25);
    	foreach($map as $key=>$val) {
    		$Page->parameter[$key] = urlencode($val);
    	}
    	$Page->setConfig('prev','上一页');
    	$Page->setConfig('next','下一页');
    	$Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
    	$show = $Page->show();
    	
    	$list = M('file')->where($map)->order('datetime DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
    	foreach ($list as $key=>$value){
    		$file_path = explode('/', $value['file_path']);
    		$list[$key]['file_path'] = $file_path[1];
    		switch ($value['status']){
    			case '1':
    				$list[$key]['status_text'] = '已上传';
    				break;
    			case '2':
    				$list[$key]['status_text'] = '已删除';
    				break;
    			
    		}
    		$list[$key]['filereceiver'] = M('filereceiver')->where("file_id='".$value['id']."'")->select();
    	}
    	
    	$this->assign('type', $type);
    	$this->assign('list', $list);
    	$this->assign('page', $show);
    	$this->display();
    }
    
    public function delete(){
    	$id = I('get.id','intval');
    	$file = M('file')->where("id='{$id}' AND userid='".session('uid')."'")->find();
    	if($file['file_path']){
    		unlink('./Uploads/Files/'.$file['file_path']);
    		M('file')->where("id='{$id}' AND userid='".session('uid')."'")->save(array('status'=>2));
    		$this->success('删除成功', U('File/index?type=fromMe'), 1);
    	}else{
    		$this->error('删除失败','',1);
    	}
    }
    
    public function edit(){
    	$id = I('get.id','intval');
    	if (IS_POST) {
    		$data['description'] = I('post.description');
    		M('file')->where('id='.$id)->save($data);
    		foreach ($_POST['to_users'] as $username){
    			$filereceiver = M('filereceiver')->where("file_id='{$id}' AND username='{$username}'")->find();
    			if(!$filereceiver){
    				$receiver_data = array();
    				$receiver_data['file_id'] = $id;
    				$receiver_data['username'] = trim($username);
    				$userinfo = M('users')->where("nickname='{$username}'")->find();
    				$receiver_data['userid'] = $userinfo['userid'];
    				M('filereceiver')->add($receiver_data);
    			}
    		}
    		
    		$filereceiver = M('filereceiver')->where("file_id='{$id}'")->select();
    		foreach ($filereceiver as $value){
    			if(!in_array($value['username'], $_POST['to_users'])){
    				M('filereceiver')->where("file_id='{$id}' AND username='{$value['username']}'")->delete();
    			}
    		}
    		
    		$this->success('编辑成功', U('File/index?type=fromMe'), 2);
    	}else{
    		$list = M('file')->where('id='.$id)->find();
    		$filereceiver = M('filereceiver')->where("file_id='{$id}'")->select();
    		foreach ($filereceiver as $value){
    			$list['filereceiver'][] = $value['username'];
    		}
    		$this->assign('list', $list);
    		$userlist = M('users')->where('status=1 AND userid NOT IN (6,55,56)')->order('department_id DESC, usort DESC, regdate ASC')->select();
    		$this->assign('userlist', $userlist);
    		$this->display();
    	}
    }
    
    public function add(){
    	set_time_limit(0);
    	if (IS_POST) {
    		$data = array();
    		$data['userid'] = session('uid');
    		$data['username'] = session('nickname');
    		$data['description'] = I('post.description', 'trim');
    		$data['file_path'] = $this->upload();
    		$data['datetime'] = date('Y-m-d H:i:s', time());
    		
    		if(!$data['file_path']){
    			$this->error('选择上传文件','',1);
    		}
    		if(!$_POST['to_users']){
    			$this->error('填写接收人','',1);
    		}
    		
    		$insertId = M('file')->add($data);
    		if($insertId){
    			foreach ($_POST['to_users'] as $username){
    				 if(trim($username)){
    				 	$receiver_data = array();
    				 	$receiver_data['file_id'] = $insertId;
    				 	$receiver_data['username'] = trim($username);
    				 	$userinfo = M('users')->where("nickname='{$username}'")->find();
    				 	$receiver_data['userid'] = $userinfo['userid'];
    				 	M('filereceiver')->add($receiver_data);
    				 }
    			}
    			$this->success('添加成功', U('File/index?type=fromMe'), 2);
    		}
    	}else{
    		$userlist = M('users')->where('status=1 AND userid NOT IN (6,55,56)')->order('department_id DESC, usort DESC, regdate ASC')->select();
    		$this->assign('userlist', $userlist);
    		$this->display();
    	}
    }
    
    public function download(){
    	$uploadpath='./Uploads/Files/';
    	$id = I('get.id','intval');
    	if($id == ''){
    		$this->error('下载失败！','',1);
    	}
    	$result = M('File')->where("id='{$id}'")->find();
    	if($result == false){
    		$this->error('下载失败！', '', 1);
    	}else{
    		$filereceiver = M('filereceiver')->where("file_id='{$id}' AND userid='".session('uid')."'")->find();
    		if($result['userid'] != session('uid') && !$filereceiver){
    			$this->error('下载失败！','',1);
    		}
    		$data = array();
    		$data['status'] = 2;
    		$data['receive_datetime'] = date('Y-m-d H:i:s', time());
    		M('filereceiver')->where("file_id='{$id}' AND userid='".session('uid')."'")->save($data);
    		
    		$file_path = explode('/', $result['file_path']);
    		$savename = $file_path[1];
    		$showname = $file_path[1];
    		//$filename = $uploadpath.iconv('utf-8', 'gb2312', $result['file_path']);
    		$filename = $uploadpath.$result['file_path'];
    		$http = new \Org\Net\Http();
			$http->download($filename, $showname);
    	}
    }
    
	public function upload(){
    	set_time_limit(0);
        $upload = new \Think\Upload();
	    $upload->maxSize = 52428800;//50MB
	    $upload->exts = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', '7z', 'jpg', 'gif', 'png', 'bmp');
	    $upload->rootPath = './Uploads/Files/';
	    $upload->savePath = '';
	    $upload->saveName = array('checkFileName',array('__FILE__'));
	    $upload->replace = true;
	    $info = $upload->upload();
	    if(!$info) {
	        $this->error($upload->getError());
	    }else{
		    foreach($info as $file){
		        //return $file['savepath'].iconv('gb2312', 'utf-8', $file['savename']);
		    	return $file['savepath'].$file['savename'];
		    }
	    }
    }
    
}