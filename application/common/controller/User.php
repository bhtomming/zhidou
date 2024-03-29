<?php

namespace app\common\controller;


use fast\Random;
use think\Config;
use think\Validate;

/**
 * 会员接口
 */
class User 
{
    
     
     private $counts = 0;
     const COUNTS=0;
    //用户升级
    public function updateuserstatus($user_id=0){
        
        
        if(!empty($user_id)){
           
           $user = model('user')->where('user_id',$user_id)->find();
           
           
           if(empty($user) || empty($user_id)){
               return json(['code'=>-1,'msg'=>'获取信息失败']);
               
           }
        
            $user_id = $user['user_id'];
           
           $list = $this->getlineusers($user['user_id']);
        
           $params = model('param')->get(1);
           
           $left_count = $right_count = 0;
           
           if(!empty($list['left'])){
               $left_count = isset($list['left']['son']) ?  count($list['left']['son']) : 0 ; 
           }
           
           if(!empty($list['right'])){
                $right_count = isset($list['right']['son']) ? count($list['right']['son']) : 0;               
           }
           
            if($left_count >0 && $right_count > 0){
                
                if(($left_count >= $params['dz9'] && $right_count >= $params['dz10']) || ($left_count >= $params['dz10'] && $right_count >= $params['dz9'])){
                    model('user')->where('user_id',$user_id)->update([
                        'ckjb'=> 9]);
                }else if(($left_count >= $params['dz7'] && $right_count >= $params['dz8']) || ($left_count >= $params['dz8'] && $right_count >= $params['dz7'])){
                     model('user')->where('user_id',$user_id)->update([
                        'ckjb'=> 7]);
                }else if(($left_count >= $params['dz5'] && $right_count >= $params['dz6']) || ($left_count >= $params['dz6'] && $right_count >= $params['dz5'])){
                     model('user')->where('user_id',$user_id)->update([
                        'ckjb'=> 5]);
                }else if(($left_count >= $params['dz3'] && $right_count >= $params['dz4']) || ($left_count >= $params['dz4'] && $right_count >= $params['dz3'])){
                     model('user')->where('user_id',$user_id)->update([
                        'ckjb'=> 3]);
                }else if(($left_count >= $params['dz1'] && $right_count >= $params['dz2']) || ($left_count >= $params['dz2'] && $right_count >= $params['dz1'])){
                     model('user')->where('user_id',$user_id)->update([
                        'ckjb'=> 1]);
                }
                
                
                return json(['code'=>1,'msg'=>'用户更新等级成功']);
                
            }else{
                
                 return json(['code'=>-2,'msg'=>'用户数未达标']);
            }
            
        }
        
    }
    
    
    public function getlineusers($top_openid,$date_from='',$date_to=''){

        $map['top_openid']=['=',$top_openid];

        if(!empty($date_from) && !empty($date_to)){

            $map['createtime']=['between time',[$date_from,$date_to]];
           
        }else if(!empty($date_from)){
            $map['createtime']=['>=',$date_from];
        }else if(!empty($date_to)){
            $map['createtime']=['<=',$date_to];
        }

        $user_list = model('user')->where($map)->select();
        if(count($user_list) > 0){
            if(count($user_list) == 2){
                
                $left_users = $user_list[0];
                $right_users = $user_list[1];
                
                $left_child_list = [];
                $right_child_list=[];


                if($left_users['isorder'] == 1){
                    self::getchildrenuser($left_users['user_id'], $left_child_list,$date_from,$date_to);
                }


                if($right_users['isorder'] == 1){
                    self::getchildrenuser($right_users['user_id'], $right_child_list,$date_from,$date_to);
                }
                
                $left_users->son = $left_child_list;
                $right_users->son = $right_child_list;
                
                
                return ['left'=>$left_users,'right'=>$right_users];
                
            }else if(count($user_list) == 1){
                
                $left_users = $user_list[0];
                
                $left_child_list=[];

                if($left_users['isorder'] == 1){
                    self::getchildrenuser($left_users['user_id'], $left_child_list,$date_from,$date_to);
                }

               
                $left_users->son = $left_child_list;
                
                return ['left'=>$left_users,'right'=>[]];
            }
        }else{
            return  ['left'=>[],'right'=>[]];
        }
        
    }
    
    
    public static function getTreeuser($top_openid,$date_from='',$date_to=''){
        $map['top_openid']=['=',$top_openid];
        $map['isorder'] = ['=',1];
        $user_list = model('user')
        ->where( $map)
        ->select();
        if(count($user_list) > 0){
            if(count($user_list) == 2){
                $left_users = $user_list[0];
                $right_users = $user_list[1];
                $left_c = 0;
                $right_c=0;
                if($left_users['isorder'] == 1){
                    self::getchildrenusers($left_users['user_id'],$left_c,$date_from,$date_to);
                   
                }
                 if($right_users['isorder'] == 1){
                    self::getchildrenusers($right_users['user_id'], $right_c,$date_from,$date_to);
                }
                
                $left_users->son = $left_c;
                $right_users->son = $right_c;
                
                
                return ['left'=>$left_users,'right'=>$right_users];
            }else if(count($user_list) == 1){
                
                $left_users = $user_list[0];
                
                $left_c=0;

                if($left_users['isorder'] == 1){
                    self::getchildrenusers($left_users['user_id'], $left_c,$date_from,$date_to);
                }

               
                $left_users->son = $left_c;
                
                return ['left'=>$left_users,'right'=>[]];
            }
        }else{
            return  ['left'=>[],'right'=>[]];
        }
       
    }
    
    public static function getchildrenusers($top_openid,&$c,$date_from='',$date_to=''){
        
        $map['top_openid']=['=',$top_openid];
        //$map['isorder'] = ['=',1];
        $childern = model('user')
        ->where( $map)
        ->select();
        
        if(empty($childern)){
            return $c;
        }
        foreach ($childern as $value){
            if($value['user_id']){
               if(!empty($date_from) && !empty($date_to)){

                    if(strtotime($value['createtime']) >= strtotime($date_from.' 00:00:00') && strtotime($value['createtime']) <= strtotime($date_to.' 23:59:59')){
                        $c+=1;
                    }
        
                }else if(!empty($date_from)){
                    if(strtotime($value['createtime']) >= strtotime($date_from.' 00:00:00') ){
                        $c+=1;
                    }
                }else if(!empty($date_to)){
                    if(strtotime($value['createtime']) <= strtotime($date_to.' 23:59:59')){
                        $c+=1;
                    }
                }else{
                    $c+=1;
                }
                
              self::getchildrenusers($value['user_id'],$c,$date_from,$date_to);
            }
        }
    }
    public static function getchildrenuser($top_openid ,&$list,$date_from='',$date_to=''){

        $map['top_openid']=['=',$top_openid];
        $map['isorder'] = ['=',1];
        if(!empty($date_from) && !empty($date_to)){

            $map['createtime']=['between time',[$date_from,$date_to]];

        }else if(!empty($date_from)){
            $map['createtime']=['>=',$date_from];
        }else if(!empty($date_to)){
            $map['createtime']=['<=',$date_to];
        }


        $childern = model('user')
        ->where( $map)
        ->select();
        if(empty($childern)){
            return $list;
        }
        
        
        foreach ($childern as $value){
            if($value['user_id']){
               $list[]= $value;
                
                self::getchildrenuser($value['user_id'],$list,$date_from,$date_to);
            }
        }
        
    }
    
    
    //平均奖励
    public function averagereward(){
        $params = model('param')->get(1);
        
        
        //当天新增用户
        $today_users = model('user')->where('qrcode','like',date('Y-m-d').'%')->select();
        
        $level_users = [];
        
        
        $level_users['level_2_user'] = model('user')->where('ckjb',2)->select();
        
        $level_users['level_3_user'] = model('user')->where('ckjb',4)->select();
        
        $level_users['level_4_user'] = model('user')->where('ckjb',6)->select();
        
        $level_users['level_5_user'] = model('user')->where('ckjb',8)->select();
        
        $level_users['level_6_user'] = model('user')->where('ckjb',10)->select();
        
        //将所有到等级的用户增加平均奖励
        for($i =2;$i<7;$i++){
            
            $reward = count($today_users) * $params['yyt'.$i] / count($level_users['level_'.$i.'_user']);
            
            foreach ($level_users['level_'.$i.'_user']  as $key=>$value){
                
                if($value['user_id']){
                    model('user')->where('user_id',$value['user_id'])->update([
                        'year'=> (is_null($value['year']) ? 0 : $value['year']) + $reward,
                        'area_id'=>$reward
                        ]);
                        
                        
                    $user_name = '';    
                    if($i == 2){
                        $user_name = '创客';
                    } else if($i ==3){
                        $user_name = '店长';
                    }  else if($i ==4){
                        $user_name = '经理';
                    }   else if($i ==5){
                        $user_name = '总监';
                    }   else if($i ==6){
                        $user_name = '总裁';
                    }    
                       
                     $content = [
                            "title" => $reward,
                            "content" => '--'.$user_name.'获得平均奖励：'.$reward,
                            "code" => $value['user_id'],
                            "totals" => $reward
                        ];
                        model('contentqr')->insert($content);
                }
            }
            
        }
        
        //升级用户
        $ready_level_user = [];
        
        $ready_level_user['level_1_user'] = model('user')->where('ckjb',1)->select();
        
        $ready_level_user['level_2_user'] = model('user')->where('ckjb',3)->select();
        
        $ready_level_user['level_3_user'] = model('user')->where('ckjb',5)->select();
        
        $ready_level_user['level_4_user'] = model('user')->where('ckjb',7)->select();
        
        $ready_level_user['level_5_user'] = model('user')->where('ckjb',9)->select();
        
        for($i=1;$i<6;$i++){
            foreach ( $ready_level_user['level_'.$i.'_user'] as $key=>$value){
                if($value['user_id']){
                     model('user')->where('user_id',$value['user_id'])->update([
                            'ckjb'=>intval($value['ckjb']) + 1
                       
                        ]);
                }
            }
           
        }
        
    }
    
   

     //更新用户上级的级别信息
    public function updateParentLevel($cur_user,$isorder = null)
    {

        //获取父节点直推用户信息
        $parent = model('user')->find(['user_id'=>$cur_user['remark']]);

        //判断用户状态是否为创客级别
        $isCk = $parent['status']<= 5;

        //判断创客直推人的开机情况
        if($isCk && $isorder == 0)
        {
            //查找兄弟节点
            $users = model('user')->where(['remark'=>$cur_user['remark']])->select();
            //开机人数默认为0
            $phone_on = 0;
            foreach ($users as $son_user)
            {
                //判断有几个开机
                $son_user['isorder'] !=1 ? : $phone_on++;
            }
            $isCk = (--$phone_on < 2);

        }

        //判断要更新的是直推人级别还是安置人级别
        $user_id =  $isCk ? $parent["user_id"] : $cur_user['top_openid'];

        //计算用户级别,返回更新字段
        $update_field= $this->countUserLevel($user_id,$cur_user['user_id'],$isorder);

        model('user')->where('user_id',$user_id)->update($update_field);
    }

    //计算当前用户共有多少未停机的卡数
    public function countLineUsers($user_id)
    {
        $lineUser = 0;
        $underUsers = model('user')->where('top_openid',$user_id)->select();
        if(!empty($underUsers))
        {
            foreach ($underUsers as $underUser)
            {
                $lineUser++;
                $lineUser += $this->countLineUsers($underUser['user_id']);
                if($underUser['isorder']==0)
                {
                    $lineUser--;
                }
            }
        }
        return $lineUser;
    }

    //计算用户级别
    public function countUserLevel($user_id,$child_id=0,$is_order=1)
    {
        $underUsers = model('user')->where('top_openid',$user_id)->select();

        $minNum  = 0;
        if(!empty($underUsers))
        {
            foreach ($underUsers as $key => $underUser)
            {
                $num = $this->countLineUsers($underUser['user_id']) + 1;
                $underUser['isorder'] != 0 ? : $num--;
                //以下为计算开机停机操作时使用
                if($child_id == $underUser['user_id'])
                {
                    $is_order == 0 ? $num-- : $num++;
                }
                $key != 0 ? : $minNum = $num;
                $minNum = $minNum < $num ? $minNum :  $num;
            }
            $minNum = count($underUsers) < 2 ? 0 : $minNum;
        }
        //获取用户升级的参数配置
        $params = model('param')->get(1);
        //默认专员
        $field = [
            'ckjb'=>0,
            'status'=>0
        ];;
        if($minNum >= $params['dz9'] && $minNum >= $params['dz10']){
            //总裁
            $field['ckjb'] = 9;
            $field['status'] = 31;
        }else if($minNum >= $params['dz7'] && $minNum >= $params['dz8']){
            //总监
            $field['ckjb'] = 7;
            $field['status'] = 21;
        }else if($minNum >= $params['dz5'] && $minNum >= $params['dz6'] ){
            //经理
            $field['ckjb'] = 5;
            $field['status'] = 11;
        }else if($minNum >= $params['dz3'] && $minNum >= $params['dz4'] ){
            //店长
            $field['ckjb'] = 3;
            $field['status'] = 6;
        }else if($minNum >= $params['dz1'] && $minNum >= $params['dz2']){
            //创客
            $field['ckjb'] = 1;
            $field['status'] = 1;
        }
        return $field;
    }
}
