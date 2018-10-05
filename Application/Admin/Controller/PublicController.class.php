<?php
namespace Admin\Controller;

class PublicController extends BaseController{

    public function _initialize() {
        parent::_initialize();
        $this->MesCom=getComponent('Message');
        $this->workOrderCom=getComponent('WorkOrder');
        $this->AProject=A('Project');
        $this->worderType = ["1"=>"个人信息","2"=>"项目相关","3"=>"其他"];
    }

    function messageControl(){
        $reqType=I('reqType');
        $this->assign('userArr',A("Project")->_getOption("create_user"));
        $this->assign('no_read',$this->MesCom->noRead());
        if($reqType){
            $this->$reqType();
        }else{         
            $this->returnHtml();
        }
    }
    function getMessageList(){
        $type = I("type") ? I("type") : I("param")['type'];
        // print_r($type);exit;
        $p=I("p")?I("p"):1;
        $where=["to_user"=>session("userId")];
        $userKey = "from_user";
        $groupBy = "";
        
        switch ($type) {
            case 1: default:
                $where["status"]=["lt",2];
                break;
            case 2:
                unset($where["to_user"]);
                $where["from_user"] = session("userId");
                $userKey = "to_user";
                $where["status"]=["lt",2];
                $groupBy = "group_id";
                break;
            case 3:
                unset($where["to_user"]);
                $where["from_user"] = session("userId");
                $userKey = "to_user";
                $where["status"] = 2;
                break;
            case 4:
                $where["status"] = 3;
                $where["_string"] = "to_user = ".session("userId")." OR from_user = ".session("userId");
                unset($where["to_user"]);
                break;
        }
        
        $parameter=[
            "where" => $where,
            'page'=>$p,
            'fields'=>"*,FROM_UNIXTIME(add_time,'%Y-%m-%d %H:%i:%s') date_time",
            'pageSize'=>$this->pageSize,
            'orderStr'=>"`status` ASC,add_time DESC",
            'groupBy'=>$groupBy,
            "joins"=>[
                'LEFT JOIN (SELECT userId,userName user_name FROM v_user) u ON u.userId='.$userKey,
            ],
        ];
        
        $listResult = $this->MesCom->getList($parameter);
        $this->assign("type",$type);
        // print_r($parameter);
        $this->tablePage($listResult,'Public/publicTable/messageList',"lastLoginList",10,"",["bigSize"=>false,"returnData"=>true]);
    }
    function readMesOne(){

    }
    function messageAdd(){
        $datas = I("data");
        $this->ajaxReturn($this->MesCom->messageAdd($datas));
    }
    function statusEdit(){
        $id = I("id");
        $status = I("status");
        $result = $this->MesCom->updateState($id,$status);
        $newMesg = $this->MesCom->newMesg();
        $html = "";
        if($newMesg[0]){
            $html .='<li><a class="nodeOn" data-nodeid="10001" href="'.U("Public/messageControl").'" data-title="内部消息"><div class="pull-left">';
            if($newMesg[0]['avatar'] !=""){
                $html .='<img src="/'.$newMesg[0]["avatar"].'" class="img-circle" alt="用户头像">';
            }else{
                $html .='<img src="'.__ROOT__.'/Public'.'/admintmpl'.'/dist/img/minlogo.png" class="img-circle" alt="用户头像">';
            }
            $html .='</div><h4>'.utf8_substr($newMesg[0]['title'],8).'<small><i class="fa fa-clock-o"></i> '.disTime($newMesg[0]['add_time']).' </small></h4><p>'.utf8_substr($newMesg[0]['content'],16).'</p></a></li>';
           $data = $newMesg[0];
        }
        $this->ajaxReturn(['errCode'=>$result->errCode,'error'=>getError($result->errCode),'data'=>$html]);
    }
    function userProfile(){
        $reqType=I('reqType');
        if($reqType){
            $this->$reqType();
        }else{         
            $this->returnHtml();
        }
    }

    function seniorOne(){
        $datas = I('data');
        $senior = I('senior');
        // $datas['birthday'] = strtotime($datas['birthday']);
        // echo $senior;
        $param = [
            'where' => ['seniorPassword'=>sha1(sha1($senior)),'userId'=>session('userId')],
            'fields' => 'userId',
        ];
        $userRes = $this->userCom->getOne($param);
        if($userRes){
            $updateData=[];
            if(isset($datas['avatar'])){
                $updateData['avatar'] =  $datas['avatar'];
            }
            foreach (['seniorPassword','password'] as $key) {
                if(isset($datas[$key] )){
                    $updateData[$key] =  sha1(sha1($datas[$key]));
                }
            }
            $updateRes = $this->userCom->update(["where"=>['userId'=>session('userId')],"data"=>$updateData]);

            if(isset($updateRes->errCode) && $updateRes->errCode == 0){
                $parArray=[
                    'where'=>['userId'=>session('userId')],
                    'fields'=>'*',
                    'joins'=>[
                        'LEFT JOIN (SELECT roleId role_id ,rolePid,roleName FROM v_role) r ON r.role_id = roleId',
                        'LEFT JOIN (SELECT roleId role_pid ,roleName rolePName FROM v_role) rp ON rp.role_pid = r.rolePid',
                    ],
                ];
                $userInfo = $this->userCom->getOne($parArray)['list'];
                $this->setLogin($userInfo);
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
            $this->ajaxReturn(['errCode'=>$updateRes->errCode,'error'=>getError($updateRes->errCode)]);
        }
        $this->ajaxReturn(['errCode'=>10006,'error'=>getError(10006)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-09-28 10:28:33 
     * @Desc: 工单 
     */    
    function workOrder(){
        $reqType=I('reqType');
        $this->assign("controlName","work_order");
        $this->assign("orderType",$this->worderType);
        $this->assign("tableName",$this->workOrderCom->tableName());
        $this->assign('projectArr',$this->AProject->_getOption("relation_project"));
        $this->assign('processAuth',['level' => 1 ,'allLevel' => 0]);
        $this->assign('nodeAuth',1);
        if($reqType){
            $this->$reqType();
        }else{         
            $this->returnHtml();
        }
    }
    function work_order_modalOne(){
        $title = "新建工单";
        $btnTitle = "提交工单";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        if($gettype=="Edit"){
            $redisName="workOrderList";
            $resultData=$this->workOrderCom->redis_one($redisName,"id",$id);
        }
        
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"workOrderModal",
        ];
        $this->modalOne($modalPara);
    }
    function manageWorderInfo($param=[]){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");
        $roleId = $param['roleId'] ? $param['roleId'] : session("roleId");
  
        if($reqType=="work_orderAdd"){
            $datas['add_time']=time();
            $datas['user_id']=$datas['user_id'] ? $datas['user_id'] : session('userId');
            $this->configCom=getComponent('Config');
            switch ($datas['type']) {
                case 1: case 3: default: //个人信息的流程
                    if($datas['type']==1){
                        $process_type = "wuser_process";
                    }else{
                        $process_type = "wother_process";
                    }
                    
                    $resultData = $this->configCom->get_val($process_type);
                    
                    if(!empty($resultData['value'])){
                        $processId = json_decode($resultData['value'],true)['processIds'];
                    }else{
                        $processId = 0;
                    }
                    unset($datas['relation_project']);
                    break;
                case 2:
                    $projectResult = getComponent('Project')->getOne(['where'=>['projectId'=>$datas['relation_project']],'fields'=>'process_id']);
                    $processId = $projectResult['list']['process_id'];
                    # code...
                    break;
            }
            //添加时必备数据 ($vtabId=false,$leader=0,$roleId=0,$processIds=0)
            $examines = getComponent('Process')->getExamine(I("vtabId"),0,$roleId,$processId);
            // print_r($examines);exit;
            $datas['process_id'] = $examines["process_id"];
            $datas['examine'] = $examines["examine"];
            $rolePlace = $examines['place'];
            if($rolePlace!==false){
                $datas['process_level']=$rolePlace+2;
                if(count(explode(",",$examines['examine'])) <= ($rolePlace+1)){
                    $datas['status'] = 1;
                }else{
                    $datas['status'] = 2;
                }
            }else{
                $datas['process_level']=$process["place"] > 0 ? $process["place"] : 1;
            }
            unset($datas['id']);
            return $datas;
        }else if($reqType=="work_orderEdit"){
            $where=["id"=>$datas['id']];
            $data=[];
            
            foreach (['status'] as  $key) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
            if(isset($datas['status'])){
                $data['status'] = $datas['status'] == 3 ? 0 : $datas['status'];
            }
            $data['upate_time']=time();
            
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    function work_orderList(){
        // $excelData = excelImport(['filename'=>"Uploads/Project/20180927/test.xlsx"]);
        // print_r($excelData);
        // exit;
        $data=I("data");
        $p=I("p")?I("p"):1;
        $roleId = session("roleId");
        $export = I('export');
        $where=[];
        if($this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME]<7){
            $where['_string'] = "user_id = ".session('userId')." OR (FIND_IN_SET({$roleId},examine) >0 AND FIND_IN_SET({$roleId},examine) <= process_level)";
   
        }
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
        $parameter=[
            'fields'=>"*,FROM_UNIXTIME(add_time,'%Y-%m-%d') add_date,FIND_IN_SET({$roleId},examine) place",
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$pageSize,
            'orderStr'=>"id DESC",
            "joins"=>[
                "LEFT JOIN (SELECT projectId,name project_name FROM v_project ) p ON p.projectId = relation_project ",
                "LEFT JOIN (SELECT userId,userName user_name FROM v_user) u ON u.userId = user_id",
            ]
        ];
        $listResult=$this->workOrderCom->getList($parameter);
        
        // excelExport($listResult['list'],$schema);exit;
        // echo M()->_sql();"--";
        $config=[];
        if($export){
            $config = ['control'=>CONTROLLER_NAME];
        }
        $this->tablePage($listResult,'Public/publicTable/workOrderList',"workOrderList",$pageSize,'',$config);
    }
    function work_orderAdd(){
        $datas = I("data");
        $info = $this->manageWorderInfo();
        if($info){
            $insertResult=$this->workOrderCom->insert($info);
            if(isset($insertResult->errCode) && $insertResult->errCode==0){
                $this->ApprLogCom->createApp($this->workOrderCom->tableName(),$insertResult->data,session("userId"),"");
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }

    function work_orderEdit(){
        $updateInfo=$this->manageWorderInfo();
        $updateResult=$this->workOrderCom->update($updateInfo);
        if(isset($updateResult->errCode) && $updateResult->errCode == 0){
            $this->ApprLogCom->updateStatus($this->workOrderCom->tableName(),$updateInfo["where"]["id"]); 
        }
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-02 09:06:56 
     * @Desc: 工单导入处理 
     */    
    function work_order_import($excelData){
        $insertData = [];
        foreach ($excelData as $index => $excelRow) {
            if($index>0){
                $temp = [];
                foreach ($excelData[0] as $i=>$key) {
                    if($key=="type"){
                        $temp[$key] = array_search($excelRow[$i],["1"=>"个人信息","2"=>"项目相关","3"=>"其他"]);
                    }elseif($key=="user_id"){
                        $temp[$key] = $excelRow[$i];
                        $temp['roleId'] = $this->userCom->getOne(['where'=>['userId'=>$excelRow[$i]],'fields'=>'roleId'])['list']['roleId'];
                    }elseif($key=="relation_project" && $excelRow[$i]!=""){
                        $temp[$key] = $this->AProject->_getOption("relation_project",$excelRow[$i],['key_type'=>'code'])[0]['projectId'];
                    }else{
                        $temp[$key] = $excelRow[$i];
                    }
                }
                $temp = $this->manageWorderInfo(["data"=>$temp,"reqType"=>"work_orderAdd","roleId"=>0]);
                array_push($insertData,$temp);
            }
        }
        return $insertData;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-02 22:44:00 
     * @Desc: 工单数据导出处理 
     */    
    function work_order_export($excelData){
        $schema=[
            'user_name' => ['name'=>'用户名'],
            'type' => ['name'=>'工单类型'],
            'project_name' => ['name'=>'关联项目'],
            'title' => ['name'=>'工单标题'],
            'add_date' => ['name'=>'申请时间'],
            'content' => ['name'=>'工单内容'],
            'status' => ['name'=>'状态'],
        ];
        foreach ($excelData as $index => $val) {
            foreach ($val as $key => $value) {
                if($key=="type"){
                    $excelData[$index][$key] = $this->worderType[$value];
                }else if($key=="status"){
                    $excelData[$index][$key] = $this->statusType[$value];
                }
            }
        }
        $exportData = ['data'=>$excelData,'schema'=> $schema,'fileName'=>'工单数据'];
        return $exportData ;
    }
}