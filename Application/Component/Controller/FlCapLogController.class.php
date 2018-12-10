<?php
namespace Component\Controller;
//资金流水记录组件
class FlCapLogController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/FloatCapitalLog');
    }
    /** 
     * @Author: vition 
     * @Date: 2018-12-10 23:07:16 
     * @Desc: 计算流水 
     */    
    function computeFloat($id,$reverse=false){
        $moneyAccCom = A("Component/MoneyAccount");

        // $this->startTrans();
        $param = [
            'where' => ['id' => $id],
            'one' => true,
        ];
        $info = $this->getOne($param);
        if($info['status'] == 1){
            if($info['log_type']==1){
                $key = 'bank_stock';
            }elseif($info['log_type']==2){
                $key = 'cash_stock';
            }elseif($info['log_type']==3){
                $key = 'strongbox';
            }
    
            $stockResult = $moneyAccCom ->getOne(['where'=>['id'=>$info['account_id']],'fields'=>$key]);
            if($reverse){
                $info['float_type'] = $info['float_type'] == 1 ? 2 : 1;
            }
            if($info['float_type'] == 1){
                $info['balance'] = $stockResult['list'][$key] + $info['money'];
            }elseif($info['float_type'] == 2){
                $info['balance'] = $stockResult['list'][$key] - $info['money'];
            }
            $info['status'] = 1;
            unset($info['id']);
            $updateParam = [
                'where' => ['id' => $id],
                'data' => $info,
            ];
            $updateResult = $this->update($updateParam);
            if($updateResult){
                if($info['float_type'] == 1){
                    $updateResult = $moneyAccCom ->M()->where(['id'=>$info['account_id']])->setInc($key,$info['money']); 
                }elseif($info['float_type'] == 2){
                    $updateResult =$moneyAccCom ->M()->where(['id'=>$info['account_id']])->setDec($key,$info['money']); 
                }
            }
        }
        
    }
    /** 
     * @Author: vition 
     * @Date: 2018-12-10 23:07:52 
     * @Desc: 管理流水添加和修改 
     */    
    function manageFlCapLogInfo($param=[]){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");
        $isInset = $param['isInset'] ? true : false;

        if(isset($datas['happen_time'])){
            $datas['happen_time']=strtotime($datas['happen_time']);
        }
        if($reqType=="flo_cap_logAdd"){
            $datas['status']=1;
            $datas['add_time'] = time();
            $datas['user_id'] = session("userId");
            if(!isset($datas['happen_time'])){
                $datas['happen_time']=time();
            }
            unset($datas['id']);
            if(!$isInset){
                $examines = A("Component/Process")->getExamine(I("vtabId"),0);
                $datas['examine'] = $examines['examine'];
                $datas['process_id'] = $examines['process_id'];
                $roleId = session("roleId");
                $rolePlace = $examines['place'];
                $datas['status'] = 0;
                if($rolePlace!==false){
                    
                    if($examines['place'] == 0 &&  $roleId == $examines['examine']){
                        $datas['status'] = 1;
                    }else{
                        $datas['process_level']=$rolePlace+2;
                        if(count(explode(",",$examines['examine'])) <= ($rolePlace+1)){
                            $datas['status'] = 1;
                        }else{
                            $datas['status'] = 2;
                        }
                    }
                    
                }else{
                    $datas['process_level'] = $examines["place"] > 0 ? $examines["place"] : 1;
                }
            }
            return $datas;
        }else if($reqType=="flo_cap_logEdit"){
            $where=["id"=>$datas['id']];
            $data=[];
            foreach (['account_id','log_type','project_id','happen_time','subject','inner_detail','bank_detail','object','float_type','remark','status','proof'] as $key) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    /** 
     * @Author: vition 
     * @Date: 2018-12-10 23:09:54 
     * @Desc: 添加流水记录 
     */    
    function flo_cap_logAdd($data,$isInset=false){
        $Info = $this->manageFlCapLogInfo(['data'=>$data,'reqType'=>'flo_cap_logAdd','isInset'=>$isInset]);
        $moneyAccCom = A("Component/MoneyAccount");
        $ApprLogCom = A("Component/ApproveLog");
        $this->startTrans();
        if($Info){
            if($Info['status'] == 1){
                if($Info['log_type']==1){
                    $key = 'bank_stock';
                }elseif($Info['log_type']==2){
                    $key = 'cash_stock';
                }elseif($Info['log_type']==3){
                    $key = 'strongbox';
                }
    
                $stockResult = $moneyAccCom ->getOne(['where'=>['id'=>$Info['account_id']],'fields'=>$key]);
                if($Info['float_type'] == 1){
                    $Info['balance'] = $stockResult['list'][$key] + $Info['money'];
                }elseif($Info['float_type'] == 2){
                    $Info['balance'] = $stockResult['list'][$key] - $Info['money'];
                    if($Info['balance'] < 0){
                        return ['errCode'=>100,'error'=>'账户金额不足。仅剩下：'.$stockResult['list'][$key]];
                    }
                }
                $insertResult = $this->insert($Info);
                if($insertResult){
                    if($Info['float_type'] == 1){
                        $updateResult = $moneyAccCom ->M()->where(['id'=>$Info['account_id']])->setInc($key,$Info['money']); 
                    }elseif($Info['float_type'] == 2){
                        $updateResult =$moneyAccCom ->M()->where(['id'=>$Info['account_id']])->setDec($key,$Info['money']); 
                    }
                    if($updateResult){
                        $this->commit();
                        return ['errCode'=>$insertResult->errCode,'error'=>getError($insertResult->errCode),'data'=>$Info,'id'=>$insertResult->data];
                    }
                }
            }else{
                $insertResult = $this->insert($Info);
                if($insertResult){
                    $this->commit();
                    return ['errCode'=>$insertResult->errCode,'error'=>getError($insertResult->errCode),'data'=>$Info,'id'=>$insertResult->data];
                }
            }
            
        }
        $this->rollback();
        return ['errCode'=>100,'error'=>getError(100)];
    }
}