<?php

namespace app\admin\controller\user;
use app\common\controller\User as CommonUser;
use app\common\controller\Backend;
use app\common\library\Auth;

use app\index\controller\Task;

use fast\Http;
use think\Db;
use think\Config;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends Backend
{

    protected $relationSearch = true;
    protected $searchFields = 'user_id,realname,username';

    /**
     * @var \app\admin\model\User
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('User');
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $map['dzjb']=['=',1];

            if($this->request->param('search')){
                $keyword = $this->request->param('search');
                $map['username|realname'] = ['like','%'.$keyword.'%'];
            }

            $list = $this->model
                ->where($map)
                ->where(array('level'=>$this->request->get('level')))
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as $k => $v) {
                // $v->avatar = $v->avatar ? cdnurl($v->avatar, true) : letter_avatar($v->nickname);
                $v->hidden(['password', 'salt']);
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    // /**
    //  * 添加
    //  */
    // public function add()
    // {
    //     if ($this->request->isPost()) {
    //         $this->token();
    //     }
    //     return parent::add();
    // }

    // /**
    //  * 编辑
    //  */
    // public function edit($ids = null)
    // {
    //     if ($this->request->isPost()) {
    //         $this->token();
    //     }
    //     $row = $this->model->get($ids);
    //     $this->modelValidate = true;
    //     if (!$row) {
    //         $this->error(__('No Results were found'));
    //     }
    //     $this->view->assign('groupList', build_select('row[group_id]', \app\admin\model\UserGroup::column('id,name'), $row['group_id'], ['class' => 'form-control selectpicker']));
    //     return parent::edit($ids);
    // }

    // /**
    //  * 删除
    //  */
    // public function del($ids = "")
    // {
    //     if (!$this->request->isPost()) {
    //         $this->error(__("Invalid parameters"));
    //     }
    //     $ids = $ids ? $ids : $this->request->post("ids");
    //     $row = $this->model->get($ids);
    //     $this->modelValidate = true;
    //     if (!$row) {
    //         $this->error(__('No Results were found'));
    //     }
    //     Auth::instance()->delete($row['id']);
    //     $this->success();
    // }
   /*
    *   
    */
    public function add(){
        if (!$this->request->isPost()) {
            $params = $this->request->get();
            $row = $this->model->get(array('oppen_id'=>$params['oppen_id']));
            $this->view->assign(['xu'=>$row['username']]);
            return $this->view->fetch();
        }else{
            $this->token();
            $params = $this->request->post("row/a");
            $lu = $this->model->get(array('username'=>$params['phone']));
            if($lu){
                $this->error("该用户已存在，不能重复添加");
            }
            $tu = $this->model->get(array('username'=>$params['xid']));
            if($tu){
                if($tu['level'] == 0){
                    $this->error("摆位失败,摆位用户还未审核通过！，请联系管理员审核！");
                }
                if($tu['discount'] == 2){
                    $this->error("该用户已满2个下级，不能继续添加");
                }
                $users['oppen_id'] = $params['phone'];
                $users['username'] = $params['phone'];
                $users['realname'] = $params['username'];
                $users['password'] = "Lt123456";
                $users['level'] = 0;
                $users['discount'] = 0;
                $users['status'] = 0;
                $users['createtime'] = date("Y-m-d H:i:s", time());
                $users['zd'] = 23;
                $users['ys'] = 1;
                $users['name'] = $tu['username']."/".$tu['realname'];
                $users['top_openid'] = $tu['user_id'];
                if($params['xid'] == $params['tid']){
                    $users['fzjc'] = $tu['username']."/".$tu['realname'];
                    $users['tj'] = $tu['user_id'];
                }else{
                    $ttu = $this->model->get(array('username'=>$params['tid']));
                    $users['fzjc'] = $ttu['username']."/".$ttu['realname'];
                    $users['tj'] = $ttu['user_id'];
                }
                $this->model->isAutoWriteTimestamp(false);
                if($this->model->save($users)){
                    $this->success("成功添加团队增加成员！请联系管理员审核");
                }else{
                    $this->error("摆位失败，可能该位置已被占用，请联系管理员审核！");
                }
            }
            $this->error("摆位失败，可能该位置已被占用，请联系管理员审核！");
        }
    }

    /*
    *
    */
    public function uml($user_id = null){
        $list = DB::name("contentqr")->where('code',$user_id)->select();
        $this->view->assign('list',$list);
        return $this->view->fetch();
    }

    /*
    *
    */
    public function upu($username = null){
        if (!$this->request->isPost()) {
            $row = $this->model->get(array('username'=>$username));
            $this->view->assign('row',$row);
            return $this->view->fetch();
        }else{
            $this->token();
            $params = $this->request->post("row/a");
            //$u['ckjb'] = 0 ;
            //$u['dzjb'] = 0 ;
            $u['username'] = $params['username'];
            $u['realname'] = $params['realname'];
            $u['oppen_id'] = $params['username'];
            $u['status'] = $params['status'];

            if(isset($params['tid']) && $params['tid'] == 1){
                $u['password'] = "Lt123456";
            }
            $this->model->where('user_id',$params['user_id'])->update($u);
            if(empty($params['bingshi'])){
                $ztu = $this->model->get(array('username'=>$params['bingshi']));
                if($ztu){
                    $z['bingshi'] = $params['bingshi'].'/'.$ztu['realname'];
                    $z['remark'] = $ztu['user_id'];
                    $this->model->where('user_id',$params['user_id'])->update($z);
                    // $z['user_id'] = $params['user_id'];
                }
            }
            $this->model->where('top_openid',$params['user_id'])->update(array('name'=>$params['username'].'/'.$params['realname']));
            $this->model->where('tj',$params['user_id'])->update(array('fzjc'=> $params['username'].'/'.$params['realname']));

            $this->success("操作成功",url('/user/user/upu',['username'=>$params['username']]));
            // $this->success("成功");
        }
    }


    /*
    *
    */
    public function um($username = null){
        if (!$this->request->isPost()) {
            $row = $this->model->get(array('username'=>$username));
            $this->view->assign(array("user_id"=>$row['user_id'],"username"=>$row['username'],"realname"=>$row['realname'],'year'=>$row['year'],'sex'=>$row['sex']));
            return $this->view->fetch();
        }else{
            $this->token();
            $params = $this->request->post("row/a");
            if($params["select"] == 1){
                $params["um"] *= -1;
            }
            $uu = $this->model->get($params['user_id']);
            $zje = empty($uu['year']) ?0:$uu['year'];
            $ytx = empty($uu['sex']) ?0:$uu['sex'];
            $kyje = $zje - $ytx + $params["um"];
            // $uma = ['year'=>$params["um"],'user_id'=>]
            $content = array(
                "title" => $params["um"],
                "content" => date("Y-m-d H:i:s", time()) . '后台人工操作用户总金额增加' . $params["um"] . '--（备注）'.$params['bz'],
                "code" => $params['user_id'],
                "totals" => $kyje
            );
            Db::startTrans();
            try {
                Db::name('contentqr')->insert($content);
                // $this->model->isAutoWriteTimestamp(false);
                // $this->model->save(array('year'=>$zje + $params['um']),array('user_id'=>$params['user_id']));
                Db::name('user')->where('user_id',$params['user_id'])->update(['year'=>$zje + $params['um']]);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error("操作失败");
            }
            $this->success("操作成功",url('/user/user/index'));
        }
    }
    /*
    *   
    */
    public function del($user_id = null,$isorder = null){
        if ($this->request->isPost()) {
            $this->token();
        }
        $row = $this->model->get($user_id);
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $url = "https://zdsc.zhidou.shop/api/Login/zjusertotal?type=2&sign=1&username=".$row->data['username'];
        $http = new Http();
        $http->get($url);
        try{
            $this->model->setAttr('isorder',$isorder);
            $this->model->isAutoWriteTimestamp(false);
            $this->model->save(array('isorder'=>$isorder),array('user_id'=>$user_id)); 
        }catch (\Exception $e) {
            $this->error("修改失败");
        }
        $this->success();      
    }

    public function delup($user_id = null,$isorder = null){
        if ($this->request->isPost()) {
            //$this->token();
            $param = $this->request->post();
            $user_id = $param['user_id'];
            $isorder = $param['isorder'];

            $row = $this->model->get($user_id);
            $this->modelValidate = true;
            if (!$row) {
                return json(['code'=>-2,'msg'=>'找不到相关信息']);
                //$this->error('找不到相关信息');
            }

            $this->model->isAutoWriteTimestamp(false);
            
            //判断是否停机操作,停机则查找其推荐人
            if($isorder != null && $row['top_openid'] != null)
            {

                $comUser = new CommonUser();


                //更新用户上级级别信息
                $comUser->updateParentLevel($row,$isorder);

            }
            
            $uid = $this->model->save(['isorder'=>$isorder],['user_id'=>$user_id]);
            
            

            if($uid){
                return json(['code'=>1,'msg'=>'修改成功']);
            }else{
                return json(['code'=>-1,'msg'=>'修改失败']);
            }

        }else{
            $this->error("非法操作");
        }







//        $url = "https://zdsc.zhidou.shop/api/Login/zjusertotal?type=1&sign=1&username=".$row->data['username'];
//        $http = new Http();
//        $http->get($url);
//        try{
//            $this->model->isAutoWriteTimestamp(false);
//            $this->model->save(array('isorder'=>$isorder),array('user_id'=>$user_id));
//        }catch (\Exception $e) {
//            $this->error("修改失败");
//        }
//        $this->success();
    }

    public function search(){
        
        $row = [];
        $list=[];

        $date_from = '';
        $date_to='';

        if ($this->request->isPost()) {
           
           
           $param = $this->request->post();
            $date_from = $param['date_from'];
            $date_to = $param['date_to'];

           $field ='realname';
           if(is_numeric($param['username'])){
               $field = 'username';
           }
           $map[$field]=['=',$param['username']];

           $row = $this->model->where($map)->find();

           
           
           if(!empty($row)){
               
               $common_user = new CommonUser();
               
               //$list = $common_user->getlineusers($row['user_id'],$date_from,$date_to);
               $list = $common_user->getTreeuser($row['user_id'],$date_from,$date_to);
               $count = 0;
               
               if(!empty($list['left'])){

                  if($list['left']['isorder']==0){
                      //$count += count($list['left']['son']);
                      $count += ($list['left']['son']);
                  }else{
                      //$count += count($list['left']['son']) + 1;
                      $count += ($list['left']['son']) + 1;
                  }
                 


               }
               
               
               if(!empty($list['right'])){

                  if($list['right']['isorder']==0){
                      //$count += count($list['right']['son']);
                      $count += ($list['right']['son']);
                  }else{
                      //$count += count($list['right']['son']) + 1;
                      $count += ($list['right']['son']) + 1;
                  }
                


               }
               
               $row['count'] = $count;
           }
           
           
        }
        
        
        $this->view->assign(['row'=>$row,'list'=>$list,'date_from'=>$date_from,'date_to'=>$date_to]);
        return $this->view->fetch();
    }

    public function list(){
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            //$where = [['dzjb','in','0,2']];

            $map=[];
            if($this->request->param('search')){
                $keyword = $this->request->param('search');
                $map['username|realname'] = ['like','%'.$keyword.'%'];
            }

            $list = $this->model
                ->whereIn('dzjb','0,2')
                ->where($map)
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as $k => $v) {
                // $v->avatar = $v->avatar ? cdnurl($v->avatar, true) : letter_avatar($v->nickname);
                $v->hidden(['password', 'salt']);

//                if($v['ckjb'] == 0){
//                    $list[$k]['ckjb'] = '专员';
//                }else if($v['ckjb'] == 1){
//                    $list[$k]['ckjb'] = '预备创客';
//                }else if($v['ckjb'] == 2){
//                    $list[$k]['ckjb'] = '创客';
//                }else if($v['ckjb'] == 3){
//                    $list[$k]['ckjb'] = '预备店长';
//                }else if($v['ckjb'] == 4){
//                    $list[$k]['ckjb'] = '店长';
//                }else if($v['ckjb'] == 5){
//                    $list[$k]['ckjb'] = '预备经理';
//                }else if($v['ckjb'] == 6){
//                    $list[$k]['ckjb'] = '经理';
//                }else if($v['ckjb'] == 7){
//                    $list[$k]['ckjb'] = '预备总监';
//                }else if($v['ckjb'] == 8){
//                    $list[$k]['ckjb'] = '总监';
//                }else if($v['ckjb'] == 9){
//                    $list[$k]['ckjb'] = '预备总裁';
//                }else if($v['ckjb'] == 10){
//                    $list[$k]['ckjb'] = '总裁';
//                }
                
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    public function checkUser($user_id = null){
        if (!$this->request->isPost()) {
            $row=$this->model->get($user_id);
            $this->view->assign(['row'=>$row]);



            return $this->view->fetch();
        }else{
           
           $params = $this->request->post();
           $user_id = $params['user_id'];
           
           $uid=0;
           
           if($params['action'] == 'check'){
               
               Db::startTrans();
                //try {
                    
                    $param = model('param')->where('paramid',1)->find();
                    $tjj = $param['tjj'];
                    $tjj1 = $param['tjj1'];




                    $user = $this->model->get($user_id);
                    
                    if(empty($user)){
                         Db::rollback();
                        $this->error("操作失败!");
                    }

                    $remark = $this->model->where('user_id',$user['remark'])->find();


                   if(empty($remark)){
                       Db::rollback();
                       $this->error("没有直推人不能审核!");
                   }
                    $zhituicount = $this->model->where('remark',$user['remark'])->where('dzjb',1)->count();
                    if($zhituicount > 0 ){
                        $tjj = $tjj1;
                    }
                    $remark_data=[
                        'year'=>$remark['year'] + $tjj,
                        'area_id'=>$tjj,
                        'createtime'=>date('Y-m-d H:i:s')
                    ];



                    $uid = $this->model->where('user_id',$remark['user_id'])->update($remark_data);



                    if(empty($uid)){

                        Db::rollback();
                        $this->error("操作失败!!");
                    }

                    $uuid = $this->model->where('user_id',$user['user_id'])->update([
                        'isorder'=>1,
                        'dzjb'=>1,
                        'qrcode'=>date('Y-m-d H:i:s').'新注册'
                   ]); 



                    
                    if(empty($uuid)){
                          Db::rollback();
                        $this->error("操作失败!!!");
                    } else{

                        $top_user = $this->model->where('user_id',$user['top_openid'])->find();
                        $discount = (empty($top_user['discount']) ? 0 : intval($top_user['discount'])) + 1;

                        $uid = $this->model->where('user_id',$user['top_openid'])->update([
                            'discount'=>$discount
                        ]);

                    }



                    $total = ($remark['year'] + $tjj) - $remark['sex'];
                    $content = [
                        "title" => $tjj,
                        "content" => date('Y-m-d H:i:s').'--推荐用户'.$user['username'].'获得奖励：'.$tjj,
                        "code" => $user['remark'],
                        "totals" => $total
                    ];


                    $uid =  Db::name('contentqr')->insert($content);
                        
                    
                    
                    
                    //这里写用户等级升级的逻辑
                    
                      $task = new Task();
                      $task->updateuserstatus($user_id);
                    
                    
                    
                    // Db::name('contentqr')->insert($content);
                    // // $this->model->isAutoWriteTimestamp(false);
                    // // $this->model->save(array('year'=>$zje + $params['um']),array('user_id'=>$params['user_id']));
                    // Db::name('user')->where('user_id',$user_id)->update(['year'=>$zje + $params['um']]);
                    Db::commit();
              //  } catch (\Exception $e) {
                 //   Db::rollback();
                   // $this->error("操作失败");
              //  }

                   if(!empty($uid)){
                       $this->success("审核成功",url('/user/user/list'));
                   }else{
                       $this->error("审核失败");
                   }
               
           }else{
                
                $uid = $this->model->where('user_id',$user_id)->update([
                    'isorder'=>0,
                    'dzjb'=>2,
                    'bio'=>$params['reason'],
                    'member_time'=>date('Y-m-d H:i:s')
                    ]);

               if(!empty($uid)){
                   $this->success("审核成功",url('/user/user/list'));
               }else{
                   $this->error("审核失败");
               }

           }

        }
    }


    public function bindtp($oppen_id = null){
        if (!$this->request->isPost()) {
            $row=$this->model->get($oppen_id);
            $this->view->assign(['row'=>$row]);
            return $this->view->fetch();
        }else{
            $username = $this->request->post('username');
            $row=$this->model->get(array('username'=>$username));
            $list=$this->model->all(array('top_openid'=>$row['user_id']));
            $this->view->assign(['row'=>$row,'list' => $list]);
            return $this->view->fetch();
        }
    }

    public function upxj($user_id = null){
        if (!$this->request->isPost()) {
            $row=$this->model->get($user_id);
            $this->view->assign(['row'=>$row]);
            return $this->view->fetch();
        }else{
            $jzsj = $this->request->post('select');
            $user_id = $this->request->post('user_id');
            try{
                $this->model->where('user_id',$user_id)->update(array('jzsj'=>$jzsj));
            }catch (\Exception $e) {
                $this->error("修改失败");
            }
            $this->success("操作成功",url('/user/user/upxj?user_id='.$user_id));
        }
    }
    
    
    //参数管理
    public function params(){
        if($this->request->isPost()){
             $this->token();
            $params = $this->request->post();
           
            
            $data=[ 
                    "tjj"=> $params['tjj'],
                    "tjj1"=> $params['tjj1'] ?$params['tjj1']:0,
                    "yyt1"=>$params['yyt1'],
                    "yyt2"=>$params['yyt2'],
                    "yyt3"=>$params['yyt3'],
                    "yyt4"=>$params['yyt4'],
                    "yyt5"=>$params['yyt5'],
                    "yyt6"=>$params['yyt6'],
                 "dz1"=>$params['dz1'],
                 "dz2"=>$params['dz2'],
                 "dz3"=>$params['dz3'],
                 "dz4"=>$params['dz4'],
                 "dz5"=>$params['dz5'],
                 "dz6"=>$params['dz6'],
                 "dz7"=>$params['dz7'],
                 "dz8"=>$params['dz8'],
                 "dz9"=>$params['dz9'],
                 "dz10"=>$params['dz10'],
                 ];
                
            $uid = model('param')->where('paramid',1)->update($data);  
            if($uid){
                $this->success("操作成功",url('/user/user/params'));
            }else{
                $this->error("修改失败");
            }
            
        }else{
            
            $result = model('param')->get(1);
            $this->assign(['result'=>$result]);
        
            return $this->view->fetch();
        
        }
        
       
        
    }
    
  
  
    
}
