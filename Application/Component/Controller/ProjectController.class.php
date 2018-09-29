<?php
namespace Component\Controller;
class ProjectController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Project');
    }

    function count($where){
        return $this->selfDB->field("SUM(amount) totalAmount,SUM(cost) totalCost,SUM(profit) totalProfit")->where($where)->find();
    }
    function getCostBudget($project_id){
       $result = $this->selfDB->field("cost_budget")->where(['projectId'=>$project_id])->find();
       if($result){
           return $result['cost_budget'];
       }
       return false;
    }
    function getCosts($project_id,$dbCom="",$ids=[]){
        $allCost = 0;
        $debit = 0;
        $expense = 0;
        $purcha = A('Component/Purcha');
        $debitCom = A('Component/Debit');
        $expenseCom = A('Component/Expense');
        $purchaParam = [
            'where' => ['project_id'=>$project_id],
            'fields' => 'SUM(contract_amount) contract_amount,status',
            'groupBy' => 'project_id,status',
            'isCount' => false,
        ];
        if(strtolower($dbCom)=="purcha" && !empty($ids)){
            $purchaParam['where']['id']=["NOT IN",$ids];
        }
        $purchaRes = $purcha->getList($purchaParam);
        if($purchaRes){
            $allCost += array_sum(array_column($purchaRes['list'],'contract_amount'));
            // foreach ($purchaRes['list'] as $cost) {
            //     if($cost['status'] == 1){
            //         $debit += $cost['contract_amount'];
            //     }else{
            //         $expense += $cost['contract_amount'];
            //     }
            // }
        }
        $debitParam = [
            'where' => ['project_id'=>$project_id],
            'fields' => 'SUM(debit_money) debit_money,status',
            'groupBy' => 'project_id,status',
            'isCount' => false,
        ];

        if(strtolower($dbCom)=="debit" && !empty($ids)){
            $debitParam['where']['id']=["NOT IN",$ids];
        }
        $debitRes = $debitCom->getList($debitParam);
        if($debitRes){
            $debit += array_sum(array_column($debitRes['list'],'debit_money'));
            // foreach ($debitRes['list'] as $cost) {
            //     if($cost['status'] == 1){
            //         $debit += $cost['debit_money'];
            //     }else{
            //         $expense += $cost['debit_money'];
            //     }
            // }
        }
        $expenseParam = [
            'where' => ['project_id'=>$project_id],
            'fields' => 'SUM(expense_money) expense_money,state status',
            'groupBy' => 'project_id,status',
            'joins' =>[
                'LEFT JOIN (SELECT parent_id,SUM(money) expense_money,status state FROM v_expense_sub GROUP BY parent_id) es ON es.parent_id = id'
            ],
            'isCount' => false,
        ];
        if(strtolower($dbCom)=="expense" && !empty($ids)){
            $expenseParam['where']['id']=["NOT IN",$ids];
        }
        $expenseRes = $expenseCom->getList($expenseParam);
        // echo $expense->M()->_sql();
        // print_r($expenseRes['list']);
        if($expenseRes){
            $expense += array_sum(array_column($expenseRes['list'],'expense_money'));
            // foreach ($expenseRes['list'] as $cost) {
            //     if($cost['status'] == 1){
            //         $debit += $cost['expense_money'];
            //     }else{
            //         $expense += $cost['expense_money'];
            //     }
            // }
        }
        $contract = $allCost;
        if($debit>$expense){
            $allCost += $debit;
        }else{
            $allCost += $expense;
        }
        $costs = [
            'contract' => $contract,
            'allCost' => $allCost,
            'debit' => $debit,
            'expense' => $expense,
        ];
        return $costs;
        // print_r($costs);exit;
        // exit;
        // SELECT project_id pproject_id,SUM(contract_amount) contract_amount,status FROM v_purcha  WHERE project_id = 1 GROUP BY pproject_id,status
        // SELECT project_id dproject_id,SUM(debit_money) debit_money,status FROM v_debit WHERE project_id = 3 GROUP BY dproject_id,status
        // SELECT SUM(expense_money) expense_money,state status FROM v_expense LEFT JOIN (SELECT parent_id,SUM(money) expense_money,status state FROM v_expense_sub GROUP BY parent_id,`status`) es ON es.parent_id = id WHERE project_id = 3 GROUP BY project_id,state;
    }
    function checkCost($project_id,$current,$dbCom="",$ids=[]){
        $costBudget = $this->getCostBudget($project_id);
        $allCost = $this->getCosts($project_id,$dbCom,$ids);
        // print_r($gettype);
        //得判断是添加还是修改，条件不同
        // print_r($allCost);
        // $array_column = array_sum(array_column($datas,'contract_amount'));
        if($costBudget>0 && ($current+$allCost['allCost']) > $costBudget){
            //<p>其中已批准成本：【'.$allCost['active'].'】</p><p>其中其他状态成本：【'.$allCost['waiting'].'】</p>
            $html='<p>成本预算超支:</p><p>该项目立项成本预算【'.$costBudget.'】</p><p>录入后的成本将是：【<span style="color:red;">'.($current+$allCost['allCost']).'</span>】超过成本预算！</p><p>请联系管理员修改成本预算</p>';
            $this->ajaxReturn(['errCode'=>77,'error'=>$html]);
        }
    }
}