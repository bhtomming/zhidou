<?php

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\Exception;
use think\Model;
use think\Db;

class Averagereward extends Command
{

  
    protected function configure()
    {
        $this->setName('Averagereward')->setDescription('Averagereward');
    }

    protected function execute(Input $input, Output $output)
    {
        
        
        $params = Db::name('param')->where('paramid',1)->find();
        

        //当天新增用户
        $today_users = Db::name('user')->where('qrcode','like',date('Y-m-d').'%')->select();
        //$today_users = Db::name('user')->where('qrcode','like',date('Y-m').'%')->select();


        $level_users = [];
        
        
        $level_users['level_2_user'] = Db::name('user')->where('status','>=',1)->where('status','<=',5)->where('isorder',1)->select();
        
        $level_users['level_3_user'] = Db::name('user')->where('status','>=',6)->where('status','<=',10)->where('isorder',1)->select();
        
        $level_users['level_4_user'] = Db::name('user')->where('status','>=',11)->where('status','<=',20)->where('isorder',1)->select();
        
        $level_users['level_5_user'] = Db::name('user')->where('status','>=',21)->where('status','<=',30)->where('isorder',1)->select();
        
        $level_users['level_6_user'] = Db::name('user')->where('status','>=',31)->where('status','<=',40)->where('isorder',1)->select();
        
        //将所有到等级的用户增加平均奖励
        for($i =2;$i<7;$i++){
            
            $reward = count($level_users['level_'.$i.'_user']) ==0  ? 0 : count($today_users) * $params['yyt'.$i] / count($level_users['level_'.$i.'_user']);

            foreach ($level_users['level_'.$i.'_user']  as $key=>$value){
                
                if($value['user_id']){

                    $reward = round($reward);

                    if($reward > 0){

                        $total = (is_null($value['year']) ? 0 : ($value['year']) + $reward);

                        Db::name('user')->where('user_id',$value['user_id'])->update([
                            'year'=> $total,
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
                             "content" => date('Y-m-d H:i:s').'--新增用户数:'.count($today_users).','.$user_name.'获得平均奖励：'.$reward,
                             "code" => $value['user_id'],
                             "totals" => $total - $value['sex'] //$reward
                         ];

                         Db::name('contentqr')->insert($content);
                     }


                }
            }
            
        }
        
        //升级用户
        $ready_level_user = [];
        
        $ready_level_user['level_1_user'] = Db::name('user')->where('status',1001)->where('isorder',1)->select();
        
        $ready_level_user['level_2_user'] = Db::name('user')->where('status',1002)->where('isorder',1)->select();
        
        $ready_level_user['level_3_user'] = Db::name('user')->where('status',1003)->where('isorder',1)->select();
        
        $ready_level_user['level_4_user'] = Db::name('user')->where('status',1004)->where('isorder',1)->select();
        
        $ready_level_user['level_5_user'] = Db::name('user')->where('status',1005)->where('isorder',1)->select();
        
        for($i=1;$i<6;$i++){

            foreach ( $ready_level_user['level_'.$i.'_user'] as $key=>$value){
                if($value['user_id']){

                    $status = 0;

                    if($value['status'] == 1001){
                        $status = 1;
                    }else if($value['status'] == 1002){
                        $status = 6;
                    }else if($value['status'] == 1003){
                        $status = 11;
                    }else if($value['status'] == 1004){
                        $status = 21;
                    }else if($value['status'] == 1005){
                        $status = 31;
                    }


                    Db::name('user')->where('user_id',$value['user_id'])->update([
                            'status'=>intval($status)
                    ]);
                }
            }
           
        }
        

        $output->info('计算平均积分完成');
    }

 
}
