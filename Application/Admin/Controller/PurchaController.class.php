<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-05-06 09:45:33 
 * @Desc: 采购管理 
 */
class PurchaController extends BaseController{

    public function _initialize() {
        $this->project=A("Project");
        $this->supplier=A("Supplier");
        parent::_initialize();
        $this->basicCom=getComponent('Basic');
        $this->projectCom=getComponent('Project');
        $this->fixExpenCom=getComponent('FixldExpense');
        $this->receivableCom=getComponent('Receivable');
        $this->wouldpayCom=getComponent('Wouldpay');
        $this->purchaCom=getComponent('Purcha');
        $this->payCom=getComponent('Pay');
        $this->InvoiceCom=getComponent('Invoice');
        $this->pCostSubCom=getComponent('ProjectCostSub');
        $this->pCostCom=getComponent('ProjectCost');
        $this->payGradeType = ["1"=>"A级[高]","2"=>"B级[次]","3"=>"C级[中]","4"=>"D级[低]"];
        $this->invoiceType = ["0"=>"无","1"=>"收据","2"=>"增值税普通","3"=>"增值税专用"];
        $this->payType = ['1'=>'公对公','2'=>'现金付款','3'=>'支票付款'];
        
    }
    /** 
     * @Author: vition 
     * @Date: 2018-07-17 23:12:16 
     * @Desc: 成本录入控制入口 
     */    
    function costInsert(){
        $reqType=I('reqType');
        $this->assign("controlName","cost_insert");
        $this->assign('tableName',$this->purchaCom->tableName());//删除数据的时候需要
        $this->assign('projectArr',$this->Com ->get_option("project_id"));
        $this->assign('supplierArr',$this->supplier->getSupType());
        // $this->assign('companyArr',$this->supplier->getSupplier());
        // $this->assign('moduleArr',$this->supplier->getModule());
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function getProjectOne($return=false){
        $id = I("id");
        $type = I("type");
        if($type){
            $key = I("key");
            $this->ajaxReturn(["data"=>$this->Com ->get_option($type,$key)]);
        }
        $parameter=[
            "where"=>["projectId"=>$id],
            "fields" => "projectId,name project_name,FROM_UNIXTIME(project_time,'%Y-%m-%d') project_date,date_sub(FROM_UNIXTIME(project_time,'%Y-%m-%d'),interval - days day) end_date,code,leader,leader_name,business,business_name,cost_budget,amount",
            "joins"=>[
                "LEFT JOIN (SELECT userId user_id,userName leader_name FROM v_user) lu ON lu.user_id = leader",
                "LEFT JOIN (SELECT userId user_id,userName business_name FROM v_user) bu ON bu.user_id = business",
            ]
        ];
        $resultData = $this->project->projectCom->getOne($parameter)["list"];
        $costs = $this->projectCom->getCosts($id);
        $resultData['current_cost'] = $costs['allCost'];
        $resultData['rate'] = round((($resultData['amount']-$costs['allCost'])/$resultData['amount'])*100,2);
        $resultData['expect_rate'] = round((($resultData['amount']-$resultData['cost_budget'])/$resultData['amount'])*100,2);
        $resultData['debit_expense'] = $costs['allCost'] - $costs['contract'];
        if($return){
            return $resultData;
        }
        $this->ajaxReturn(["data"=>$resultData]);
    }
    function getSuprtype(){
        $key = I("key",'');
        $resultData =$this->supplier->getSupType($key);
        $this->ajaxReturn(["data"=>$resultData]);
    }
    function getSuprComList(){
        $key = I("key",'');
        $type = I("pid",0);
        $gpid = I("gpid",0);
        $resultData = $this->supplier->getSupplier($key,$type,$gpid);
        $this->ajaxReturn(["data"=>$resultData]);
    }
    function getSuprContList(){
        $key = I("key",'');
        $companyId = I("pid",0);
        $resultData = $this->supplier->getSuprCont($key,$companyId);
        $this->ajaxReturn(["data"=>$resultData]);
    }
    function getModuleList(){
        $pid = I("pid",'');
        $key = I("key",'');
        $resultData = $this->supplier->getModule($pid,$key);
        $this->ajaxReturn(["data"=>$resultData]);
    }
    function getSuprLiOne(){
        $rows = I("rows");
        $this->assign('projectArr',$this->Com ->get_option("project_id"));
        $this->assign('supplierArr',$this->supplier->getSupType());
        $this->assign('companyArr',$this->supplier->getSupplier());
        $this->assign('moduleArr',$this->supplier->getModule());
        $this->assign('rows',$rows);
        $html=$this->fetch('Purcha/purchaTable/suprLi');
        $this->ajaxReturn(['html'=>$html]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-07-17 23:41:10 
     * @Desc: 成本录入新增编辑控制 
     */    
    function cost_insert_modalOne(){
        $title = "成本录入";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        $roleId = session("roleId");
        
        if($gettype=="Edit"){
            $title = "编辑成本";
            $btnTitle = "保存数据";
            $redisName="cost_insertList";
            $where = ["project_id"=>$id];
            if($this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]<7){
                $where['_string'] = "user_id = ".session('userId')." OR FIND_IN_SET({$roleId},examine)>0";
            }
            $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
            $parameter=[
                'where'=>$where,
                'fields'=>"*,FROM_UNIXTIME(sign_date,'%Y-%m-%d') sign_date,FROM_UNIXTIME(advance_date,'%Y-%m-%d') advance_date,FIND_IN_SET({$roleId},examine) place",
                'page'=>$p,
                'pageSize'=>$pageSize,
                'orderStr'=>"id DESC",
                "joins"=>[
                    "LEFT JOIN(SELECT projectId, name,code,business,leader FROM v_project) p ON p.projectId = project_id",
                    "LEFT JOIN (SELECT userId buser_id,userName business_name FROM v_user) bu ON bu.buser_id = p.business",
                    "LEFT JOIN (SELECT userId luser_id,userName leader_name FROM v_user) lu ON lu.luser_id = p.leader",
                    "LEFT JOIN (SELECT companyId cid,company supplier_com_name,provinceId,cityId FROM v_supplier_company WHERE status=1) c ON c.cid=supplier_com",
                    "LEFT JOIN (SELECT contactId cid,contact supplier_cont_name FROM v_supplier_contact WHERE status=1) ct ON ct.cid=supplier_cont",
                    "LEFT JOIN (SELECT pid ,province province_name FROM v_province) pr ON pr.pid=c.provinceId",
                    "LEFT JOIN (SELECT cid,city city_name,pid FROM v_city) ci ON ci.cid=c.cityId",
                    // "LEFT JOIN (SELECT basicId,name module_name FROM v_basic WHERE class='module') m ON m.basicId=module",
                    "LEFT JOIN (SELECT basicId,name suprt_name FROM v_basic WHERE class='supType') st ON st.basicId=type",
                    "LEFT JOIN (SELECT basicId,name module_name FROM v_basic WHERE class='module') sm ON sm.basicId=module",
                    "LEFT JOIN (SELECT table_id tid , SUBSTRING_INDEX( GROUP_CONCAT(user_id),',',-1) tuserid,SUBSTRING_INDEX(GROUP_CONCAT(remark),',',-1) aremark FROM v_approve_log WHERE status > 0 AND effect = 1 AND table_name ='v_purcha' GROUP BY table_id ORDER BY add_time DESC) ap ON ap.tid=id",
                    "LEFT JOIN (SELECT userId auser_id,userName approve_name FROM v_user) au ON au.auser_id = ap.tuserid",
                ],
                'isCount' => false,
            ];
            $resultData=$this->purchaCom->getList($parameter);
            // echo $this->purchaCom->M()->_sql();exit;
            foreach ($resultData['list'] as $key => $purcha) {
                if($purcha['module']){
                    $parameter=[
                        'where'=>["class"=>"module",'basicId'=>["IN",explode(",",$purcha['module'])]],
                        'fields'=>'basicId,name',
                        'page'=>1,
                        'pageSize'=>9999,
                        'orderStr'=>"basicId DESC",
                    ];
                    $basicResult=$this->basicCom->getList($parameter);
                    $resultData['list'][$key]["modules"]=$basicResult['list'];
                }
            }
            // print_r($resultData);exit;
            $resultData["template"] = $this->fetch('Purcha/purchaTable/suprLi');
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"costModal",
        ];
        $this->modalOne($modalPara);
    }
    function cost_insertList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $roleId = session("roleId");
        $where=[];
        foreach (['name','code','business_name','leader_name'] as $key) {
            if(isset($data[$key])){
                $where[$key]=['LIKE','%'.$data[$key].'%'];
            }
        }
        if($this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]<7){
            $where['user_id'] = session('userId');
        }
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
        $parameter=[
            'where'=>$where,
            'fields'=>"id,type,project_id,state status,user_id,COUNT(supplier_com) supr_num,SUM(contract_amount) amount, name,code,business_name,leader_name,FIND_IN_SET({$roleId},examine) place,CASE WHEN expense_money>debit_money THEN expense_money ELSE debit_money END debit_expense",
            'page'=>$p,
            'pageSize'=>$pageSize,
            'orderStr'=>"id DESC",
            'groupBy' => 'project_id',
            "joins"=>[
                "LEFT JOIN(SELECT projectId, name,code,business,leader FROM v_project) p ON p.projectId = project_id",
                "LEFT JOIN (SELECT userId buser_id,userName business_name FROM v_user) bu ON bu.buser_id = p.business",
                "LEFT JOIN (SELECT userId luser_id,userName leader_name FROM v_user) lu ON lu.luser_id = p.leader",
                "LEFT JOIN (SELECT project_id project_sid, CASE WHEN FIND_IN_SET(0,GROUP_CONCAT(status))>0 THEN 0 WHEN FIND_IN_SET(1,GROUP_CONCAT(status))>0 THEN 1 WHEN FIND_IN_SET(2,GROUP_CONCAT(status))>0 THEN 2  WHEN FIND_IN_SET(3,GROUP_CONCAT(status))>0 THEN 3  WHEN FIND_IN_SET(4,GROUP_CONCAT(status))>0 THEN 4 ELSE 0 END state FROM v_purcha GROUP BY project_id) s ON s.project_sid=project_id",
                "LEFT JOIN (SELECT project_id eproject_id,SUM(expense_money) expense_money FROM `v_expense` LEFT JOIN (SELECT parent_id,SUM(money) expense_money,status state FROM v_expense_sub GROUP BY parent_id) es ON es.parent_id = id GROUP BY project_id) e ON e.eproject_id=project_id",
                "LEFT JOIN (SELECT project_id dproject_id, SUM(debit_money) debit_money FROM v_debit GROUP BY project_id) d ON d.dproject_id=project_id",
            ],
        ];
        
        $listResult=$this->purchaCom->getList($parameter);
        // echo $this->purchaCom->M()->_sql();exit;
        $this->tablePage($listResult,'Purcha/purchaTable/costInsertList',"cost_insertList",$pageSize);
    }
    function manageCostInsertInfo($datas,$reqType=false){
        $reqType = $reqType ? $reqType : I("reqType");
        foreach (["sign_date","advance_date"] as $date) {
            $datas[$date] = strtotime($datas[$date]);
        }
        if(isset($datas['module'])){
            $datas['module']=implode(",",$datas['module']);
        }
        if($reqType=="cost_insertAdd"){
            $datas['add_time']=time();
            $datas['user_id']=session("userId");
            unset($datas['id']);
            return $datas;
        }else if($reqType=="cost_insertEdit"){
            $where=["id"=>$datas['id']];
            $data=[];
            $data['updateTime']=time();
            foreach (["project_id","supplier_com","supplier_cont","sign_date","contract_amount","contract_file","offer_file","advance_date","remark","module",'type'] as $key) {
                if(isset($datas[$key])){
                    $data[$key] = $datas[$key];
                } 
            }
            if(isset($datas['status'])){
                $parameter=[
                    'where'=>["id"=>$datas['id']],
                ];
                $result=$this->purchaCom->getList($parameter,true);
                $data = $this->status_update($result,$datas["status"],$data);
            }
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    function cost_insertAdd(){
        $datas=I("data");
        $isInsert =false;
        
        
        // $process = $this->nodeCom->getProcess(I("vtabId"));

        // $process_id = $process["processId"];
        if($datas[0]["project_id"]>0){ 
            //检查成本预算是否超支
            $this->projectCom->checkCost($datas[0]["project_id"],array_sum(array_column($datas,'contract_amount')));
            // $costBudget = $this->projectCom->getCostBudget($datas[0]["project_id"]);
            // $allCost = $this->projectCom->getCosts($datas[0]["project_id"]);
            // // print_r($allCost);
            // $array_column = array_sum(array_column($datas,'contract_amount'));
            // if(($array_column+$allCost['allCost']) > $costBudget){
            //     //<p>其中已批准成本：【'.$allCost['active'].'】</p><p>其中其他状态成本：【'.$allCost['waiting'].'】</p>
            //     $html='<p>成本预算超支:</p><p>该项目立项成本预算【'.$costBudget.'】</p><p>当前使用已使用成本：【'.$allCost['allCost'].'】</p><p>请联系管理员修改成本预算</p>';
            //     $this->ajaxReturn(['errCode'=>77,'error'=>$html]);
            // }
            //存在项目，则第一个审批的人是项目主管,examine需要
            
            // if($datas[0]["leader"]>0){
            //     $userRole = $this->userCom->getUserInfo($datas[0]["leader"]);
            // }
            // $examine = trim(implode(",",array_unique(explode(",",$userRole['roleId'].",".$process["examine"]))),",");
        }else{
            // $examine = $process["examine"];
        }
        // $process_level=$process["place"];
        //如果是审批者自己提交的执行下列代码
        // $roleId = session("roleId");
        // $examineArr = explode(",",$examine);
        // $rolePlace = search_last_key($roleId,explode(",",$userRole['roleId'].",".$process["examine"]));
        
        //添加时审批流数据
        $examines = getComponent('Process')->getExamine(I("vtabId"),$datas[0]["leader"]);
        foreach ($datas as $suprInfo) {
            $dataInfo = $this->manageCostInsertInfo($suprInfo);
            $dataInfo["process_id"] = $examines["process_id"];
            $dataInfo['examine'] = $examines["examine"];
            $dataInfo['process_level'] = $examines["status"];
            $dataInfo['status'] = $examines["process_level"];
            // print_r($process);
            // print_r($dataInfo);exit;
            unset($dataInfo['leader']);
            if($dataInfo){
                $insertResult=$this->purchaCom->insert($dataInfo);
                
                if(isset($insertResult->errCode) && $insertResult->errCode==0){
                    $this->ApprLogCom->createApp($this->purchaCom->tableName(),$insertResult->data,session("userId"),"");
                    $this->wouldpayCom->insert(["cost_id"=>$insertResult->data]);
                    $isInsert =true;
                }
            }
        }
        if($isInsert){
            $touser = $this->userCom->getQiyeId(explode(',',$examines["examine"])[0],true);
            if(!empty($touser)){
                $desc = "<div class='gray'>".date("Y年m月d日",time())."</div> <div class='normal'>".session('userName')."添加供应商成本，@你了，点击进入审批吧！</div>";
                $url = C('qiye_url')."/Admin/Index/Main.html?action=Purcha/costInsert";
                $msgResult = $this->QiyeCom-> textcard($touser,session('userName')."添加供应商成本",$desc,$url);
            }
            $this->ajaxReturn(['errCode'=>0,'error'=>"添加成功"]);
        }
        $this->ajaxReturn(['errCode'=>$insertResult->errCode,'error'=>$insertResult->error]);
    }
    function cost_insertEdit(){
        $datas=I("data");
        $dels=I("del");
        // print_r($dels);
        // print_r($datas);exit;
        if($dels && !empty($dels)){
            $delResult=$this->purchaCom->del(["id"=>["IN",$dels]]);
            if(isset($delResult->errCode) && $delResult->errCode==0){
                $isUpdate =true;
            }
        }

        //添加时审批流数据
        $examines = getComponent('Process')->getExamine(I("vtabId"),$datas[0]["leader"]);
        // $datas['process_id'] = $examines["process_id"];
        // $datas['examine'] = $examines["examine"];
        // $datas['process_level'] = $examines["process_level"];
        // $datas['status'] = $examines["status"];

        // $rolePlace = $examines['place'];
        // $status = 0;
        // if($rolePlace!==false){
        //     $process_level=$rolePlace+2;
        //     if(count(explode(",",$examines['examine'])) <= ($rolePlace+1)){
        //         $status = 1;
        //     }else{
        //         $status = 2;
        //     }
        // }else{
        //     $process_level=$rolePlace > 0 ? $rolePlace : 1;
        // }

        if($datas[0]["project_id"]>0){
            $ids = array_column($datas,'id');
            $dbCom = "purcha";
            // print_r($ids);exit;
            $this->projectCom->checkCost($datas[0]["project_id"],array_sum(array_column($datas,'contract_amount')),$dbCom,$ids);
            // $costBudget = $this->projectCom->getCostBudget($datas[0]["project_id"]);
            // $allCost = $this->projectCom->getCosts($datas[0]["project_id"]);
            // // print_r($allCost);
            // $array_column = array_sum(array_column($datas,'contract_amount'));
            // if(($array_column+$allCost['allCost']) > $costBudget){
            //     //<p>其中已批准成本：【'.$allCost['active'].'】</p><p>其中其他状态成本：【'.$allCost['waiting'].'】</p>
            //     $html='<p>成本预算超支:</p><p>该项目立项成本预算【'.$costBudget.'】</p><p>当前使用已使用成本：【'.$allCost['allCost'].'】</p><p>请联系管理员修改成本预算</p>';
            //     $this->ajaxReturn(['errCode'=>77,'error'=>$html]);
            // }
        }
        // exit;
        $isUpdate =false;
        foreach ($datas as $suprInfo) {
            
            if($suprInfo["id"]>0){
                $dataInfo = $this->manageCostInsertInfo($suprInfo);
                // print_r($dataInfo);
                if($dataInfo){
                    $updateResult=$this->purchaCom->update($dataInfo);
                    if($updateResult->errCode==0){
                        $this->ApprLogCom->updateStatus($this->purchaCom->tableName(),$dataInfo["where"]["id"]);
                        $isUpdate =true;
                    }
                }
            }else{
                $dataInfo = $this->manageCostInsertInfo($suprInfo,"cost_insertAdd");
                $dataInfo["process_id"] = $examines['process_id'];
                $examines['examine'] = $examines["examine"];
                $dataInfo['process_level'] = $examines["process_level"];
                $dataInfo['status'] = $examines["status"];
                // print_r($dataInfo);
                if($dataInfo){
                    $insertResult=$this->purchaCom->insert($dataInfo);
                    if($insertResult->errCode==0){
                        $this->wouldpayCom->insert(["cost_id"=>$insertResult->data]);
                        $isUpdate =true;
                    }
                }
            }
        }
        
        if($isUpdate){
            $this->ajaxReturn(['errCode'=>0,'error'=>"修改成功"]);
        }
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }

    /** 
     * @Author: vition 
     * @Date: 2018-07-17 23:45:33 
     * @Desc: 项目采购成本审批 
     */    
    function purchaApply(){
        $reqType=I('reqType');
        $this->assign("controlName","purcha_apply");
        // $this->assign('tableName',"");//删除数据的时候需要
        $this->assign('payType',$this->payType);//
        $this->assign('invoiceType',$this->invoiceType);//
        $this->assign("tableName",$this->purchaCom->tableName());
        if($reqType){
            $this->$reqType();
        }else{
            //自动计算成本
            $sql = "SELECT s_parent_id , s_scompany_id,s_cost_total,s_scompany_cid,item_num FROM (SELECT parent_id s_parent_id , scompany_id s_scompany_id,SUM(cost_total) s_cost_total, scompany_cid s_scompany_cid,COUNT(id) item_num  FROM v_project_cost_sub WHERE scompany_id > 0 GROUP BY parent_id,scompany_cid) pcs LEFT JOIN (SELECT id wid , cost_id,supplier_id FROM v_wouldpay) w ON w.cost_id = pcs.s_parent_id AND w.supplier_id = pcs.s_scompany_id  WHERE ISNULL(wid)";
            
            $listResult =M()->query($sql);
            foreach ($listResult as  $supplierCost) {
                $insertResult = $this->wouldpayCom->insert(['cost_id'=>$supplierCost['s_parent_id'],'supplier_id'=>$supplierCost['s_scompany_id'],'contract_money'=>$supplierCost['s_cost_total'],'add_time'=>time(),'status'=>1,'supplier_cid'=>$supplierCost['s_scompany_cid'],'item_num'=>$supplierCost['item_num']]);
            }
            $this->returnHtml();
        }
    }

    function purcha_applyList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=[];

        // foreach (['project_name','code','supplier_cont_name','supplier_com_name','business_name','leader_name'] as $key) {
        //     if(isset($data[$key])){
        //         $where[$key]=['LIKE','%'.$data[$key].'%'];
        //     }
        // }
        // $roleId = session('roleId');
        // if($this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]<7){
        //     // $whereSub['user_id'] = session('userId');
        //     // $whereSub['_string'] = "FIND_IN_SET({$roleId},examine)>0";
        //     // $where['_logic'] = 'or';
        //     // $where['_complex'] = $whereSub;

        //     $where["_string"] = "FIND_IN_SET({$roleId},examine) <= process_level AND FIND_IN_SET({$roleId},examine) > 0 OR user_id = ".session('userId');
        // }
        // $parameter=[
        //     'where'=>$where,
        //     'fields'=>"*,FIND_IN_SET({$roleId},examine) place",
        //     'page'=>$p,
        //     'pageSize'=>$this->pageSize,
        //     'orderStr'=>"id DESC",
        //     "joins"=>[
        //         "LEFT JOIN (SELECT projectId, name project_name,code,business,leader,brand ,project_time project_date,days FROM v_project) p ON p.projectId = project_id",
        //         "LEFT JOIN (SELECT userId uuser_id,userName user_name FROM v_user) u ON u.uuser_id = user_id",
        //         "LEFT JOIN (SELECT userId buser_id,userName business_name FROM v_user) bu ON bu.buser_id = p.business",
        //         "LEFT JOIN (SELECT userId luser_id,userName leader_name FROM v_user) lu ON lu.luser_id = p.leader",
        //         "LEFT JOIN (SELECT companyId cid,company supplier_com_name,supr_type,provinceId,cityId FROM v_supplier_company WHERE status=1) c ON c.cid=supplier_com",
        //         "LEFT JOIN (SELECT contactId cid,contact supplier_cont_name,phone supplier_cont_phone,email supplier_cont_email FROM v_supplier_contact WHERE status=1) ct ON ct.cid=supplier_cont",
        //         "LEFT JOIN (SELECT basicId,name type_name FROM v_basic WHERE class='supType') st ON st.basicId=c.supr_type",
        //         "LEFT JOIN (SELECT basicId,name module_name FROM v_basic WHERE class='module') bm ON bm.basicId=module",
        //         "LEFT JOIN (SELECT basicId brand_id,name brand_name FROM v_basic WHERE class = 'brand' ) b ON b.brand_id = p.brand",
        //         "LEFT JOIN (SELECT pid ,province province_name FROM v_province) pr ON pr.pid=c.provinceId",
        //         "LEFT JOIN (SELECT cid,city city_name,pid FROM v_city) ci ON ci.cid=c.cityId",
        //         "LEFT JOIN (SELECT table_id tid , SUBSTRING_INDEX( GROUP_CONCAT(user_id),',',-1) tuserid,SUBSTRING_INDEX(GROUP_CONCAT(remark),',',-1) aremark FROM v_approve_log WHERE status > 0 AND effect = 1 AND table_name ='v_purcha' GROUP BY table_id ORDER BY add_time DESC) ap ON ap.tid=id",
        //         "LEFT JOIN (SELECT userId auser_id,userName approve_name FROM v_user) au ON au.auser_id = ap.tuserid",
        //     ],
        // ];
        
        // $listResult=$this->purchaCom->getList($parameter);
        // // echo $this->purchaCom->M()->_sql();exit;
        // $this->tablePage($listResult,'Purcha/purchaTable/purapplyList',"purapplyList");
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
        $where=['status'=>1];
        $parameter=[
            'where'=>$where,
            'fields'=>"*",
            'page'=>$p,
            'pageSize'=>$pageSize,
            'orderStr'=>"id DESC",
            'joins' => [
                'LEFT JOIN (SELECT id pcid,project_id,flag,section FROM v_project_cost ) pc ON pc.pcid = cost_id',
                'LEFT JOIN (SELECT companyId company_id,company supplier_name,supr_type,module,provinceId,cityId FROM v_supplier_company ) sc ON sc.company_id = supplier_id',
                'LEFT JOIN (SELECT projectId,code project_code,name project_name,FROM_UNIXTIME(project_time,"%Y-%m-%d") project_date,DATE_ADD(FROM_UNIXTIME(project_time,"%Y-%m-%d"),INTERVAL days day) end_date,leader FROM v_project ) p ON p.projectId = pc.project_id',
                "LEFT JOIN (SELECT userId user_id,userName leader_name FROM v_user) lu ON lu.user_id = p.leader",
                "LEFT JOIN (SELECT project_id f_project_id,supplier_id f_supplier_id,SUM(money) f_money,section f_section FROM v_float_capital_log WHERE float_type = 2 AND supplier_id > 0  GROUP BY project_id,supplier_id) f ON f.f_project_id = pc.project_id AND f.f_supplier_id = supplier_id AND f.f_section = pc.section"
            ],
        ];
        $listResult = $this->wouldpayCom->getList($parameter);
        // echo $this->wouldpayCom->M()->_sql();exit;
        // print_r($listResult);exit;
        $this->tablePage($listResult,'Purcha/purchaTable/supplierpayList',"supplierpayList");
    }

    function purcha_apply_modalOne(){
        $title = "采购成本审批";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        // if($gettype=="Edit"){
        //     $title = "采购成本审批";
        //     $btnTitle = "保存数据";
        //     $redisName="purapplyList";
        //     $resultData=$this->purchaCom->redis_one($redisName,"id",$id);
        // }
        // // $resultData["project_date"] = date("Y-m-d",$resultData["project_time"]);
        // foreach (["project_date","sign_date"] as  $date) {
        //     if(isset($resultData[$date])){
        //         $resultData[$date] = date("Y-m-d",$resultData[$date]);
        //     }
        // }
        // $resultData["tableData"] = [];
        // $resultData["tableData"]["suprpay-list"] = ["list"=>$this->payCom->getList(["where"=>["purcha_id"=>$id,"insert_type"=>1],"fields"=>"*,FROM_UNIXTIME(pay_date,'%Y-%m-%d') pay_date"])["list"],"template"=>$this->fetch('Purcha/purchaTable/suprpayLi')];
        // $resultData["tableData"]["suprfina-list"] = ["list"=>$this->payCom->getList(["where"=>["purcha_id"=>$id,"insert_type"=>2],"fields"=>"*,FROM_UNIXTIME(pay_date,'%Y-%m-%d') pay_date"])["list"],"template"=>$this->fetch('Purcha/purchaTable/suprfinapayLi')];
        // $resultData["tableData"]["invoice-list"] = ["list"=>$this->InvoiceCom->getList(["where"=>["relation_id"=>$id,"relation_type"=>1],"fields"=>"*,FROM_UNIXTIME(invoice_date,'%Y-%m-%d') invoice_date"])["list"],"template"=>$this->fetch('Purcha/purchaTable/invoiceLi')];

        // $resultData["end_date"] = date("Y-m-d",strtotime($resultData["project_date"]." +".$resultData["days"]."day"));
        // $modalPara=[
        //     "data"=>$resultData,
        //     "title"=>$title,
        //     "btnTitle"=>$btnTitle,
        //     "template"=>"purchaModal",
        // ];
        // $this->modalOne($modalPara);
        $this->assign('fin_accountArr',$this->Com ->get_option("fin_account"));
        if($gettype=="Edit"){
            $title = "采购成本审批";
            $btnTitle = "保存数据";
            $redisName="purapplyList";
            $parameter=[
                'fields'=>"*,FROM_UNIXTIME(sign_date,'%Y-%m-%d') sign_date ",
                'where'=>['id'=>$id],
                "joins"=>[
                    'LEFT JOIN (SELECT id s_id , read_type s_read_type , parent_id s_parent_id , class_sort s_class_sort , cost_class s_cost_class , class_sub s_class_sub , class_notes s_class_notes , classify s_classify , sort s_sort , item_content s_item_content , num s_num , unit s_unit , price s_price , act_num s_act_num , act_unit s_act_unit , total s_total , cost_price s_cost_price ,SUM(cost_total) s_cost_total , profit s_profit , profit_ratio s_profit_ratio , remark s_remark , ouser_id s_ouser_id , cuser_id s_cuser_id , add_time s_add_time , update_time s_update_time , status s_status , scompany_id s_scompany_id , scompany_cid s_scompany_cid,COUNT(id) item_num FROM v_project_cost_sub WHERE scompany_id > 0 GROUP BY parent_id,scompany_cid) pcs ON pcs.s_parent_id = cost_id AND pcs.s_scompany_id = supplier_id',
                    'LEFT JOIN (SELECT id pcid,project_id,flag FROM v_project_cost ) pc ON pc.pcid = cost_id',
                    'LEFT JOIN (SELECT projectId,code project_code,name project_name,FROM_UNIXTIME(project_time,"%Y-%m-%d") project_date,DATE_ADD(FROM_UNIXTIME(project_time,"%Y-%m-%d"),INTERVAL days day) end_date,leader FROM v_project ) p ON p.projectId = pc.project_id',
                    "LEFT JOIN (SELECT userId user_id,userName leader_name FROM v_user) lu ON lu.user_id = p.leader",
                    'LEFT JOIN (SELECT companyId company_id,company supplier_com_name,supr_type,module,provinceId,cityId FROM v_supplier_company ) sc ON sc.company_id = pcs.s_scompany_id',
                    'LEFT JOIN (SELECT contactId contact_id,contact supplier_cont_name,phone supplier_cont_phone, email supplier_cont_email FROM v_supplier_contact ) suco ON suco.contact_id = pcs.s_scompany_cid',
                    "LEFT JOIN (SELECT basicId type_id,name type_name FROM v_basic WHERE class = 'supType' ) t ON t.type_id = sc.supr_type",
                    "LEFT JOIN (SELECT pid ,province province_name FROM v_province) pr ON pr.pid=sc.provinceId",
                    "LEFT JOIN (SELECT cid,city city_name,pid FROM v_city) ci ON ci.cid=sc.cityId",
                    // 'LEFT JOIN (SELECT id wid , cost_id,supplier_id,contract_file FROM v_wouldpay) w ON w.cost_id = pcs.s_parent_id AND w.supplier_id = pcs.s_scompany_id',
                ],
            ];
            $resultData = $this->wouldpayCom->getOne($parameter)['list'];
            if($resultData['module']){
                $param = [
                    'fields'=>"GROUP_CONCAT(name) modules",
                    'where' => ['class'=>'module','basicId'=>['IN',explode(',',$resultData['module'])]],
                ];
                $moduleResult = $this->basicCom->getOne($param);
                if($moduleResult){
                    $resultData['modules'] = $moduleResult['list']['modules'];
                }
                if(strpos($resultData['module'], '999999999') !== false){
                    $resultData['modules'].=',全部承接模块';
                }
            }
            $resultData["tableData"] = [];
            $resultData["tableData"]["suprpay-list"] = ["list"=>$this->payCom->getList(["where"=>["purcha_id"=>$id,"insert_type"=>1],"fields"=>"*,FROM_UNIXTIME(pay_date,'%Y-%m-%d') pay_date"])["list"]];
            // $resultData["tableData"]["suprfina-list"] = ["list"=>$this->payCom->getList(["where"=>["purcha_id"=>$id,"insert_type"=>2],"fields"=>"*,FROM_UNIXTIME(pay_date,'%Y-%m-%d') pay_date"])["list"],"template"=>$this->fetch('Purcha/purchaTable/suprfinapayLi')];
            $resultData["tableData"]["invoice-list"] = ["list"=>$this->InvoiceCom->getList(["where"=>["relation_id"=>$id,"relation_type"=>1],"fields"=>"*,FROM_UNIXTIME(invoice_date,'%Y-%m-%d') invoice_date"])["list"]];
        }
        $resultData['getSuprpayLiItem'] = $this->fetch('Purcha/purchaTable/suprpayitem');
        $resultData['suprInvoiceLiItem'] = $this->fetch('Purcha/purchaTable/invoiceLi');

        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"supplierPayModal",
        ];
        $this->modalOne($modalPara);
    }
    function purcha_applyEdit(){
        // exit;
        // $data=I("data");
        extract($_POST);

        $isUpdate = false;
        $dataInfo = ["where"=>["id" => $purcha_id],"data"=>[]];
        foreach (["contract_file","pay_grade","contract_money","finance_id","sign_date","remark"] as $key) {
            if(isset($$key) && $$key!=""){
                if($key=="sign_date"){
                    $dataInfo['data'][$key] = strtotime($$key);
                }else{
                    $dataInfo['data'][$key] = $$key;
                }
            }
        }

        if(!isset($data) || !is_array($data)){ 
            $this->ajaxReturn(['errCode'=>114,'error'=>getError(114)]);
        }

        // print_r($dataInfo);exit;

        if(count($dataInfo['data']) > 1){

            $isUpdate = true;
            $updateResult=$this->wouldpayCom->update($dataInfo);
            // $updateResult=$this->purchaCom->update($dataInfo);
            if($updateResult->errCode == 0){
                $this->ApprLogCom->updateStatus($this->wouldpayCom->tableName(),$dataInfo['where']["id"]);
                // $this->ApprLogCom->updateStatus($this->purchaCom->tableName(),$dataInfo["id"]);
            }
        }
        foreach (["suprpay-list","suprfina-list","invoice-list"] as $itemInfoList) {
            foreach ($data[$itemInfoList] as $key => $itemInfo) {
                // print_r($itemInfo);
                if(in_array($itemInfoList,["suprpay-list","suprfina-list"])){
                    $itemInfo["pay_date"] = strtotime($itemInfo["pay_date"]);
                    $listCom = $this->payCom;
                    
                }else{
                    $itemInfo["invoice_date"] = strtotime($itemInfo["invoice_date"]);
                    $listCom = $this->InvoiceCom;
                }
                if($itemInfo["id"]>0){
                    if($itemInfo){
                        $updateResult=$listCom->update($itemInfo);
                        if($updateResult->errCode==0){
                            $isUpdate =true;
                        }
                    }
                }else{
                    unset($itemInfo["id"]);
                    if($itemInfo){
                        $updateResult=$listCom->insert($itemInfo);
                        if($updateResult->errCode==0){
                            $isUpdate =true;
                        }
                    }
                }
            }
        }
        if($isUpdate){
            $this->ajaxReturn(['errCode'=>0,'error'=>"修改成功"]);
        }
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    function getSuprpayLiOne(){
        $rows = I("rows");
        $this->assign('projectArr',$this->Com ->get_option("project_id"));
        $this->assign('supplierArr',$this->supplier->getSupType());
        $this->assign('companyArr',$this->supplier->getSupplier());
        $this->assign('moduleArr',$this->supplier->getModule());
        $this->assign('rows',$rows);
        $html=$this->fetch('Purcha/purchaTable/suprpayLi');
        $this->ajaxReturn(['html'=>$html]);
    }
    function suprFinapayLiOne(){
        $rows = I("rows");
        $this->assign('projectArr',$this->Com ->get_option("project_id"));
        $this->assign('supplierArr',$this->supplier->getSupType());
        $this->assign('companyArr',$this->supplier->getSupplier());
        $this->assign('moduleArr',$this->supplier->getModule());
        $this->assign('rows',$rows);
        $html=$this->fetch('Purcha/purchaTable/suprfinapayLi');
        $this->ajaxReturn(['html'=>$html]);
    }
    function suprInvoiceLiOne(){
        $rows = I("rows");
        $this->assign('rows',$rows);
        $html=$this->fetch('Purcha/purchaTable/invoiceLi');  
        $this->ajaxReturn(['html'=>$html]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-11-26 10:20:34 
     * @Desc: 采购成本列表 
     */    
    function purchase_details(){
        $reqType=I('reqType');
        $this->assign("controlName","purchase_details");
        $this->assign("tableName",$this->pCostSubCom->tableName());
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function purchase_detailsList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $roleId = session("roleId");
        $where=[];
        // if($this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]<7){
        //     $where['ouser_id'] = session('userId');
        // }
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
        $sql = 'SELECT *,COUNT(id) item_num,"供应商" suprtype FROM v_project_cost_sub WHERE scompany_id > 0  GROUP BY parent_id  UNION ALL SELECT *,COUNT(id) item_num,"非供应商" suprtype FROM v_project_cost_sub WHERE scompany_id = 0 GROUP BY parent_id';
        $sqlLimit= 'SELECT *,FROM_UNIXTIME(add_time,"%Y-%m-%d") add_time FROM ('.$sql.') pcs LEFT JOIN (SELECT id pId,project_id,section FROM v_project_cost) pc ON pId = pcs.parent_id LEFT JOIN (SELECT projectId,code project_code,name project_name FROM v_project ) p ON p.projectId = pc.project_id LEFT JOIN (SELECT userId, userName ouser_name FROM v_user ) u ON u.userId = pcs.ouser_id LEFT JOIN (SELECT userId, userName cuser_name FROM v_user ) u1 ON u1.userId = pcs.cuser_id ORDER BY add_time DESC LIMIT '.$p.','.$pageSize;
        $sqlCount= 'SELECT COUNT(id) v_count FROM ('.$sql.') pcs';
        if(isset($data['suprtype'])){
            if($data['suprtype']==1){
                // $sql = ''
            }
        }else{

        }
        //SELECT *,COUNT(DISTINCT scompany_id) num FROM v_project_cost_sub WHERE scompany_id > 0  GROUP BY parent_id  UNION ALL SELECT *,COUNT(id) num FROM v_project_cost_sub WHERE scompany_id = 0 GROUP BY parent_id ;
        
        // $parameter=[
        //     'fields'=>"*,FROM_UNIXTIME(add_time,'%Y-%m-%d') add_time,COUNT(DISTINCT scompany_id) num",
        //     'where'=>$where,
        //     'page'=>$p,
        //     'pageSize'=>$pageSize,
        //     'orderStr'=>"id DESC",
        //     "joins"=>[
                // "LEFT JOIN (SELECT projectId,code project_code,name project_name FROM v_project ) p ON p.projectId = project_id ",
                // "LEFT JOIN (SELECT userId, userName ouser_name FROM v_user ) u ON u.userId = ouser_id ",
            // ]
        // ];
        $listResult['list']=$this->pCostSubCom->M()->query($sqlLimit);
        $listResult['count']=$this->pCostSubCom->M()->query($sqlCount)[0]['v_count'];
        // print_r( $listResult);exit;
        // $this->
        // if($type == 'offer'){
        //     $listTemplate = 'project_offerList';
        // }else if($type == 'cost'){
        //     $listTemplate = 'project_costList';
        // }
        $this->tablePage($listResult,'Purcha/purchaTable/purchase_detailsList',"purchase_detailsList",$pageSize);
    }
}