<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-05-20 22:24:35 
 * @Desc: 基本数据管理 
 */
class BasicController extends BaseController{
    public function _initialize() {
        $this->statusType = [0=>"未启用",1=>"启用",3=>"无效",4=>"删除"];
        parent::_initialize();
        // $this->basicCom=getComponent('Basic');
        $this->fieldCom=getComponent('Field');
        $this->assign('tableName',$this->basicCom->tableName());
        Vendor("levelTree.levelTree");
        $this->levelTree=new \levelTree();
    }
    //品牌管理开始
    /** 
     * @Author: vition 
     * @Date: 2018-05-20 22:30:14 
     * @Desc: 品牌管理 
     */    
    function brandControl(){
        $reqType=I('reqType');
        $this->assign("controlName","basic_brand");
        // $this->assign('tableName',$this->basicCom->tableName());//删除数据的时候需要
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function basic_brand_modalOne(){
        $title = "新建品牌";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑品牌";
            $btnTitle = "保存数据";
            $redisName="brandList";
            $resultData=$this->basicCom->redis_one($redisName,"basicId",$id);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"brandModal",
        ];
        $this->modalOne($modalPara);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-20 22:45:25 
     * @Desc: 品牌列表 
     */    
    function basic_brandList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $export = I('export');
        $where=["class"=>"brand"];

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
        $this->tablePage($basicResult,'Basic/basicTable/brandList',"brandList",$pageSize,'',$config);
        // if($basicResult){
        //     $basicRed="brandList";
        //     $this->Redis->set($basicRed,json_encode($basicResult['list']),3600);
        //     $page = new \Think\VPage($basicResult['count'], $this->pageSize);
        //     $pageShow = $page->show();
            
        //     $this->assign('list',$basicResult['list']);
        //     $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('Basic/basicTable/brandList'),'page'=>$pageShow,"count"=>$count]);
        // }
        // $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);
    }
    function manageBrandInfo($param=[]){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");
        if($reqType=="basic_brandAdd"){
            $datas['class']="brand";
            $datas['status']=1;
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="basic_brandEdit"){
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
    function basic_brandAdd(){
        $brandInfo=$this->manageBrandInfo();
        if($brandInfo){
            $insertResult=$this->basicCom->insertBasic($brandInfo);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    } 
    function basic_brandEdit(){
        $brandInfo=$this->manageBrandInfo();
        $updateResult=$this->basicCom->updateBasic($brandInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-03 08:58:30 
     * @Desc: 品牌导入 
     */    
    function basic_brand_import($excelData){
        $insertData = [];
        foreach ($excelData as $index => $excelRow) {
            if($index>0){
                $temp = [];
                foreach ($excelData[0] as $i=>$key) {
                    $temp[$key] = $excelRow[$i];
                }
                $tempData = $this->manageBrandInfo(["data"=>$temp,"reqType"=>"basic_brandAdd"]);
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
     * @Desc: 品牌导出 
     */    
    function basic_brand_export($excelData){
        $schema=[
            'basicId' => ['name'=>'品牌id'],
            'name' => ['name'=>'品牌名称'],
            'alias' => ['name'=>'品牌别名'],
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
        $exportData = ['data'=>$excelData,'schema'=> $schema,'fileName'=>'品牌数据表'];
        return $exportData ;
    } 
    //品牌管理结束
    //场地管理开始
    function fieldControl(){
        $reqType=I('reqType');
        $this->assign("controlName","basic_field");
        $this->assign("tableName",$this->fieldCom->tableName());
        $this->assign("provinceArr",$this->basicCom->get_provinces());
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function basic_field_modalOne(){
        $title = "新建场地";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑场地";
            $btnTitle = "保存数据";
            $redisName="fieldList";
            $resultData=$this->fieldCom->redis_one($redisName,"id",$id);
        }
        $resultData["citys"] = $this->basicCom->get_citys($resultData["province"]);
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"fieldModal",
        ];
        $this->modalOne($modalPara);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-20 22:45:25 
     * @Desc: 品牌列表 
     */    
    function basic_fieldList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $export = I('export');
        $where=[];
        foreach (['name','alias'] as $key) {
            if(isset($data[$key])){
                $where[$key]=['LIKE','%'.$data[$key].'%'];
            }
        }
        foreach (['province','city'] as $key) {
            if(isset($data[$key])){
                $where[$key]=$data[$key];
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
            'orderStr'=>"id DESC",
            'joins'=>[
                "LEFT JOIN (SELECT pid ,province province_name FROM v_province ) p ON p.pid = province",
                "LEFT JOIN (SELECT cid ctid ,city city_name,pid cpid FROM v_city ) ct ON ct.ctid = city AND ct.cpid = province",
            ]
        ];
        if($export){
            $config = ['control'=>CONTROLLER_NAME];
        }
        
        $basicResult=$this->fieldCom->getList($parameter);
        $this->tablePage($basicResult,'Basic/basicTable/fieldList',"fieldList",$pageSize,'',$config);
        // if($basicResult){
        //     $basicRed="fieldList";
        //     $this->Redis->set($basicRed,json_encode($basicResult['list']),3600);
        //     $page = new \Think\VPage($basicResult['count'], $this->pageSize);
        //     $pageShow = $page->show();
            
        //     $this->assign('list',$basicResult['list']);
        //     $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('Basic/basicTable/fieldList'),'page'=>$pageShow,"count"=>$count]);
        // }
        // $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);
    }
    function manageFieldInfo($param=[]){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");
        if($reqType=="basic_fieldAdd"){
            $datas['status'] = 1;
            unset($datas['id']);
            return $datas;
        }else if($reqType=="basic_fieldEdit"){
            $where=["id"=>$datas['id']];
            $data=[];
            foreach (['name','alias','remark','status','province','city'] as $key) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    function basic_fieldAdd(){
        $fieldInfo=$this->manageFieldInfo();
        if($fieldInfo){
            $insertResult=$this->fieldCom->insert($fieldInfo);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    } 
    function basic_fieldEdit(){
        $fieldInfo=$this->manageFieldInfo();
        $updateResult=$this->fieldCom->update($fieldInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-03 08:58:30 
     * @Desc: 场地导入 
     */    
    function basic_field_import($excelData){
        $insertData = [];
        foreach ($excelData as $index => $excelRow) {
            if($index>0){
                $temp = [];
                foreach ($excelData[0] as $i=>$key) {
                    if($key=="province"){
                        $temp[$key] = M("province")->where(['province'=>$excelRow[$i]])->find()['pid'];
                    }elseif($key=="city"){
                        $temp[$key] = M("city")->where(["city"=>$excelRow[$i],'pid'=>$temp["province"]])->find()['cid'];
                    }else{
                        $temp[$key] = $excelRow[$i];
                    }
                }
                $tempData = $this->manageFieldInfo(["data"=>$temp,"reqType"=>"basic_fieldAdd"]);
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
     * @Date: 2018-10-04 08:48:49 
     * @Desc: 场地导出 
     */    
    function basic_field_export($excelData){
        $schema=[
            'id' => ['name'=>'场地id'],
            'name' => ['name'=>'场地名称'],
            'alias' => ['name'=>'场地别名'],
            'province_name' => ['name'=>'场地所在省份'],
            'city_name' => ['name'=>'场地所在城市'],
            'remark' => ['name'=>'备注'],
            'status' => ['name'=>'状态'],
        ];
        foreach ($excelData as $index => $val) {
            foreach ($val as $key => $value) {
                if($key=="status"){
                    $excelData[$index][$key] = $this->statusType[$value];
                }
            }
        }
        $exportData = ['data'=>$excelData,'schema'=> $schema,'fileName'=>'场地数据表'];
        return $exportData ;
    } 
    //场地管理结束
    //项目阶段管理开始
    function stageControl(){
        $reqType=I('reqType');
        $this->assign("controlName","basic_stage");
        // $this->assign('tableName',$this->basicCom->tableName());//删除数据的时候需要
        if($reqType){
            $this->$reqType();
        }else{
            
            $this->returnHtml();
        }
    }
    function basic_stage_modalOne(){
        $title = "新建项目阶段";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑项目阶段";
            $btnTitle = "保存数据";
            $redisName="stageList";
            $resultData=$this->basicCom->redis_one($redisName,"basicId",$id);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"stageModal",
        ];
        $this->modalOne($modalPara);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-20 22:45:25 
     * @Desc: 品牌列表 
     */    
    function basic_stageList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $export = I('export');
        $where=["class"=>"stage"];

        foreach (['name','alias'] as $key) {
            if(isset($data[$key])){
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
        $basicResult=$this->basicCom->getBasicList($parameter);
        if($export){
            $config = ['control'=>CONTROLLER_NAME];
        }
        $this->tablePage($basicResult,'Basic/basicTable/stageList',"stageList",$pageSize,'',$config);
        // if($basicResult){
        //     $basicRed="stageList";
        //     $this->Redis->set($basicRed,json_encode($basicResult['list']),3600);
        //     $page = new \Think\VPage($basicResult['count'], $this->pageSize);
        //     $pageShow = $page->show();
            
        //     $this->assign('list',$basicResult['list']);
        //     $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('Basic/basicTable/stageList'),'page'=>$pageShow,"count"=>$count]);
        // }
        // $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);
    }
    function manageStageInfo($param=[]){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");
        if($reqType=="basic_stageAdd"){
            $datas['class']="stage";
            $datas['status']=1;
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="basic_stageEdit"){
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
    function basic_stageAdd(){
        $stageInfo=$this->manageStageInfo();
        if($stageInfo){
            $insertResult=$this->basicCom->insertBasic($stageInfo);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    } 
    function basic_stageEdit(){
        $stageInfo=$this->manageStageInfo();
        $updateResult=$this->basicCom->updateBasic($stageInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-03 08:58:30 
     * @Desc: 项目导入 
     */    
    function basic_stage_import($excelData){
        $insertData = [];
        foreach ($excelData as $index => $excelRow) {
            if($index>0){
                $temp = [];
                foreach ($excelData[0] as $i=>$key) {
                    $temp[$key] = $excelRow[$i];
                }
                $tempData = $this->manageStageInfo(["data"=>$temp,"reqType"=>"basic_stageAdd"]);
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
     * @Desc: 项目阶段导出 
     */    
    function basic_stage_export($excelData){
        $schema=[
            'basicId' => ['name'=>'项目阶段id'],
            'name' => ['name'=>'项目阶段名称'],
            'alias' => ['name'=>'项目阶段别名'],
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
        $exportData = ['data'=>$excelData,'schema'=> $schema,'fileName'=>'项目阶段数据表'];
        return $exportData ;
    } 
    //项目阶段管理结束
    //项目类型管理结束
    function projectTypeControl(){
        $reqType=I('reqType');
        $this->assign("controlName","basic_projectType");
        // $this->assign('tableName',$this->basicCom->tableName());//删除数据的时候需要
        if($reqType){
            $this->$reqType();
        }else{
            
            $this->returnHtml();
        }
    }
    function basic_projectType_modalOne(){
        $title = "新建项目类型";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑项目类型";
            $btnTitle = "保存数据";
            $redisName="projectTypeList";
            $resultData=$this->basicCom->redis_one($redisName,"basicId",$id);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"projectTypeModal",
        ];
        $this->modalOne($modalPara);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-20 22:45:25 
     * @Desc: 品牌列表 
     */    
    function basic_projectTypeList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $export = I('export');
        $where=["class"=>"projectType"];

        foreach (['name','alias'] as $key) {
            if(isset($data[$key])){
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
        $basicResult=$this->basicCom->getBasicList($parameter);
        if($export){
            $config = ['control'=>CONTROLLER_NAME];
        }
        $this->tablePage($basicResult,'Basic/basicTable/projectTypeList',"projectTypeList",$pageSize,'',$config);
        // if($basicResult){
        //     $basicRed="projectTypeList";
        //     $this->Redis->set($basicRed,json_encode($basicResult['list']),3600);
        //     $page = new \Think\VPage($basicResult['count'], $this->pageSize);
        //     $pageShow = $page->show();
            
        //     $this->assign('projectTypeList',$basicResult['list']);
        //     $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('Basic/basicTable/projectTypeList'),'page'=>$pageShow,"count"=>$count]);
        // }
        // $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);
    }

    // function projectTypeOne(){
    //     $id	=I("id");
    //     $parameter=[
    //         'basicId'=>$id,
    //     ];
    //     $blistRed="projectTypeList";
    //     $projectTypeList=$this->Redis->get($blistRed);
    //     $blist=[];
    //     if($projectTypeList){
    //         foreach ($projectTypeList as $projectType) {
    //            if($projectType['basicId']==$id){
    //             $blist=$projectType;
    //             break;
    //            }
    //         }
    //     }
    //     if(empty($blist)){
    //         $basicResult=$this->basicCom->getOne($parameter);
    //         if($basicResult->errCode==0){
    //             $blist=$basicResult->data;
    //         }
    //     }
    //     if(!empty($blist)){
    //         $this->ajaxReturn(['errCode'=>0,'info'=>$blist]);
    //     }
    //     $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    // }
    function manageProjectTypeInfo($param=[]){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");
        if($reqType=="basic_projectTypeAdd"){
            $datas['class']="projectType";
            $datas['status']=1;
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="basic_projectTypeEdit"){
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
    function basic_projectTypeAdd(){
        $projectTypeInfo=$this->manageProjectTypeInfo();
        if($projectTypeInfo){
            $insertResult=$this->basicCom->insertBasic($projectTypeInfo);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    } 
    function basic_projectTypeEdit(){
        $projectTypeInfo=$this->manageProjectTypeInfo();
        $updateResult=$this->basicCom->updateBasic($projectTypeInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-03 08:58:30 
     * @Desc: 项目类型导入 
     */    
    function basic_projectType_import($excelData){
        $insertData = [];
        foreach ($excelData as $index => $excelRow) {
            if($index>0){
                $temp = [];
                foreach ($excelData[0] as $i=>$key) {
                    $temp[$key] = $excelRow[$i];
                }
                $tempData = $this->manageProjectTypeInfo(["data"=>$temp,"reqType"=>"basic_projectTypeAdd"]);
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
     * @Desc: 项目类型导出 
     */    
    function basic_projectType_export($excelData){
        $schema=[
            'basicId' => ['name'=>'项目类型id'],
            'name' => ['name'=>'项目类型名称'],
            'alias' => ['name'=>'项目类型别名'],
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
        $exportData = ['data'=>$excelData,'schema'=> $schema,'fileName'=>'项目类型数据表'];
        return $exportData ;
    } 
    //项目类型管理结束
    //执行类型管理execute开始
    function executeControl(){
        $reqType=I('reqType');
        $this->assign("controlName","basic_execute");
        // $this->assign('tableName',$this->basicCom->tableName());//删除数据的时候需要
        $exe_root=$this->basicCom->get_exe_root();
        $root_arr=array_combine(array_column($exe_root,"basicId"),array_column($exe_root,"name"));
        $this->assign("root_arr",$root_arr);
        $this->assign("exe_root",$exe_root);
        if($reqType){
            $this->$reqType();
        }else{
            
            $this->returnHtml();
        }
    }
    function basic_execute_modalOne(){
        $title = "新建执行类型";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑执行类型";
            $btnTitle = "保存数据";
            $redisName="executeList";
            $resultData=$this->basicCom->redis_one($redisName,"basicId",$id);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"executeModal",
        ];
        $this->modalOne($modalPara);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-20 22:45:25 
     * @Desc: 品牌列表 
     */    
    function basic_executeList(){
        $this->ajaxReturn(["tree"=>$this->getExecuteTree()]);
        // $data=I("data");
        // $p=I("p")?I("p"):1;
        // $where=["class"=>"execute"];
        // if($data['pId']){
        //     $where['pId']=['EQ',$data['pId']];
        // }
        // if($data['name']){
        //     $where['name']=['LIKE','%'.$data['name'].'%'];
        // }
        // if($data['alias']){
        //     $where['alias']=['LIKE','%'.$data['alias'].'%'];
        // }
        // if(isset($data['status'])){
        //     $where['status']=$data['status'];
        // }else{
        //     $where['status']=["lt",3];
        // }
        // $parameter=[
        //     'where'=>$where,
        //     'page'=>$p,
        //     'pageSize'=>$this->pageSize,
        //     'orderStr'=>"basicId DESC",
        // ];
        // $basicResult=$this->basicCom->getBasicList($parameter);
        // $this->tablePage($basicResult,'Basic/basicTable/executeList',"executeList");
    }
    function getExecuteTree(){
        $parameter=[
            'where'=>["class"=>"execute"],
            'page'=>0,
            'pageSize'=>9999,
            'orderStr'=>'level DESC,sort ASC',
        ];
        $executeResult=$this->basicCom->getList($parameter);
        $executeTree=[];
        $level=[];
        
        $executeArray=$executeResult["list"];
        foreach ($executeArray AS $key => $executeInfo) {
            $level[$executeInfo["level"]][$executeInfo["Pid"]][]= $executeInfo;
            unset($executeArray[$key]);
        }
        $this->Redis->set("executeArray",json_encode($executeResult["list"]),3600);
        asort($level);
        
        $this->levelTree->setKeys(["idName"=>"basicId","pidName"=>"pId"]);
        $this->levelTree->setReplace(["name"=>"text","basicId"=>"id"]);
        $this->levelTree->switchOption(["beNode"=>false,"idAsKey"=>false]);
        $executeTree=$this->levelTree->createTree($executeResult["list"]);
        return $executeTree;
    }

    // function executeOne(){
    //     $id	=I("id");
    //     $parameter=[
    //         'basicId'=>$id,
    //     ];
    //     $blistRed="executeList";
    //     $executeList=$this->Redis->get($blistRed);
    //     $blist=[];
    //     if($executeList){
    //         foreach ($executeList as $execute) {
    //            if($execute['basicId']==$id){
    //             $blist=$execute;
    //             break;
    //            }
    //         }
    //     }
    //     if(empty($blist)){
    //         $basicResult=$this->basicCom->getOne($parameter);
    //         if($basicResult->errCode==0){
    //             $blist=$basicResult->data;
    //         }
    //     }
    //     if(!empty($blist)){
    //         $this->ajaxReturn(['errCode'=>0,'info'=>$blist]);
    //     }
    //     $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    // }
    function manageExecuteInfo($param=[]){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");

        if(isset($datas['pId'])){
            if($datas['pId']>0){
                $nodePInfo=$this->getPid('execute',$datas['pId']);
                // print_r($nodePInfo);
                $datas['level'] = $nodePInfo['level']+1;
            }else{
                $datas['level'] = 1;
            }
        }
        if($reqType=="basic_executeAdd"){
            $datas['class']="execute";
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="basic_executeEdit"){
            $where=["basicId"=>$datas['basicId']];
            $data=[];
            foreach (['name','alias','remark','pId',"level",'sort'] as $key ) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    function getPid($class,$pid){
        return $this->basicCom->getOne(['where'=>['class'=>$class,'basicId'=>$pid]])['list'];
    }
    function basic_executeAdd(){
        $executeInfo=$this->manageExecuteInfo();
        if($executeInfo){
            $insertResult=$this->basicCom->insertBasic($executeInfo);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    } 
    function basic_executeEdit(){
        $executeInfo=$this->manageExecuteInfo();
        // print_r($executeInfo);
        $updateResult=$this->basicCom->updateBasic($executeInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    //执行类型管理execute结束
    //费用类别管理开始
    function feeTypeControl(){
        $reqType=I('reqType');
        $this->assign("controlName","basic_feeType");
        // $this->assign('tableName',$this->basicCom->tableName());//删除数据的时候需要
        $fee_t_main=$this->basicCom->get_class_data("FTMClass");//费用类型主类
        $main_array=array_combine(array_column($fee_t_main,"basicId"),array_column($fee_t_main,"name"));
        $this->assign("fee_main",$fee_t_main);
        $this->assign("main_array",$main_array);
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function basic_feeType_modalOne(){
        $title = "新建费用类型";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        $this->assign("provinceArr",$this->basicCom->get_provinces());
        if($gettype=="Edit"){
            $title = "编辑费用类型";
            $btnTitle = "保存数据";
            $redisName="feeTypeList";
            $resultData=$this->basicCom->redis_one($redisName,"basicId",$id);
            $param = [
                "where" => ['class'=> 'regLimit','pId'=> $id,]
            ];
            $limitRes = $this->basicCom->getList($param);
            // print_r($limitRes);exit;
            $resultData["limits"] = $limitRes["list"];
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"feeTypeModal",
        ];
        $option='<option value="0">根Root</option>';
        foreach ($this->basicCom->getFeeTypeTree() as $key => $value) {
            // print_r($value);
            $option.=$this->basicCom->getfeeType($value,0);
        }
        // print_r($option);
        $this->assign("pidoption",$option);
        $this->modalOne($modalPara);
    }
    // function feeTypeOne(){
    //     $basicId = I("basicId");

    //     $feeTypeInfo=$this->getFeeTypeOne($basicId);
    //     if(!empty($feeTypeInfo)){
    //         $this->ajaxReturn(['errCode'=>0,'info'=>$feeTypeInfo]);
    //     }
    //     $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);

    //     $parameter=[
    //         'basicId'=>$id,
    //     ];
    //     $blistRed="feeTypeList";
    //     $feeTypeList=$this->Redis->get($blistRed);
    //     $blist=[];
    //     if($feeTypeList){
    //         foreach ($feeTypeList as $feeType) {
    //            if($feeType['basicId']==$id){
    //             $blist=$feeType;
    //             break;
    //            }
    //         }
    //     }
    //     if(empty($blist)){
    //         $basicResult=$this->basicCom->getOne($parameter);
    //         if($basicResult->errCode==0){
    //             $blist=$basicResult->data;
    //         }
    //     }
    //     if(!empty($blist)){
    //         $this->ajaxReturn(['errCode'=>0,'info'=>$blist]);
    //     }
    //     $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    // }
    function getFeeTypeOne($basicId){
        $parameter=[
            'basicId'=>$basicId,
        ];
        $fListRed="feeTypeArray";
        $feeTypeList=$this->Redis->get($fListRed);
        if($feeTypeList){
            foreach ($feeTypeList as $feeType) {
                if($feeType['basicId']==$basicId){
                    return $feeType;
                }
            }
        }
        $feeTypeResult=$this->basicCom->getFeeTypeOne($parameter);
        if($feeTypeResult->errCode==0){
            return $feeTypeResult->data['list'];
        }
        return [];
    }
    function manageFeeTypeInfo($param=[]){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");
        $feeTypePInfo=$this->getFeeTypeOne($datas['pId']);
        $datas['level']=$feeTypePInfo['level']?($feeTypePInfo['level']+1):1;
        if($reqType=="basic_feeTypeAdd"){
            $datas['class']="feeType";
            $datas['status']="1";
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="basic_feeTypeEdit"){
            $where=["basicId"=>$datas['basicId']];
            $data=[];
            foreach (['name','alias','remark','pId','level',] as $key) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    function basic_feeTypeAdd(){
        $feeTypeInfo=$this->manageFeeTypeInfo();
        $region = $feeTypeInfo["region"];
        $all = count($region);
        $num = 0;
        unset($feeTypeInfo["region"]);
        if($feeTypeInfo){
            $this->basicCom->M()->startTrans();
            $insertResult=$this->basicCom->insertBasic($feeTypeInfo);
            if($insertResult && $insertResult->errCode==0){
                foreach ($region as $regInfo) {
                    $param = [
                        'class'=> 'regLimit',
                        'name'=> implode(",",$regInfo["regions"]),
                        'alias'=> json_encode($regInfo["regionStr"],JSON_UNESCAPED_UNICODE),
                        'pId'=> $insertResult->data,
                        'remark'=> $regInfo["limit_money"],
                        'status'=> 1,
                    ];
                    $limitRes = $this->basicCom->insert($param);
                    if(isset($limitRes->errCode) && $limitRes->errCode == 0){
                        $num ++;
                    }
                }
                if(($num>0 && $num == $all) || ($updateResult->errCode==0)){
                    $this->basicCom->M()->commit();
                    $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
                }
                
            }
            $this->basicCom->M()->rollback();
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    } 
    function basic_feeTypeEdit(){
        $datas = I("data");
        $region = $datas["region"];
        $all = count($region);
        $num = 0;
        $this->basicCom->M()->startTrans();

        $feeTypeInfo=$this->manageFeeTypeInfo();
        // print_r($feeTypeInfo);
        $updateResult=$this->basicCom->updateBasic($feeTypeInfo);
        foreach ($region as $regInfo) {
            if($regInfo["limitId"]>0){
                $param = [
                    "where"=> ["basicId"=>$regInfo["limitId"]],
                    "data" =>[
                        'name'=> implode(",",$regInfo["regions"]),
                        'alias'=> json_encode($regInfo["regionStr"],JSON_UNESCAPED_UNICODE),
                        'remark'=> $regInfo["limit_money"],
                    ],
                ];
                $limitRes = $this->basicCom->update($param);
            }else{
                $param = [
                    'class'=> 'regLimit',
                    'name'=> implode(",",$regInfo["regions"]),
                    'alias'=> json_encode($regInfo["regionStr"],JSON_UNESCAPED_UNICODE),
                    'pId'=> $feeTypeInfo["where"]["basicId"],
                    'remark'=> $regInfo["limit_money"],
                    'status'=> 1,
                ];
                $limitRes = $this->basicCom->insert($param);
            }
            
            if(isset($limitRes->errCode) && $limitRes->errCode == 0){
                $num ++;
            }
        }
        if(($num>0 && $num == $all) || ($updateResult->errCode==0)){
            $this->basicCom->M()->commit();
            $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
        }
        $this->basicCom->M()->rollback();
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-24 06:42:53 
     * @Desc: 返回费用类型的节点 
     */    
    function basic_feeTypeList(){
        $this->ajaxReturn(["tree"=>$this->basicCom->getFeeTypeTree()]);
    }
    //费用类别管理结束
    //承接模块管理开始
    function moduleControl(){
        $reqType=I('reqType');
        $this->assign("controlName","basic_module");
        $this->assign("supTypeList",A('Supplier')->getSupType());
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function basic_module_modalOne(){
        $title = "新建承接模块";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑承接模块";
            $btnTitle = "保存数据";
            $redisName="moduleList";
            $resultData=$this->basicCom->redis_one($redisName,"basicId",$id);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"moduleModal",
        ];
        $this->modalOne($modalPara);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-20 22:45:25 
     * @Desc: 承接模块列表 
     */    
    function basic_moduleList(){
        $export = I('export');
        if($export){
            $p=I("p")?I("p"):1;
            $data=I("data");
            $where=["class"=>"module"];
    
            foreach (['name','alias'] as $key) {
                if(isset($data[$key])){
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
                'orderStr'=>"sort ASC,basicId DESC",
            ];
            
            $config = ['control'=>CONTROLLER_NAME];
            
            $basicResult=$this->basicCom->getBasicList($parameter);
            $this->tablePage($basicResult,'Basic/basicTable/moduleList',"moduleList",$pageSize,'',$config);
        }else{
            $this->ajaxReturn(["tree"=>$this->getModuleTree()]);
        }
        
        // $data=I("data");
        // $p=I("p")?I("p"):1;
        // $where=["class"=>"module"];

        // if($data['name']){
        //     $where['name']=['LIKE','%'.$data['name'].'%'];
        // }
        // if($data['alias']){
        //     $where['alias']=['LIKE','%'.$data['alias'].'%'];
        // }
        // if(isset($data['status'])){
        //     $where['status']=$data['status'];
        // }else{
        //     $where['status']=["lt",3];
        // }
        // $parameter=[
        //     'where'=>$where,
        //     'page'=>$p,
        //     'pageSize'=>$this->pageSize,
        //     'orderStr'=>"basicId DESC",
        //     'joins' => [
        //         'LEFT JOIN (SELECT basicId stId ,name supType_name FROM v_basic WHERE class="supType") st ON st.stId = pId'
        //     ],
        // ];
        // $basicResult=$this->basicCom->getBasicList($parameter);
        // $this->tablePage($basicResult,'Basic/basicTable/moduleList',"moduleList");
    }
    function getModuleTree(){
        $parameter=[
            'where'=>["class"=>["IN",["module",'supType']]],
            'page'=>0,
            'pageSize'=>9999,
            'orderStr'=>'level DESC,sort ASC',
        ];
        $moduleResult=$this->basicCom->getList($parameter);
        $moduleTree=[];
        $level=[];
        
        $moduleArray=$moduleResult["list"];
        foreach ($moduleArray AS $key => $moduleInfo) {
            $level[$moduleInfo["level"]][$moduleInfo["Pid"]][]= $moduleInfo;
            unset($moduleArray[$key]);
        }
        $this->Redis->set("moduleArray",json_encode($moduleResult["list"]),3600);
        asort($level);
        
        $this->levelTree->setKeys(["idName"=>"basicId","pidName"=>"pId"]);
        $this->levelTree->setReplace(["name"=>"text","basicId"=>"id"]);
        $this->levelTree->switchOption(["beNode"=>false,"idAsKey"=>false]);
        $moduleTree=$this->levelTree->createTree($moduleResult["list"]);
        return $moduleTree;
    }
    function manageModuleInfo($param=[]){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");
        $datas['level'] = 2;
        if($reqType=="basic_moduleAdd"){
            $datas['class'] = "module";
            $datas['status'] = 1;
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="basic_moduleEdit"){
            $where=["basicId"=>$datas['basicId']];
            $data=[];
            foreach (['name','alias','pId','remark','status','sort'] as $key ) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    function basic_moduleAdd(){
        $moduleInfo=$this->manageModuleInfo();
        if($moduleInfo){
            $insertResult=$this->basicCom->insertBasic($moduleInfo);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    } 
    function basic_moduleEdit(){
        $moduleInfo=$this->manageModuleInfo();
        $updateResult=$this->basicCom->updateBasic($moduleInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-03 08:58:30 
     * @Desc: 承接模块导入 
     */    
    function basic_module_import($excelData){
        $insertData = [];
        foreach ($excelData as $index => $excelRow) {
            if($index>0){
                $temp = [];
                foreach ($excelData[0] as $i=>$key) {
                    $temp[$key] = $excelRow[$i];
                }
                $tempData = $this->manageModuleInfo(["data"=>$temp,"reqType"=>"basic_moduleAdd"]);
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
     * @Desc: 承接模块导出 
     */    
    function basic_module_export($excelData){
        $schema=[
            'basicId' => ['name'=>'承接模块id'],
            'name' => ['name'=>'承接模块名称'],
            'alias' => ['name'=>'承接模块别名'],
            'pId' => ['name'=>'供应商类别'],
            'remark' => ['name'=>'备注'],
            'sort' => ['name'=>'排序'],
            'status' => ['name'=>'状态'],
        ];
        foreach ($excelData as $index => $val) {
            foreach ($val as $key => $value) {
                if($key=="pId"){
                    $excelData[$index][$key] = $this->basicCom->getOne(['where'=>['basicId'=>$value]])['list']['name'];
                }else if($key=="status"){
                    $excelData[$index][$key] = $this->statusType[$value];
                }
            }
        }
        $exportData = ['data'=>$excelData,'schema'=> $schema,'fileName'=>'供应商承接模块表'];
        return $exportData ;
    } 
    //承接模块管理结束
    //固定支出分类开始
    function expenClasControl(){
        $reqType=I('reqType');
        $this->assign("controlName","expenClas");
        // $this->assign('tableName',$this->basicCom->tableName());//删除数据的时候需要
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function expenClas_modalOne(){
        $title = "新建固定支出类别";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑固定支出类别";
            $btnTitle = "保存数据";
            $redisName="expenClasList";
            $resultData=$this->basicCom->redis_one($redisName,"basicId",$id);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"expenClasModal",
        ];
        $this->modalOne($modalPara);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-20 22:45:25 
     * @Desc: 品牌列表 
     */    
    function expenClasList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $export = I('export');
        $where=["class"=>"expenClas"];

        foreach (['name','alias'] as $key) {
            if(isset($data[$key])){
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
        $basicResult=$this->basicCom->getBasicList($parameter);
        if($export){
            $config = ['control'=>CONTROLLER_NAME];
        }
        $this->tablePage($basicResult,'Basic/basicTable/expenClasList',"expenClasList",$pageSize,'',$config);
    }
    function manageExpenClasInfo($param=[]){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");
        if($reqType=="expenClasAdd"){
            $datas['class']="expenClas";
            $datas['status']=1;
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="expenClasEdit"){
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
    function expenClasAdd(){
        $Info=$this->manageExpenClasInfo();
        if($Info){
            $insertResult=$this->basicCom->insertBasic($Info);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    } 
    function expenClasEdit(){
        $Info=$this->manageExpenClasInfo();
        $updateResult=$this->basicCom->updateBasic($Info);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-03 08:58:30 
     * @Desc: 固定支出类别导入 
     */    
    function expenClas_import($excelData){
        $insertData = [];
        foreach ($excelData as $index => $excelRow) {
            if($index>0){
                $temp = [];
                foreach ($excelData[0] as $i=>$key) {
                    $temp[$key] = $excelRow[$i];
                }
                $tempData = $this->manageExpenClasInfo(["data"=>$temp,"reqType"=>"expenClasAdd"]);
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
     * @Desc: 固定支出类别导出 
     */    
    function expenClas_export($excelData){
        $schema=[
            'basicId' => ['name'=>'固定支出类别id'],
            'name' => ['name'=>'固定支出类别名称'],
            'alias' => ['name'=>'固定支出类别别名'],
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
        $exportData = ['data'=>$excelData,'schema'=> $schema,'fileName'=>'固定支出类别数据表'];
        return $exportData ;
    } 
    //固定支出分类结束

    
    //报销类别开始
    /** 
     * @Author: vition 
     * @Date: 2018-08-16 09:59:15 
     * @Desc: 报销类别管理 
     */    
    function expense_typeControl(){
        $reqType=I('reqType');
        $this->assign("controlName","basic_expense_type");
        // $this->assign('tableName',$this->basicCom->tableName());//删除数据的时候需要
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function basic_expense_type_modalOne(){
        $title = "新建报销类别";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑报销类别";
            $btnTitle = "保存数据";
            $redisName="expense_typeList";
            $resultData=$this->basicCom->redis_one($redisName,"basicId",$id);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"expense_typeModal",
        ];
        $this->modalOne($modalPara);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-08-16 10:21:43 
     * @Desc: 报销类别列表 
     */    
    function basic_expense_typeList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $export = I('export');
        $where=["class"=>"expense_type"];

        foreach (['name','alias'] as $key) {
            if(isset($data[$key])){
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
        $basicResult=$this->basicCom->getBasicList($parameter);
        if($export){
            $config = ['control'=>CONTROLLER_NAME];
        }
        
        $this->tablePage($basicResult,'Basic/basicTable/expense_typeList',"expense_typeList",$pageSize,'',$config);
    }
    function manageExpenseTypeInfo($param=[]){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");
        if($reqType=="basic_expense_typeAdd"){
            $datas['class']="expense_type";
            $datas['status']=1;
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="basic_expense_typeEdit"){
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
    function basic_expense_typeAdd(){
        $Info=$this->manageExpenseTypeInfo();
        if($Info){
            $insertResult=$this->basicCom->insertBasic($Info);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    } 
    function basic_expense_typeEdit(){
        $Info=$this->manageExpenseTypeInfo();
        $updateResult=$this->basicCom->updateBasic($Info);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-03 08:58:30 
     * @Desc: 报销类别导入 
     */    
    function basic_expense_type_import($excelData){
        $insertData = [];
        foreach ($excelData as $index => $excelRow) {
            if($index>0){
                $temp = [];
                foreach ($excelData[0] as $i=>$key) {
                    $temp[$key] = $excelRow[$i];
                }
                $tempData = $this->manageExpenseTypeInfo(["data"=>$temp,"reqType"=>"basic_expense_typeAdd"]);
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
     * @Desc: 报销类别导出 
     */    
    function basic_expense_type_export($excelData){
        $schema=[
            'basicId' => ['name'=>'报销类别id'],
            'name' => ['name'=>'报销类别名称'],
            'alias' => ['name'=>'报销类别别名'],
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
        $exportData = ['data'=>$excelData,'schema'=> $schema,'fileName'=>'报销类别数据表'];
        return $exportData ;
    } 
    //报销类别结束
    //成本分类开始
    /** 
     * @Author: vition 
     * @Date: 2018-05-20 22:30:14 
     * @Desc: 成本分类 
     */    
    function costClassControl(){
        $reqType=I('reqType');
        $this->assign("controlName","basic_costClass");
        // $this->assign('tableName',$this->basicCom->tableName());//删除数据的时候需要
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function basic_costClass_modalOne(){
        $title = "新建成本分类";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑成本分类";
            $btnTitle = "保存数据";
            $redisName="costClassList";
            $resultData=$this->basicCom->redis_one($redisName,"basicId",$id);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"costClassModal",
        ];
        $this->modalOne($modalPara);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-20 22:45:25 
     * @Desc: 成本分类列表 
     */    
    function basic_costClassList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $export = I('export');
        $where=["class"=>"costClass"];

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
        $this->tablePage($basicResult,'Basic/basicTable/costClassList',"costClassList",$pageSize,'',$config);
    }
    function managecostClassInfo($param=[]){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");
        if($reqType=="basic_costClassAdd"){
            $datas['class']="costClass";
            $datas['status']=1;
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="basic_costClassEdit"){
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
    function basic_costClassAdd(){
        $costClassInfo=$this->managecostClassInfo();
        if($costClassInfo){
            $insertResult=$this->basicCom->insertBasic($costClassInfo);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    } 
    function basic_costClassEdit(){
        $costClassInfo=$this->managecostClassInfo();
        $updateResult=$this->basicCom->updateBasic($costClassInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-03 08:58:30 
     * @Desc: 成本分类导入 
     */    
    function basic_costClass_import($excelData){
        $insertData = [];
        foreach ($excelData as $index => $excelRow) {
            if($index>0){
                $temp = [];
                foreach ($excelData[0] as $i=>$key) {
                    $temp[$key] = $excelRow[$i];
                }
                $tempData = $this->managecostClassInfo(["data"=>$temp,"reqType"=>"basic_costClassAdd"]);
                if(isset($temp["basicId"])){
                    $tempData["basicId"] = $temp["basicId"];
                }
                $result = $this->basicCom->getOne(['where'=>['class'=>'costClass','name'=>$temp["name"]]]);
                if(!$result){
                    array_push($insertData,$tempData);
                }
            }
        }
        return $insertData;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-04 08:48:49 
     * @Desc: 成本分类导出 
     */    
    function basic_costClass_export($excelData){
        $schema=[
            'basicId' => ['name'=>'成本分类id'],
            'name' => ['name'=>'成本分类名称'],
            'alias' => ['name'=>'成本分类别名'],
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
        $exportData = ['data'=>$excelData,'schema'=> $schema,'fileName'=>'成本分类数据表'];
        return $exportData ;
    } 
    //成本分类结束
    //单位开始
    /** 
        * @Author: vition 
        * @Date: 2018-05-20 22:30:14 
        * @Desc: 单位
        */    
    function unitControl(){
        $reqType=I('reqType');
        $this->assign("controlName","basic_unit");
        // $this->assign('tableName',$this->basicCom->tableName());//删除数据的时候需要
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function basic_unit_modalOne(){
        $title = "新建单位";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑单位";
            $btnTitle = "保存数据";
            $redisName="unitList";
            $resultData=$this->basicCom->redis_one($redisName,"basicId",$id);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"unitModal",
        ];
        $this->modalOne($modalPara);
    }
    /** 
        * @Author: vition 
        * @Date: 2018-05-20 22:45:25 
        * @Desc: 单位列表 
        */    
    function basic_unitList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $export = I('export');
        $where=["class"=>"unit"];

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
        $this->tablePage($basicResult,'Basic/basicTable/unitList',"unitList",$pageSize,'',$config);
    }
    function manageunitInfo($param=[]){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");
        if($reqType=="basic_unitAdd"){
            $datas['class']="unit";
            $datas['status']=1;
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="basic_unitEdit"){
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
    function basic_unitAdd(){
        $info=$this->manageunitInfo();
        if($info){
            $insertResult=$this->basicCom->insert($info);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    } 
    function basic_unitEdit(){
        $info=$this->manageunitInfo();
        $updateResult=$this->basicCom->update($info);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    /** 
        * @Author: vition 
        * @Date: 2018-10-03 08:58:30 
        * @Desc: 单位导入 
        */    
    function basic_unit_import($excelData){
        $insertData = [];
        foreach ($excelData as $index => $excelRow) {
            if($index>0){
                $temp = [];
                foreach ($excelData[0] as $i=>$key) {
                    $temp[$key] = $excelRow[$i];
                }
                $tempData = $this->manageunitInfo(["data"=>$temp,"reqType"=>"basic_unitAdd"]);
                if(isset($temp["basicId"])){
                    $tempData["basicId"] = $temp["basicId"];
                }
                $result = $this->basicCom->getOne(['where'=>['class'=>'unit','name'=>$temp["name"]]]);
                if(!$result){
                    array_push($insertData,$tempData);
                }
            }
        }
        return $insertData;
    }
    /** 
        * @Author: vition 
        * @Date: 2018-10-04 08:48:49 
        * @Desc: 单位导出 
        */    
    function basic_unit_export($excelData){
        $schema=[
            'basicId' => ['name'=>'单位id'],
            'name' => ['name'=>'单位名称'],
            'alias' => ['name'=>'单位别名'],
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
        $exportData = ['data'=>$excelData,'schema'=> $schema,'fileName'=>'单位数据表'];
        return $exportData ;
    } 
    //单位结束
}
