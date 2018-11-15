<?php
namespace Component\Controller;
//资金流水记录组件
class FlCapLogController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/FloatCapitalLog');
    }
}