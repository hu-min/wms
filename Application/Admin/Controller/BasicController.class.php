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
        $this->assign('dbName',"Basic");//删除数据的时候需要
        $this->fieldCom=getComponent('Field');
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
        $where=["class"=>"brand"];

        if($data['name']){
            $where['name']=['LIKE','%'.$data['name'].'%'];
        }
        if($data['alias']){
            $where['alias']=['LIKE','%'.$data['alias'].'%'];
        }
        if(isset($data['status'])){
            $where['status']=$data['status'];
        }else{
            $where['status']=["lt",3];
        }
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"basicId DESC",
        ];
        $basicResult=$this->basicCom->getBasicList($parameter);
        $this->tablePage($basicResult,'Basic/basicTable/brandList',"brandList");
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
    function manageBrandInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($reqType=="basic_brandAdd"){
            $datas['class']="brand";
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="basic_brandEdit"){
            $where=["basicId"=>$datas['basicId']];
            $data=[];
            if(isset($datas['name'])){
                $data['name']=$datas['name'];
            }
            if(isset($datas['alias'])){
                $data['alias']=$datas['alias'];
            }
            if(isset($datas['remark'])){
                $data['remark']=$datas['remark'];
            }
            if(isset($datas['status'])){
                $data['status']=$datas['status'];
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
    //品牌管理结束
    //场地管理开始
    function fieldControl(){
        $reqType=I('reqType');
        $this->assign("controlName","basic_field");
        $this->assign("dbName","Field");
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
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"id DESC",
            'joins'=>[
                "LEFT JOIN (SELECT pid ,province province_name FROM v_province ) p ON p.pid = province",
                "LEFT JOIN (SELECT cid ctid ,city city_name,pid cpid FROM v_city ) ct ON ct.ctid = city AND ct.cpid = province",
            ]
        ];
        $basicResult=$this->fieldCom->getList($parameter);
        $this->tablePage($basicResult,'Basic/basicTable/fieldList',"fieldList");
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
    function manageFieldInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($reqType=="basic_fieldAdd"){
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
    //场地管理结束
    //项目阶段管理开始
    function stageControl(){
        $reqType=I('reqType');
        $this->assign("controlName","basic_stage");
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
        $where=["class"=>"stage"];

        if($data['name']){
            $where['name']=['LIKE','%'.$data['name'].'%'];
        }
        if($data['alias']){
            $where['alias']=['LIKE','%'.$data['alias'].'%'];
        }
        if(isset($data['status'])){
            $where['status']=$data['status'];
        }else{
            $where['status']=["lt",3];
        }
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"basicId DESC",
        ];
        $basicResult=$this->basicCom->getBasicList($parameter);
        $this->tablePage($basicResult,'Basic/basicTable/stageList',"stageList");
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
    function manageStageInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($reqType=="basic_stageAdd"){
            $datas['class']="stage";
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="basic_stageEdit"){
            $where=["basicId"=>$datas['basicId']];
            $data=[];
            if(isset($datas['name'])){
                $data['name']=$datas['name'];
            }
            if(isset($datas['alias'])){
                $data['alias']=$datas['alias'];
            }
            if(isset($datas['remark'])){
                $data['remark']=$datas['remark'];
            }
            if(isset($datas['status'])){
                $data['status']=$datas['status'];
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
    //项目阶段管理结束
    //项目类型管理结束
    function projectTypeControl(){
        $reqType=I('reqType');
        $this->assign("controlName","basic_projectType");
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
        $where=["class"=>"projectType"];

        if($data['name']){
            $where['name']=['LIKE','%'.$data['name'].'%'];
        }
        if($data['alias']){
            $where['alias']=['LIKE','%'.$data['alias'].'%'];
        }
        if(isset($data['status'])){
            $where['status']=$data['status'];
        }else{
            $where['status']=["lt",3];
        }
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"basicId DESC",
        ];
        $basicResult=$this->basicCom->getBasicList($parameter);
        $this->tablePage($basicResult,'Basic/basicTable/projectTypeList',"projectTypeList");
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
    function manageProjectTypeInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($reqType=="basic_projectTypeAdd"){
            $datas['class']="projectType";
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="basic_projectTypeEdit"){
            $where=["basicId"=>$datas['basicId']];
            $data=[];
            if(isset($datas['name'])){
                $data['name']=$datas['name'];
            }
            if(isset($datas['alias'])){
                $data['alias']=$datas['alias'];
            }
            if(isset($datas['remark'])){
                $data['remark']=$datas['remark'];
            }
            if(isset($datas['status'])){
                $data['status']=$datas['status'];
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
    //项目类型管理结束
    //执行类型管理execute开始
    function executeControl(){
        $reqType=I('reqType');
        $this->assign("controlName","basic_execute");
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
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=["class"=>"execute"];
        if($data['pId']){
            $where['pId']=['EQ',$data['pId']];
        }
        if($data['name']){
            $where['name']=['LIKE','%'.$data['name'].'%'];
        }
        if($data['alias']){
            $where['alias']=['LIKE','%'.$data['alias'].'%'];
        }
        if(isset($data['status'])){
            $where['status']=$data['status'];
        }else{
            $where['status']=["lt",3];
        }
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"basicId DESC",
        ];
        $basicResult=$this->basicCom->getBasicList($parameter);
        $this->tablePage($basicResult,'Basic/basicTable/executeList',"executeList");
        // if($basicResult){
        //     $basicRed="executeList";
        //     $this->Redis->set($basicRed,json_encode($basicResult['list']),3600);
        //     $page = new \Think\VPage($basicResult['count'], $this->pageSize);
        //     $pageShow = $page->show();
            
        //     $this->assign('executeList',$basicResult['list']);
        //     $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('Basic/basicTable/executeList'),'page'=>$pageShow,"count"=>$count]);
        // }
        // $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);
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
    function manageExecuteInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($reqType=="basic_executeAdd"){
            $datas['class']="execute";
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="basic_executeEdit"){
            $where=["basicId"=>$datas['basicId']];
            $data=[];
            if(isset($datas['name'])){
                $data['name']=$datas['name'];
            }
            if(isset($datas['alias'])){
                $data['alias']=$datas['alias'];
            }
            if(isset($datas['remark'])){
                $data['remark']=$datas['remark'];
            }
            if(isset($datas['status'])){
                $data['status']=$datas['status'];
            }
            return ["where"=>$where,"data"=>$data];
        }
        return "";
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
        $updateResult=$this->basicCom->updateBasic($executeInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    //执行类型管理execute结束
    //费用类别管理开始
    function feeTypeControl(){
        $reqType=I('reqType');
        $this->assign("controlName","basic_feeType");
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
        
        if($gettype=="Edit"){
            $title = "编辑费用类型";
            $btnTitle = "保存数据";
            $redisName="feeTypeList";
            $resultData=$this->basicCom->redis_one($redisName,"basicId",$id);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"feeTypeModal",
        ];
        $option='<option value="0">根Root</option>';
        foreach ($this->getFeeTypeTree() as $key => $value) {
            // print_r($value);
            $option.=$this->getfeeType($value,0);
        }
        // print_r($option);
        $this->assign("pidoption",$option);
        $this->modalOne($modalPara);
    }
    function getfeeType($element,$level){
        $option="";
        $strs="";
        for ($i=0; $i < $level; $i++) { 
            $strs.="——";
        }
        if(is_array($element["nodes"])){
            $level++;
            foreach ($element["nodes"] as $key => $value) {
                $option.= $this->getfeeType($value,$level);
            }
        }
        return '<option value="'.$element["id"].'">'.$strs.$element["text"].'</option>'.$option;
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
    function manageFeeTypeInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        $feeTypePInfo=$this->getFeeTypeOne($datas['pId']);
        $datas['level']=$feeTypePInfo['level']?($feeTypePInfo['level']+1):1;
        if($reqType=="basic_feeTypeAdd"){
            $datas['class']="feeType";
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="basic_feeTypeEdit"){
            $where=["basicId"=>$datas['basicId']];
            $data=[];
            if(isset($datas['name'])){
                $data['name']=$datas['name'];
            }
            if(isset($datas['alias'])){
                $data['alias']=$datas['alias'];
            }
            if(isset($datas['remark'])){
                $data['remark']=$datas['remark'];
            }
            if(isset($datas['pId'])){
                $data['pId']=$datas['pId'];
            }
            if(isset($datas['level'])){
                $data['level']=$datas['level'];
            }
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    function basic_feeTypeAdd(){
        $feeTypeInfo=$this->manageFeeTypeInfo();
        if($feeTypeInfo){
            $insertResult=$this->basicCom->insertBasic($feeTypeInfo);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    } 
    function basic_feeTypeEdit(){
        $feeTypeInfo=$this->manageFeeTypeInfo();
        $updateResult=$this->basicCom->updateBasic($feeTypeInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-24 06:42:53 
     * @Desc: 返回费用类型的节点 
     */    
    function basic_feeTypeList(){
        $this->ajaxReturn(["tree"=>$this->getFeeTypeTree()]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-24 06:43:12 
     * @Desc: 获取费用类型节点 
     */    
    function getFeeTypeTree(){
        $parameter=[
            'where'=>["class"=>"feeType"],
            'page'=>0,
            'pageSize'=>9999,
            'orderStr'=>'level DESC',
        ];
        $feeTypeResult=$this->basicCom->getBasicList($parameter);
        $feeTypeTree=[];
        $level=[];
        
        $feeTypeArray=$feeTypeResult["list"];
        foreach ($feeTypeArray AS $key => $feeTypeInfo) {
            $level[$feeTypeInfo["level"]][$feeTypeInfo["Pid"]][]= $feeTypeInfo;
            unset($feeTypeArray[$key]);
        }
        $this->Redis->set("feeTypeArray",json_encode($feeTypeResult["list"]),3600);
        asort($level);
        
        $this->levelTree->setKeys(["idName"=>"basicId","pidName"=>"pId"]);
        $this->levelTree->setReplace(["name"=>"text","basicId"=>"id"]);
        $this->levelTree->switchOption(["beNode"=>false,"idAsKey"=>false]);
        $feeTypeTree=$this->levelTree->createTree($feeTypeResult["list"]);
        return $feeTypeTree;
    }
    //费用类别管理结束
    //承接模块管理开始
    function moduleControl(){
        $reqType=I('reqType');
        $this->assign("controlName","basic_module");
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
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=["class"=>"module"];

        if($data['name']){
            $where['name']=['LIKE','%'.$data['name'].'%'];
        }
        if($data['alias']){
            $where['alias']=['LIKE','%'.$data['alias'].'%'];
        }
        if(isset($data['status'])){
            $where['status']=$data['status'];
        }else{
            $where['status']=["lt",3];
        }
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"basicId DESC",
        ];
        $basicResult=$this->basicCom->getBasicList($parameter);
        $this->tablePage($basicResult,'Basic/basicTable/moduleList',"moduleList");
    }
    function manageModuleInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($reqType=="basic_moduleAdd"){
            $datas['class']="module";
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="basic_moduleEdit"){
            $where=["basicId"=>$datas['basicId']];
            $data=[];
            if(isset($datas['name'])){
                $data['name']=$datas['name'];
            }
            if(isset($datas['alias'])){
                $data['alias']=$datas['alias'];
            }
            if(isset($datas['remark'])){
                $data['remark']=$datas['remark'];
            }
            if(isset($datas['status'])){
                $data['status']=$datas['status'];
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
    //承接模块管理结束
    //固定支出分类开始
    function expenClasControl(){
        $reqType=I('reqType');
        $this->assign("controlName","expenClas");
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
        $where=["class"=>"expenClas"];

        if($data['name']){
            $where['name']=['LIKE','%'.$data['name'].'%'];
        }
        if($data['alias']){
            $where['alias']=['LIKE','%'.$data['alias'].'%'];
        }
        if(isset($data['status'])){
            $where['status']=$data['status'];
        }else{
            $where['status']=["lt",3];
        }
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"basicId DESC",
        ];
        $basicResult=$this->basicCom->getBasicList($parameter);
        $this->tablePage($basicResult,'Basic/basicTable/expenClasList',"expenClasList");
    }
    function manageExpenClasInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($reqType=="expenClasAdd"){
            $datas['class']="expenClas";
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="expenClasEdit"){
            $where=["basicId"=>$datas['basicId']];
            $data=[];
            if(isset($datas['name'])){
                $data['name']=$datas['name'];
            }
            if(isset($datas['alias'])){
                $data['alias']=$datas['alias'];
            }
            if(isset($datas['remark'])){
                $data['remark']=$datas['remark'];
            }
            if(isset($datas['status'])){
                $data['status']=$datas['status'];
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
    //固定支出分类结束

    function getCityList(){
        $this->ajaxReturn(["data"=>A("Project")->_getOption("city")]);
    }
    //报销类别开始
    /** 
     * @Author: vition 
     * @Date: 2018-08-16 09:59:15 
     * @Desc: 报销类别管理 
     */    
    function expense_typeControl(){
        $reqType=I('reqType');
        $this->assign("controlName","basic_expense_type");
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
        $where=["class"=>"expense_type"];

        if($data['name']){
            $where['name']=['LIKE','%'.$data['name'].'%'];
        }
        if($data['alias']){
            $where['alias']=['LIKE','%'.$data['alias'].'%'];
        }
        if(isset($data['status'])){
            $where['status']=$data['status'];
        }else{
            $where['status']=["lt",3];
        }
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"basicId DESC",
        ];
        $basicResult=$this->basicCom->getBasicList($parameter);
        $this->tablePage($basicResult,'Basic/basicTable/expense_typeList',"expense_typeList");
    }
    function manageExpenseTypeInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($reqType=="basic_expense_typeAdd"){
            $datas['class']="expense_type";
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="basic_expense_typeEdit"){
            $where=["basicId"=>$datas['basicId']];
            $data=[];
            if(isset($datas['name'])){
                $data['name']=$datas['name'];
            }
            if(isset($datas['alias'])){
                $data['alias']=$datas['alias'];
            }
            if(isset($datas['remark'])){
                $data['remark']=$datas['remark'];
            }
            if(isset($datas['status'])){
                $data['status']=$datas['status'];
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
    //报销类别结束
}
