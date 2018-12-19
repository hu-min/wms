<?php
namespace Component\Controller;
class ApproveLogController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/ApproveLog');
    }
    /** 
     * @Author: vition 
     * @Date: 2018-08-23 23:34:14 
     * @Desc: 提交申请的时候加入 
     */    
    function createApp($table,$id,$userId,$remark,$effect=1){
        $parameter=[
            "table_name" => $table,
            "table_id" => $id,
            "add_time" => time(),
            "user_id" => $userId,
            "status" => 0,
            "remark" => $remark,
            "effect" => $effect,
        ];
        $param = $parameter;
        unset($param['add_time']);
        unset($param['remark']);
        unset($param['status']);
        $hasApp = $this->getOne(['where'=>$param]);
        if(!$hasApp){
            return $this->insert($parameter);
        }
        return false;
        
    }
    /** 
     * @Author: vition 
     * @Date: 2018-08-23 23:34:29 
     * @Desc: 修改数据的时候需要判断 
     */    
    function updateStatus($table,$id,$parentId=null){
        // $delRes = $this->del();
        $updateRes = $this->update(["where"=>["table_name"=>$table,"table_id"=>$id,"status"=>["IN",[1,3]]],"data"=>["effect"=>0]]);
        //只有删除了驳回和存在父id才需要执行下面的代码
        if(isset($updateRes->errCode) && $updateRes->errCode==0){                          
            $db = M($table,NULL);
            if($parentId){
                $where = ["parent_id" => $parentId];
   
                //判断当前的表中，指定的id是否存在着驳回的数据，在之前驳回的状态已改成0的前提，如果不存在驳回状态，那么更改父表的状态。
                //获取所有状态
                $rebutRes = $db ->field("status")->where($where)->select();
                if($rebutRes){
                    $allStatus = array_column($rebutRes,"status");
                    $parentDb = M("v_expense",NULL);
                    // 判断不存在驳回，同时属于审核中，那么修改状态为审核中，否则修改为提交中
                    if(!in_array(3,$allStatus)){
                        if(in_array(2,$allStatus)){
                            $parentDb->where([$parentDb->getPk()=>$parentId])->save(["status"=>2]);
                        }else{
                            $parentDb->where([$parentDb->getPk()=>$parentId])->save(["status"=>0,"process_level"=>1]);
                        }
                    }
                }
            }else{
                $db->where([$db->getPk()=>$id])->save(["status"=>0,"process_level"=>1]);
            }   
        }
    }
}