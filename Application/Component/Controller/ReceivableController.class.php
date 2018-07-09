<?php
namespace Component\Controller;
class ReceivableController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Receivable');
    }
}