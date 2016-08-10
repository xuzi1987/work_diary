<?php
namespace Home\Controller;
use Think\Controller;
class PackageController extends CommonController {
    public function index(){
    	$map = array();
    	$map['username'] = array('eq', session('nickname'));
    	$map['project_header'] = array('eq', session('email'));
    	$map['it_header'] = array('eq', session('email'));
    	$map['_logic'] = 'OR';
    	$srchusername = I('get.srchusername','', 'trim');
    	$srchfile_path = I('get.srchfile_path','', 'trim');
    	if($srchusername) {
    		$map['username'] = array('LIKE','%'.$srchusername.'%');
    		$this->assign('srchusername', $srchusername);
    	}
    	if($srchfile_path) {
    		$map['file_path'] = array('LIKE','%'.$srchfile_path.'%');
    		$this->assign('srchfile_path', $srchfile_path);
    	}
    	
    	$package = M('package');
    	$count = $list = $package->where($map)->count();
    	$Page = new \Think\Page($count,25);
    	foreach($map as $key=>$val) {
    		$Page->parameter[$key] = urlencode($val);
    	}
    	$Page->setConfig('prev','上一页');
    	$Page->setConfig('next','下一页');
    	$Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
    	$show = $Page->show();
    	
    	$list = $package->where($map)->order('datetime DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
    	
    	$this->assign('list', $list);
    	$this->assign('page', $show);
    	$this->display();
    }
    
    public function delete(){
    	$id = I('get.id','intval');
    	$data = M('package')->where('id='.$id)->find();
    	if($data['it_header_is_check'] == 1 OR $data['project_header_is_check'] == 1){
    		exit('Access deny');
    	}
    	if($data['userid'] != session('uid') && session('group_id') != 1){
    		exit('Access deny');
    	}
    	M('package')->where("id='".$id."'")->delete();
    	$this->success('删除成功', U('Package/index'), 2);
    }
    
    public function edit(){
    	$id = I('get.id','intval');
    	$package = M('package');
    	$data = $package->where('id='.$id)->find();
    	if($data['it_header_is_check'] == 1 OR $data['project_header_is_check'] == 1){
    		exit('Access deny');
    	}
    	if($data['userid'] != session('uid') && session('group_id') != 1){
    		exit('Access deny');
    	}
    	if (IS_POST) {
    		$data['description'] = I('description');
    		$data['checksum'] = I('post.checksum');
    		$data['project_code'] = I('post.project_code');
    		$data['project_header'] = I('post.project_header');
    		$package->where('id='.$id)->save($data);
    		
    		if($data['status'] == 3){
    			$data = array (
    					'type' => 'check',
    					'id' => $id,
    			);
    			sendHttpSocket($data, 'checkEmail');
    		}
    		
    		$this->success('编辑成功', U('Package/index'), 2);
    	}else{
    		$list = $package->where('id='.$id)->find();
    		$project = $this->getProject($list['project_code']);
    		$list['project_header_text'] = $project['project_header_text'];
    		$this->assign('list', $list);
    		$this->assign('projects', $this->getProject());
    		$this->display();
    	}
    }
    
    public function add(){
    	set_time_limit(0);
    	ignore_user_abort(true);
    	if (IS_POST) {
    		$type = I('post.type');
    		$data['userid'] = session('uid');
    		$data['username'] = session('nickname');
    		$data['description'] = I('post.description');
    		$data['checksum'] = I('post.checksum');
    		$data['file_path'] = I('post.file_path', 'trim');
    		$data['project_code'] = I('post.project_code');
    		$data['project_header'] = I('post.project_header');
    		$data['it_header'] = I('post.it_header');
    		$data['datetime'] = date('Y-m-d H:i:s', time());
    		
    		if(!$data['project_code']){
    			exit('项目Code不能为空');
    		}
    		if(!$data['file_path']){
    			exit('文件路径不能为空');
    		}
    		if(!$data['checksum']){
    			exit('Cksum不能为空');
    		}
    		
    		$package = M('package');
    		if($data['project_code'] && $data['checksum'] && $data['file_path']){
	    		$insertId = $package->add($data);
    		}
    		
    		if($data['check_email'] != 1){
    			$data = array (
    					'type' => 'check',
    					'id' => $insertId,
    			);
    			sendHttpSocket($data, 'checkEmail');
    		}
    		if($insertId){
    			$this->success('添加成功', U('Package/index'), 2);
    		}
    	}else{
    		$this->assign('projects', $this->getProject());
    		$this->display();
    	}
    }
    
    public function getFromSftp($id){
    	$data = array (
    			'id' => $id,
    	);
    	sendHttpSocket($data, 'getFromSftp');
    }
    
    public function upload(){
    	set_time_limit(0);
        $upload = new \Think\Upload();
	    $upload->maxSize = 0 ;
	    $upload->exts = array('zip', 'tar', 'tgz', 'rar', 'gz', '7z');
	    $upload->rootPath = './Uploads/';
	    $upload->savePath = '';
	    $upload->saveName = array('checkFileName',array('__FILE__'));
	    $upload->replace = true;
	    $info = $upload->upload();
	    if(!$info) {
	        $this->error($upload->getError());
	    }else{
		    foreach($info as $file){
		        echo $file['savepath'].$file['savename'];
		    }
	    }
    }
    
    public function permissionCheck(){
    	set_time_limit(0);
    	ignore_user_abort(true);
    	$id = I('get.id','intval');
    	$type = I('get.type');
    	$package = M('package');
    	$data = $package->where('id='.$id)->find();
    	if($data['project_header'] != session('email') && $data['it_header'] != session('email')){
    		exit('Access deny');
    	}
    	if($type == 'project'){
    		$data['project_header_is_check'] = 1;
    	}
    	if($type == 'it'){
    		$data['status'] = 4;
    		$package->where('id='.$id)->save($data);
    		
    		$data['it_header_is_check'] = 1;
    		if($data['file_path'] && file_exists("/Storage/Upload/".$data['file_path']) && $data['project_code']){
    			$this->gpgEncryt($data['project_code'], $data['file_path'], $id);
    		}
    	}
    	$package->where('id='.$id)->save($data);
    	if($type == 'it' || ($type == 'project' && $data['status'] == 1 && $data['it_header_is_check'] == 1)){
    		echo '1';
    	}
    }
    
    public function gpgEncryt($project_code, $local, $id){
    	$project = $this->getProject($project_code);
    	$key = $project['keys'];
    	if(file_exists('./Public/keys/'.$key)){
    		$data = array (
    				'id' => $id,
    				'key' => $key,
    				'local' => $local,
    		);
    		sendHttpSocket($data, 'gpgEncryt');
    	}
    }
    
    public function ftpUpload(){
    	$id = I('get.id','intval');
    	$package = M('package');
    	$data = $package->where('id='.$id)->find();
    	if($data['project_header'] != session('email') && $data['it_header'] != session('email')){
    		exit('Access deny');
    	}
    	$local = './Uploads/'.$data['file_path'].'.asc';
    	if($data['status'] == 1 && $data['it_header_is_check'] == 1 && file_exists($local)){
    		$this->assign('list', $data);
    		$this->display();
    	}else{
    		$this->success('未审核', U('Package/index'), 2);
    	}
    }
    
    public function publish(){
    	$id = I('get.id','intval');
    	$package = M('package');
    	$data = $package->where('id='.$id)->find();
    	if($data['project_header'] != session('email') && $data['it_header'] != session('email')){
    		exit('Access deny');
    	}
    	$data['status'] = 5;
    	$package->where('id='.$id)->save($data);
    	sendHttpSocket(array('id'=>$id, 'ftp_path'=>I('get.ftp_path')), 'publish');
    }
    
    public function getProject($code){
    	return $code ? M('project')->where("code='{$code}'")->find() : M('project')->select();
    }
    
    public function getCode(){
    	$project_code = I('get.project_code', 'trim');
    	$project = $this->getProject($project_code);
    	if($project){
    		echo json_encode($project);
    	}
    }
    
    public function ftpSettings(){
    	if(session('group_id') != 1){
    		exit('Access deny');
    	}
    	 
    	$results = M('ftpsettings')->select();
    	if (IS_POST) {
    		$post_data = array();
    		$post_data['ip'] = I('post.ip','trim');
    		$post_data['username'] = I('post.username','trim');
    		$post_data['password'] = I('post.password','trim');
    		$post_data['port'] = I('post.port','intval');
    
    		$insert_data = array(
    				'ip' => $post_data['ip'],
    				'settings' => innoEncode(serialize($post_data))
    		);
    
    		if(!$results){
    			M('ftpsettings')->add($insert_data);
    		}else{
    			M('ftpsettings')->where('id='.$results[0]['id'])->save($insert_data);
    		}
    		$this->success('添加成功', U('Package/ftpSettings'), 2);
    	}else{
    		$data = array();
    		$data = unserialize(innoDecode($results[0]['settings']));
    		$data['port'] = $data['port'] ? $data['port'] : '21';
    		$this->assign('data', $data);
    		$this->display();
    	}
    }
}
