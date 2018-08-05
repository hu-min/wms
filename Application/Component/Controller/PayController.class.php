<?php
namespace Component\Controller;
class PayController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Pay');
    }
}