<?php
namespace Component\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-06-05 23:21:08 
 * @Desc: 固定费用支出组件 
 */
class FixldExpenseController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/FixldExpense');
    }
}