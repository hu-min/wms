<?php
namespace Component\Controller;
class ResetApplyController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/ResetApply');
    }
}