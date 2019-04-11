<?php
namespace Component\Controller;
class DebitController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Debit');
    }
    /**
     * 项目借支回退
     */
    public function pdebit_rollback($id){
        $debitInfo = $this->getOne(['id'=>$id]);
        $debitSubCom = getComponent('DebitSub');
        $PCSCom = getComponent('ProjectCostSub');
        //获取到所有借支子项对应的成本id数据
        $debitSubList = $debitSubCom->getList(['where'=>['parent_id'=>$id]])['list'];
        foreach ($debitSubList as  $debitSubInfo) {
            $test = $PCSCom->getOne(['id'=>$debitSubInfo['cost_id']]);
            $PCSCom->where(['id'=>$debitSubInfo['cost_id']])->setDec('costed',$debitSubInfo['debit_money']); 
        }
        $this->where(['id'=>$id])->save(['debit_money'=>'0']);
        return $debitSubCom->where(['parent_id'=>$id])->save(['debit_money'=>'0','update_time'=>time()]);
    }
}