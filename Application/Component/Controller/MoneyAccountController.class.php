<?php
namespace Component\Controller;
//资金账户组件
class MoneyAccountController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/MoneyAccount');
    }
}