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
                //添加时审批流数据
                $examines = A("Component/Process")->getExamine(I("vtabId"),0);
                $datas['process_id'] = $examines["process_id"];
                $datas['examine'] = $examines["examine"];
                $datas['process_level'] = $examines["process_level"];
                $datas['status'] = $examines["status"];
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
    /** 
     * @Author: vition 
     * @Date: 2018-12-22 08:55:48 
     * @Desc: 双向绑定 
     */    
    function two_way_bind($param=[]){
        extract($param);
        $moneyAccCom = A("Component/MoneyAccount");
        $ApprLogCom = A("Component/ApproveLog");
        $account = $moneyAccCom->getOne(["id" => $id]);

        if($type == "btc"){
            $inType = 2;
            $outType = 1;
            $inFloat = 1;
            $outFloat = 2;
            $outAccount = "bank_stock";
            $inAccount = "cash_stock";
            $inner_detail = "内部提取备用金（银行库存转现金库存）";
        }else{
            $inType = 1;
            $outType = 2;
            $inFloat = 2;
            $outFloat = 1;
            $outAccount = "cash_stock";
            $inAccount = "bank_stock";
            $inner_detail = "现金库转银行库存存";
        }
        
        if($account[$outAccount] < $get_cash){
            return ['errCode'=>100,'error'=>"库存金额不足{$get_cash}，无法提取"];
        }
        $bindResult = $this->getOne(['where'=>['bind_id'=>["GT",0]],"fields"=>"bind_id","groupBy"=>"bind_id","orderStr"=>"bind_id DESC","one"=>true]);
        
        if(!$bindResult){
            $bind_id = 1;
        }else{
            $bind_id = $bindResult['bind_id'] + 1;
        }
        
        $datas = [
            'account_id' => $id,
            'happen_time' => time(),
            'money' => $get_cash,
            'user_id' => session('userId'),
            'add_time' => time(),
            'object' => '内部操作',
            'bind_id' => $bind_id,
            'inner_detail' => $inner_detail,
        ];
        $nodeId = A('Component/Node')->tableNodeId("v_float_capital_log");
        $examines = A("Component/Process")->getExamine($nodeId,0);

        $datas['process_id'] = $examines["process_id"];
        $datas['examine'] = $examines["examine"];
        $datas['process_level'] = $examines["process_level"];
        $datas['status'] = $examines["status"];
        $data['examine'] = $examines["examine"];
        $outParam = [
            'log_type' => $outType,
            'float_type' => $outFloat,
        ];
        $inParam = [
            'log_type' => $inType,
            'float_type' => $inFloat,
        ];
        $moneyAccCom -> startTrans();
        $this -> startTrans();
        $isUpdate = false;
        if($examines["place"] == 1 && count(explode(",",$datas['examine'])) == 1 && explode(",",$datas['examine'])[0] == session("roleId") ){
            $isUpdate = true;
            $outParam['balance'] = $account[$outAccount] - $get_cash;
            $inParam['balance'] = $account[$inAccount] + $get_cash;

            $accountParam = [
                'where' => ["id" => $id],
                'data' => [
                    $outAccount => $outParam['balance'],
                    $inAccount => $inParam['balance'],
                    'update_time' => time(),
                ]
            ];
            $updateResult = $moneyAccCom -> update($accountParam);
        }
        $ids = [];
        $inResult = $this->insert(array_merge($datas,$inParam));
        array_push($ids,$inResult->data);
        $outResult = $this->insert(array_merge($datas,$outParam));
        array_push($ids,$outResult->data);
        if($inResult && $outResult && ((isset($updateResult) && $updateResult) || !isset($updateResult))){
            $moneyAccCom -> commit();
            $this -> commit();
            return ['errCode'=>0,'error'=>getError(0),'isUpdate'=>$isUpdate,'data'=>$data,"ids"=>$ids];
        }else{
            $moneyAccCom -> rollback();
            $this -> rollback();
        }
        return ['errCode'=>100,'error'=>getError(100),'isUpdate'=>$isUpdate];
    }
}