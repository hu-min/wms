<?php
namespace Component\Controller;
class DebitSubController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/DebitSub');
    }
}