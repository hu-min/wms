<?php
namespace Component\Controller;
class ProcessController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Process');
    }
}