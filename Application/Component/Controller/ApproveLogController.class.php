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
    function createApp($table,$id,$userId,$remark){
        $parameter=[
            "table_name" => $table,
            "table_id" => $id,
            "add_time" => time(),
            "user_id" => $userId,
            "status" => 0,
            "remark" => $remark,
        ];
        return $this->insert($parameter);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-08-23 23:34:29 
     * @Desc: 修改数据的时候需要判断 
     */    
    function updateStatus($table,$id,$parentId=null){
        $delRes = $this->del(["table_name"=>$table,"table_id"=>$id,"status"=>3]);
        //只有删除了驳回和存在父id才需要执行下面的代码
        if(isset($delRes->errCode) && $delRes->errCode==0 && $parentId){
            $db = M($table,NULL);
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
                        $parentDb->where([$parentDb->getPk()=>$parentId])->save(["status"=>0]);
                    }
                }
            }
        }
    }
}