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
        $param = [
            'where' => ["table_name"=>$table,"table_id"=>$id,"status"=>3],
        ];
        $hasRefute = $this ->getOne($param);

        $db = M($table,NULL);

        $itemResult = $db ->where([$db->getPk()=>$id])->field("user_id")->find();

        //如果申请中存在反驳则需要修改了且修改者是提交者才会重新修改数据
        if($hasRefute && $itemResult['user_id'] == session('userId')){
             
            $this->startTrans();
            $updateRes = $this->update(["where"=>["table_name"=>$table,"table_id"=>$id,"status"=>["IN",[1,3]]],"data"=>["effect"=>0]]);
            if(isset($updateRes->errCode) && $updateRes->errCode == 0){
                
                $db->startTrans();
                $db -> where([$db->getPk()=>$id])->save(["status"=>2,"process_level"=>1]);
                $this->commit();
                $db->commit();  
            }else{
                $this->rollback();
            }
        }   
    }
}