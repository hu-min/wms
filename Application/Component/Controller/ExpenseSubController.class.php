<?php
namespace Component\Controller;
class ExpenseSubController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/ExpenseSub');
    }
}