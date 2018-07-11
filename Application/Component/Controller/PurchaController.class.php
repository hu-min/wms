<?php
namespace Component\Controller;
class PurchaController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Purcha');
    }
}