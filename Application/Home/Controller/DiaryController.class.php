<?php
namespace Home\Controller;
use Think\Controller;
class DiaryController extends CommonController {
    public function index(){
    	$map = array();
    	if(I('get.view') == 'self' || I('get.username','', 'trim') == session('username')){
    		$map['diary_diary.userid'] = session('uid');
    	}else if(I('get.view') == 'development'){
    		if (session('access')['view_development_diary'] < 1) {
    			$this->error('没有权限查看此页面...', U('Index/index'));
    		}
    		$map['diary_users.department_id'] = array('NEQ',4);
    	}else if(I('get.view') == 'administration'){
    		if (session('access')['view_administration_diary'] < 1) {
    			$this->error('没有权限查看此页面...', U('Index/index'));
    		}
    		$map['diary_users.department_id'] = 4;
    	}else if(I('get.username','', 'trim')){
    		$userinfo = M('users')->where("username='".I('get.username','', 'trim')."'")->find();
    		$team_list = M('teamusers')->where('userid='.$userinfo['userid'])->select();
    		$is_owner = false;
    		foreach ($team_list as $team){
    			$teaminfo = M('teamusers')->where(array('id'=>$team['id'],'type'=>1,'userid'=>session('uid')))->find();
    			if($teaminfo){
    				$is_owner = true;
    			}
    		}
    		if (!$is_owner) {
    			$this->error('没有权限查看此页面...', U('Index/index'));
    		}
    	}elseif (session('access')['view_administration_diary'] < 1 && session('access')['view_development_diary'] < 1) {
		    $this->error('没有权限查看此页面...', U('Index/index'));
		}
    	$srchname = I('get.srchname','', 'trim');
    	$srchregdatestart = I('get.srchregdatestart','', 'trim');
    	$srchregdateend = I('get.srchregdateend','', 'trim');
    	$srchdepartmentid = I('get.srchdepartmentid','', 'intval');
    	 
    	if(I('get.username','', 'trim')) {
    		$map['diary_users.username'] = I('get.username','', 'trim');
    	}
    	if($srchname) {
    		$map['diary_users.nickname'] = array('LIKE','%'.$srchname.'%');
    		$this->assign('srchname', $srchname);
    	}
    	if($srchdepartmentid) {
    		$map['diary_users.department_id'] = $srchdepartmentid;
    		$this->assign('srchdepartmentid', $srchdepartmentid);
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
    	
    	if(I('get.view') != 'self' && !I('get.username')){
	    	$diary_userids = array();
	    	$teams = M('teamusers')->where(array('userid'=>session('uid'), 'type'=>1))->select();
	    	foreach ($teams as $t){
	    		$team_users = M('teamusers')->where(array('teamid'=>$t['teamid']))->select();
	    		foreach ($team_users as $tu){
	    			if(session('uid') != 84 || (session('uid') == 84 && ($tu['userid'] !=37 && $tu['userid'] != 38))){
	    				$diary_userids[] = $tu['userid'];
	    			}
	    		}
	    	}
	    	$diary_userids = implode($diary_userids, ',');
	    	if($diary_userids && session('group_id') != 7){
	    		$map['diary_diary.userid'] = array('in',$diary_userids);
	    	}
    	}
    	
    	$diary = M('diary');
    	$count = $list = $diary
    	->join('diary_users ON diary_diary.userid = diary_users.userid')
    	->join('diary_department ON diary_users.department_id = diary_department.id')
    	->where($map)->count();
    	$Page = new \Think\Page($count,20);
    	foreach($map as $key=>$val) {
    		$Page->parameter[$key] = urlencode($val);
    	}
    	$Page->setConfig('prev','上一页');
    	$Page->setConfig('next','下一页');
    	$Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
    	$show = $Page->show();
    	
    	$results = $diary->field('diary_diary.*,diary_users.nickname,diary_department.name,diary_users.department_id,diary_users.usort,diary_users.regdate')
    	->join('diary_users ON diary_diary.userid = diary_users.userid')
    	->join('diary_department ON diary_users.department_id = diary_department.id')
    	->where($map)->order('diary_department.sort ASC, diary_diary.datetime DESC, diary_users.usort DESC, diary_users.regdate ASC, diary_users.username ASC, diary_diary.id ASC')->limit($Page->firstRow.','.$Page->listRows)->select();
    	$list = array();
    	foreach ($results as $key=>$rs){
    		$key_str = $rs['nickname'].date('Y-m-d', strtotime($rs['datetime']));
    		$summary = M('summary')->where("datetime LIKE '%".date('Y-m-d', strtotime($rs['datetime']))."%' AND userid='".$rs['userid']."'")->order('id ASC')->select();
    		$rs['summary'] = $summary;
    		
    		$diaryitem = M('diaryitem')->where("datetime LIKE '%".date('Y-m-d', strtotime($rs['datetime']))."%' AND userid='".$rs['userid']."'")->select();
    		$rs['diaryitem'] = $diaryitem;
    		
    		$comment = M('comment')->where("diary_id='".$rs['id']."'")->select();
    		foreach ($comment as $key=>$value){
    			if($value['userid']){
    				$userinfo = M('users')->where('userid='.$value['userid'])->find();
    				$comment[$key]['content'] .= '----by:'.$userinfo['nickname'];
    			}
    		}
    		$rs['comment'] = $comment;
    		$list[$key_str][] = $rs;
    	}
    	
    	foreach ($list as $key=>$value){
    		foreach ($value as $k=>$v){
    			$list[$key][$k]['rowspan'] = count($value);
    		}
    	}
    	
    	$this->assign('list', $list);
    	$this->assign('page', $show);
    	
    	$department_list = M('Department')->order('sort asc')->limit(50)->select();
    	$this->assign('department_list', $department_list);
    	$this->assign('view', I('get.view'));
    	$this->assign('username', I('get.username','', 'trim'));
    	$this->display();
    }
    
    public function export(){
    	set_time_limit(0);
    	$map = array();
    	if(I('get.view') == 'development'){
    		$map['diary_users.department_id'] = array('NEQ',4);
    	}
    	if(I('get.view') == 'administration'){
    		$map['diary_users.department_id'] = 4;
    	}
    	$map['diary_diary.datetime'] = array('LIKE','%'.date('Y-m-d', time()).'%');
    	
    	if(I('get.view') != 'self' && !I('get.username')){
	    	$diary_userids = array();
	    	$teams = M('teamusers')->where(array('userid'=>session('uid'), 'type'=>1))->select();
	    	foreach ($teams as $t){
	    		$team_users = M('teamusers')->where(array('teamid'=>$t['teamid']))->select();
	    		foreach ($team_users as $tu){
	    			if(session('uid') != 84 || (session('uid') == 84 && ($tu['userid'] !=37 && $tu['userid'] != 38))){
	    				$diary_userids[] = $tu['userid'];
	    			}
	    		}
	    	}
	    	$diary_userids = implode($diary_userids, ',');
	    	if($diary_userids && session('group_id') != 7){
	    		$map['diary_diary.userid'] = array('in',$diary_userids);
	    	}
    	}
    	
    	$diary = M('diary');
    	$results = $diary->field('diary_diary.*,diary_users.nickname,diary_department.name,diary_users.department_id,diary_users.usort,diary_users.regdate')
    	->join('diary_users ON diary_diary.userid = diary_users.userid')
    	->join('diary_department ON diary_users.department_id = diary_department.id')
    	->where($map)->order('diary_department.sort ASC, diary_diary.datetime DESC, diary_users.usort DESC, diary_users.regdate ASC, diary_users.username ASC, diary_diary.id ASC')->select();
    	$list = array();
    	foreach ($results as $key=>$rs){
    		$key_str = $rs['nickname'].date('Y-m-d', strtotime($rs['datetime']));
    		$summary = M('summary')->where("datetime LIKE '%".date('Y-m-d', strtotime($rs['datetime']))."%' AND userid='".$rs['userid']."'")->order('id ASC')->select();
    		$rs['summary'] = $summary;
    	
    		$diaryitem = M('diaryitem')->where("datetime LIKE '%".date('Y-m-d', strtotime($rs['datetime']))."%' AND userid='".$rs['userid']."'")->select();
    		$rs['diaryitem'] = $diaryitem;
    	
    		$comment = M('comment')->where("diary_id='".$rs['id']."'")->select();
    		foreach ($comment as $key=>$value){
    			if($value['userid']){
    				$userinfo = M('users')->where('userid='.$value['userid'])->find();
    				$comment[$key]['content'] .= '----by:'.$userinfo['nickname'];
    			}
    		}
    		$rs['comment'] = $comment;
    		$list[$key_str][] = $rs;
    	}
    	
    	$content = '<style>br {mso-data-placement:same-cell;}</style><table>';
    	$content .= '<tr style="border-bottom:#000 solid 0.5pt;">
					<td><strong>部门 </strong></td>
					<td style="border-left:#000 solid 0.5pt;"><strong>姓名</strong></td>
					<td style="border-left:#000 solid 0.5pt;"><strong>上日工作内容总结</strong></td>
					<td style="border-left:#000 solid 0.5pt;"><strong>今日工作日志</strong></td>
					<td style="border-left:#000 solid 0.5pt;"><strong>完成百分比</strong></td>
					<td style="border-left:#000 solid 0.5pt;"><strong>预计完成时间</strong></td>
					<td style="border-left:#000 solid 0.5pt;"><strong>特别成果</strong></td>
					<td style="border-left:#000 solid 0.5pt;"><strong>求助问题</strong></td>
					<td style="border-left:#000 solid 0.5pt;border-right:#000 solid 0.5pt;"><strong>主管反馈</strong></td></tr>';
    	foreach ($list as $result=>$value){
    		foreach ($value as $key=>$rs){
    			$rs['rowspan'] = count($value);
    			$content .= '<tr style="border-bottom:#000 solid 0.5pt;">';
    			if($key == 0){
    				$content .= '<td rowspan="'.$rs['rowspan'].'">'.$rs['name'].'</td>';
    				$content .= '<td style="border-left:#000 solid 0.5pt;" rowspan="'.$rs['rowspan'].'">'.$rs['nickname'].'</td>';
    				$content .= '<td style="border-left:#000 solid 0.5pt;" rowspan="'.$rs['rowspan'].'">';
    				$content .= '<table width="100%"><tr style="border-bottom:#000 dotted 0.5pt; color:#333;"><td>工作内容</td><td style="border-left:#000 dotted 0.5pt; color:#333;">完成百分比</td><td style="border-left:#000 dotted 0.5pt; color:#333;">完成时间</td></tr>';
    				foreach ($rs['summary'] as $k=>$data){
    					$border = ($k == count($rs['summary']) - 1) ? '' : 'border-bottom:#000 dotted 0.5pt;';
    					$background = ($data['percent'] == '100') ? 'background:#00b150;' : '';
    					$content .= '<tr style="border-bottom:#000 dotted 0.5pt;"><td style="color:#333;border-bottom:#000 dotted 0.5pt;">'.htmlspecialchars_decode($data['content']).'</td>';
    					$content .= '<td style="border-left:#000 dotted 0.5pt;text-align:right;color:#333;border-bottom:#000 dotted 0.5pt;'.$background.'">'.($data['percent'] ? $data['percent'].'%' : '').'</td>';
    					$content .= '<td style="border-left:#000 dotted 0.5pt;text-align:right;color:#333;border-bottom:#000 dotted 0.5pt;">'.str_replace('.', '月', $data['done_time']).'</td>';
    					$content .= '</td></tr>';
    				}
    				$content .= '</table>';
    				$content .= '</td>';
    			}
    			$background = ($rs['percent'] == '100') ? 'background:#00b150;' : '';
    			$content .= '<td style="border-left:#000 solid 0.5pt;">'.htmlspecialchars_decode($rs['content']).'</td>';
    			$content .= '<td style="border-left:#000 solid 0.5pt;text-align:right;'.$background.'">'.($rs['percent'] ? $rs['percent'].'%' : '').'</td>';
    			$content .= '<td style="border-left:#000 solid 0.5pt;text-align:right;">'.str_replace('.', '月', $rs['done_time']).'</td>';
    			if($key == 0){
    				$content .= '<td rowspan="'.$rs['rowspan'].'" style="border-left:#000 solid 0.5pt;">';
    				foreach ($rs['diaryitem'] as $k=>$data){
    					$content .= htmlspecialchars_decode($data['achievement']);
    				}
    				$content .= '</td>';
    				$content .= '<td rowspan="'.$rs['rowspan'].'" style="border-left:#000 solid 0.5pt;">';
    				foreach ($rs['diaryitem'] as $k=>$data){
    					$content .= htmlspecialchars_decode($data['diffculty']);
    				}
    				$content .= '</td>';
    			}
    			$content .= '<td style="border-left:#000 solid 0.5pt;border-right:#000 solid 0.5pt;">';
    			foreach ($rs['comment'] as $k=>$data){
    				$content .= '<div style="margin-bottom:20px;">'.htmlspecialchars_decode($data['content']).'</div>';
    			}
    			$content .= '</td>';
    			$content .= '</tr>';
    		}
    	}
    	$content .= '</table>';
    	
    	$file_name = "Daily_".I('get.view').'_'.date('YmdHis',time()).".xls";
    	header("Content-Type: application/x-msdownload");
    	header("Content-Disposition: attachment; filename=\"".$file_name."\"");
    	header("Pragma: no-cache");
    	header("Expires: 0");
    	//echo iconv('utf-8','gb2312//IGNORE',$content);
    	echo $content;
    	exit();
    }
    
    public function delete(){
    	$id = I('get.id','intval');
    	M('diary')->where("id='".$id."'")->delete();
    	M('comment')->where("diary_id='".$id."'")->delete();
    	$this->success('删除成功', U('Diary/index'), 2);
    }
    
    public function edit(){
    	$id = I('get.id','intval');
    	$diary = M('diary');
    	$diary_list = $diary->where('id='.$id)->find();
    	$date_str = date('Y-m-d', strtotime($diary_list['datetime']));
    	if (IS_POST) {
    		$contents = I('post.content');
    		$percents = I('post.percent');
    		$complete_times = I('post.complete_time');
    		$data['content'] = $this->cleanHtml($contents[0]);
    		$data['percent'] = $percents[0];
    		$data['done_time'] = $complete_times[0];
    		$diary->where('id='.$id)->save($data);
    		
    		M('summary')->where("datetime LIKE '%".$date_str."%' AND userid='".$diary_list['userid']."'")->delete();
    		$summary_contents = I('post.summary_content');
    		$summary_percents = I('post.summary_percent');
    		$summary_complete_times = I('post.summary_complete_time');
    		if(is_array($summary_contents)){
    			foreach ($summary_contents as $key=>$value){
    				$data = array();
    				if(!empty($value)){
    					$data['userid'] = $diary_list['userid'];
    					$data['content'] = $this->cleanHtml($value);
    					$data['percent'] = $summary_percents[$key];
    					$data['done_time'] = $summary_complete_times[$key];
    					$data['datetime'] = $diary_list['datetime'];
    					$summary = M('summary');
    					$summary->add($data);
    				}
    			}
    		}
    		M('diaryitem')->where("datetime LIKE '%".$date_str."%' AND userid='".$diary_list['userid']."'")->delete();
    		$achievement = I('post.achievement');
    		$diffculty = I('post.diffculty');
    		if($achievement || $diffculty){
    			$data = array();
    			$data['userid'] = $diary_list['userid'];
    			$data['achievement'] = $achievement;
    			$data['diffculty'] = $diffculty;
    			$data['datetime'] = $diary_list['datetime'];
    			$diaryitem = M('diaryitem');
    			$diaryitem->add($data);
    		}
    		$this->success('编辑成功', U('Diary/index?view=self'), 2);
    		
    	}else{
    		$diary_list['summary'] = M('summary')->where("datetime LIKE '%".$date_str."%' AND userid='".$diary_list['userid']."'")->order('id ASC')->select();
    		$diary_list['diaryitem'] = M('diaryitem')->where("datetime LIKE '%".$date_str."%' AND userid='".$diary_list['userid']."'")->select();
    		
    		$this->assign('summary_count', count($diary_list['summary']));
    		$this->assign('diary', $diary_list);
    		$this->display();
    	}
    }
    
    public function add(){
    	if (IS_POST) {
    		$contents = I('post.content');
    		$percents = I('post.percent');
    		$complete_times = I('post.complete_time');
    		if(is_array($contents)){
    			foreach ($contents as $key=>$value){
    				$data = array();
    				if(!empty($value)){
    					$data['userid'] = session('uid');
    					$data['content'] = $this->cleanHtml($value);
    					$data['percent'] = $percents[$key];
    					$data['done_time'] = $complete_times[$key];
    					$data['datetime'] = date('Y-m-d 00:00:00', time());
    					$data['type'] = I('post.type');;
    					$diary = M('diary');
    					$diary->add($data);
    				}
    			}
    		}
    		
    		$summary_contents = I('post.summary_content');
    		$summary_percents = I('post.summary_percent');
    		$summary_complete_times = I('post.summary_complete_time');
    		if(is_array($summary_contents)){
    			foreach ($summary_contents as $key=>$value){
    				$data = array();
    				if(!empty($value)){
    					$data['userid'] = session('uid');
    					$data['content'] = $this->cleanHtml($value);
    					$data['percent'] = $summary_percents[$key];
    					$data['done_time'] = $summary_complete_times[$key];
    					$data['datetime'] = date('Y-m-d H:i:s', time());
    					$summary = M('summary');
    					$summary->add($data);
    				}
    			}
    		}
    		
    		$achievement = I('post.achievement');
    		$diffculty = I('post.diffculty');
    		if($achievement || $diffculty){
    			$data = array();
    			$data['userid'] = session('uid');
    			$data['achievement'] = $achievement;
    			$data['diffculty'] = $diffculty;
    			$data['datetime'] = date('Y-m-d H:i:s', time());
    			$diaryitem = M('diaryitem');
    			$diaryitem->add($data);
    		}
    		session('no_diary_days', 0);
    		$this->success('添加成功', U('Diary/index?view=self'), 2);
    	}else{
    		$date_str = date('Y-m-d', time());
    		$map = array();
    		$map['userid'] = session('uid');
    		$map['datetime'] = array('LIKE','%'.$date_str.'%');
    		$diary = M('diary');
    		$diary_list = $diary->where($map)->order('id ASC')->select();
    		if(empty($diary_list) && I('get.type') != 2){//表示已经添加过日志
    			$date_str = date('Y-m-d', strtotime("-1 day"));
    			$map['userid'] = session('uid');
    			$map['datetime'] = array('LIKE','%'.$date_str.'%');
    			$diary_list = $diary->where($map)->order('id ASC')->select();
    			$this->assign('diary_list', $diary_list);
    			$this->assign('total_count', count($diary_list)+1);
    		}
    		$this->assign('type', I('get.type'));
    		$this->display();
    	}
    }
    
    public function comment(){
    	$id = I('get.id','intval');
    	$diary = M('diary');
    	$diary_list = $diary->where('id='.$id)->find();
    	$date_str = date('Y-m-d', strtotime($diary_list['datetime']));
    	if (IS_POST) {
    		$data = array();
    		$data['userid'] = session('uid');
    		$data['content'] = I('post.comment');
    		$data['diary_id'] = $id;
    		$data['datetime'] = date('Y-m-d H:i:s', time());
    		$comment = M('comment');
    		$comment->add($data);
    		if(session('access')['view_development_diary'] == 1 && session('access')['view_administration_diary'] == 1){
    			$this->success('编辑成功', U('Diary/index'), 2);
    		}else{
	    		if(session('access')['view_development_diary'] == 1){
	    			$this->success('编辑成功', U('Home/Diary/index?view=development&srchregdatestart='.date('Y-m-d',time()).'&srchregdateend='.date('Y-m-d',time())), 2);
	    		}
	    		if(session('access')['view_administration_diary'] == 1){
	    			$this->success('编辑成功', U('Home/Diary/index?view=administration'), 2);
	    		}
    		}
    	}else{
    		$diary_list['summary'] = M('summary')->where("datetime LIKE '%".$date_str."%' AND userid='".$diary_list['userid']."'")->order('id ASC')->select();
    		$diary_list['diaryitem'] = M('diaryitem')->where("datetime LIKE '%".$date_str."%' AND userid='".$diary_list['userid']."'")->select();
    
    		$this->assign('diary', $diary_list);
    		$this->display();
    	}
    }
    
    public function noneDiary(){
    	$d_map = array();
    	$d_map['datetime'] = array(array('egt',date('Y-m-d 00:00:00',time())),array('elt',date('Y-m-d 23:59:59',time())),'AND');
    	
    	$uids  = '';
    	$results = M('diary')->field('userid')->where($d_map)->select();
    	foreach ($results as $result){
    		$uids  .= $result['userid'].',';
    	}
    	$uids  .= '6,55,56';
    	
    	$map = array();
    	$map['status'] = 1;
    	$map['userid'] = array('not in', $uids);
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
    	$list = $User->where($map)->field('diary_users.*')
    	->join('diary_department ON diary_users.department_id = diary_department.id')
    	->order('diary_department.sort asc, diary_users.regdate desc')->limit($Page->firstRow.','.$Page->listRows)->select();
    	foreach ($list as $key=>$value) {
    		$department = M('Department')->where(array('id'=>$value['department_id']))->find();
    		$group = M('Group')->where(array('id'=>$value['group_id']))->find();
    		$list[$key]['department'] = $department['name'];
    		$list[$key]['group'] = $group['title'];
    	}
    	
    	$this->assign('list', $list);
    	$this->assign('page', $show);
    	$this->display();
    }
    
    public function cleanHtml($html){
    	$html = preg_replace('/(&lt;)(.*?)body(.*?)(&gt;)/', '', $html);
    	$html = preg_replace('/(&lt;)(.*?)table(.*?)(&gt;)/', '', $html);
    	$html = preg_replace('/(&lt;)(.*?)tr(.*?)(&gt;)/', '', $html);
    	$html = preg_replace('/(&lt;)(.*?)td(.*?)(&gt;)/', '', $html);
    	$html = preg_replace('/(&lt;)(.*?)th(.*?)(&gt;)/', '', $html);
    	$html = preg_replace('/(&lt;)(.*?)tbody(.*?)(&gt;)/', '', $html);
    	return $html;
    }
}