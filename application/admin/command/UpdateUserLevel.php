<?php

namespace app\admin\command;


use app\common\model\User;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\Exception;
use think\Db;
use app\common\controller\User as UserManager;

class UpdateUserLevel extends Command
{

  
    protected function configure()
    {
        $this->setName('UpdateUserLevel')->setDescription('Update User Level');
    }

    protected function execute(Input $input, Output $output)
    {
        $users = Db::name('user')->where('user_id',">",1)->select();

        $counter = 0;
        foreach ($users as $user)
        {
            $output->info('开始用户'.$user['user_id'].'的上级用户等级更新');
            $this->updateParentLevel($user,$user['isorder']);
            $output->info('完成用户'.$user['user_id'].'的上级用户等级更新');
            $counter++;
        }
        $output->info('共完成'.$counter.'位用户更新');
        $output->info('完成所有用户等级更新');
    }

    //更新用户上级的级别信息
    public function updateParentLevel($cur_user,$isorder = null)
    {

        //获取父节点直推用户信息
        $parent =  Db::name('user')->where('user_id',"=",$cur_user['remark'])->select()[0];

        //判断用户状态是否为创客级别
        $isCk = $parent['status']<= 5;

        //判断创客直推人的开机情况
        if($isCk && $isorder == 0)
        {
            //查找兄弟节点
            $users = Db::name('user')->where('remark',"=",$cur_user['remark'])->select();
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

        Db::name('user')->where('user_id',"=",$user_id)->update($update_field);
    }

    //计算当前用户共有多少未停机的卡数
    public function countLineUsers($user_id)
    {
        $lineUser = 0;
        $underUsers = Db::name('user')->where('top_openid',"=",$user_id)->select();
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
        $underUsers = Db::name('user')->where('top_openid',"=",$user_id)->select();

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
        $params = Db::name('param')->where('paramid','=',1)->select()[0];
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
