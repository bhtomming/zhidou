<?php

namespace app\index\controller;
use app\common\controller\User as CommonUser;
use think\Model;

class Task
{

    public function task()
    {

        return 'task';
    }


    //用户升级
    public function updateuserstatus($user_id = 0)
    {


        if (!empty($user_id)) {

            $flag = false;

            $user = model('user')->where('user_id', $user_id)->find();


            if (empty($user) || empty($user_id)) {
                return json(['code' => -1, 'msg' => '获取信息失败']);
            }


            //判断专员是否能升创客,找直推人
            $remark = model('user')->where('user_id',$user['remark'])->find();

            if(empty($remark)){
                return json(['code' => -2, 'msg' => '获取直推人信息失败']);
            }



            if($remark['status'] == 0){
                $remarks = model('user')->where('remark',$user['remark'])->select();
                $remark_count = count($remarks);

                if($remark_count >= 2){

                    model('user')->where('user_id', $user['remark'])->update([
                        'status' => 1001]);

                    $flag = true;
                }
            }

            //判断下线是否够人升级 ，找报单人

            $top_user = model('user')->where('user_id',$user['top_openid'])->find();

            if(empty($top_user)){
                return json(['code' => -3, 'msg' => '获取报单人信息失败']);
            }

            $common_user = new CommonUser();

            $list = $common_user->getlineusers($top_user['user_id']);

            $params = model('param')->get(1);

            $left_count = $right_count = 0;

            if(!empty($list['left'])){

                if($list['left']['isorder']==0){
                    $left_count += count($list['left']['son']);
                }else{
                    $left_count += count($list['left']['son']) + 1;
                }


            }

            if(!empty($list['right'])){

                if($list['right']['isorder']==0){
                    $right_count += count($list['right']['son']);
                }else{
                    $right_count += count($list['right']['son']) + 1;
                }

            }


            if ($left_count > 0 || $right_count > 0) {

                if (($left_count >= $params['dz9'] && $right_count >= $params['dz10']) || ($left_count >= $params['dz10'] && $right_count >= $params['dz9'])) {
                    model('user')->where('user_id', $top_user['user_id'])->where('status','<','31')->update([
                        'status' => 1005]);
                } else if (($left_count >= $params['dz7'] && $right_count >= $params['dz8']) || ($left_count >= $params['dz8'] && $right_count >= $params['dz7'])) {
                    model('user')->where('user_id', $top_user['user_id'])->where('status','<','21')->update([
                        'status' => 1004]);
                } else if (($left_count >= $params['dz5'] && $right_count >= $params['dz6']) || ($left_count >= $params['dz6'] && $right_count >= $params['dz5'])) {
                    model('user')->where('user_id', $top_user['user_id'])->where('status','<','11')->update([
                        'status' => 1003]);
                } else if (($left_count >= $params['dz3'] && $right_count >= $params['dz4']) || ($left_count >= $params['dz4'] && $right_count >= $params['dz3'])) {
                    model('user')->where('user_id', $top_user['user_id'])->where('status','<','6')->update([
                        'status' => 1002]);
                }

                return json(['code' => 1, 'msg' => '用户更新等级成功']);

            } else {

                if($flag){
                    return json(['code' => 1, 'msg' => '用户更新等级成功!']);
                }else{
                    return json(['code' => -2, 'msg' => '用户数未达标']);
                }


            }

        }

    }



    public function updatealluserstatus(){

        $users = model('user')->where('isorder',1)->where('dzjb',1)->select();

        foreach ($users as $value) {

            $user_id = $value['user_id'];

            $user = model('user')->where('user_id', $user_id)->find();


            if (empty($user) || empty($user_id)) {
                continue;
                //return json(['code' => -1, 'msg' => '获取信息失败']);
            }


            //判断专员是否能升创客,找直推人
            $remark = model('user')->where('user_id', $user['remark'])->find();

            if (empty($remark)) {
                continue;
                //return json(['code' => -2, 'msg' => '获取直推人信息失败']);
            }


            if ($remark['status'] == 0) {
                $remarks = model('user')->where('remark', $user['remark'])->select();
                $remark_count = count($remarks);

                if ($remark_count >= 2) {

                    model('user')->where('user_id', $user['remark'])->update([
                        'status' => 1001]);


                }
            }

            //判断下线是否够人升级 ，找报单人

            $top_user = model('user')->where('user_id', $user['top_openid'])->find();

            if (empty($top_user)) {
                continue;
                //return json(['code' => -3, 'msg' => '获取报单人信息失败']);
            }

            $common_user = new CommonUser();

            $list = $common_user->getlineusers($top_user['user_id']);

            $params = model('param')->get(1);

            $left_count = $right_count = 0;

            if (!empty($list['left'])) {

                if ($list['left']['isorder'] == 0) {
                    $left_count += count($list['left']['son']);
                } else {
                    $left_count += count($list['left']['son']) + 1;
                }


            }

            if (!empty($list['right'])) {

                if ($list['right']['isorder'] == 0) {
                    $right_count += count($list['right']['son']);
                } else {
                    $right_count += count($list['right']['son']) + 1;
                }

            }


            if ($left_count > 0 || $right_count > 0) {

                if (($left_count >= $params['dz9'] && $right_count >= $params['dz10']) || ($left_count >= $params['dz10'] && $right_count >= $params['dz9'])) {
                    model('user')->where('user_id', $top_user['user_id'])->where('status','<','31')->update([
                        'status' => 1005]);
                } else if (($left_count >= $params['dz7'] && $right_count >= $params['dz8']) || ($left_count >= $params['dz8'] && $right_count >= $params['dz7'])) {
                    model('user')->where('user_id', $top_user['user_id'])->where('status','<','21')->update([
                        'status' => 1004]);
                } else if (($left_count >= $params['dz5'] && $right_count >= $params['dz6']) || ($left_count >= $params['dz6'] && $right_count >= $params['dz5'])) {
                    model('user')->where('user_id', $top_user['user_id'])->where('status','<','11')->update([
                        'status' => 1003]);
                } else if (($left_count >= $params['dz3'] && $right_count >= $params['dz4']) || ($left_count >= $params['dz4'] && $right_count >= $params['dz3'])) {
                    model('user')->where('user_id', $top_user['user_id'])->where('status','<','6')->update([
                        'status' => 1002]);
                }

                //return json(['code' => 1, 'msg' => '用户更新等级成功']);

            }



        }

        return json(['code' => 1, 'msg' => '执行所有用户等级更新成功']);

    }



    public function updatecontentqr(){
        $result = model('contentqr')->where('id','>',9730)->select();

        foreach ($result as $value){
            if($value['code']){
                $user = model('user')->where('user_id',$value['code'])->find();
                $total = $user['year'] - $user['sex'];

                model('contentqr')->where('id',$value['id'])->update([
                    'totals'=>$total
                ]);

            }
        }

        return json(['code' => 1, 'msg' => '更新成功']);

    }


    public function test(){
        $user_id = 13;

        $result1 = model('contentqr')->where('code',$user_id)->where('title','>',0)->select();

        $result2 = model('contentqr')->where('code',$user_id)->where('title','<',0)->select();


        $count1 = 0;
        $count2 = 0;

        foreach ($result1 as $v){
            if($v['title']){
                $count1 += $v['title'];
            }
        }


        foreach ($result2 as $v){
            if($v['title']){
                $count2 += $v['title'];
            }
        }

        return json(['count1'=>$count1,'count2'=>$count2]);

    }

}