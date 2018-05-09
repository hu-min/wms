<?php
namespace Component\Controller;
// use Common\Controller\BaseController;

class CustomerController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Customer');
    }
}