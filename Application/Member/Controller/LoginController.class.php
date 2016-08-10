<?php
namespace Member\Controller;
use Think\Controller;

/**
 * Class LoginController
 * @package Member\Controller
 */
class LoginController extends Controller {
    /**
     * 用户登录
     */
    public function login()
    {
        // 判断提交方式
        if (IS_POST) {
            // 实例化Login对象
            $login = D('login');

            // 自动验证 创建数据集
            if (!$data = $login->create()) {
                // 防止输出中文乱码
                header("Content-type: text/html; charset=utf-8");
                exit($login->getError());
            }
			
            // 组合查询条件
            $where = array();
            $where['username'] = $data['username'];
            $result = $login->where($where)->find();
            // 验证用户名 对比 密码
            //if ($result && $result['password'] == $data['password']) {
            if ($result) {
            	$lifeTime= 24*3600;
            	setcookie(session_name(),session_id(),time()+$lifeTime,"/");
            	
            	//更新IP
            	$ip = get_client_ip();
            	if($ip != $result['ip']){
            		M('users')->where('userid='.$result['userid'])->save(array('ip'=>$ip));
            	}
            	
                // 存储session
                session('uid', $result['userid']);          // 当前用户id
                session('nickname', $result['nickname']);   // 当前用户昵称
                session('username', $result['username']);   // 当前用户名
                session('group_id', $result['group_id']);
                session('department_id', $result['department_id']);
                session('email', $result['email']);
                
                // 获取用户权限
                $group_access = M('group')->where(array('id'=>session('group_id')))->find();
                $access = unserialize($group_access['access']);
                $user_access = unserialize($result['access']);
                foreach ($user_access as $key=>$value){
                	if($value){
                		$access[$key] = $value;
                	}
                }
                session('access', $access);
                
                //判断是否为评委
                if(M('judge')->where('userid='.$result['userid'])->find()){
                	session('access_judge', 1);
                }else{
                	session('access_judge', 0);
                }
                
                if($result['userid'] > 6){
	                //判断有多少天没有写日报
	                $no_diary_days = 0;
	                $latest_diary = M('diary')->where('userid='.$result['userid'])->order('datetime DESC')->limit(0,1)->select();
	                $latest_time = $latest_diary[0]['datetime'];
	                if(date('Y-m-d', strtotime($latest_time)) != date('Y-m-d', time())){
		                $dates = prDates(date('Y-m-d', strtotime($latest_time)), date('Y-m-d', time()));
		                foreach ($dates as $k=>$v){
		                	if($k > 0 && date('w', strtotime($v)) != 0 && date('w', strtotime($v)) != 6){
		                		$no_diary_days++;
		                	}
		                }
		                session('no_diary_days', $no_diary_days);
	                }
                }
                
                //近期项目公告
                if(in_array($result['group_id'], array(1,7,8,3)) || in_array($result['department_id'], array(7))){
                	session('projects_view', 1);
                }
                //近期测试公告
                if(in_array($result['group_id'], array(1,7)) || in_array($result['department_id'], array(1,5,7)) || in_array($result['userid'], array(74,173,63,32,44,43,70))){
                	session('projects_test_view', 1);
                }
                
                //获取最新公告
                $new_article = M('article')->where("type='1' AND datediff('".date('Y-m-d 23:59:59', time())."', datetime) < 8")->count();
                session('new_article', $new_article);
                
                //获取最新提醒
                $new_message = M('message')->where("to_username = '".session('nickname')."' AND datediff('".date('Y-m-d 23:59:59', time())."', datetime) < 3")->count();
                session('new_message', $new_message);
                
                //获取最新任务
                $new_task = M('iplists')->where("datediff(official_datetime, '".date('Y-m-d 23:59:59', time())."') > 0 AND (
                moni_headers LIKE '%".session('nickname')."%' OR 
                shuzi_headers LIKE '%".session('nickname')."%' OR 
               	bantu_headers LIKE '%".session('nickname')."%' OR 
                header LIKE '%".session('nickname')."%' 
                )")->count();
                session('new_task', $new_task);
                
                //$this->success('登录成功,正跳转至系统首页...', U('Index/index'), 0);
                redirect(U('Home/Diary/index?view=self'));
            } else {
                $this->error('登录失败,用户名或密码不正确!');
            }
        } else {
            $this->display();
        }
    }

    /**
     * 用户注册
     */
    public function register()
    {
        // 判断提交方式 做不同处理
        if (IS_POST) {
            // 实例化User对象
            $user = D('users');
            // 自动验证 创建数据集
            if (!$data = $user->create()) {
                // 防止输出中文乱码
                header("Content-type: text/html; charset=utf-8");
                exit($user->getError());
            }

            //插入数据库
            if ($id = $user->add($data)) {
            	$ip = get_client_ip();
            	M('users')->where('userid='.$id)->save(array('ip'=>$ip));
                $this->success('注册成功', U('Index/index'), 2);
            } else {
                $this->error('注册失败');
            }
        } else {
        	$list = M('Department')->order('sort asc')->limit(50)->select();
        	$this->assign('list', $list);
            $this->display();
        }
    }

    /**
     * 用户注销
     */
    public function logout()
    {
        // 清楚所有session
        session(null);
        redirect(U('Login/login'), 0, 'Logout...');
    }

    /**
     * 验证码
     */
    public function verify()
    {
        // 实例化Verify对象
        $verify = new \Think\Verify();

        // 配置验证码参数
        $verify->fontSize = 14;     // 验证码字体大小
        $verify->length = 4;        // 验证码位数
        $verify->imageH = 34;       // 验证码高度
        $verify->useImgBg = false;   // 开启验证码背景
        $verify->useNoise = false;  // 关闭验证码干扰杂点
        $verify->entry();
    }
}