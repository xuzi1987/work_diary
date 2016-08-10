<?php
namespace Home\Controller;
use Think\Controller;
class ArticleController extends CommonController {
    public function index(){
    	$map = array();
    	$type = I('get.type','','intval');
    	if($type){
    		$map['type'] = $type;
    	}else{
    		$map['type'] = array('not in', '3,4');
    	}
    	$article = M('article');
    	
    	$index = I('get.index');
    	if($index && $type){
    		$lists = $article->where("type='{$type}'")->order('datetime DESC')->select();
    		$this->redirect('Article/view?type='.$type.'&id='.$lists[0]['id']);
    	}
    	
    	$count = $list = $article->where($map)->count();
    	$Page = new \Think\Page($count,25);
    	foreach($map as $key=>$val) {
    		$Page->parameter[$key] = urlencode($val);
    	}
    	$Page->setConfig('prev','上一页');
    	$Page->setConfig('next','下一页');
    	$Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
    	$show = $Page->show();
    	
    	$list = $article->where($map)->order('datetime DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
    	foreach($list as $key=>$value){
    		if((time() - strtotime($value['datetime'])) < 7*60*60*24){
    			$list[$key]['new'] = 1;
    		}
    	}
    	$this->assign('type', $type);
    	$this->assign('list', $list);
    	$this->assign('page', $show);
    	$this->display();
    }
    
    public function view(){
    	$id = I('get.id');
    	$type = I('get.type','','intval');
    	$article = M('article');
    	if(!$id){
    		$lists = $article->where('id>0')->order('datetime DESC')->select();
    		$list = $lists[0];
    	}else{
    		$list = $article->where('id='.$id)->find();
    	}
    	$this->assign('type', $type);
    	$this->assign('list', $list);
    	$this->display();
    }
    
    public function delete(){
    	$id = I('get.id','intval');
    	M('article')->where("id='".$id."'")->delete();
    	$this->success('删除成功', U('Article/index'), 2);
    }
    
    public function edit(){
    	$id = I('get.id','intval');
    	$article = M('article');
    	if (IS_POST) {
    		$data['title'] = I('post.title');
    		$data['content'] = I('post.content');
    		$data['author'] = I('post.author');
    		$data['type'] = I('post.type');
    		$article->where('id='.$id)->save($data);
    		$this->success('编辑成功', U('Article/index'), 2);
    	}else{
    		$list = $article->where('id='.$id)->find();
    		$this->assign('list', $list);
    		$this->display();
    	}
    }
    
    public function add(){
    	if (IS_POST) {
    		$data = array();
    		$data['userid'] = session('uid');
    		$data['title'] = I('post.title');
    		$data['content'] = I('post.content');
    		$data['author'] = I('post.author');
    		$data['type'] = I('post.type');
    		$data['datetime'] = date('Y-m-d H:i:s', time());
    		
    		$article = M('article');
    		$article->add($data);
    		$this->success('添加成功', U('Article/index'), 2);
    	}else{
    		$type = I('get.type','','intval');
    		$this->assign('type', $type);
    		$this->display();
    	}
    }
    
    public function uploader(){
    	$upload = new \Think\Upload();
    	$upload->maxSize = 0 ;
    	$upload->exts = array('jpg', 'png', 'gif');
    	$upload->rootPath = './Public/attachments/';
    	$upload->savePath = '';
    	$upload->autoSub = false;
    	$info = $upload->upload();
    	if(!$info) {
    		$this->error($upload->getError());
    	}else{
    		foreach($info as $file){
    			echo stripslashes(json_encode(array("filelink" => __ROOT__.'/Public/attachments/'.$file['savepath'].$file['savename'])));
    		}
    	}
    }
    
    public function uploaded(){
    	$uploads = __ROOT__."/Public/attachments/";
    	$directory = "./Public/attachments/";
    	
    	$files = array();
    	if ($handle = opendir($directory)) {
    		while ($image = readdir($handle)) {
    			if (!in_array($image, array(".", "..")) and !is_dir($directory.$image)) {
    				$extension = strtolower(pathinfo($image, PATHINFO_EXTENSION));
    				if (in_array($extension, array("jpg", "jpeg", "gif", "png", "bmp")))
    					$files[] = array("thumb" => $uploads.$image, "image" => $uploads.$image);
    			}
    		}
    	}
    	
    	echo stripslashes(json_encode($files));
    }
}