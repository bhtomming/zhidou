<?php

namespace app\index\controller;

use addons\wechat\model\WechatCaptcha;
use app\common\controller\Frontend;
use app\common\library\Ems;
use app\common\library\Sms;
use app\common\model\Attachment;
use think\Config;
use think\Cookie;
use think\Hook;
use think\Session;
use think\Validate;
use think\Db;

/**
 * 会员中心
 */
class Wo extends Frontend
{
    protected $layout = 'default';
    protected $noNeedLogin = ["*"];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        
        parent::_initialize();
        $auth = $this->auth;

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'), '/');
        }
        
        // 监听注册登录退出的事件
        Hook::add('user_login_successed', function ($user) use ($auth) {
            $expire = input('post.keeplogin') ? 30 * 86400 : 0;
            Cookie::set('uid', $user->id, $expire);
            Cookie::set('token', $auth->getToken(), $expire);
        });
        Hook::add('user_register_successed', function ($user) use ($auth) {
            Cookie::set('uid', $user->id);
            Cookie::set('token', $auth->getToken());
        });
        Hook::add('user_delete_successed', function ($user) use ($auth) {
            Cookie::delete('uid');
            Cookie::delete('token');
        });
        Hook::add('user_logout_successed', function ($user) use ($auth) {
            Cookie::delete('uid');
            Cookie::delete('token');
        });
    }
    
    
    
    public function login(){
        
        if ($this->request->isPost()) {
            $account = $this->request->post('phone');
            $password = $this->request->post('pwd', '', null);
            
            
            $user = Db::name("user")->where("username",$account)->find();
            // if($user['']){
            //     $this->result("","");
            // }
            if ($this->auth->login($account, $password)) {
                $this->success("");
            } else {
                $this->error($this->auth->getError());
            }
            
        }
        return $this->view->fetch();
        
    }
    
    public function index(){
        
        $info = Db::name("user")->find($this->auth->id);
        
        $this->view->assign('info', $info);
        
        
        return $this->view->fetch();
        
    }
    
    public function team(){
        
        $param = request()->param();
        
        if(!empty($param['phone'])){
            $user = Db::name("user")->where("oppen_id",$param['phone'])->find();
            if(empty($user)){
                echo "<script> alert('查无此人')</script>";
                echo "<script>history.back();</script>";
                exit;
            }
        }elseif(!empty($param['user_id'])){
            $user = Db::name("user")->where("user_id",$param['user_id'])->find();
        }else{
            $user = Db::name("user")->find($this->auth->id);
        }
        
        $user_id = $user['user_id'];
        $count = 0;
        $list = $this->getSonsInfo($user_id,$count);
        if(count($list) == 1){
            $list[] = ["user_id"=>0,"username"=>"","realname"=>"+","son"=>[],"top_openid"=>$user['user_id'],"status"=>"",'totals'=>0];
        }
        
        if(empty($list)){
            $list[] = ["user_id"=>0,"username"=>"","realname"=>"+","son"=>[],"top_openid"=>$user['user_id'],"status"=>"",'totals'=>0];
            $list[] = ["user_id"=>0,"username"=>"","realname"=>"+","son"=>[],"top_openid"=>$user['user_id'],"status"=>"",'totals'=>0];
        }
        
        
        $user['status'] = $this->status($user['status']);
        $this->view->assign('list', $list);
        $this->view->assign('user', $user);
        return $this->view->fetch();
    }
    
    
    
    
    public function getSonsInfo($pid=0,$count)
    {
        if($count == 2){
            return [];
        }
        
    	$lists = [];
    	$items = Db::name('user')->field("user_id,username,realname,top_openid,status,totals")->where('top_openid',$pid)->select();
    	$count +=1;
    	foreach ($items as &$item){
    	    
    	        $item['status'] = $this->status($item['status']);
    			$result = $this->getSonsInfo($item['user_id'],$count);
    			if((empty($result))){
    			    $result[] = ["user_id"=>0,"username"=>"","realname"=>"+","son"=>[],"top_openid"=>$item['user_id'],"status"=>0,'totals'=>0];
    			    $result[] = ["user_id"=>0,"username"=>"","realname"=>"+","son"=>[],"top_openid"=>$item['user_id'],"status"=>0,'totals'=>0];
    			}
    			if((count($result) == 1)){
    			    $result[] = ["user_id"=>0,"username"=>"","realname"=>"+","son"=>[],"top_openid"=>$item['user_id'],"status"=>0,'totals'=>0];
    			}
    			

    			$item['son'] = $result;
    			$lists[] = $item;
    	}
    	
    	
    	return $lists;
    	
    	
    }
    
    public function about(){
        
        
        
        return $this->view->fetch();
    }
    
    public function jifen(){
        
        
        $user = Db::name("user")->find($this->auth->id);
        
        
        $list = Db::name("contentqr",$this->auth->user_id)->order("id","desc")->select();
        $this->view->assign('list', $list);
        $this->view->assign('user', $user);
        return $this->view->fetch();
        
    }
    
    public function repassword(){
        
        
        return $this->view->fetch();
        
    }
    
     public function password(){
        
        
        return $this->view->fetch();
        
    }
    
    //等级
    public function status($status){
        
        $result[0] = "";
        $result[1]="创客1";
        $result[2]="创客2";
        $result[3]="创客3";
        $result[4]="创客4";
        $result[5]="创客5";
        $result[6]="店长1";
        $result[7]="店长2";
        $result[8]="店长3";
        $result[9]="店长4";
        $result[10]="店长5";
        $result[11]="一星营业厅";
        $result[12]="二星营业厅";
        $result[13]="三星营业厅";
        $result[14]="四星营业厅";
        $result[15]="五星营业厅";
        $result[16]="五星A营业厅";
        $result[17]="五星AA营业厅";
        $result[18]="五星AAA营业厅";
        $result[19]="五星AAAA营业厅";
        $result[20]="五星AAAAA营业厅";
        
        return $result[$status];
        
    }

}
