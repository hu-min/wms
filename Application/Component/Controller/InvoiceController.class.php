<?php
namespace Component\Controller;
class InvoiceController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Invoice');
    }
}