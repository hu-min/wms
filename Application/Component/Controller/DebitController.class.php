<?php
namespace Component\Controller;
class DebitController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Debit');
    }
}