<?php
namespace Component\Controller;
class ApproveLogController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/ApproveLog');
    }
}