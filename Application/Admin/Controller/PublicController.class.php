<?php
namespace Admin\Controller;

class PublicController extends BaseController{

    public function _initialize() {
        $this->AUser=A('User');
        parent::_initialize();
        $this->MesCom=getComponent('Message');
        $this->workOrderCom=getComponent('WorkOrder');
        $this->pubFilesCom=getComponent('PublicFiles');
        $this->AProject=A('Project');
        $this->worderType = ["1"=>"个人信息","2"=>"项目相关","3"=>"其他"];
    }

    function messageControl(){
        $reqType=I('reqType');
        $this->assign('userArr',$this->Com ->get_option("to_user"));
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
        $data=I("data");
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
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
        $parameter=[
            "where" => $where,
            'page'=>$p,
            'fields'=>"*,FROM_UNIXTIME(add_time,'%Y-%m-%d %H:%i:%s') date_time",
            'pageSize'=>$pageSize,
            'orderStr'=>"`status` ASC,add_time DESC",
            'groupBy'=>$groupBy,
            "joins"=>[
                'LEFT JOIN (SELECT userId,userName user_name FROM v_user) u ON u.userId='.$userKey,
            ],
        ];
        
        $listResult = $this->MesCom->getList($parameter);
        $this->assign("type",$type);
        // print_r($parameter);
        $this->tablePage($listResult,'Public/publicTable/messageList',"lastLoginList",$pageSize,"",["bigSize"=>false,"returnData"=>true]);
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
        $this->assign('projectArr',$this->Com ->get_option("relation_project"));
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
            //添加时审批流数据
            $examines = getComponent('Process')->getExamine(I("vtabId"),0,$roleId,$processId);
            $datas['process_id'] = $examines["process_id"];
            $datas['examine'] = $examines["examine"];
            $datas['process_level'] = $examines["process_level"];
            $datas['status'] = $examines["status"];

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
                        $temp[$key] = $this->Com ->get_option("relation_project",$excelRow[$i],['key_type'=>'code'])[0]['projectId'];
                    }else{
                        $temp[$key] = $excelRow[$i];
                    }
                }
                $tempData = $this->manageWorderInfo(["data"=>$temp,"reqType"=>"work_orderAdd","roleId"=>0]);
                if(isset($temp["id"])){
                    $tempData["id"] = $temp["id"];
                }
                array_push($insertData,$tempData);
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
    /** 
     * @Author: vition 
     * @Date: 2018-11-09 14:11:23 
     * @Desc: 共享文件管理 
     */    
    
    /** 
     * @Author: vition 
     * @Date: 2018-11-09 14:13:12 
     * @Desc: 共享文件类型 
     */    
    function pubFilesType(){
        $reqType=I('reqType');
        $this->assign("controlName","pub_files_type");
        $this->assign('tableName',$this->basicCom->tableName());//删除数据的时候需要
        if($reqType){
            $this->$reqType();
        }else{         
            $this->returnHtml();
        }
    }
    //显示文件类型
    function pub_files_type_modalOne(){
        $title = "新建文件类型";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑文件类型";
            $btnTitle = "保存数据";
            $redisName="pubFilesTypeList";
            $resultData=$this->basicCom->redis_one($redisName,"basicId",$id);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"pubFilesTypeModal",
        ];
        $this->modalOne($modalPara);
    }
    //文件类型列表
    function pub_files_typeList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $export = I('export');
        $where=["class"=>"pub_files"];

        foreach (['name','alias'] as $key) {
            if($data[$key]){
                $where[$key]=['LIKE','%'.$data[$key].'%'];
            }
        }
        if(isset($data['status'])){
            $where['status']=$data['status'];
        }else{
            $where['status']=["lt",3];
        }
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$pageSize,
            'orderStr'=>"basicId DESC",
        ];
        if($export){
            $config = ['control'=>CONTROLLER_NAME];
        }
        $basicResult=$this->basicCom->getBasicList($parameter);
        $this->tablePage($basicResult,'Public/publicTable/pubFilesTypeList',"pubFilesTypeList",$pageSize,'',$config);
    }
    //控制文件类型
    function managepubFilesTypeInfo($param=[]){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");
        if($reqType=="pub_files_typeAdd"){
            $datas['class']="pub_files";
            $datas['status']=1;
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="pub_files_typeEdit"){
            $where=["basicId"=>$datas['basicId']];
            $data=[];
            foreach (['name','alias','remark','status'] as $key) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    //添加文件类型
    function pub_files_typeAdd(){
        $info=$this->managepubFilesTypeInfo();
        if($info){
            $insertResult=$this->basicCom->insertBasic($info);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    } 
    //编辑文件类型
    function pub_files_typeEdit(){
        $info=$this->managepubFilesTypeInfo();
        $updateResult=$this->basicCom->updateBasic($info);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    //文件类型导入
    function pub_files_type_import($excelData){
        $insertData = [];
        foreach ($excelData as $index => $excelRow) {
            if($index>0){
                $temp = [];
                foreach ($excelData[0] as $i=>$key) {
                    $temp[$key] = $excelRow[$i];
                }
                $tempData = $this->manageBrandInfo(["data"=>$temp,"reqType"=>"pub_files_typeAdd"]);
                if(isset($temp["basicId"])){
                    $tempData["basicId"] = $temp["basicId"];
                }
                array_push($insertData,$tempData);
            }
        }
        return $insertData;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-04 08:48:49 
     * @Desc: 文件类型导出 
     */    
    function pub_files_type_export($excelData){
        $schema=[
            'basicId' => ['name'=>'文件类型id'],
            'name' => ['name'=>'文件类型名称'],
            'alias' => ['name'=>'文件类型别名'],
            'remark' => ['name'=>'备注'],
            'sort' => ['name'=>'排序'],
            'status' => ['name'=>'状态'],
        ];
        foreach ($excelData as $index => $val) {
            foreach ($val as $key => $value) {
                if($key=="status"){
                    $excelData[$index][$key] = $this->statusType[$value];
                }
            }
        }
        $exportData = ['data'=>$excelData,'schema'=> $schema,'fileName'=>'文件类型数据表'];
        return $exportData ;
    } 
    /** 
     * @Author: vition 
     * @Date: 2018-11-09 15:58:29 
     * @Desc:  文件管理
     */    
    function pubFiles(){
        $reqType=I('reqType');

        $this->assign("controlName","pub_files");
        // $this->assign("pubFilesType",$this->worderType);
        $this->assign("tableName",$this->pubFilesCom->tableName());
        $where=["class"=>"pub_files"];
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>9999999,
            'orderStr'=>"basicId DESC",
        ];
        $basicResult = $this->basicCom->getList($parameter);
        $filesType = array_combine(array_column($basicResult['list'],"basicId"),array_column($basicResult['list'],"name"));
        $this->assign("filesType",$filesType);
        if($reqType){
            $this->$reqType();
        }else{         
            $this->returnHtml();
        }
    }
    //显示文件
    function pub_files_modalOne(){
        $title = "新建文件";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        // print_r($this->AUser->getRoles(1));
        $this->assign("groupData",$this->AUser->getRoles(1));
	    $this->assign("roleData",$this->AUser->getRoles(2));
        if($gettype=="Edit"){
            $title = "编辑文件";
            $btnTitle = "保存数据";
            $redisName="pubFilesList";
            $resultData=$this->pubFilesCom->redis_one($redisName,"id",$id);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"pubFilesModal",
        ];
        $this->modalOne($modalPara);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-11-09 15:59:36 
     * @Desc: 公共文件列表 
     */    
    function pub_filesList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $export = I('export');
        $where=[];
        $user_id = session("userId");
        $role_id = session("roleId");
        $role_pid = session("rolePid");
        foreach (['file_name'] as $key) {
            if($data[$key]){
                $where[$key]=['LIKE','%'.$data[$key].'%'];
            }
        }
        if(isset($data['file_type'])){
            $where['file_type'] = $data['file_type'];
        }
        $where['_string'] = " (uids ='' AND gids ='') OR ( FIND_IN_SET({$role_id},uids) > 0 OR FIND_IN_SET({$role_id},gids) > 0 OR FIND_IN_SET({$role_pid},gids) > 0 ) OR (user_id = {$user_id})";
 
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$pageSize,
            'orderStr'=>"id DESC",
            'joins' => [
                'LEFT JOIN (SELECT userId,userName user_name FROM v_user) u ON u.userId= user_id',
                'LEFT JOIN (SELECT basicId,name type_name FROM v_basic) b ON b.basicId= file_type',
            ],
        ];
        if($export){
            $config = ['control'=>CONTROLLER_NAME];
        }
        $basicResult=$this->pubFilesCom->getList($parameter);
        $this->tablePage($basicResult,'Public/publicTable/pubFilesList',"pubFilesList",$pageSize,'',$config);
    }
    function managepubFilesInfo($param=[]){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");

        $datas['uids'] = "";
        $datas['gids'] = "";
        $datas['user_id'] = session("userId");
        
        if($datas['roleType'] == 1){
            $datas['gids'] = implode(",",$datas['groups']);
        }if($datas['roleType'] == 2){
            $datas['uids'] = implode(",",$datas['roles']);
        }
        unset($datas['groups']);
        unset($datas['roles']);
        if($reqType=="pub_filesAdd"){
            $datas['add_time'] = time();
            unset($datas['id']);
            return $datas;
        }else if($reqType=="pub_filesEdit"){
            $where=["id"=>$datas['id']];
            $data=[];
            foreach (['user_id','file_name','file_path','file_type','file_size','uids','gids','status'] as $key) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    //文件添加
    function pub_filesAdd(){
        $info=$this->managepubFilesInfo();
        $insertResult=$this->pubFilesCom->insert($info);
        $this->ajaxReturn(['errCode'=>$insertResult->errCode,'error'=>getError($insertResult->errCode)]);
    } 
    //编辑文件
    function pub_filesEdit(){
        $info=$this->managepubFilesInfo();
        $updateResult=$this->pubFilesCom->update($info);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    //文件导入
    function pub_files_import($excelData){
        $insertData = [];
        foreach ($excelData as $index => $excelRow) {
            if($index>0){
                $temp = [];
                foreach ($excelData[0] as $i=>$key) {
                    $temp[$key] = $excelRow[$i];
                }
                $tempData = $this->pubFilesCom(["data"=>$temp,"reqType"=>"pub_filesAdd"]);
                if(isset($temp["id"])){
                    $tempData["id"] = $temp["id"];
                }
                $tempData["user_id"] = session("userId");
                $tempData["add_time"] = time();
                array_push($insertData,$tempData);
            }
        }
        return $insertData;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-04 08:48:49 
     * @Desc: 文件导出 
     */    
    function pub_files_export($excelData){
        $schema=[
            'id' => ['name'=>'文件id'],
            'file_name' => ['name'=>'文件名称'],
            'user_id' => ['name'=>'上传者'],
            'type_name' => ['name'=>'文件类别'],
            'file_size' => ['name'=>'文件大小'],
            'add_time' => ['name'=>'上传时间'],
            'sort' => ['name'=>'排序'],
            'status' => ['name'=>'状态'],
        ];
        foreach ($excelData as $index => $val) {
            foreach ($val as $key => $value) {
                if($key=="status"){
                    $excelData[$index][$key] = $this->statusType[$value];
                }elseif($key=="file_size"){
                    $excelData[$index][$key] = fsizeFormat($value);
                }
            }
        }
        $exportData = ['data'=>$excelData,'schema'=> $schema,'fileName'=>'文件类型数据表'];
        return $exportData ;
    } 
}