<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-05-09 22:46:55 
 * @Desc: 供应商管理 
 */
class SupplierController extends BaseController{
    protected $pageSize=10;
    public function _initialize() {
        $this->statusType = [0=>"未启用",1=>"启用",3=>"无效",4=>"删除"];
        parent::_initialize();
        $this->basicCom=getComponent('Basic');
        $this->supplierCom=getComponent('Supplier');
        
    }
    //内部公用方法
    /** 
     * @Author: vition 
     * @Date: 2018-05-27 15:13:52 
     * @Desc: 内部获取供应商类型列表
     */    
    function getSupType($key=""){
        $where=["class"=>"supType"];
        if ($key!=""){
            $where["name"]=["LIKE","%{$key}%"];
        }
        $parameter=[
            'where'=>$where,
            'fields'=>'basicId,name',
            'page'=>1,
            'pageSize'=>20,
            'orderStr'=>"sort ASC , basicId DESC",
        ];
        $basicResult=$this->basicCom->getBasicList($parameter);
        return $basicResult['list'] ? $basicResult['list'] : [];
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-27 15:14:05 
     * @Desc: 内部获取供应商列表 
     */    
    function getSupplier($key="",$type="",$gpid=""){
        $where=["status"=>"1"];
        if ($key!=""){
            $where["company"]=["LIKE","%{$key}%"];
        }
        if(is_array($type)){
            $where["_string"]="";
            foreach ($type as $moduleId) {
                $where["_string"].=" FIND_IN_SET({$moduleId},module) OR";
            }
            $where["_string"] = rtrim($where["_string"],"OR");
            // echo $where["_string"];exit;
        }else{
            if($type!=""){
                if($type!=999999999){
                    $where["_string"]="FIND_IN_SET({$type},module)";
                }elseif($gpid!=""){
                    $where["supr_type"]=$gpid;
                }
            }elseif($gpid!=""){
                $where["supr_type"]=$gpid;
            }
        }
        
        $parameter=[
            'where'=>$where,
            'fields'=>'companyId,company,provinceId,cityId,province_name,city_name',
            'page'=>1,
            'pageSize'=>20,
            'orderStr'=>"companyId DESC",
            "joins"=>[
                "LEFT JOIN (SELECT pid ,province province_name FROM v_province) p ON p.pid=provinceId",
                "LEFT JOIN (SELECT cid,city city_name,pid FROM v_city) c ON c.cid=cityId",
            ],
            "isCount" =>false,
        ];
        $supplierResult = $this->supplierCom->getCompanyList($parameter);
        return $supplierResult['list'] ? $supplierResult['list'] : [];
    }
    //内部公用方法结束
    //供应商配置开始
    /** 
     * @Author: vition 
     * @Date: 2018-05-27 15:14:34 
     * @Desc: 供应商配置总控制 
     */    
    function supType(){
        $reqType=I('reqType');
        $this->assign("controlName","supType");
        $this->assign('tableName',$this->basicCom->tableName());
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function supType_modalOne(){
        $title = "新建供应商类型";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑供应商类型";
            $btnTitle = "保存数据";
            $redisName="supTypeList";
            $resultData=$this->basicCom->redis_one($redisName,"basicId",$id);
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"supTypeModal",
        ];
        $this->modalOne($modalPara);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-20 22:45:25 
     * @Desc: 供应商类型列表 
     */    
    function supTypeList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $export = I('export');
        $where=["class"=>"supType"];

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
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$pageSize,
            'orderStr'=>"sort ASC,basicId DESC",
        ];
        if($export){
            $config = ['control'=>CONTROLLER_NAME];
        }
        $basicResult=$this->basicCom->getBasicList($parameter);
        $this->tablePage($basicResult,'Supplier/supplierTable/supTypeList',"supTypeList",$pageSize,'',$config);

    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-27 15:12:06 
     * @Desc: 供应商类型one 
     */    
    // function supTypeOne(){
    //     $id	=I("id");
    //     $parameter=[
    //         'basicId'=>$id,
    //     ];
    //     $blistRed="supTypeList";
    //     $supTypeList=$this->Redis->get($blistRed);
    //     $blist=[];
    //     if($supTypeList){
    //         foreach ($supTypeList as $supType) {
    //            if($supType['basicId']==$id){
    //             $blist=$supType;
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
    /** 
     * @Author: vition 
     * @Date: 2018-05-27 15:12:20 
     * @Desc: 供应商类型数据管理 
     */    
    function manageSupTypeInfo($param=[]){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");
        if($reqType=="supTypeAdd"){
            $datas['class']="supType";
            $datas['status'] = 1;
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="supTypeEdit"){
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
    /** 
     * @Author: vition 
     * @Date: 2018-05-27 15:12:31 
     * @Desc: 供应商类型添加 
     */    
    function supTypeAdd(){
        $supTypeInfo=$this->manageSupTypeInfo();
        if($supTypeInfo){
            $insertResult=$this->basicCom->insertBasic($supTypeInfo);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    } 
    /** 
     * @Author: vition 
     * @Date: 2018-05-27 15:12:39 
     * @Desc: 供应商类型编辑 
     */    
    function supTypeEdit(){
        $supTypeInfo=$this->manageSupTypeInfo();
        $updateResult=$this->basicCom->updateBasic($supTypeInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-03 00:29:05 
     * @Desc: 供应商类别导入 
     */    
    function supType_import($excelData){
        $insertData = [];
        foreach ($excelData as $index => $excelRow) {
            if($index>0){
                $temp = [];
                foreach ($excelData[0] as $i=>$key) {
                    $temp[$key] = $excelRow[$i];
                }
                $temp = $this->manageSupTypeInfo(["data"=>$temp,"reqType"=>"supTypeAdd"]);
                array_push($insertData,$temp);
            }
        }
        return $insertData;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-03 07:37:08 
     * @Desc: 供应商类别导出 
     */    
    function supType_export($excelData){
        $schema=[
            'basicId' => ['name'=>'供应商类别id'],
            'name' => ['name'=>'供应商类别名称'],
            'alias' => ['name'=>'供应商别名'],
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
        $exportData = ['data'=>$excelData,'schema'=> $schema,'fileName'=>'供应商类别表'];
        return $exportData ;
    }
    //供应商配置结束

    //供应商公司管理开始
    /** 
     * @Author: vition 
     * @Date: 2018-05-27 15:14:55 
     * @Desc: 供应商公司总控制器 
     */    
    function companyControl(){
        $reqType=I('reqType');
        $this->assign("controlName","sup_company");
        $this->assign("supTypeList",$this->getSupType());
        $this->assign("province",$this->basicCom->get_provinces());
        $this->assign('tableName',"v_supplier_company");//删除数据的时候需要
        if($reqType){
            $this->$reqType();
        }else{
            
            
            $this->returnHtml();
        }
    }
    function sup_company_modalOne(){
        $title = "新建供应商";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑供应商";
            $btnTitle = "保存数据";
            $redisName="sup_companyList";
            $resultData=$this->supplierCom->redis_one($redisName,"companyId",$id,"companyDB");
        }
        // print_r($resultData);exit;
        if($resultData['module']){
            $parameter=[
                'where'=>["class"=>"module",'basicId'=>["IN",explode(",",$resultData['module'])]],
                'fields'=>'basicId,name',
                'page'=>1,
                'pageSize'=>9999,
                'orderStr'=>"basicId DESC",
            ];
            $basicResult=$this->basicCom->getList($parameter);
            $resultData['modules']=$basicResult['list'];
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"companyModal",
        ];
        $this->modalOne($modalPara);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-27 15:16:16 
     * @Desc: 接口获取供应商类型列表 
     */    
    function getSupTypeList(){
        $key=I("key");
        $this->ajaxReturn(["data"=>$this->getSupType($key)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-27 15:16:49 
     * @Desc: 接口获取供应商列表 
     */    
    function getSupplierList(){
        $key=I("key") ? I("key") : '';
        $this->ajaxReturn(["data"=>$this->getSupplier($key)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-27 08:44:47 
     * @Desc: 获取城市列表jk 
     */    
    function getCityList(){
        $pid=I("pid");
        $cityList=$this->basicCom->get_citys($pid);
        $this->ajaxReturn(['errCode'=>0,'citys'=>$cityList]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:51:01 
     * @Desc: 供应商列表 
     */    
    function sup_companyList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $export = I('export');
        $where=[];
        if($data['company']){
            $where['company']=['LIKE','%'.$data['company'].'%'];
        }
        if($data['alias']){
            $where['alias']=['LIKE','%'.$data['alias'].'%'];
        }
        if($data['provinceId']){
            $where['provinceId']=$data['provinceId'];
        }
        if($data['cityId']){
            $where['cityId']=$data['cityId'];
        }
        if(isset($data['status'])){
            $where['status']=$data['status'];
        }
        if($data['supr_type']){
            $where['supr_type']=$data['supr_type'];;
        }
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
        $parameter=[
            'where'=>$where,
            'fields'=>"`companyId`,`supr_type`,`module`,`company`,`alias`,`provinceId`,`cityId`,`province`,`city`,`address`,`remarks`,`addTime`,`updateTime`,`status`,`typeName`,moule_name",
            'page'=>$p,
            'pageSize'=>$pageSize,
            'orderStr'=>"companyId DESC",
            "joins"=>[
                "LEFT JOIN v_province p ON p.pid=provinceId","LEFT JOIN v_city c ON c.pid=p.pid AND c.cid=cityId",
                "LEFT JOIN (SELECT basicId,name typeName FROM v_basic WHERE class='supType') b ON b.basicId=supr_type",
                "LEFT JOIN (SELECT s.module mid,GROUP_CONCAT(name) moule_name FROM v_basic LEFT JOIN (SELECT distinct module FROM `v_supplier_company`) s ON  FIND_IN_SET(basicId,s.module) WHERE class='module' AND !ISNULL(s.module) GROUP BY s.module) m ON m.mid = module",
            ],
        ];
        if($export){
            $config = ['control'=>CONTROLLER_NAME];
        }
        
        $listResult=$this->supplierCom->getCompanyList($parameter);
        // echo $this->supplierCom->M()->_sql();exit;
        $this->tablePage($listResult,'Supplier/supplierTable/companyList',"sup_companyList",$pageSize,'',$config);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:43:33 
     * @Desc: 添加和修改供应商数据管理 
     */    
    function manageCompanyInfo($param=[]){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");
        if(isset($datas['module'])){
            $datas['module']=implode(",",$datas['module']);
        }
        if($reqType=="sup_companyAdd"){
            $datas['addTime']=time();
            $datas['status'] = 1;
            $datas['process_level']=$this->processAuth["level"];
            $datas['author']=session("userId");
            unset($datas['companyId']);
            return $datas;
        }else if($reqType=="sup_companyEdit"){
            $where=["companyId"=>$datas['companyId']];
            $data=[];
            foreach (['company','alias','provinceId','cityId','address','remarks','supr_type','module','status'] as $key ) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
            $data['upateTime']=time();
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:46:44 
     * @Desc: 添加供应商信息 
     */    
    function sup_companyAdd(){
        $dataInfo=$this->manageCompanyInfo();
        if($dataInfo){
            $insertResult=$this->supplierCom->insertCompany($dataInfo);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:59:28 
     * @Desc: 获取单一条供应商信息 
     */    
    // function sup_supcompanyOne(){
    //     $id	=I("id");
    //     $parameter=[
    //         'companyId'=>$id,
    //     ];
    //     $pListRed="supcompanyList";
    //     $companyList=$this->Redis->get($pListRed);
    //     $plist=[];
    //     if($companyList){
    //         foreach ($companyList as $company) {
    //            if($company['companyId']==$id){
    //             $plist=$company;
    //             break;
    //            }
    //         }
    //     }
    //     if(empty($plist)){
    //         $companyResult=$this->supplierCom->getCompanyList($parameter,true);
    //         if($companyResult->errCode==0){
    //             $plist=$companyResult->data;
    //         }
    //     }
    //     if(!empty($plist)){
    //         $this->ajaxReturn(['errCode'=>0,'info'=>$plist]);
    //     }
    //     $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    // }
    /** 
     * @Author: vition 
     * @Date: 2018-05-10 00:02:10 
     * @Desc: 修改供应商信息 
     */    
    function sup_companyEdit(){
        $companyInfo=$this->manageCompanyInfo();
        $updateResult=$this->supplierCom->updateCompany($companyInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-04 09:47:34 
     * @Desc: 供应商导入 
     */    
    function sup_company_import($excelData){
        $insertData = [];
        foreach ($excelData as $index => $excelRow) {
            if($index>0){
                $temp = [];
                foreach ($excelData[0] as $i=>$key) {
                    if($key=="supr_type"){
                        $temp[$key] = $this->basicCom->getOne(['where'=>['class'=>'supType','name'=>$excelRow[$i]]])['list']['basicId'];
                    }elseif($key=="module"){
                        $temp[$key] = explode(',', $this->basicCom->getOne(['fields'=>'GROUP_CONCAT(basicId) basicIds','where'=>['class'=>'module','pId'=>$temp["supr_type"],'name'=>['IN',explode(',',str_replace('，',',',$excelRow[$i]))]]])['list']['basicIds']);
                        // $module = $temp[$key] ;
                        //SELECT GROUP_CONCAT(basicId) FROM v_basic WHERE class='module' AND `name` IN ('测试模块','技术模块','互动装置');
                    }elseif($key=="provinceId"){
                        $temp[$key] = M("province")->where(['province'=>$excelRow[$i]])->find()['pid'];
                    }elseif($key=="cityId"){
                        $temp[$key] = M("city")->where(["city"=>$excelRow[$i],'pid'=>$temp["provinceId"]])->find()['cid'];
                    }else{
                        $temp[$key] = $excelRow[$i];
                    }
                }
                $temp = $this->manageCompanyInfo(["data"=>$temp,"reqType"=>"sup_companyAdd"]);
                array_push($insertData,$temp);
            }
        }
        return $insertData;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-03 07:37:08 
     * @Desc: 供应商导出 
     */    
    function sup_company_export($excelData){
        $schema=[
            'companyId' => ['name'=>'供应商id'],
            'company' => ['name'=>'供应商名称'],
            'typeName' => ['name'=>'供应商类型'],
            'moule_name' => ['name'=>'供应商承接模块'],
            'alias' => ['name'=>'供应商别名'],
            'province' => ['name'=>'省份'],
            'city' => ['name'=>'城市'],
            'address' => ['name'=>'地址'],
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
        $exportData = ['data'=>$excelData,'schema'=> $schema,'fileName'=>'供应商类别表'];
        return $exportData ;
    } 
    //供应商公司管理结束

    //供应商联系人管理开始
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:42:41 
     * @Desc: 供应商联系人总控制 
     */    
    function contactControl(){
        $reqType=I('reqType');
        $this->assign("controlName","supcontact");
        $this->assign("supplierList",$this->getSupplier());
        $this->assign('tableName',"VSupplierContact");//删除数据的时候需要
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function supcontact_modalOne(){
        $title = "新建供应商联系人";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $resultData=[];
        $id = I("id");
        
        if($gettype=="Edit"){
            $title = "编辑供应商联系人";
            $btnTitle = "保存数据";
            $redisName="sup_contactList";
            $resultData=$this->supplierCom->redis_one($redisName,"contactId",$id,"contactDB");
        }
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"contactModal",
        ];
        $this->modalOne($modalPara);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:51:01 
     * @Desc: 供应商列表 
     */    
    function supcontactList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $export = I('export');
        $where=[];
        if($data['companyId']){
            $where['companyId']=$data['companyId'];
        }
        if($data['contact']){
            $where['contact']=['LIKE','%'.$data['contact'].'%'];
        }
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
        $parameter=[
            'fields'=>"`contactId`,`companyId`,`contact`,`phone`,`email`,`address`,`remarks`,`addTime`,`updateTime`,`status`,company",
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$pageSize,
            'orderStr'=>"contactId DESC",
            "joins"=>"LEFT JOIN (SELECT companyId cid,company FROM v_supplier_company WHERE status=1) c ON c.cid=companyId",
        ];
        if($export){
            $config = ['control'=>CONTROLLER_NAME];
        }
        
        $listResult=$this->supplierCom->getSuprContList($parameter);
        $this->tablePage($listResult,'Supplier/supplierTable/contactList',"sup_contactList",$pageSize,'',$config);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:43:33 
     * @Desc: 添加和修改供应商采购管理 
     */    
    function manageContactInfo($param=[]){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");
        if($reqType=="supcontactAdd"){
            $datas['addTime']=time();
            $datas['status']=1;
            unset($datas['contactId']);
            return $datas;
        }else if($reqType=="supcontactEdit"){
            $where=["contactId"=>$datas['contactId']];
            $data=[];
            $data['updateTime']=time();
            foreach (['companyId','contact','phone','email','address','remarks','status'] as $key ) {
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
     * @Date: 2018-05-09 23:46:44 
     * @Desc: 添加供应商联系人信息 
     */    
    function supcontactAdd(){
        $dataInfo=$this->manageContactInfo();
        if($dataInfo){
            $insertResult=$this->supplierCom->insertContact($dataInfo);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-04 09:47:34 
     * @Desc: 供应商导入 
     */    
    function supcontact_import($excelData){
        $insertData = [];
        foreach ($excelData as $index => $excelRow) {
            if($index>0){
                $temp = [];
                foreach ($excelData[0] as $i=>$key) {
                    if($key=="companyId"){
                        $temp[$key] = $this->supplierCom->getCompanyList(['where'=>['company'=>$excelRow[$i]]],true)['companyId'];
                    }else{
                        $temp[$key] = $excelRow[$i];
                    }
                }
                $temp = $this->manageContactInfo(["data"=>$temp,"reqType"=>"supcontactAdd"]);
                array_push($insertData,$temp);
            }
        }
        return $insertData;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-10-03 07:37:08 
     * @Desc: 供应商导出 
     */    
    function supcontact_export($excelData){
        $schema=[
            'contactId' => ['name'=>'供应商联系人id'],
            'company' => ['name'=>'供应商名称'],
            'contact' => ['name'=>'供应商联系人'],
            'phone' => ['name'=>'联系电话'],
            'email' => ['name'=>'电子邮箱'],
            'address' => ['name'=>'地址'],
            'remarks' => ['name'=>'备注'],
            'status' => ['name'=>'状态'],
        ];
        foreach ($excelData as $index => $val) {
            foreach ($val as $key => $value) {
                if($key=="status"){
                    $excelData[$index][$key] = $this->statusType[$value];
                }
            }
        }
        $exportData = ['data'=>$excelData,'schema'=> $schema,'fileName'=>'供应商联系人表'];
        return $exportData ;
    } 
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:59:28 
     * @Desc: 获取单一条供应商联系人信息 
     */    
    // function supcontactOne(){
    //     $id	=I("id");
    //     $parameter=[
    //         'contactId'=>$id,
    //     ];
    //     $pListRed="supcontactList";
    //     $contactList=$this->Redis->get($pListRed);
    //     $plist=[];
    //     if($contactList){
    //         foreach ($contactList as $contact) {
    //            if($contact['contactId']==$id){
    //             $plist=$contact;
    //             break;
    //            }
    //         }
    //     }
    //     if(empty($plist)){
    //         $contactResult=$this->supplierCom->getUser($parameter);
    //         if($contactResult->errCode==0){
    //             $plist=$contactResult->data;
    //         }
    //     }
    //     if(!empty($plist)){
    //         $this->ajaxReturn(['errCode'=>0,'info'=>$plist]);
    //     }
    //     $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    // }
    /** 
     * @Author: vition 
     * @Date: 2018-05-10 00:02:10 
     * @Desc: 修改供应商信息 
     */    
    function supcontactEdit(){
        $contactInfo=$this->manageContactInfo();
        $updateResult=$this->supplierCom->updateContact($contactInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }

    function getOptionList(){
        $key=I("key");
        $type=I("type");
        $project = A("Project");
        $this->ajaxReturn(["data"=>$project->_getOption($type,$key)]);
    }

    function getSuprCont($key="",$companyId=0){
        $where=["status"=>"1"];
        if ($key!=""){
            $where["contact"]=["LIKE","%{$key}%"];
        }
        if($companyId>0){
            $where["companyId"]=$companyId;
        }
        $parameter=[
            'where'=>$where,
            'fields'=>'contactId,contact',
            'page'=>1,
            'pageSize'=>20,
            'orderStr'=>"contactId DESC",
        ];
        $supplierResult = $this->supplierCom->getSuprContList($parameter);
        return $supplierResult['list'] ? $supplierResult['list'] : [];
    }
    function getModule($pid="",$key=""){
        $where=["class"=>"module"];
        if ($key!=""){
            $where["name"]=["LIKE","%{$key}%"];
        }
        if ($pid!=""){
            $where["pId"] = $pid;
        }
        $parameter=[
            'where'=>$where,
            'fields'=>'basicId,name',
            'page'=>1,
            'pageSize'=>20,
            'orderStr'=>"basicId DESC",
        ];
        $basicResult=$this->basicCom->getBasicList($parameter);
        return $basicResult['list'] ? $basicResult['list'] : [];
    }
    function getModuleList(){
        $key=I("key");
        $pid=I("pid");
        $this->ajaxReturn(["data"=>$this->getModule($pid,$key)]);
    }
}