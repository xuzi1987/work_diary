<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends CommonController {
    public function index(){
    	//echo session('uid');
    	if (session('uid')) {
    		$this->redirect('/Member/Index/index');
    	} 
    }
}