<?php
namespace Home\Controller;
use Think\Controller;
class MaterialController extends CommonController {
    public function index(){
    	$map = array();
    	$cateid = I('get.cateid','', 'trim');
    	$manufacturer = I('get.manufacturer','', 'trim');
    	$partnumber = I('get.partnumber','', 'trim');
    	$package = I('get.package','', 'trim');
    	$description = I('get.description','', 'trim');
    	$stock = I('get.stock','', 'trim');
    	if($cateid){
    		$map['cateid'] = $cateid;
    	}
    	if($manufacturer){
    		$map['manufacturer'] = $manufacturer;
    	}
    	if($partnumber){
    		$map['partnumber'] = $partnumber;
    	}
    	if($package){
    		$map['package'] = $package;
    	}
    	if($description){
    		$map['description'] = $description;
    	}
    	if($stock){
    		$map['stock'] = $stock;
    	}
    	
    	$count = $list = M('material')->where($map)->count();
    	$Page = new \Think\Page($count,25);
    	foreach($map as $key=>$val) {
    		$Page->parameter[$key] = urlencode($val);
    	}
    	$Page->setConfig('prev','上一页');
    	$Page->setConfig('next','下一页');
    	$Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
    	$show = $Page->show();
    	 
    	$list = M('material')->field('diary_material.*, diary_category.name as catename')
    	->join('diary_category ON diary_category.id = diary_material.cateid', 'LEFT')
    	->where($map)->order('diary_material.id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
    	
    	$category = M('category')->select();
    	
    	$this->assign('category', $category);
    	$this->assign('list', $list);
    	$this->assign('page', $show);
    	$this->assign('cateid', $cateid);
    	$this->assign('manufacturer', $manufacturer);
    	$this->assign('partnumber', $partnumber);
    	$this->assign('package', $package);
    	$this->assign('description', $description);
    	$this->assign('stock', $stock);
    	$this->display();
    }
    
    public function buyApplyIndex(){
    	$map = array();
    	$srchname = I('get.srchname','', 'trim');
    	$srchregdatestart = I('get.srchregdatestart','', 'trim');
    	$srchregdateend = I('get.srchregdateend','', 'trim');
    	if($srchname) {
    		$map['username'] = array('LIKE','%'.$srchname.'%');
    		$this->assign('srchname', $srchname);
    	}
    	if($srchregdatestart && $srchregdateend){
    		$this->assign('srchregdatestart', $srchregdatestart);
    		$this->assign('srchregdateend', $srchregdateend);
    		$map['diary_diary.datetime'] = array(array('egt',date('Y-m-d 00:00:00',strtotime($srchregdatestart))),array('elt',date('Y-m-d 23:59:59',strtotime($srchregdateend))),'AND');
    	}else{
    		if($srchregdatestart) {
    			$map['diary_diary.datetime'] = array('egt',date('Y-m-d 00:00:00',strtotime($srchregdatestart)));
    			$this->assign('srchregdatestart', $srchregdatestart);
    		}
    		if($srchregdateend) {
    			$map['diary_diary.datetime'] = array('elt',date('Y-m-d 23:59:59',strtotime($srchregdateend)));
    			$this->assign('srchregdateend', $srchregdateend);
    		}
    	}
    	
    	$count = $list = M('buyapply')->where($map)->count();
    	$Page = new \Think\Page($count,25);
    	foreach($map as $key=>$val) {
    		$Page->parameter[$key] = urlencode($val);
    	}
    	$Page->setConfig('prev','上一页');
    	$Page->setConfig('next','下一页');
    	$Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
    	$show = $Page->show();
    	
    	$list = M('buyapply')->where($map)->order('datetime DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
    	foreach($list as $key=>$value){
    		$list[$key]['applylist'] = M('buyapplylist')->where("apply_id='".$value['id']."'")->order('id ASC')->select();
    	}
    	$this->assign('list', $list);
    	$this->assign('page', $show);
    	$this->display();
    }
    
    public function buyApplyEdit(){
    	$id = intval(I('get.id'));
    	$apply_id = intval(I('get.apply_id'));
    	$buyapply = M('buyapply')->where("id='{$apply_id}'")->find();
    	if(session('group_id') != 1 && session('uid') != $buyapply['user_id']){
    		exit('Access deny');
    	}
    	
    	if (IS_POST) {
    		M('buyapplylist')->where("id='{$id}'")->save(
    			array(
    				'manufacturer' =>I('post.manufacturer'),
    				'partnumber'   =>I('post.partnumber'),
    				'package'      =>I('post.package'),
    				'quantity'     =>I('post.quantity'),
    				'project'      =>I('post.project'),
    				'description'  =>I('post.description'),
    				'receive_time' =>I('post.receive_time'),
    				'is_emergency' =>I('post.is_emergency')
    			)
    		);
    		$this->success('添加成功', U('Material/buyApplyIndex'), 2);
    	}else{
    		if($id){
    			$list = M('buyapplylist')->where("id='{$id}'")->find();
    		}
    		$this->assign('list', $list);
    		$this->display();
    	}
    }
    
    public function buyApplyAdd(){
    	if (IS_POST) {
    		if(!empty($_POST)){
    			$insertId = M('buyapply')->add(
    					array(
    							'user_id'  =>session('uid'),
    							'username' =>session('nickname'),
    							'datetime' =>date('Y-m-d H:i:s', time()),
    							'status'   =>1
    					)
    			);
    			foreach ($_POST['manufacturer'] as $key=>$value){
    				M('buyapplylist')->add(
	    				array(
		    				'apply_id'     =>$insertId,
		    				'manufacturer' =>$_POST['manufacturer'][$key],
		    				'partnumber'   =>$_POST['partnumber'][$key],
		    				'package'      =>$_POST['package'][$key],
		    				'quantity'     =>$_POST['quantity'][$key],
		    				'project'      =>$_POST['project'][$key],
		    				'description'  =>$_POST['description'][$key],
		    				'receive_time' =>$_POST['receive_time'][$key],
		    				'is_emergency' =>$_POST['is_emergency'][$key],
		    				'status'       =>1
		    			)
	    			);
	    		}
    		}
    		$this->success('添加成功', U('Material/buyApplyIndex'), 2);
    	}else{
    		$manufacturers = M('material')->distinct(true)->field('manufacturer')->order('id ASC')->limit("0, 500")->select();
    		$this->assign('manufacturers', $manufacturers);
    		
    		$partnumbers = M('material')->distinct(true)->field('partnumber')->order('id ASC')->limit("0, 500")->select();
    		$this->assign('partnumbers', $partnumbers);
    		 
    		$projects = M('buyapplylist')->distinct(true)->field('project')->order('id DESC')->limit("0, 500")->select();
    		$this->assign('projects', $projects);
    
    		$this->display();
    	}
    }
    
    public function finalBuyApplyIndex(){
    	$count = M('finalbuyapply')->count();
    	$Page = new \Think\Page($count,25);
    	$Page->setConfig('prev','上一页');
    	$Page->setConfig('next','下一页');
    	$Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
    	$show = $Page->show();
    	
    	$results = array();
    	$finalbuyapply_list = M('finalbuyapply')->field("id, username, datetime")->order('datetime DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
    	foreach($finalbuyapply_list as $finalbuyapply_key=>$finalbuyapply){
    		$finalbuyapplylist_list = M('finalbuyapplylist')->where("status=1 AND final_apply_id='".$finalbuyapply['id']."'")->order('final_apply_id DESC')->select();
    		foreach($finalbuyapplylist_list as $finalbuyapplylist_key=>$finalbuyapplylist){
    			$buyapplylist = M('buyapplylist')->where("id='".$finalbuyapplylist['applylist_id']."'")->find();
    			$buyapplylist['supplier'] = $finalbuyapplylist['supplier'];
    			$finalbuyapply['applylist'][$finalbuyapplylist['apply_id']]['applylist'][] = $buyapplylist;
    			
    			$buyapply = M('buyapply')->where("id='".$finalbuyapplylist['apply_id']."'")->find();
    			$finalbuyapply['applylist'][$finalbuyapplylist['apply_id']]['username'] = $buyapply['username'];
    			$finalbuyapply['applylist'][$finalbuyapplylist['apply_id']]['datetime'] = $buyapply['datetime'];
    		}
    		$results[] = $finalbuyapply;
    	}
    	$this->assign('list', $results);
    	$this->display();
    }
    
    public function finalBuyApplyAdd(){
    	if (session('group_id') > 1 && !session('access')['option_finalbuyapply']) {
    		$this->error('没有权限查看此页面...', U('Index/index'));
    	}
    	if (IS_POST) {
    		if(!empty($_POST)){
    			$insertId = M('finalbuyapply')->add(
    					array(
    							'user_id'  =>session('uid'),
    							'username'  =>session('nickname'),
    							'datetime' =>date('Y-m-d H:i:s', time()),
    							'status'   =>1
    					)
    			);
    			foreach ($_POST['applylist_ids'] as $key=>$value){
    				M('finalbuyapplylist')->add(
	    				array(
		    				'final_apply_id' =>$insertId,
		    				'apply_id'       =>intval($_POST['apply_ids'][$key]),
		    				'applylist_id'   =>intval($value),
		    				'supplier'       =>$_POST['supplier'][$key],
		    				'status'         =>1
	    				)
    				);
    				
    				M('buyapply')->where("id='".intval($_POST['apply_ids'][$key])."'")->save(
	    				array(
	    					'status'=>2
	    				)
    				);
    				
    				M('buyapplylist')->where("id='".intval($value)."'")->save(
	    				array(
		    				'status'=>2,
		    				'quantity'=>intval($_POST['quantity'][$key])
	    				)
    				);
    			}
    		}
    		$this->success('添加成功', U('Material/finalBuyApplyIndex'), 2);
    	}else{
    		$list = M('buyapply')->where("status=1")->order('datetime DESC')->select();
    		foreach($list as $key=>$value){
    			$list[$key]['applylist'] = M('buyapplylist')->where("status=1 AND apply_id='".$value['id']."'")->order('id ASC')->select();
    		}
    		$this->assign('list', $list);
    		$this->display();
    	}
    }
    
    public function buyConfirmIndex(){
    	$map = array();
    	$map['status'] = array('gt', 1);
    	$count = M('finalbuyapply')->where($map)->count();
    	$Page = new \Think\Page($count,4);
    	foreach($map as $key=>$val) {
    		$Page->parameter[$key] = urlencode($val);
    	}
    	$Page->setConfig('prev','上一页');
    	$Page->setConfig('next','下一页');
    	$Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
    	$show = $Page->show();
    	
    	$results = array();
    	$finalbuyapply_list = M('finalbuyapply')->field("id, username, datetime")->where($map)->order('datetime DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
    	foreach($finalbuyapply_list as $finalbuyapply_key=>$finalbuyapply){
    		$finalbuyapplylist_list = M('finalbuyapplylist')->where("status=1 AND final_apply_id='".$finalbuyapply['id']."'")->order('final_apply_id DESC')->select();
    		foreach($finalbuyapplylist_list as $finalbuyapplylist_key=>$finalbuyapplylist){
    			$buyapplylist = M('buyapplylist')->where("id='".$finalbuyapplylist['applylist_id']."'")->find();
    			$buyapplylist['supplier'] = $finalbuyapplylist['supplier'];
    			
    			$buyconfirm = M('buyconfirm')->where("applylist_id='".$finalbuyapplylist['applylist_id']."'")->find();
    			$buyapplylist['shipping_time'] = $buyconfirm['shipping_time'];
    			$buyapplylist['price'] = $buyconfirm['price'];
    			$buyapplylist['discount'] = $buyconfirm['discount'];
    			$buyapplylist['tax'] = $buyconfirm['tax'];
    			$buyapplylist['express'] = $buyconfirm['express'];
    			$buyapplylist['advance_payment'] = $buyconfirm['advance_payment'];
    			
    			$finalbuyapply['applylist'][$finalbuyapplylist['apply_id']]['applylist'][] = $buyapplylist;
    				
    			$buyapply = M('buyapply')->where("id='".$finalbuyapplylist['apply_id']."'")->find();
    			$finalbuyapply['applylist'][$finalbuyapplylist['apply_id']]['username'] = $buyapply['username'];
    			$finalbuyapply['applylist'][$finalbuyapplylist['apply_id']]['datetime'] = $buyapply['datetime'];
    		}
    		$results[] = $finalbuyapply;
    	}
    	
    	$this->assign('list', $results);
    	$this->assign('page', $show);
    	$this->display();
    }
    
    public function buyConfirmAdd(){
    	if (session('group_id') > 1 && !session('access')['option_buyconfirm']) {
    		$this->error('没有权限查看此页面...', U('Index/index'));
    	}
    	if (IS_POST) {
    		if(!empty($_POST)){
    			foreach ($_POST['applylist_ids'] as $key=>$value){
    				M('buyconfirm')->add(
	    				array(
		    				'apply_id'        =>intval($_POST['apply_ids'][$key]),
		    				'applylist_id'    =>intval($_POST['applylist_ids'][$key]),
		    				'shipping_time'   =>$_POST['shipping_time'][$key],
		    				'price'           =>round($_POST['price'][$key], 4),
		    				'discount'        =>round($_POST['discount'][$key], 4),
		    				'tax'             =>round($_POST['tax'][$key], 4),
		    				'express'         =>round($_POST['express'][$key], 4),
		    				'advance_payment' =>round($_POST['advance_payment'][$key], 4)
	    				)
    				);
    		
    				M('finalbuyapply')->where("id='".intval($_POST['final_apply_ids'][$key])."'")->save(
	    				array(
	    					'status'=>2
	    				)
    				);
    			}
    		}
    		$this->success('添加成功', U('Material/buyConfirmIndex'), 2);
    	}else{
			$results = array();
    		$finalbuyapply_list = M('finalbuyapply')->field("id, username, datetime")->where("status=1")->order('datetime DESC')->select();
    		foreach($finalbuyapply_list as $finalbuyapply_key=>$finalbuyapply){
    			$finalbuyapplylist_list = M('finalbuyapplylist')->where("status=1 AND final_apply_id='".$finalbuyapply['id']."'")->order('final_apply_id DESC')->select();
    			foreach($finalbuyapplylist_list as $finalbuyapplylist_key=>$finalbuyapplylist){
    				$buyapplylist = M('buyapplylist')->where("id='".$finalbuyapplylist['applylist_id']."'")->find();
    				$buyapplylist['supplier'] = $finalbuyapplylist['supplier'];
    				$finalbuyapply['applylist'][$finalbuyapplylist['apply_id']]['applylist'][] = $buyapplylist;
    				 
    				$buyapply = M('buyapply')->where("id='".$finalbuyapplylist['apply_id']."'")->find();
    				$finalbuyapply['applylist'][$finalbuyapplylist['apply_id']]['username'] = $buyapply['username'];
    				$finalbuyapply['applylist'][$finalbuyapplylist['apply_id']]['datetime'] = $buyapply['datetime'];
    			}
    			$results[] = $finalbuyapply;
    		}
    	 
    		$this->assign('list', $results);
    		$this->display();
    	}
    }
    
    public function buyCheckoutIndex(){
    	$map = array();
    	$map['status'] = array('gt', 2);
    	$count = M('finalbuyapply')->where($map)->count();
    	$Page = new \Think\Page($count,2);
    	foreach($map as $key=>$val) {
    		$Page->parameter[$key] = urlencode($val);
    	}
    	$Page->setConfig('prev','上一页');
    	$Page->setConfig('next','下一页');
    	$Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
    	$show = $Page->show();
    	
    	$results = array();
    	$finalbuyapply_list = M('finalbuyapply')->field("id, username, datetime")->where($map)->order('datetime DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
    	foreach($finalbuyapply_list as $finalbuyapply_key=>$finalbuyapply){
    		$finalbuyapplylist_list = M('finalbuyapplylist')->where("status=1 AND final_apply_id='".$finalbuyapply['id']."'")->order('final_apply_id DESC')->select();
    		foreach($finalbuyapplylist_list as $finalbuyapplylist_key=>$finalbuyapplylist){
    			$buyapplylist = M('buyapplylist')->where("id='".$finalbuyapplylist['applylist_id']."'")->find();
    			$buyapplylist['supplier'] = $finalbuyapplylist['supplier'];
    				
    			$buyconfirm = M('buyconfirm')->where("applylist_id='".$finalbuyapplylist['applylist_id']."'")->find();
    			$buyapplylist['shipping_time'] = $buyconfirm['shipping_time'];
    			$buyapplylist['price'] = $buyconfirm['price'];
    			$buyapplylist['discount'] = $buyconfirm['discount'];
    			$buyapplylist['tax'] = $buyconfirm['tax'];
    			$buyapplylist['express'] = $buyconfirm['express'];
    			$buyapplylist['advance_payment'] = $buyconfirm['advance_payment'];
    				
    			$finalbuyapply['applylist'][$finalbuyapplylist['apply_id']]['applylist'][] = $buyapplylist;
    	
    			$buyapply = M('buyapply')->where("id='".$finalbuyapplylist['apply_id']."'")->find();
    			$finalbuyapply['applylist'][$finalbuyapplylist['apply_id']]['user_id'] = $buyapply['user_id'];
    			$finalbuyapply['applylist'][$finalbuyapplylist['apply_id']]['username'] = $buyapply['username'];
    			$finalbuyapply['applylist'][$finalbuyapplylist['apply_id']]['datetime'] = $buyapply['datetime'];
    		}
    	
    		$buycheckouts = M('buycheckout')->where("final_apply_id='".$finalbuyapply['id']."'")->select();
    		foreach ($buycheckouts as $buycheckout){
    			if($buycheckout['name'] == 'proposer_checkout'){
    				$finalbuyapply['proposer_checkout'][$buycheckout['user_id']] = $buycheckout['value'];
    			}
    			if($buycheckout['name'] == 'optiontor_checkout'){
    				$finalbuyapply['optiontor_checkout'] = $buycheckout['value'];
    			}
    			if($buycheckout['name'] == 'header_checkout'){
    				$finalbuyapply['header_checkout'] = $buycheckout['value'];
    			}
    			if($buycheckout['name'] == 'teamleader_checkout'){
    				$finalbuyapply['teamleader_checkout'] = $buycheckout['value'];
    			}
    			if($buycheckout['name'] == 'administrator_checkout'){
    				$finalbuyapply['administrator_checkout'] = $buycheckout['value'];
    			}
    		}
    		 
    		$results[] = $finalbuyapply;
    	}
    	$this->assign('list', $results);
    	$this->assign('page', $show);
    	$this->display();
    }
    
    public function buyCheckout(){
    	if (session('group_id') > 1 && !session('access')['option_buycheckout']) {
    		$this->error('没有权限查看此页面...', U('Index/index'));
    	}
    	if (IS_POST) {
    		if(!empty($_POST)){
    			foreach ($_POST['final_apply_ids'] as $key=>$value){
    				$data = array();
    				if($_POST['is_proposer'][$key]){
    					foreach ($_POST['proposer_checkout'][$value] as $user_id=>$proposer){
    						$data['final_apply_id'] = intval($value);
    						$data['name'] = 'proposer_checkout';
    						$data['user_id'] = $user_id;
    						$data['value'] = '1';
    						$data['datetime'] = date('Y-m-d H:i:s', time());
    						if($_POST['proposer_checkout'][$value][$user_id] && !M('buycheckout')->where("user_id='".$user_id."' AND name='proposer_checkout' AND final_apply_id='".intval($value)."'")->find()){
    							M('buycheckout')->add($data);
    						}
    					}
    				}
    				if($_POST['is_optiontor'][$key] && $_POST['optiontor_checkout'][$value]){
    					$data['final_apply_id'] = intval($value);
    					$data['name'] = 'optiontor_checkout';
    					$data['value'] = '1';
    					$data['datetime'] = date('Y-m-d H:i:s', time());
    					if(!M('buycheckout')->where("name='optiontor_checkout' AND final_apply_id='".intval($value)."'")->find()){
    						M('buycheckout')->add($data);
    					}
    				}
    				if($_POST['is_header'][$key] && $_POST['header_checkout'][$value]){
    					$data['final_apply_id'] = intval($value);
    					$data['name'] = 'header_checkout';
    					$data['value'] = '1';
    					$data['datetime'] = date('Y-m-d H:i:s', time());
    					if(!M('buycheckout')->where("name='header_checkout' AND final_apply_id='".intval($value)."'")->find()){
    						M('buycheckout')->add($data);
    					}
    				}
    				if($_POST['is_teamleader'][$key] && $_POST['teamleader_checkout'][$value]){
    					$data['final_apply_id'] = intval($value);
    					$data['name'] = 'teamleader_checkout';
    					$data['value'] = '1';
    					$data['datetime'] = date('Y-m-d H:i:s', time());
    					if(!M('buycheckout')->where("name='teamleader_checkout' AND final_apply_id='".intval($value)."'")->find()){
    						M('buycheckout')->add($data);
    					}
    				}
    				if($_POST['is_administrator'][$key] && $_POST['administrator_checkout'][$value]){
    					$data['final_apply_id'] = intval($value);
    					$data['name'] = 'administrator_checkout';
    					$data['value'] = '1';
    					$data['datetime'] = date('Y-m-d H:i:s', time());
    					if(!M('buycheckout')->where("name='administrator_checkout' AND final_apply_id='".intval($value)."'")->find()){
    						M('buycheckout')->add($data);
    					}
    				}
    				
    				$buycheckouts = M('buycheckout')->where("final_apply_id='".intval($value)."'")->select();
    				if(count($buycheckouts) == (count($_POST['proposers'][$value])+ 4)){
    					M('finalbuyapply')->where("id='".intval($value)."'")->save(
	    					array(
	    						'status'=>3
	    					)
    					);
    				}
    			}
    		}
    		$this->success('操作成功', U('Material/buyCheckout'), 2);
    	}else{
    		$results = array();
    		$finalbuyapply_list = M('finalbuyapply')->field("id, username, datetime")->where("status=2")->order('datetime DESC')->select();
    		foreach($finalbuyapply_list as $finalbuyapply_key=>$finalbuyapply){
    			$finalbuyapplylist_list = M('finalbuyapplylist')->where("status=1 AND final_apply_id='".$finalbuyapply['id']."'")->order('final_apply_id DESC')->select();
    			foreach($finalbuyapplylist_list as $finalbuyapplylist_key=>$finalbuyapplylist){
    				$buyapplylist = M('buyapplylist')->where("id='".$finalbuyapplylist['applylist_id']."'")->find();
    				$buyapplylist['supplier'] = $finalbuyapplylist['supplier'];
    				 
    				$buyconfirm = M('buyconfirm')->where("applylist_id='".$finalbuyapplylist['applylist_id']."'")->find();
    				$buyapplylist['shipping_time'] = $buyconfirm['shipping_time'];
    				$buyapplylist['price'] = $buyconfirm['price'];
    				$buyapplylist['discount'] = $buyconfirm['discount'];
    				$buyapplylist['tax'] = $buyconfirm['tax'];
    				$buyapplylist['express'] = $buyconfirm['express'];
    				$buyapplylist['advance_payment'] = $buyconfirm['advance_payment'];
    				 
    				$finalbuyapply['applylist'][$finalbuyapplylist['apply_id']]['applylist'][] = $buyapplylist;
    		
    				$buyapply = M('buyapply')->where("id='".$finalbuyapplylist['apply_id']."'")->find();
    				$finalbuyapply['applylist'][$finalbuyapplylist['apply_id']]['user_id'] = $buyapply['user_id'];
    				$finalbuyapply['applylist'][$finalbuyapplylist['apply_id']]['username'] = $buyapply['username'];
    				$finalbuyapply['applylist'][$finalbuyapplylist['apply_id']]['datetime'] = $buyapply['datetime'];
    				
    				if($buyapply['user_id'] == session('uid') || session('uid') == 207){
    					$finalbuyapply['is_proposer'][$buyapply['user_id']] = 1;
    				}
    				if(session('uid') == 207){
    					$finalbuyapply['is_optiontor'] = 1;
    				}
    				if(session('uid') == 33 || session('uid') == 207){
    					$finalbuyapply['is_header'] = 1;
    				}
    				if(session('uid') == 34 || session('uid') == 169 || session('uid') == 207){
    					$finalbuyapply['is_teamleader'] = 1;
    				}
    				if(session('uid') == 80 || session('uid') == 207){
    					$finalbuyapply['is_administrator'] = 1;
    				}
    			}
    		
    			$buycheckouts = M('buycheckout')->where("final_apply_id='".$finalbuyapply['id']."'")->select();
    			foreach ($buycheckouts as $buycheckout){
    				if($buycheckout['name'] == 'proposer_checkout'){
    					$finalbuyapply['proposer_checkout'][$buycheckout['user_id']] = $buycheckout['value'];
    				}
    				if($buycheckout['name'] == 'optiontor_checkout'){
    					$finalbuyapply['optiontor_checkout'] = $buycheckout['value'];
    				}
    				if($buycheckout['name'] == 'header_checkout'){
    					$finalbuyapply['header_checkout'] = $buycheckout['value'];
    				}
    				if($buycheckout['name'] == 'teamleader_checkout'){
    					$finalbuyapply['teamleader_checkout'] = $buycheckout['value'];
    				}
    				if($buycheckout['name'] == 'administrator_checkout'){
    					$finalbuyapply['administrator_checkout'] = $buycheckout['value'];
    				}
    			}
    			
    			$results[] = $finalbuyapply;
    		}
    		$this->assign('list', $results);
    		$this->display();
    	}
    }
    
    public function receiveIndex(){
    	$map = array();
    	$map['diary_receive.shipping_progress'] = '已到货';
    	
    	$srchregdatestart = I('get.srchregdatestart','', 'trim');
    	$srchregdateend = I('get.srchregdateend','', 'trim');
    	if($srchregdatestart && $srchregdateend){
    		$this->assign('srchregdatestart', $srchregdatestart);
    		$this->assign('srchregdateend', $srchregdateend);
    		$map['diary_receive.updatetime'] = array(array('egt',date('Y-m-d 00:00:00',strtotime($srchregdatestart))),array('elt',date('Y-m-d 23:59:59',strtotime($srchregdateend))),'AND');
    	}else{
    		if($srchregdatestart) {
    			$map['diary_receive.updatetime'] = array('egt',date('Y-m-d 00:00:00',strtotime($srchregdatestart)));
    			$this->assign('srchregdatestart', $srchregdatestart);
    		}
    		if($srchregdateend) {
    			$map['diary_receive.updatetime'] = array('elt',date('Y-m-d 23:59:59',strtotime($srchregdateend)));
    			$this->assign('srchregdateend', $srchregdateend);
    		}
    	}
    	
    	$count = M('receive')->where($map)->count();
    	$Page = new \Think\Page($count,25);
    	foreach($map as $key=>$val) {
    		$Page->parameter[$key] = urlencode($val);
    	}
    	$Page->setConfig('prev','上一页');
    	$Page->setConfig('next','下一页');
    	$Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
    	$show = $Page->show();
    	
    	$results = array();
    	$list = M('receive')->field('diary_receive.updatetime, diary_buyconfirm.price, diary_buyapplylist.*')
    	->join('diary_buyconfirm ON diary_buyconfirm.applylist_id = diary_receive.applylist_id', 'LEFT')
    	->join('diary_buyapplylist ON diary_buyapplylist.id = diary_receive.applylist_id', 'LEFT')
    	->where($map)->order('updatetime DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
    	$this->assign('list', $list);
    	$this->assign('page', $show);
    	$this->display();
    }
    public function receiveAdd(){
    	if (session('group_id') > 1 && !session('access')['option_receive']) {
    		$this->error('没有权限查看此页面...', U('Index/index'));
    	}
    	if (IS_POST) {
    		if(!empty($_POST)){
    			$receive_status = true;
    			foreach ($_POST['applylist_ids'] as $key=>$value){
    				$data = array();
    				$data['final_apply_id'] = intval($_POST['final_apply_id']);
    				$data['apply_id'] = intval($_POST['apply_ids'][$key]);
    				$data['applylist_id'] = intval($value);
    				
    				$receive = M('receive')->where("final_apply_id='".$data['final_apply_id']."' AND apply_id='".$data['apply_id']."' AND applylist_id='".$data['applylist_id']."'")->find();
    				if($receive){
    					$data['updatetime'] = date('Y-m-d H:i:s', time());
    					if($receive['shipping_progress'] == '已到货'){
    						if($receive['invoice_progress'] != '已到货' && $_POST['invoice_progress'][$key]){
    							$data['invoice_progress'] = $_POST['invoice_progress'][$key];
    						}
    					}else{
    						$data['total'] = $_POST['total'][$key];
    						$data['remark'] = $_POST['remark'][$key];
    						$data['payer'] = $_POST['payer'][$key];
    						if($_POST['shipping_progress'][$key]){
    							$data['shipping_progress'] = $_POST['shipping_progress'][$key];
    						}
    						if($_POST['invoice_progress'][$key]){
    							$data['invoice_progress'] = $_POST['invoice_progress'][$key];
    						}
    					}
    					M('receive')->where("final_apply_id='".$data['final_apply_id']."' AND apply_id='".$data['apply_id']."' AND applylist_id='".$data['applylist_id']."'")->save($data);
    				}else{
    					$data['total'] = $_POST['total'][$key];
    					$data['remark'] = $_POST['remark'][$key];
    					$data['payer'] = $_POST['payer'][$key];
    					$data['shipping_progress'] = $_POST['shipping_progress'][$key];
    					$data['invoice_progress'] = $_POST['invoice_progress'][$key];
    					$data['datetime'] = date('Y-m-d H:i:s', time());
    					M('receive')->add($data);
    				}
    				
    				if($_POST['invoice_progress'][$key] != '已到货'){
    					$receive_status = false;
    				}
    				
    				if($_POST['shipping_progress'][$key] != '已到货'){
    					$receive_status = false;
    				}else{
    					//入库
    					$buyapplylist = M('buyapplylist')->where("id='".intval($value)."'")->find();
    					if($buyapplylist['code']){
    						M('material')->where("code='".$buyapplylist['code']."'")->save("stock=stock+".$buyapplylist['quantity']);
    					}else{
    						$code = $_POST['codes'][$key];
    						$buyconfirm = M('buyconfirm')->where("applylist_id='".intval($value)."'")->find();
    						$material_data = array(
    								'code'         =>$code,
    								'manufacturer' =>$buyapplylist['manufacturer'],
    								'partnumber'   =>$buyapplylist['partnumber'],
    								'package'      =>$buyapplylist['package'],
    								'stock'        =>$buyapplylist['quantity'],
    								'description'  =>$buyapplylist['description'],
    								'price'        =>$buyconfirm['price'],
    								'status'       =>1
    						);
    						M('material')->add($material_data);
    					}
    					
    				}
    			}
    			if($receive_status){
    				M('finalbuyapply')->where("id='".intval($_POST['final_apply_id'])."'")->save(
	    				array(
	    					'status'=>4
	    				)
    				);
    			}
    		}
    		
    		$this->success('操作成功', U('Material/receiveIndex'), 2);
    	}else{
    		$results = array();
    		$finalbuyapply_list = M('finalbuyapply')->field("id, username, datetime")->where("status=3")->order('datetime DESC')->select();
    		foreach($finalbuyapply_list as $finalbuyapply_key=>$finalbuyapply){
    			$finalbuyapplylist_list = M('finalbuyapplylist')->where("status=1 AND final_apply_id='".$finalbuyapply['id']."'")->order('final_apply_id DESC')->select();
    			foreach($finalbuyapplylist_list as $finalbuyapplylist_key=>$finalbuyapplylist){
    				$buyapplylist = M('buyapplylist')->where("id='".$finalbuyapplylist['applylist_id']."'")->find();
    				$buyapplylist['supplier'] = $finalbuyapplylist['supplier'];

    				$buyconfirm = M('buyconfirm')->where("applylist_id='".$finalbuyapplylist['applylist_id']."'")->find();
    				$buyapplylist['shipping_time'] = $buyconfirm['shipping_time'];
    				$buyapplylist['price'] = $buyconfirm['price'];
    				$buyapplylist['discount'] = $buyconfirm['discount'];
    				$buyapplylist['tax'] = $buyconfirm['tax'];
    				$buyapplylist['express'] = $buyconfirm['express'];
    				$buyapplylist['advance_payment'] = $buyconfirm['advance_payment'];
    				
    				$receive = M('receive')->where("applylist_id='".$finalbuyapplylist['applylist_id']."'")->find();
    				$buyapplylist['total'] = $receive['total'];
    				$buyapplylist['remark'] = $receive['remark'];
    				$buyapplylist['payer'] = $receive['payer'];
    				$buyapplylist['shipping_progress'] = $receive['shipping_progress'];
    				$buyapplylist['invoice_progress'] = $receive['invoice_progress'];
    				
    				
    				$finalbuyapply['applylist'][$finalbuyapplylist['apply_id']]['applylist'][] = $buyapplylist;
    					
    				$buyapply = M('buyapply')->where("id='".$finalbuyapplylist['apply_id']."'")->find();
    				$finalbuyapply['applylist'][$finalbuyapplylist['apply_id']]['username'] = $buyapply['username'];
    				$finalbuyapply['applylist'][$finalbuyapplylist['apply_id']]['datetime'] = $buyapply['datetime'];
    			}
    			$results[] = $finalbuyapply;
    		}
    		
    		$this->assign('list', $results);
    		$this->display();
    	}
    }
    
    public function usesIndex(){
    	if (session('group_id') > 1 && !session('access')['option_materialuses']) {
    		$this->error('没有权限查看此页面...', U('Index/index'));
    	}
    	$map = array();
    	$srchregdatestart = I('get.srchregdatestart','', 'trim');
    	$srchregdateend = I('get.srchregdateend','', 'trim');
    	if($srchregdatestart && $srchregdateend){
    		$this->assign('srchregdatestart', $srchregdatestart);
    		$this->assign('srchregdateend', $srchregdateend);
    		$map['diary_materialuses.datetime'] = array(array('egt',date('Y-m-d 00:00:00',strtotime($srchregdatestart))),array('elt',date('Y-m-d 23:59:59',strtotime($srchregdateend))),'AND');
    	}else{
    		if($srchregdatestart) {
    			$map['diary_materialuses.datetime'] = array('egt',date('Y-m-d 00:00:00',strtotime($srchregdatestart)));
    			$this->assign('srchregdatestart', $srchregdatestart);
    		}
    		if($srchregdateend) {
    			$map['diary_materialuses.datetime'] = array('elt',date('Y-m-d 23:59:59',strtotime($srchregdateend)));
    			$this->assign('srchregdateend', $srchregdateend);
    		}
    	}
    	 
    	$count = M('materialuses')->where($map)->count();
    	$Page = new \Think\Page($count,25);
    	foreach($map as $key=>$val) {
    		$Page->parameter[$key] = urlencode($val);
    	}
    	$Page->setConfig('prev','上一页');
    	$Page->setConfig('next','下一页');
    	$Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
    	$show = $Page->show();
    	 
    	$results = array();
    	/* $list = M('materialuses')->field('diary_material.*,diary_materialuses.project,diary_materialuses.datetime,diary_materialuses.username,diary_materialuses.add_username,diary_materialuses.quantity')
    	->join('diary_material ON diary_material.code = diary_material.code', 'RIGHT')
    	->where($map)->order('datetime DESC')->limit($Page->firstRow.','.$Page->listRows)->select(); */
    	$list = M('materialuses')->where($map)->order('datetime DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
    	$this->assign('list', $list);
    	$this->assign('page', $show);
    	$this->display();
    }
    
    public function usesAdd(){
    	if (session('group_id') > 1 && !session('access')['option_materialuses']) {
    		$this->error('没有权限查看此页面...', U('Index/index'));
    	}
    	if (IS_POST) {
    		foreach ($_POST['code'] as $key=>$code){
    			//if($code){
    				M('materialuses')->add(array(
    						'code'        =>$code,
    						'project'     =>$_POST['project'][$key],
    						'quantity'    =>intval($_POST['quantity'][$key]),
    						'datetime'    =>date('Y-m-d H:i:s', time()),
    						'add_user_id' =>session('uid'),
    						'add_username'=>session('nickname')
    					)
    				);
    				
    				//M('material')->where("code='{$code}'")->save("stock=stock-".intval($_POST['quantity'][$key]));
    			}
    		//}
    		$this->success('添加成功', U('Material/usesAdd'), 2);
    	}else{
    		$this->display();
    	}
    }
    
    public function materialSearch(){
    	if (IS_POST) {
    		$map = array();
    		$code = I('post.code','', 'trim');
    		$manufacturer = I('post.manufacturer','', 'trim');
    		$partnumber = I('post.partnumber','', 'trim');
    		$package = I('post.package','', 'trim');
    		$description = I('post.description','', 'trim');
    		if($code){
    			$map['code'] = $code;
    		}
    		if($manufacturer){
    			$map['manufacturer'] = $manufacturer;
    		}
    		if($partnumber){
    			$map['partnumber'] = $partnumber;
    		}
    		if($package){
    			$map['package'] = $package;
    		}
    		if($description){
    			$map['description'] = $description;
    		}
    		
    		if(!$code && !$manufacturer && !$partnumber && !$package && !$description){
    			exit();
    		}
    		
    		$list = M('material')->where($map)->order('id DESC')->limit('0, 30')->select();
    		$this->ajaxReturn($list);
    	}
    }
    
    public function categoryIndex(){
    	$map = array();
    	$count = $list = M('category')->where($map)->count();
    	$Page = new \Think\Page($count,25);
    	foreach($map as $key=>$val) {
    		$Page->parameter[$key] = urlencode($val);
    	}
    	$Page->setConfig('prev','上一页');
    	$Page->setConfig('next','下一页');
    	$Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
    	$show = $Page->show();
    	
    	$list = M('category')->where($map)->order('sort ASC')->limit($Page->firstRow.','.$Page->listRows)->select();
    	$this->assign('list', $list);
    	$this->display();
    }
    
    public function categoryAdd(){
    	if (session('group_id') > 1) {
    		$this->error('没有权限查看此页面...', U('Index/index'));
    	}
    	if (IS_POST) {
    		$data = array();
    		$data['name'] = I('post.name');
    		$data['sort'] = I('post.sort');
    		$data['code'] = I('post.code');
    		$data['type'] = 1;
    		$data['status'] = 1;
    	
    		$category = M('category');
    		$category->add($data);
    		$this->success('添加成功', U('Material/categoryIndex'), 2);
    	}else{
    		$this->display();
    	}
    }
    
    public function categoryEdit(){
    	if (session('group_id') > 1) {
    		$this->error('没有权限查看此页面...', U('Index/index'));
    	}
    	$id = I('get.id','intval');
    	if (IS_POST) {
    		$data = array();
    		$data['name'] = I('post.name');
    		$data['sort'] = I('post.sort');
    		$data['code'] = I('post.code');
    		$data['type'] = 1;
    		$data['status'] = 1;
    		 
    		$category = M('category');
    		$category->where('id='.$id)->save($data);
    		$this->success('编辑成功', U('Material/categoryIndex'), 2);
    	}else{
    		$list = M('category')->where('id='.$id)->find();
    		$this->assign('list', $list);
    		$this->display();
    	}
    }
    
    public function categoryDelete(){
    	if (session('group_id') > 1) {
    		$this->error('没有权限查看此页面...', U('Index/index'));
    	}
    	$id = I('get.id','intval');
    	M('category')->where("id='".$id."'")->delete();
    	$this->success('删除成功', U('Material/categoryIndex'), 2);
    }
}