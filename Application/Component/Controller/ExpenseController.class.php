<?php
namespace Component\Controller;
class ExpenseController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Expense');
    }
}