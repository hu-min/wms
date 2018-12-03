<?php
namespace Component\Controller;
//资金流水记录组件
class FlCapLogController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/FloatCapitalLog');
    }
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
}