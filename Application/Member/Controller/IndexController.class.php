<?php
namespace Member\Controller;
use Think\Controller;

/**
 * 首页控制器
 * @package Member\Controller
 */
class IndexController extends CommonController {
    /**
     * 系统首页
     */
    public function index(){
    	redirect(U('Home/Diary/index?view=self'));
    }
}