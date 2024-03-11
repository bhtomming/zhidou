<?php

namespace app\index\controller;

use app\common\controller\User as CommonUser;

//use addons\wechat\model\WechatCaptcha;
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
class User extends Frontend
{
    protected $layout = 'default';
    protected $noNeedLogin = ['login', 'register', 'third', 'forget'];
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

    /**
     * 会员中心
     */
    public function index()
    {
        
        
        $user = Db::name("user")->find($this->auth->id);
        $result = Db::name("contentqr")->where("code",$user['user_id'])->order("id","desc")->select();
        
        
        
        $this->view->assign('user', $user);
        $this->view->assign('result', $result);
        $this->view->assign('title', __('User center'));
        return $this->view->fetch();
    }

    /**
     * 注册会员
     */
    public function register()
    {
        $url = $this->request->request('url', '', 'url_clean');
        if ($this->auth->id) {
            $this->success(__('You\'ve logged in, do not login again'), $url ? $url : url('user/index'));
        }
        if ($this->request->isPost()) {
            $username = $this->request->post('username');
            $password = $this->request->post('password', '', null);
            $email = $this->request->post('email');
            $mobile = $this->request->post('mobile', '');
            $captcha = $this->request->post('captcha');
            $token = $this->request->post('__token__');
            $rule = [
                'username'  => 'require|length:3,30',
                'password'  => 'require|length:6,30',
                'email'     => 'require|email',
                'mobile'    => 'regex:/^1\d{10}$/',
                '__token__' => 'require|token',
            ];

            $msg = [
                'username.require' => 'Username can not be empty',
                'username.length'  => 'Username must be 3 to 30 characters',
                'password.require' => 'Password can not be empty',
                'password.length'  => 'Password must be 6 to 30 characters',
                'email'            => 'Email is incorrect',
                'mobile'           => 'Mobile is incorrect',
            ];
            $data = [
                'username'  => $username,
                'password'  => $password,
                'email'     => $email,
                'mobile'    => $mobile,
                '__token__' => $token,
            ];
            //验证码
            $captchaResult = true;
            $captchaType = config("fastadmin.user_register_captcha");
            if ($captchaType) {
                if ($captchaType == 'mobile') {
                    $captchaResult = Sms::check($mobile, $captcha, 'register');
                } elseif ($captchaType == 'email') {
                    $captchaResult = Ems::check($email, $captcha, 'register');
                } elseif ($captchaType == 'wechat') {
                    $captchaResult = WechatCaptcha::check($captcha, 'register');
                } elseif ($captchaType == 'text') {
                    $captchaResult = \think\Validate::is($captcha, 'captcha');
                }
            }
            if (!$captchaResult) {
                $this->error(__('Captcha is incorrect'));
            }
            $validate = new Validate($rule, $msg);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
            }
            if ($this->auth->register($username, $password, $email, $mobile)) {
                $this->success(__('Sign up successful'), $url ? $url : url('user/index'));
            } else {
                $this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
            }
        }
        //判断来源
        $referer = $this->request->server('HTTP_REFERER', '', 'url_clean');
        if (!$url && $referer && !preg_match("/(user\/login|user\/register|user\/logout)/i", $referer)) {
            $url = $referer;
        }
        $this->view->assign('captchaType', config('fastadmin.user_register_captcha'));
        $this->view->assign('url', $url);
        $this->view->assign('title', __('Register'));
        return $this->view->fetch();
    }

    /**
     * 会员登录
     */
    public function login()
    {
        $url = $this->request->request('url', '', 'url_clean');
        if ($this->auth->id) {
            $this->success(__('You\'ve logged in, do not login again'), $url ?: url('user/index'));
        }
        if ($this->request->isPost()) {
            $account = $this->request->post('account');
            $password = $this->request->post('password', '', null);
            $keeplogin = (int)$this->request->post('keeplogin');
            $token = $this->request->post('__token__');
            $rule = [
                'account'   => 'require|length:3,50',
                'password'  => 'require|length:6,30',
                // '__token__' => 'require|token',
            ];

            $msg = [
                'account.require'  => 'Account can not be empty',
                'account.length'   => 'Account must be 3 to 50 characters',
                'password.require' => 'Password can not be empty',
                'password.length'  => 'Password must be 6 to 30 characters',
            ];
            $data = [
                'account'   => $account,
                'password'  => $password,
                // '__token__' => $token,
            ];
            $validate = new Validate($rule, $msg);
            $result = $validate->check($data);
            
            
            
            if (!$result) {
                //$this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
                return json(['code'=>-3,'msg'=>'账号或者密码不能为空']);
                
            }
            
            
             $user = Db::name('user')->where('username',$account)->find();
                
            if($user['isorder'] == 0){
                return json(['code'=>-4,'msg'=>'账户已被冻结,请联系管理员']);
            }else{
                 if ($this->auth->login($account, $password)) {
                
               
                    return json(['code'=>1,'msg'=>'登录成功']);
                        //$this->success(__('Logged in successful'), $url ? $url : url('user/index'));
                 } else {
                        return json(['code'=>-2,'msg'=>'密码错误或者账号不存在！登录失败']);
                        //$this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
                 }
            }
            
           
        }
        //判断来源
        $referer = $this->request->server('HTTP_REFERER', '', 'url_clean');
        if (!$url && $referer && !preg_match("/(user\/login|user\/register|user\/logout|uer\/forget)/i", $referer)) {
            $url = $referer;
        }
        $this->view->assign('url', $url);
        $this->view->assign('title', __('Login'));
        return $this->view->fetch();
    }

    /**
     * 退出登录
     */
    public function logout()
    {
       // if ($this->request->isPost()) {
           // $this->token();
            //退出本站
            $this->auth->logout();
            
           
            //$this->success(__('Logout successful'), url('user/index'));
       // }
        
         $html = '<script>location.href="'.url('user/login').'"</script>';
        //$html = "<form id='logout_submit' name='logout_submit' action='' method='post'>" . token() . "<input type='submit' value='ok' style='display:none;'></form>";
        //$html .= "<script>document.forms['logout_submit'].submit();</script>";

        return $html;
    }

    /**
     * 忘记密码
     */
    public function forget(){
        $url = $this->request->request('url', '', 'url_clean');
        if ($this->request->isPost()) {
            
            
            
        }
        
         $referer = $this->request->server('HTTP_REFERER', '', 'url_clean');
        if (!$url && $referer && !preg_match("/(user\/login|user\/register|user\/logout|uer\/forget)/i", $referer)) {
            $url = $referer;
        }
        $this->view->assign('url', $url);
  
        $this->view->assign('title', "忘记密码");
        return $this->view->fetch();
    }
    
    

    /**
     * 个人信息
     */
    public function profile()
    {
        
        
        $user = Db::name("user")->find($this->auth->id);
        $result = Db::name("contentqr")->where("code",$user['user_id'])->order("id","desc")->select();
        

        
        $this->view->assign('result', $result);
        $this->view->assign('user', $user);
        $this->view->assign('title', __('Profile'));
        return $this->view->fetch();
    }

    /**
     * 修改密码
     */
    public function changepwd()
    {
        if ($this->request->isPost()) {
            $oldpassword = $this->request->post("oldpassword", '', null);
            $newpassword = $this->request->post("newpassword", '', null);
            $renewpassword = $this->request->post("renewpassword", '', null);
            $token = $this->request->post('__token__');
            $rule = [
                'oldpassword'   => 'require|regex:\S{6,30}',
                'newpassword'   => 'require|regex:\S{6,30}',
                'renewpassword' => 'require|regex:\S{6,30}|confirm:newpassword',
                '__token__'     => 'token',
            ];

            $msg = [
                'renewpassword.confirm' => __('Password and confirm password don\'t match')
            ];
            $data = [
                'oldpassword'   => $oldpassword,
                'newpassword'   => $newpassword,
                'renewpassword' => $renewpassword,
                '__token__'     => $token,
            ];
            $field = [
                'oldpassword'   => __('Old password'),
                'newpassword'   => __('New password'),
                'renewpassword' => __('Renew password')
            ];
            $validate = new Validate($rule, $msg, $field);
            $result = $validate->check($data);
            if (!$result) {
                
                return json(['code'=>-2,'msg'=>$validate->getError()]);
                //$this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
            }


            $ret = Db::name("user")->where("id",$this->auth->id)->update(['password'=>$data['newpassword']]);
            //$ret = $this->auth->changepwd($newpassword, $oldpassword);
            
            if ($ret) {
                
                return json(['code'=>1,'msg'=>'密码修改成功！']);
                
                //$this->success(__('Reset password successful'), url('user/login'));
            } else {
                
                 return json(['code'=>-1,'msg'=>$this->auth->getError()]);
                
                //$this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
            }
        }
        
        
        $this->view->assign('title', __('Change password'));
        return $this->view->fetch();
    }
    
    
    public function add(){
        
       
        
        if ($this->request->isPost()) {
            
           
            
            $params = request()->param();
            //更新
            if(!empty($params['id'])) {
                $users['oppen_id'] = $params['phone'];
                $users['username'] = $params['phone'];
                $users['realname'] = $params['realname'];
                $users['bio'] = '';
                $users['dzjb'] = 0;
                $users['cardNo'] = $params['cardNo'];
                if(!empty($params['bingshi'])){
                    $bingshi_user = Db::name("user")->where("username",$params['bingshi'])->find();
                    
                    if(empty($bingshi_user)){
                        return json(['code'=>-5,'msg'=>"直推人手机号有误！请重新核实"]);
                    }
                    
                    $users['bingshi'] = $bingshi_user['username']."/".$bingshi_user['realname'];
                    $users['remark'] = $bingshi_user['user_id'];
                }else{
                     return json(['code'=>-4,'msg'=>"直推人手机不得为空"]);
                }
                $flag = Db::name('user')->where('id',$params['id'])->update($users);
                if($flag){
                    return json(['code'=>1,'msg'=>"重新提交成功！请联系管理员审核"]);
                    
                }else{
                    return json(['code'=>-1,'msg'=>"重新提交失败！"]);
                }
            }
            $lu = Db::name("user")->where("username",$params['phone'])->find();
            if($lu){
                
                $this->error("该用户已存在，不能重复添加");
            }
            $tu = Db::name("user")->where("user_id",$params['xid'])->find();
           
            if($tu){
                if($tu['level'] == 0){
                    
                     return json(['code'=>-3,'msg'=>"摆位失败,摆位用户还未审核通过！，请联系管理员审核！"]);
                    //$this->error("摆位失败,摆位用户还未审核通过！，请联系管理员审核！");
                }

                 if($tu['isorder'] == 0){
                     return json(['code'=>-4,'msg'=>"安置人已停机，请联系管理员审核！"]);
                 }
                
                if($tu['discount'] == 1){
                    $discount = 2;
                }
                
                $user_id = Db::name("user")->max("user_id");

                $users['user_id'] = $user_id+1;
                $users['oppen_id'] = $params['phone'];
                $users['username'] = $params['phone'];
                $users['realname'] = $params['realname'];
                $users['password'] = "Lt123456";
                $users['level'] = 2;
                $users['discount'] = 0;
                $users['status'] = 0;
                $users['isorder']=1;
                $users['createtime'] = date("Y-m-d H:i:s", time());
                $users['zd'] = 23;
                $users['ys'] = 1;
                $users['ckjb'] = 0;
                $users['dzjb'] = 0;
                $users['totals'] = 0;
                $users['jzsj'] = 2;
                $users['area_id']=0;
                $users['sex']=0;
                $users['year']=0;

                $users['name'] = $tu['username']."/".$tu['realname'];
                $users['top_openid'] = $tu['user_id'];
                
                if($params['xid'] == $params['tid']){
                    $users['fzjc'] = $tu['username']."/".$tu['realname'];
                    $users['tj'] = $tu['user_id'];
                }else{
                    $ttu = Db::name("user")->where("user_id",$params['tid'])->find();
                    $users['fzjc'] = $ttu['username']."/".$ttu['realname'];
                    $users['tj'] = $ttu['user_id'];
                }
                
                if(!empty($params['bingshi'])){
                    $bingshi_user = Db::name("user")->where("username",$params['bingshi'])->find();
                    
                    if(empty($bingshi_user)){
                        return json(['code'=>-5,'msg'=>"直推人手机号有误！请重新核实"]);
                    }
                    
                    $users['bingshi'] = $bingshi_user['username']."/".$bingshi_user['realname'];
                    $users['remark'] = $bingshi_user['user_id'];
                }else{
                     return json(['code'=>-4,'msg'=>"直推人手机不得为空"]);
                }
                
                //$user['qrcode'] = isset($params['avatar'])&& !empty($params['avatar']) ? $params['avatar'] : date('Y-m-d H:i:s').'新注册';
                $users['cardNo'] = $params['cardNo'];
                
                $flag = Db::name("user")->insert($users);
                if($flag){
                    // Db::name("user")->where("username",$params['xid'])->update(['discount'=>2]);
                    //$this->success("成功添加团队增加成员！请联系管理员审核");
                    return json(['code'=>1,'msg'=>"成功添加团队增加成员！请联系管理员审核"]);
                    
                }else{
                    //$this->error("摆位失败，可能该位置已被占用，请联系管理员审核！");
                    return json(['code'=>-1,'msg'=>"摆位失败，可能该位置已被占用，请联系管理员审核！"]);
                }
            }else{
                return json(['code'=>-2,'msg'=>"安置人不得为空"]); 
            }
            
        }else{
            
            
            $result = request()->param();
            $top_user =  Db::name("user")->find($this->auth->id);
            $top = Db::name("user")->where("user_id",$result['xid'])->find();
            $this->view->assign('top',$top);
            $this->view->assign('top_user',$top_user);
            $this->view->assign('id',!empty($result['id']) ? $result['id']:0);
             
            return $this->view->fetch();
        }
        
        
        
    }
    
       
    /**
     * 图片上传
     */
    public function upload(){
        $file = $_FILES['file'];  
        
        $file_arr = explode('.',$_FILES['file']['name']);
        $file_name = uniqid();
        $ext = $file_arr[1];
        
        
        
        if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
            
            $tempFile = $_FILES['file']['tmp_name'];
            $pathName = 'uploads/';
            
            
            $targetFile =  $pathName .$file_name.'.'.$ext;
            
            
            
            if (move_uploaded_file($tempFile, $targetFile)) {
			
				//上传成功
				 echo '/'.$targetFile;
				
			
             }else{
				//上传失败
				echo '';
			 }
			 
		}else{
		    
		    //上传失败
		    	
			 	echo '';
		}
		
    }
    
    
    /**
     * 我的团队
     */
    public function team(){
        
        
        $result = request()->param();
        $flag = 0;
        $search_result="";

        if(!empty($result['phone'])){
            $user = Db::name("user")->where("oppen_id",$result['phone'])->find();


            if(empty($user)){
                $flag = 1;
                $user =  Db::name("user")->find($this->auth->id);
                $search_result = "查无".$result['phone']."号码的数据";
            }
            
            $user_id = $user['user_id'];
        }else if(!empty($result['user_id'])){
            
            $user = Db::name("user")->where("user_id",$result['user_id'])->find();

            $user_id = isset($user['user_id']) ? $user['user_id'] : 0;
            
        }else{
            $user = Db::name("user")->find($this->auth->id);
            $user_id = $user['user_id'];
        }
        
        $count = 1;
        
        
        
       
        
        
        
        $result = $this->getSonsInfo($user_id,$count);
        
        if(count($result) == 0){
            $result[] = ['user_id'=>0,"username"=>"添加","realname"=>"+","son"=>[],"count"=>0,'isorder'=>1];
            $result[] = ['user_id'=>0,"username"=>"添加","realname"=>"+","son"=>[],"count"=>0,'isorder'=>1];
        }else if(count($result) == 1){
            $result[] = ['user_id'=>0,"username"=>"添加","realname"=>"+","son"=>[],"count"=>0,'isorder'=>1];

        }
            
            
            $total = 0;
            
            // if(isset($result[0]) && !empty($result[0]) && $result[0]['realname'] != '+'){
            //     $total += $result[0]['count'] + 1;
            // }
            
            
            // if(isset($result[1]) && !empty($result[1]) && $result[1]['realname'] != '+'){
            //     $total += $result[1]['count'] + 1;
            // }
            
            
            $top_user = Db::name("user")->find($this->auth->id);
                
            $common_user = new CommonUser();
                   
            $list = $common_user->getlineusers($user_id);
                   
          
                   
            if(!empty($list['left'])){


                $left_total_users = 0;

                if($list['left']['isorder'] == 0){
                  //  $leftLineUsers = $common_user->getlineusers($list['left']['user_id']);
                   // $total += count($leftLineUsers['left']['son']) + count($leftLineUsers['right']['son']);
                    $left_users = [];
                    CommonUser::getchildrenuser($list['left']['user_id'],$left_users);
                    $left_total_users = count($left_users);
                }else{
                    $left_total_users = count($list['left']['son']) + 1;
                }
                $total += $left_total_users;

                //if(isset($result[0]) ) {$result[0]['count'] = count($list['left']['son']);}
                if(isset($result[0]) ) {$result[0]['count'] = $left_total_users;}
            }
                   
                   
            if(!empty($list['right'])){

                $right_total_users = 0;
                if($list['right']['isorder'] == 0){
                   // $total += count($list['right']['son']);
                    $right_users = [];
                    CommonUser::getchildrenuser($list['right']['user_id'],$right_users);
                    $right_total_users = count($right_users);
                }else{
                    $right_total_users = count($list['right']['son']) + 1;
                }
                $total += $right_total_users;


                if(isset($result[1]) ) {$result[1]['count'] = $right_total_users;}
            }
            
            
            
            
            $user['count'] = $total;
     
        
        
        
        $reason_list = Db::name('user')->where('tj',$top_user['user_id'])->where('dzjb',2)->where('isorder',0)->select();
        
        
           
        if($user['realname']){
                
                if(mb_strlen($user['realname']) >= 3){
                    $tmp_arr=[];
                    $tmp_arr[0] = mb_substr($user['realname'], 0,1);
                    $tmp_arr[1] = mb_substr($user['realname'], mb_strlen($user['realname'])-1 ,mb_strlen($user['realname']));
                    
                    $tmp_str = "";
                    for($i=0;$i<mb_strlen($user['realname'])-2;$i++ ){
                        $tmp_str .="*";
                    }
                    
                    $user['realname'] = $tmp_arr[0].$tmp_str.$tmp_arr[1];
                }else if(mb_strlen($user['realname']) == 2){
                    $tmp_str = mb_substr($user['realname'],0,1);
                    $user['realname'] = $tmp_str.'*';
                }
                
                
        }
        
        
        $this->view->assign('result',$result);
        $this->view->assign('users',$user);
        $this->view->assign('search_result',$search_result);
        $this->view->assign('reason_list',$reason_list);

        $this->assignconfig('flag',$flag);
         
        return $this->view->fetch();
    }
    
    
    
    
    
    public function getSonsInfo($pid=0,$count)
    {
        
        $lists =[];
        $items = Db::name('user')->field("user_id,username,realname,isorder")->where('top_openid',$pid)->select();
        
        foreach ($items as $key=>$value){
            $result = $this->getSonInfo1($value['user_id']);
            
            $value['son'] = $result;
            $value['count'] = count($result);
            
            if($value['realname']){
                
                if(mb_strlen($value['realname']) >= 3){
                    $tmp_arr=[];
                    $tmp_arr[0] = mb_substr($value['realname'], 0,1);
                    $tmp_arr[1] = mb_substr($value['realname'], mb_strlen($value['realname'])-1 ,mb_strlen($value['realname']));
                    
                    $tmp_str = "";
                    for($i=0;$i<mb_strlen($value['realname'])-2;$i++ ){
                        $tmp_str .="*";
                    }
                    
                    $value['realname'] = $tmp_arr[0].$tmp_str.$tmp_arr[1];
                }else if(mb_strlen($value['realname']) == 2){
                    $tmp_str = mb_substr($value['realname'],0,1);
                    $value['realname'] = $tmp_str.'*';
                }
                
                
            }
            
            $lists[] = $value;
        }
        
        
        
        
        return $lists;
        
    //     if($count == 4){
    //         return [];
    //     }
        
    // 	$lists = [];
    // 	$items = Db::name('user')->field("user_id,username,realname")->where('top_openid',$pid)->select();
    // 	$count +=1;
    // 	foreach ($items as $item){
    // 			$result = $this->getSonsInfo($item['user_id'],$count);
    			
    // 			$item['count'] = count($result);
    			
    		    
    // 		    if(empty($result)){
    		        
    // 		        $result[] = ["user_id"=>0,"username"=>"添加","realname"=>"+","son"=>[],"count"=>0];
    // 		        $result[] = ["user_id"=>0,"username"=>"添加","realname"=>"+","son"=>[],"count"=>0];
    		        
    // 		    }else if (count($result) == 1){
    			    
    // 			    $result[] = ["user_id"=>0,"username"=>"添加","realname"=>"+","son"=>[],"count"=>0];
    			    
    // 			}
    			
    // 			$item['son'] = $result;
    		   
    // 			$lists[] = $item;
    // 	}
    	

    	
    // 	return $lists;
    	
    	
    }

    public function getSonInfo1($pid = 0){
        $lists =  Db::name('user')->field("user_id,username,realname,isorder")->where('top_openid',$pid)->select();
        
        foreach ($lists as $key=>$value){
            
            if($value['realname']){
                
                if(mb_strlen($value['realname']) >= 3){
                    $tmp_arr=[];
                    $tmp_arr[0] = mb_substr($value['realname'], 0,1);
                    $tmp_arr[1] = mb_substr($value['realname'],mb_strlen($value['realname'])-1 ,mb_strlen($value['realname']));
                    
                    $tmp_str = "";
                    for($i=0;$i<mb_strlen($value['realname'])-2;$i++ ){
                        $tmp_str .="*";
                    }
                    
                    $lists[$key]['realname'] = $tmp_arr[0].$tmp_str.$tmp_arr[1];
                    
                }else if(mb_strlen($value['realname']) == 2){
                    $tmp_str = mb_substr($value['realname'],0 ,1);
                    $lists[$key]['realname'] = $tmp_str.'*';
                }
                
                
            }
            
            
            
            
        }
        
        
        return $lists;
    }



    /**
     * 关于我们
     */
    public function about(){
        
        $result =  Db::name('content')->find(5);
         $this->view->assign('result',$result);
         return $this->view->fetch();
    }


    public function attachment()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $mimetypeQuery = [];
            $where = [];
            $filter = $this->request->request('filter');
            $filterArr = (array)json_decode($filter, true);
            if (isset($filterArr['mimetype']) && preg_match("/(\/|\,|\*)/", $filterArr['mimetype'])) {
                $this->request->get(['filter' => json_encode(array_diff_key($filterArr, ['mimetype' => '']))]);
                $mimetypeQuery = function ($query) use ($filterArr) {
                    $mimetypeArr = array_filter(explode(',', $filterArr['mimetype']));
                    foreach ($mimetypeArr as $index => $item) {
                        $query->whereOr('mimetype', 'like', '%' . str_replace("/*", "/", $item) . '%');
                    }
                };
            } elseif (isset($filterArr['mimetype'])) {
                $where['mimetype'] = ['like', '%' . $filterArr['mimetype'] . '%'];
            }

            if (isset($filterArr['filename'])) {
                $where['filename'] = ['like', '%' . $filterArr['filename'] . '%'];
            }

            if (isset($filterArr['createtime'])) {
                $timeArr = explode(' - ', $filterArr['createtime']);
                $where['createtime'] = ['between', [strtotime($timeArr[0]), strtotime($timeArr[1])]];
            }
            $search = $this->request->get('search');
            if ($search) {
                $where['filename'] = ['like', '%' . $search . '%'];
            }

            $model = new Attachment();
            $offset = $this->request->get("offset", 0);
            $limit = $this->request->get("limit", 0);
            $total = $model
                ->where($where)
                ->where($mimetypeQuery)
                ->where('id', $this->auth->id)
                ->order("id", "DESC")
                ->count();

            $list = $model
                ->where($where)
                ->where($mimetypeQuery)
                ->where('id', $this->auth->id)
                ->order("id", "DESC")
                ->limit($offset, $limit)
                ->select();
            $cdnurl = preg_replace("/\/(\w+)\.php$/i", '', $this->request->root());
            foreach ($list as $k => &$v) {
                $v['fullurl'] = ($v['storage'] == 'local' ? $cdnurl : $this->view->config['upload']['cdnurl']) . $v['url'];
            }
            unset($v);
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $mimetype = $this->request->get('mimetype', '');
        $mimetype = substr($mimetype, -1) === '/' ? $mimetype . '*' : $mimetype;
        $this->view->assign('mimetype', $mimetype);
        $this->view->assign("mimetypeList", \app\common\model\Attachment::getMimetypeList());
        return $this->view->fetch();
    }
}
