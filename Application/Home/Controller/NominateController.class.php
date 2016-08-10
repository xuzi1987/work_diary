<?php
namespace Home\Controller;
use Think\Controller;
class NominateController extends CommonController {
	private function getNominate($type){
		$map = array();
		if($type == 'index'){
			$map['status'] = 2;
		}
		if($type == 'self'){
			$map['poster_userid'] = session('uid');
		}
		//默认显示当前年份当前季度
		$quarter = getQuarterByMonth(date('Ym',time()));
		$map['YEAR(datetime)'] = date('Y',time());
		$map['QUARTER(datetime)'] = $quarter;
		$srchyear = I('get.srchyear','', 'trim');
		$srchquarter = I('get.srchquarter','', 'trim');
		$srchdepartmentid = I('get.srchdepartmentid','', 'intval');
		$srchprojectid = I('get.srchprojectid','', 'intval');
		$srchmedalid = I('get.srchmedalid','', 'intval');
			
		if($srchdepartmentid) {
			$map['access_department_id'] = $srchdepartmentid;
			$this->assign('srchdepartmentid', $srchdepartmentid);
		}
		if($srchprojectid) {
			$map['access_project_id'] = $srchprojectid;
			$this->assign('srchprojectid', $srchprojectid);
		}
		if($srchmedalid) {
			$map['medal_id'] = $srchmedalid;
			$this->assign('srchmedalid', $srchmedalid);
		}
		if($srchyear){
			$this->assign('srchyear', $srchyear);
			$map['YEAR(datetime)'] = $srchyear;
		}
		if($srchquarter){
			$this->assign('srchquarter', $srchquarter);
			$map['QUARTER(datetime)'] = $srchquarter;
		}
			
		$nominate = M('nominate');
		/* $count = $nominate->where($map)->count();
		 $Page = new \Think\Page($count,100);
		foreach($map as $key=>$val) {
		$Page->parameter[$key] = urlencode($val);
		}
		$Page->setConfig('prev','上一页');
		$Page->setConfig('next','下一页');
		$Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
		$show = $Page->show(); 
		$list = $nominate->where($map)->order('datetime DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->assign('page', $show);*/
		$list = $nominate->where($map)->order('access_userid ASC, datetime DESC')->select();
		return $list;
	}
	
	//提名总览页
	public function index(){
		$list = $this->getNominate('index');
		foreach ($list as $key=>$rs){
			$medal = M('medal')->where("id=".$rs['medal_id'])->find();
			$rs['medal'] = $medal['name'];
	
			$department = M('department')->where("id=".$rs['access_department_id'])->find();
			$rs['department'] = $department['name'];
			
			$datas[$rs['medal_id']][] = $rs;
		}
		
		$this->assign('list', $datas);
		 
		$department_list = M('department')->order('sort ASC')->select();
		$project_list = M('project')->order('datetime DESC')->select();
		$medal_list = M('medal')->where('status=1')->order('datetime DESC')->select();
		$this->assign('department_list', $department_list);
		$this->assign('project_list', $project_list);
		$this->assign('medal_list', $medal_list);
		 
		$this->display();
	}
	
	//按提名次数浏览
	public function rank(){
		$list = $this->getNominate('index');
		$datas = array();
		foreach ($list as $key=>$rs){
			$medal = M('medal')->where("id=".$rs['medal_id'])->find();
			$rs['medal'] = $medal['name'];
	
			$department = M('department')->where("id=".$rs['access_department_id'])->find();
			$rs['department'] = $department['name'];
	
			$datas[$rs['access_username']][] = $rs;
		}
		 
		$results = array();
		foreach ($datas as $access_username=>$nominates){
			$results[$access_username]['access_username'] = $access_username;
			foreach ($nominates as $nominate){
				$results[$access_username][$nominate['medal_id']][] = $nominate;
			}
		}
	
		$this->assign('list', $results);
	
		$department_list = M('department')->order('sort ASC')->select();
		$project_list = M('project')->order('datetime DESC')->select();
		$medal_list = M('medal')->where('status=1')->order('datetime DESC')->select();
		$this->assign('department_list', $department_list);
		$this->assign('project_list', $project_list);
		$this->assign('medal_list', $medal_list);
	
		$this->display();
	}
	
	//我的提名,按候选人浏览
    public function selfview(){
    	$list = $this->getNominate('self');
    	$datas = array();
    	foreach ($list as $key=>$rs){
    		$medal = M('medal')->where("id=".$rs['medal_id'])->find();
    		$rs['medal'] = $medal['name'];
    		
    		$department = M('department')->where("id=".$rs['access_department_id'])->find();
    		$rs['department'] = $department['name'];
    		
    		$datas[$rs['access_username']][] = $rs;
    	}
    	
    	$this->assign('list', $datas);
    	
    	$department_list = M('department')->order('sort ASC')->select();
    	$project_list = M('project')->order('datetime DESC')->select();
    	$medal_list = M('medal')->where('status=1')->order('datetime DESC')->select();
    	$this->assign('department_list', $department_list);
    	$this->assign('project_list', $project_list);
    	$this->assign('medal_list', $medal_list);
    	
    	$this->display();
    }
    
    //我的提名，按奖项浏览
    public function selfbymedal(){
    	$list = $this->getNominate('self');
    	foreach ($list as $key=>$rs){
    		$medal = M('medal')->where("id=".$rs['medal_id'])->find();
    		$rs['medal'] = $medal['name'];
    
    		$department = M('department')->where("id=".$rs['access_department_id'])->find();
    		$rs['department'] = $department['name'];
    			
    		$datas[$rs['medal_id']][] = $rs;
    	}
    
    	$this->assign('list', $datas);
    		
    	$department_list = M('department')->order('sort ASC')->select();
    	$project_list = M('project')->order('datetime DESC')->select();
    	$medal_list = M('medal')->where('status=1')->order('datetime DESC')->select();
    	$this->assign('department_list', $department_list);
    	$this->assign('project_list', $project_list);
    	$this->assign('medal_list', $medal_list);
    		
    	$this->display();
    }
    
    public function publish(){
    	$id = I('get.id','intval');
    	$nominate = M('nominate');
    	$data['status'] = 2;
    	$nominate->where('id='.$id)->save($data);
    	$this->success('发布成功', U('nominate/selfview'), 1);
    }
    public function unpublish(){
    	$id = I('get.id','intval');
    	$nominate = M('nominate');
    	$data['status'] = 1;
    	$nominate->where('id='.$id)->save($data);
    	$this->success('取消发布成功', U('nominate/selfview'), 1);
    }
    
    public function delete(){
    	$id = I('get.id','intval');
    	M('nominate')->where("id='".$id."'")->delete();
    	$this->success('删除成功', U('nominate/selfview'), 1);
    }
    
    public function edit(){
    	$id = I('get.id','intval');
    	$nominate = M('nominate');
    	if (IS_POST) {
    		$data['access_department_id'] = I('post.access_department_id');
    		$data['access_project_id'] = I('post.access_project_id');
    		$data['access_username'] = I('post.access_username');
    		$data['access_userid'] = I('post.access_userid');
    		$data['medal_id'] = I('post.medal_id');
    		$data['status'] = I('post.status');
    		$data['description'] = I('post.description');
    		$data['position'] = I('post.position');
    		
    		$nominate = M('nominate');
    		$count = $nominate->where('medal_id='.$data['medal_id'].' AND poster_userid='.session('uid'))->count();
    		$medal = M('medal')->where('id='.$data['medal_id'])->find();
    		if($medal['places'] == $count){
    			$this->error('您的此项提名已满额', U('Nominate/add'), 1);
    		}
    		
    		$count = $nominate->where('medal_id='.$data['medal_id'].' AND poster_userid='.session('uid').' AND access_userid='.$data['access_userid'])->count();
    		if($count > 0){
    			$this->error('不能重复提名', U('Nominate/add'), 1);
    		}
    		
    		$nominate->where('id='.$id)->save($data);
    		$this->success('编辑成功', U('nominate/selfview'), 1);
    	}else{
    		$list = $nominate->where('id='.$id)->find();
    		$medal = M('medal')->where("id=".$list['medal_id'])->find();
    		$list['medal'] = $medal['name'];
    		$department = M('department')->where("id=".$list['access_department_id'])->find();
    		$list['department'] = $department['name'];
    		$this->assign('list', $list);
    		
    		$department_list = M('department')->order('sort ASC')->select();
    		$project_list = M('project')->order('datetime DESC')->select();
    		$medal_list = M('medal')->where('status=1')->order('datetime DESC')->select();
    		$this->assign('department_list', $department_list);
    		$this->assign('project_list', $project_list);
    		$this->assign('medal_list', $medal_list);
    		
    		$this->display();
    	}
    }
    
    public function add(){
    	if (IS_POST) {
    		if(!I('post.access_username')){
    			$this->error('员工姓名不能为空', U('Nominate/add'), 1);
    		}
    		$data = array();
    		$data['poster_userid'] = session('uid');
    		$data['poster_username'] = session('nickname');
    		//poster_department_id  poster_project_id
    		$data['access_department_id'] = I('post.access_department_id');
    		$data['access_project_id'] = I('post.access_project_id');
    		$data['access_username'] = I('post.access_username');
    		$data['access_userid'] = I('post.access_userid');
    		$data['medal_id'] = I('post.medal_id');
    		$data['status'] = I('post.status');
    		$data['description'] = I('post.description');
    		$data['position'] = I('post.position');
    		$data['datetime'] = date('Y-m-d H:i:s', time());
    		
    		$nominate = M('nominate');
    		$count = $nominate->where('medal_id='.$data['medal_id'].' AND poster_userid='.session('uid'))->count();
    		$medal = M('medal')->where('id='.$data['medal_id'])->find();
    		if($medal['places'] == $count){
    			$this->error('您的此项提名已满额', U('Nominate/add'), 1);
    		}
    		
    		$count = $nominate->where('medal_id='.$data['medal_id'].' AND poster_userid='.session('uid').' AND access_userid='.$data['access_userid'])->count();
    		if($count > 0){
    			$this->error('不能重复提名', U('Nominate/add'), 1);
    		}
    		
    		$nominate->add($data);
    		$this->success('添加成功', U('Nominate/selfview'), 1);
    	}else{
    		$department_list = M('department')->order('sort ASC')->select();
    		$project_list = M('project')->order('datetime DESC')->select();
    		$medal_list = M('medal')->where('status=1')->order('datetime DESC')->select();
    		$this->assign('department_list', $department_list);
    		$this->assign('project_list', $project_list);
    		$this->assign('medal_list', $medal_list);
    		$this->display();
    	}
    }
    
    public function userlist(){
    	$map = array();
    	$where = array();
    	$srchname = I('get.srchname','', 'trim');
    	if($srchname) {
    		$where['nickname'] = array('LIKE','%'.$srchname.'%');
    		$where['username'] = array('LIKE','%'.$srchname.'%');
    		$where['_logic'] = 'or';
    	}
    	$map['_complex'] = $where;
    	$map['status'] = 1;
    	$User = M('users');
    	$list = $User->where($map)->order('regdate desc')->select();
    	$this->ajaxReturn($list);
    }
    
    public function positionlist(){
    	$list = array();
    	$data = array('模拟工程师','数字工程师','系统工程师','版图工程师','固件工程师','图像算法工程师');
    	$srchname = I('get.srchname','', 'trim');
    	if($srchname){
	    	foreach ($data as $v){
	    		if(preg_match('/'.$srchname.'/', $v)){
	    			$list[] = $v;
	    		}
	    	}
    	}else{
    		$list = $data;
    	}
    	$this->ajaxReturn($list);
    }
    
    //邮件通知候选人
    public function email(){
    	$id = I('get.id','intval');
    	$nominate = M('nominate');
    	$list = $nominate->where('id='.$id)->find();
    	$userinfo = M('users')->where('userid='.$list['access_userid'])->find();
    	$medal = M('medal')->where("id=".$list['medal_id'])->find();
    	$department = M('department')->where("id=".$list['access_department_id'])->find();
    	//$to = $userinfo['email'];
    	$to = 'wangyp@innosilicon.com.cn';
    	$title = '恭喜你被提名为：'.$medal['name'];
    	$content = '恭喜你被提名为：'.$medal['name'].', 请按要求填写以下表格并邮件提交给你的上级<br/><br/>';
    	$content .= '<table border="1" cellpadding="0" cellspacing="0" width="600">
		  <tr>
		    <td style="font-size:12px; padding:6px 4px;" width="60">姓名</td>
		    <td style="font-size:12px; padding:6px 4px;" width="80">'.$list['access_username'].'</td>
		    <td style="font-size:12px; padding:6px 4px;" width="60">组别</td>
		    <td style="font-size:12px; padding:6px 4px;" width="100">'.$department['name'].'</td>
		    <td style="font-size:12px; padding:6px 4px;" width="60">岗位</td>
		    <td style="font-size:12px; padding:6px 4px;">'.$list['position'].'</td>
		  </tr>
		  <tr>
		    <td style="font-size:12px; padding:6px 4px;" colspan="2">推荐参评奖项</td>
		    <td style="font-size:12px; padding:6px 4px;" colspan="4">'.$medal['name'].'</td>
		  </tr>
		  <tr>
		    <td style="font-size:12px; padding:6px 4px;" colspan="2">个人业绩简述</td>
		    <td style="font-size:12px; padding:6px 4px;" colspan="4" valign="top"> 
		    <p>1. 在体现核心价值观方面的突出表现（责任/协作/创新）</p>
		      <div style="width:400px; height:200px;"></div>
		   <p>2. 个人突出的价值贡献（按参评奖项的评选标准填报）</p> 
		      <div style="width:400px; height:200px;"></div></td>
		  </tr>
		  <tr>
		    <td style="font-size:12px; padding:6px 4px;" colspan="2">部门推荐意见</td>
		    <td style="font-size:12px; padding:6px 4px;" colspan="4">            
		    	<table width="100%">
		        	<tr>
		            <td style="font-size:12px;">签名：</td>
		            <td></td>
		            <td style="font-size:12px;">时间：</td>
		            <td></td>
		            </tr>
		        </table>
		    </td>
		  </tr>
		  <tr>
		    <td style="font-size:12px; padding:6px 4px;" colspan="2">评审委员会意见</td>
		    <td style="font-size:12px; padding:6px 4px;" colspan="4">
		    	<table width="100%">
		        	<tr>
		            <td style="font-size:12px;">签名：</td>
		            <td></td>
		            <td style="font-size:12px;">时间：</td>
		            <td></td>
		            </tr>
		        </table>
		    </td>
		  </tr>
		</table>';
    	$send = sendMail($to, $title, $content);
    	if($send){
    		$nominate->where('id='.$id)->save(array('is_email'=>1));//标记为已发送邮件
    		$this->success('发送成功', U('nominate/selfview'), 1);
    	}
    }
    
    public function export(){
    	set_time_limit(0);
    	$type = I('get.type','', 'trim');
    	if($type == 'index' || $type == 'rank'){
    		$list = $this->getNominate('index');
    	}
    	if($type == 'selfview' || $type == 'selfbymedal'){
    		$list = $this->getNominate('self');
    	}
    	
    	$datas = array();
    	$content = '<style>br {mso-data-placement:same-cell;}</style><table width="1024">';
    	if($type == 'selfview'){
    		foreach ($list as $key=>$rs){
    			$medal = M('medal')->where("id=".$rs['medal_id'])->find();
    			$rs['medal'] = $medal['name'];
    		
    			$department = M('department')->where("id=".$rs['access_department_id'])->find();
    			$rs['department'] = $department['name'];
    		
    			$datas[$rs['access_username']][] = $rs;
    		}
    		
    		$content .= '<tr><td style="border-bottom:#000 solid 0.5pt;border-right:#000 solid 0.5pt;">候选人</td>';
    		$medal_list = M('medal')->where('status=1')->order('datetime DESC')->select();
    		foreach ($medal_list as $v){
    			$content .= '<td style="border-bottom:#000 solid 0.5pt;border-right:#000 solid 0.5pt;"><strong>'.$v['name'].'</strong></td>';
    		}
    		$content .= '</tr>';
    		foreach ($datas as $nominate){
    			$content .= '<tr>';
    			$content .= '<td style="border-bottom:#000 solid 0.5pt;border-right:#000 solid 0.5pt;">'.$nominate[0]['access_username'].'</td>';
    			foreach ($medal_list as $v){
    				$content .= '<td style="border-bottom:#000 solid 0.5pt;border-right:#000 solid 0.5pt;">';
    				foreach ($nominate as $value){
    					if($value['medal_id'] == $v['id']){
    						$content .= '√';
    					}
    				}
    				$content .= '</td>';
    			}
    			$content .= '</tr>';
    		}
    	}
    	
    	if($type == 'selfbymedal'){
    		foreach ($list as $key=>$rs){
    			$medal = M('medal')->where("id=".$rs['medal_id'])->find();
    			$rs['medal'] = $medal['name'];
    	
    			$department = M('department')->where("id=".$rs['access_department_id'])->find();
    			$rs['department'] = $department['name'];
    	
    			$datas[$rs['medal_id']][] = $rs;
    		}
    	
    		$content .= '<tr>
    				     <td style="border-bottom:#000 solid 0.5pt;"><strong>奖项</strong></td>
    				     <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;"><strong>候选人</strong></td>
    				     <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;"><strong>组别</strong></td>
    				     <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;"><strong>岗位</strong></td>
    				     <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;"><strong>提名理由</strong></td>
    				     <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;border-right:#000 solid 0.5pt;"><strong>时间</strong></td></tr>';
    	
    		$medal_list = M('medal')->where('status=1')->order('datetime DESC')->select();
    		foreach ($medal_list as $v){
    			if(count($datas[$v['id']]) > 0){
    				foreach ($datas[$v['id']] as $k=>$value){
    					$content .= '<tr>';
    					if($k == 0){
    						$content .= '<td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;text-align:center;" rowspan="'.count($datas[$v['id']]).'"><strong>'.$v['name'].'</strong><br/>('.$v['places'].'名)</td>';
    					}
    					$content .= '<td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;">'.$value['access_username'].'</td>
    						<td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;">'.$value['department'].'</td>
    						<td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;">'.$value['position'].'</td>
                            <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;">'.str_replace('</p>','',str_replace('<p>','',htmlspecialchars_decode($value['description']))).'</td>
                            <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;border-right:#000 solid 0.5pt;">'.$value['datetime'].'</td>';
    					$content .= '</tr>';
    				}
    			}else{
    				$content .= '<tr>
                            <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;text-align:center;"><strong>'.$v['name'].'</strong><br/>('.$v['places'].'名)</td>
                            <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;">&nbsp;</td>
                            <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;">&nbsp;</td>
                            <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;">&nbsp;</td>
                            <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;">&nbsp;</td>
                            <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;border-right:#000 solid 0.5pt;">&nbsp;</td>
						</tr>';
    			}
    		}
    	}
    	
    	if($type == 'rank'){
    		foreach ($list as $key=>$rs){
    			$medal = M('medal')->where("id=".$rs['medal_id'])->find();
    			$rs['medal'] = $medal['name'];
    	
    			$department = M('department')->where("id=".$rs['access_department_id'])->find();
    			$rs['department'] = $department['name'];
    	
    			$datas[$rs['access_username']][] = $rs;
    		}
    		
    		$results = array();
    		foreach ($datas as $access_username=>$nominates){
    			$results[$access_username]['access_username'] = $access_username;
    			foreach ($nominates as $nominate){
    				$results[$access_username][$nominate['medal_id']][] = $nominate;
    			}
    		}
    		
    	
    		$content .= '<tr><td style="border-bottom:#000 solid 0.5pt;">候选人\提名次数</td>';
    		$medal_list = M('medal')->where('status=1')->order('datetime DESC')->select();
    		foreach ($medal_list as $v){
    			$content .= '<td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;"><strong>'.$v['name'].'</strong></td>';
    		}
    		$content .= '<td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;border-right:#000 solid 0.5pt;"><strong>提名次数总计</strong></td>';
    		$content .= '</tr>';
    		foreach ($results as $access_username=>$nominate){
    			$content .= '<tr>';
    			$content .= '<td style="border-bottom:#000 solid 0.5pt;">'.$access_username.'</td>';
    			$total = 0;
    			foreach ($medal_list as $v){
    				$content .= '<td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;">';
    				if(count($nominate[$v[id]]) > 0){
    					$content .= count($nominate[$v[id]]);
    				}
    				$total += count($nominate[$v[id]]);
    				$content .= '</td>';
    			}
    			$content .= '<td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;border-right:#000 solid 0.5pt;">'.$total.'</td>';
    			$content .= '</tr>';
    		}
    	}
    	
    	if($type == 'index'){
    		foreach ($list as $key=>$rs){
    			$medal = M('medal')->where("id=".$rs['medal_id'])->find();
    			$rs['medal'] = $medal['name'];
    		
    			$department = M('department')->where("id=".$rs['access_department_id'])->find();
    			$rs['department'] = $department['name'];
    				
    			$datas[$rs['medal_id']][] = $rs;
    		}
    		
    		$content .= '<tr>
    				     <td style="border-bottom:#000 solid 0.5pt;"><strong>奖项</strong></td>
    				     <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;"><strong>候选人</strong></td>
    				     <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;"><strong>组别</strong></td>
    				     <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;"><strong>岗位</strong></td>
    					 <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;"><strong>提名人</strong></td>
    				     <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;"><strong>提名理由</strong></td>
    				     <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;border-right:#000 solid 0.5pt;"><strong>时间</strong></td></tr>';
    		
    		$medal_list = M('medal')->where('status=1')->order('datetime DESC')->select();
    		foreach ($medal_list as $v){
    			if(count($datas[$v['id']]) > 0){
    				foreach ($datas[$v['id']] as $k=>$value){
    					$content .= '<tr>';
    					if($k == 0){
    						$content .= '<td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;text-align:center;" rowspan="'.count($datas[$v['id']]).'"><strong>'.$v['name'].'</strong><br/>('.$v['places'].'名)</td>';
    					}
    					$content .= '<td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;">'.$value['access_username'].'</td>
    						<td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;">'.$value['department'].'</td>
    						<td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;">'.$value['position'].'</td>
                            <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;">'.$value['poster_username'].'</td>
                            <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;">'.str_replace('</p>','',str_replace('<p>','',htmlspecialchars_decode($value['description']))).'</td>
                            <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;border-right:#000 solid 0.5pt;">'.$value['datetime'].'</td>';
						$content .= '</tr>';
    				}
    			}else{
    				$content .= '<tr>
                            <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;text-align:center;"><strong>'.$v['name'].'</strong><br/>('.$v['places'].'名)</td>
                            <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;">&nbsp;</td>
                            <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;">&nbsp;</td>
                            <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;">&nbsp;</td>
                            <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;">&nbsp;</td>
                            <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;">&nbsp;</td>
                            <td style="border-bottom:#000 solid 0.5pt;border-left:#000 solid 0.5pt;border-right:#000 solid 0.5pt;">&nbsp;</td>
						</tr>';
    			}
    		}
    	}
    	$content .= '</table>';
    	 
    	$file_name = 'Nomination_'.date('YmdHis',time()).".xls";
    	header("Content-Type: application/x-msdownload");
    	header("Content-Disposition: attachment; filename=\"".$file_name."\"");
    	header("Pragma: no-cache");
    	header("Expires: 0");
    	echo iconv('utf-8','gb2312//IGNORE',$content);
    	//echo $content;
    	exit();
    }
    
    /* public function importemail(){
    	$file = 'D:/www/work_diary/Public/address.xls';
    	$data = readExcel($file);
    	$max_rows = 0;
    	for($row=2;$row<=$data->sheets[0]['numRows']&&($row<=$max_rows||$max_rows==0);$row++)
    	{
    		$nickname = $data->sheets[0]['cells'][$row][1];
    		$email = $data->sheets[0]['cells'][$row][2];
    		$user_info = M('users')->where("nickname='".$nickname."'")->find();
    		$updates = array();
    		if($user_info){
    			$updates['email'] = $email;
    			M('users')->where("nickname='".$nickname."'")->save($updates);
    		}
    	}
    } */
}