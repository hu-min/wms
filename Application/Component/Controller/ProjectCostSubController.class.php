<?php
namespace Component\Controller;
class ProjectCostSubController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/ProjectCostSub');
    }
     /** 
     * @Author: vition 
     * @Date: 2019-01-04 22:50:42 
     * @Desc: 删除报价和成本子数据 
     * @
     */    
    function subCostDel($dels=[],$update=false){
        $offerCom = A('Component/ProjectOffer');
        $costCom =  A('Component/ProjectCost');
        $offerCom -> startTrans();
        $costCom -> startTrans();
        $this -> startTrans();

        $delResult = $this ->getList(['where'=>['id'=>["IN",$dels]]])['list'];
        $all_total = array_sum(array_column($delResult,'total'));
        $all_cost = array_sum(array_column($delResult,'cost_total'));
        $parent_oid = array_unique(array_column($delResult,'parent_oid'));
        $parent_cid = array_unique(array_column($delResult,'parent_cid'));

        $update_time = time();
        if($parent_oid[0] && $update){
            $offerResult = $offerCom ->getOne(['where'=>['id'=>$parent_oid[0]],"one"=>true]);
            $total = $offerResult['total'] - $all_total;
            $oUpData = compact('update_time','total');
            $oResult = $offerCom->update(['where'=>['id'=>$parent_oid[0]],'data'=>$oUpData]);
            // $this->log($parent_oid);
        }
        if($parent_cid[0] && $update){
            $costResult = $offerCom ->getOne(['where'=>['id'=>$parent_cid[0]],"one"=>true]);
            $total = 0;
            if($offerResult){
                $total = $offerResult['total'] - $all_total;
            }
            $cost_total = $costResult['cost_total'] - $all_cost;
            $profit = $total - $cost_total;
            $profit_ratio = $profit > 0 ? round($profit/$total*100,2) : 0;
            $cUpData = compact('update_time','cost_total','profit','profit_ratio');
            $cResult = $costCom->update(['where'=>['id'=>$parent_cid[0]],'data'=>$cUpData]);
            // $this->log($parent_cid);
        }
        $delResult = $this ->del(['id'=>["IN",$dels]]);
        if( (($parent_oid[0] && $oResult && $parent_cid[0] && $cResult) || ($parent_oid[0] && $oResult) || ($parent_cid[0] && $cResult) && $delResult && $update) || ($update == false && $delResult) ){

            $offerCom -> commit();
            $costCom -> commit();
            $this -> commit();
            return true;
        }else{
            $offerCom -> rollback();
            $costCom -> rollback();
            $this -> rollback();
            return false;
        }
    }
}