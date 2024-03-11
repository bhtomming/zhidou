<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\library\Auth;

/**
 * 兑换管理
 *
 * @icon fa fa-user
 */
class Exchange extends Backend
{

    // protected $relationSearch = true;
    // protected $searchFields = 'id,username,nickname';

    /**
     * @var \app\admin\model\Exchange
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Exchange');
    }

    /**
     * 开通积分兑换
     */
    public function dhlist()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }


            $map=[];
            if($this->request->param('search')){
                $keyword = $this->request->param('search');
                $map['cardNo|name'] = ['like','%'.$keyword.'%'];
            }



            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                ->where($map)
                ->where('status','>=','0')
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as $k => $v) {
                $v->hidden(['password', 'salt']);
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    public function dhfh($did = null){
        if (!$this->request->isPost()) {
            $row=$this->model->get($did);
            $this->view->assign(['row'=>$row]);
            return $this->view->fetch();
        }else{
            $params = $this->request->post('row/a');
            try{
                $this->model->where('did',$params['did'])->update(array('status'=>1,'code'=>$params['code'],'remark'=>$params['remark']));
            }catch (\Exception $e) {
                $this->error("修改失败");
            }
            $this->success("操作成功",url('/exchange/dhlist'));
        }
    }


}
