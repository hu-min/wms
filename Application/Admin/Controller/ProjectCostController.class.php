<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-11-16 11:26:07 
 * @Desc: 项目成本控制器 
 */
class ProjectCostController extends BaseController{
    public function _initialize() {
        parent::_initialize();
        $this->projectCom=getComponent('Project');
        $this->pCostCom=getComponent('ProjectCost');
        $this->pCostSubCom=getComponent('ProjectCostSub');
        // $this->supplierCom=getComponent('Supplier');
        // $this->purchaCom=getComponent('Purcha');
        // $this->fieldCom=getComponent('Field');
        // $this->filesCom=getComponent('ProjectFiles');
        // $this->ReceCom=getComponent('Receivable');
        // $this->whiteCom=getComponent('White');
        // $this->InvoiceCom=getComponent('Invoice');
        // $this->payCom=getComponent('Pay');
        // $this->processArr=["0"=>"沟通","1"=>"完结","2"=>"裁决","3"=>"提案","4"=>"签约","5"=>"LOST","6"=>"筹备","7"=>"执行","8"=>"完成"];
        // $this->dateArr=["0"=>"立项日期","1"=>"提案日期","2"=>"项目日期","3"=>"结束日期"];

        // Vendor("levelTree.levelTree");
        // $this->levelTree=new \levelTree();
    }
    /** 
     * @Author: vition 
     * @Date: 2018-11-16 11:27:03 
     * @Desc: 项目报价 
     */    
    function project_offer(){
        $reqType=I('reqType');
        $this->assign("controlName","project_offer");
        $this->assign("listType","offer");
        $this->assign("tableName",$this->pCostCom->tableName());
        
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function project_offer_modalOne($listType='offer',$fixedTitle=false){
        $title = "新增报价";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $this->assign('costClassArr',$this->Com ->get_option('costClass'));
        $this->assign('moduleArr',$this->Com ->get_option('module'));
        $this->assign('unitArr',$this->Com ->get_option('unit'));
        $this->assign('projectArr',$this->Com ->get_option('project'));
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑报价";
            $btnTitle = "保存数据";
            $redisName="project_offerList";
            $param = [
                'where'=>['id'=>$id],
            ];
            $resultData=$this->pCostCom->getOne($param)['list'];
            $where = [
                'parent_id' => $resultData['id'],
                'read_type' => 1,
            ];
            if($listType == "cost"){
                $where['read_type'] = ['EGT',1];
            }
            $sParam =[
                'where'=>$where,
                'orderStr'=>"class_sort ASC , sort ASC",
                'joins' => [
                    'LEFT JOIN (SELECT basicId, name cost_class_name FROM v_basic WHERE class ="costClass" ) bc ON bc.basicId = cost_class'
                ],
            ];
            $resultData['list'] = $this->pCostSubCom->getList($sParam)['list'];
            // echo $this->pCostSubCom->M()->_sql();exit;
            // $resultData=[];
        }
        $resultData['panel'] = $this->fetch('ProjectCost/projectcostTable/panel');
        $resultData['item'] = $this->fetch('ProjectCost/projectcostTable/item');
        $title = $fixedTitle ? $fixedTitle : $title;
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"project_offerModal",
        ];
        $this->modalOne($modalPara);
    }
    function project_offerList($type='offer'){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=[];
        // if($this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]<7){
        //     $where['ouser_id'] = session('userId');
        // }
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
        $parameter=[
            'fields'=>"*,FROM_UNIXTIME(add_time,'%Y-%m-%d') add_time",
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$pageSize,
            'orderStr'=>"id DESC",
            "joins"=>[
                "LEFT JOIN (SELECT projectId,code project_code,name project_name FROM v_project ) p ON p.projectId = project_id ",
                "LEFT JOIN (SELECT userId, userName ouser_name FROM v_user ) u ON u.userId = ouser_id ",
            ]
        ];
        $listResult=$this->pCostCom->getList($parameter);
        // $this->
        // if($type == 'offer'){
        //     $listTemplate = 'project_offerList';
        // }else if($type == 'cost'){
        //     $listTemplate = 'project_costList';
        // }
        $this->tablePage($listResult,'ProjectCost/projectcostTable/project_offerList',"project_offerList",$pageSize);
    }
    function project_offerMange($param){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");

        if(isset($datas['cost_total']) && $datas['cost_total']>0){
            $total = $datas['total'] > 0 ? $datas['total'] : 0;
            $datas['profit'] = round($total - $datas['cost_total'],2);
            $datas['profit_ratio'] = $total == 0 ? -100 : round($datas['profit'] / $total,2)*100;
        }
        if($reqType=="project_offerAdd"){
            $datas['status']=1;
            $datas['add_time'] = time();
            $datas['ouser_id'] = session("userId");
            unset($datas['id']);
            return $datas;
        }else if($reqType=="project_offerEdit"){
            $where=["id"=>$datas['id']];
            $data=[];
            foreach (['class_notes','class_sort','cost_class','sort','classify','item_content','num','unit','act_num','act_unit','price','total','status','class_sub','cost_price','cost_total','profit','profit_ratio'] as $key) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
            $data['update_time']=time();
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    //添加报价
    function project_offerAdd(){
        // exit;
        extract($_POST);
        $isInsert = false;
        $pResult = $this->projectCom->getOne(['where'=>['project_id' => $data['project_id']],'leader'])['list'];
        $param = [
            'fields' => 'id',
            'where' => ['project_id' => $data['project_id']],
            'pageSize' => 99999999,
        ];
        $pOfferData = [];
        $hasData = $this->pCostCom->getList($param);
        
        if($hasData){
            $pOfferData['section'] = $hasData['count'] + 1;
        }else{
            $pOfferData['section'] = 1;
        }
        $pOfferData['project_id'] = $data['project_id'];
        $pOfferData['total'] = $data['total'];
        $pOfferData['tax_rate'] = $data['tax_rate'];
        $pOfferData['ouser_id'] = session('userId');
        $pOfferData['add_time'] = time();
        $examines = getComponent('Process')->getExamine($vtabId,$pResult['leader']);
        $pOfferData['process_id'] = $examines["process_id"];
        $pOfferData['examine'] = $examines["examine"];
        $roleId = session("roleId");
        $rolePlace = $examines['place'];
        $pOfferData['status'] = 0;
        if($rolePlace!==false){
            $pOfferData['process_level']=$rolePlace+2;
            if(count(explode(",",$examines['examine'])) <= ($rolePlace+1)){
                $pOfferData['status'] = 1;
            }else{
                $pOfferData['status'] = 2;
            }
        }else{
            $pOfferData['process_level'] = $examines["place"] > 0 ? $examines["place"] : 1;
        }
        // print_r($pOfferData);exit;
        $this->pCostCom->startTrans();
        $pInsertResult = $this->pCostCom->insert($pOfferData);
        if(isset($pInsertResult->errCode) && $pInsertResult->errCode==0){
            $this->pCostSubCom->startTrans();
            $parent_id = $pInsertResult->data;//
            foreach ($data['list'] as  $subData) {
                $infoData = $this->project_offerMange(['data'=>$subData]);
                $infoData['parent_id'] = $parent_id;
                $insertResult = $this->pCostSubCom->insert($infoData);
                if(isset($insertResult->errCode) && $insertResult->errCode==0){
                    $isInsert = true;
                }else{
                    $isInsert = false;
                    $this->pCostCom->rollback();
                    $this->pCostSubCom->rollback();
                    break;
                }
                // print_r($infoData);
            }
            if($isInsert){
                $this->ApprLogCom->createApp($this->pCostCom->tableName(),$parent_id,session("userId"),"");
                $this->pCostCom->commit();
                $this->pCostSubCom->commit();
            }
        }
        
        $this->ajaxReturn(['errCode'=>$insertResult->errCode,'error'=>$insertResult->error]);
        // print_r($pOfferData);
    }
    //报价编辑
    function project_offerEdit(){
        extract($_POST);
        // print_r($data['list']);exit;
        $this->pCostCom->startTrans();
        
        $pOfferData = [
            'where' => ['id'=>$data['id']],
            'data' => [
                'project_id' => $data['project_id'],
                'tax_rate' => $data['tax_rate'],
                'total' => $data['total'],
                'update_time' => time(),
            ]
        ];
        $parent_id = $data['id'];
        // print_r($pOfferData);exit;
        $this->pCostCom->startTrans();
        $this->pCostSubCom->startTrans();
        $pInsertResult = $this->pCostCom->update($pOfferData);

        foreach ($data['list'] as  $subData) {
            if( $subData['id']>0){//编辑
                $infoData = $this->project_offerMange(['data'=>$subData]);
                $upateResult = $this->pCostSubCom->update($infoData);
                // print_r($infoData);
            }else{//新增
                $infoData = $this->project_offerMange(['data'=>$subData,'reqType'=>'project_offerAdd']);
                $infoData['parent_id'] = $parent_id;
                $upateResult = $this->pCostSubCom->insert($infoData);
                // print_r($infoData);
            }
        }
        $this->pCostCom->commit();
        $this->pCostSubCom->commit();
        $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
    }
    function  project_costControl(){
        $reqType=I('reqType');
        $this->assign("controlName","pcost_control");
        $this->assign("listType","cost");
        $this->assign("tableName",$this->pCostCom->tableName());
        
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function pcost_controlList(){
        $this->project_offerList('cost');
    }
    function  pcost_control_modalOne(){
        $this->project_offer_modalOne('cost','查看/编辑成本');
    }
    function pcost_controlEdit(){
        extract($_POST);
        // print_r($data['list']);
        $this->pCostCom->startTrans();
        
        $pOfferData = [
            'where' => ['id'=>$data['id']],
            'data' => [
                'cuser_id' => session('userId'),
                'cost_total' => $data['cost_total'],
                'update_time' => time(),
                'profit' => $data['total'] - $data['cost_total'],
                'profit_ratio' => round((($data['total'] - $data['cost_total']) / $data['total'])*100,2) ,
            ]
        ];
        $parent_id = $data['id'];
        // print_r($pOfferData);exit;
        $this->pCostCom->startTrans();
        $this->pCostSubCom->startTrans();
        $pInsertResult = $this->pCostCom->update($pOfferData);

        foreach ($data['list'] as  $subData) {
            if( $subData['id']>0){//编辑
                $infoData = $this->project_offerMange(['data'=>$subData,'reqType'=>'project_offerEdit']);
                $upateResult = $this->pCostSubCom->update($infoData);
                // print_r($infoData);
            }else{//新增
                $infoData = $this->project_offerMange(['data'=>$subData,'reqType'=>'project_offerAdd']);
                $infoData['parent_id'] = $parent_id;
                $infoData['read_type'] = 2;
                $upateResult = $this->pCostSubCom->insert($infoData);
                // print_r($infoData);
            }
        }
        // exit;
        $this->pCostCom->commit();
        $this->pCostSubCom->commit();
        $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-11-20 17:12:34 
     * @Desc: 成本对照 
     */    
    function project_costContrast(){
        $reqType=I('reqType');
        $this->assign("controlName","project_costCon");
        $this->assign("listType","contrast");
        $this->assign("tableName",$this->pCostCom->tableName());
        
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function project_costConList(){
        $this->project_offerList('contrast');
    }
    function  project_costCon_modalOne(){
        $this->project_offer_modalOne('contrast','查看成本对照');
    }
}