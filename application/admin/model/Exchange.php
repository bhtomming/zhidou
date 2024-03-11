<?php

namespace app\admin\model;

use think\Model;

class Exchange extends Model
{

    // 表名
    protected $name = 'duihuan';

    public function getOriginData()
    {
        return $this->origin;
    }

    protected static function init()
    {

    }

    // public function getGenderList()
    // {
    //     return ['1' => __('Male'), '0' => __('Female')];
    // }

    // public function getStatusList()
    // {
    //     return ['normal' => __('Normal'), 'hidden' => __('Hidden')];
    // }


    // public function getPrevtimeTextAttr($value, $data)
    // {
    //     $value = $value ? $value : ($data['prevtime'] ?? "");
    //     return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    // }

    // public function getLogintimeTextAttr($value, $data)
    // {
    //     $value = $value ? $value : ($data['logintime'] ?? "");
    //     return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    // }

    // public function getJointimeTextAttr($value, $data)
    // {
    //     $value = $value ? $value : ($data['jointime'] ?? "");
    //     return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    // }

    // protected function setPrevtimeAttr($value)
    // {
    //     return $value && !is_numeric($value) ? strtotime($value) : $value;
    // }

    // protected function setLogintimeAttr($value)
    // {
    //     return $value && !is_numeric($value) ? strtotime($value) : $value;
    // }

    // protected function setJointimeAttr($value)
    // {
    //     return $value && !is_numeric($value) ? strtotime($value) : $value;
    // }

    // protected function setBirthdayAttr($value)
    // {
    //     return $value ? $value : null;
    // }

    // public function group()
    // {
    //     return $this->belongsTo('UserGroup', 'group_id', 'id', [], 'LEFT')->setEagerlyType(0);
    // }

}
