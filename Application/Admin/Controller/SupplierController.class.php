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
        parent::_initialize();
        $this->basicCom=getComponent('Basic');
        $this->supplierCom=getComponent('Supplier');
        $this->statusType=["0"=>"未启用","1"=>"启用"];
    }
    //内部公用方法
    /** 
     * @Author: vition 
     * @Date: 2018-05-27 15:13:52 
     * @Desc: 内部获取供应商类型列表
     */    
    protected function getSupType($key=""){
        $where=["class"=>"supType"];
        if ($key!=""){
            $where["name"]=["LIKE","%{$key}%"];
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
    /** 
     * @Author: vition 
     * @Date: 2018-05-27 15:14:05 
     * @Desc: 内部获取供应商列表 
     */    
    protected function getSupplier($key=""){
        $where=["status"=>"1"];
        if ($key!=""){
            $where["company"]=["LIKE","%{$key}%"];
        }
        $parameter=[
            'where'=>$where,
            'fields'=>'companyId,company',
            'page'=>1,
            'pageSize'=>20,
            'orderStr'=>"companyId DESC",
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
        if($reqType){
            $this->$reqType();
        }else{
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->returnHtml();
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-20 22:45:25 
     * @Desc: 供应商类型列表 
     */    
    function supTypeList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=["class"=>"supType"];

        if($data['name']){
            $where['name']=['LIKE','%'.$data['name'].'%'];
        }
        if($data['alias']){
            $where['alias']=['LIKE','%'.$data['alias'].'%'];
        }
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"basicId DESC",
        ];
        $basicResult=$this->basicCom->getBasicList($parameter);
        if($basicResult){
            $basicRed="supTypeList";
            $this->Redis->set($basicRed,json_encode($basicResult['list']),3600);
            $page = new \Think\VPage($basicResult['count'], $this->pageSize);
            $pageShow = $page->show();
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->assign('list',$basicResult['list']);
            $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('Supplier/supplierTable/supTypeList'),'page'=>$pageShow,"count"=>$count]);
        }
        $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-27 15:12:06 
     * @Desc: 供应商类型one 
     */    
    function supTypeOne(){
        $id	=I("id");
        $parameter=[
            'basicId'=>$id,
        ];
        $blistRed="supTypeList";
        $supTypeList=$this->Redis->get($blistRed);
        $blist=[];
        if($supTypeList){
            foreach ($supTypeList as $supType) {
               if($supType['basicId']==$id){
                $blist=$supType;
                break;
               }
            }
        }
        if(empty($blist)){
            $basicResult=$this->basicCom->getOne($parameter);
            if($basicResult->errCode==0){
                $blist=$basicResult->data;
            }
        }
        if(!empty($blist)){
            $this->ajaxReturn(['errCode'=>0,'info'=>$blist]);
        }
        $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-27 15:12:20 
     * @Desc: 供应商类型数据管理 
     */    
    function manageSupTypeInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($reqType=="supTypeAdd"){
            $datas['class']="supType";
            unset($datas['basicId']);
            return $datas;
        }else if($reqType=="supTypeEdit"){
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
    //供应商配置结束

    //供应商公司管理开始
    /** 
     * @Author: vition 
     * @Date: 2018-05-27 15:14:55 
     * @Desc: 供应商公司总控制器 
     */    
    function companyControl(){
        $reqType=I('reqType');
        $this->assign('statusType',$this->statusType);
        if($reqType){
            $this->$reqType();
        }else{
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->assign("supTypeList",$this->getSupType());
            $this->assign("province",$this->basicCom->get_provinces());
            $this->returnHtml();
        }
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
        $key=I("key");
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
    function companyList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
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
        if($data['type']){
            $where['type']=$data['type'];;
        }
        $parameter=[
            'where'=>$where,
            'fields'=>"`companyId`,`type`,`company`,`alias`,`provinceId`,`cityId`,`province`,`city`,`address`,`remarks`,`addTime`,`updateTime`,`status`,`typeName`",
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"companyId DESC",
            "joins"=>["LEFT JOIN v_province p ON p.pid=provinceId","LEFT JOIN v_city c ON c.pid=p.pid AND c.cid=cityId","LEFT JOIN (SELECT basicId,name typeName FROM v_basic WHERE class='supType') b ON b.basicId=type"],
        ];
        
        $listResult=$this->supplierCom->getCompanyList($parameter);
        if($listResult){
            $companyRed="supcompanyList";
            $this->Redis->set($companyRed,json_encode($listResult['list']),3600);
            $page = new \Think\VPage($listResult['count'], $this->pageSize);
            $pageShow = $page->show();
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->assign('list',$listResult['list']);

            $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('Supplier/supplierTable/companyList'),'page'=>$pageShow]);
        }
        $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:43:33 
     * @Desc: 添加和修改供应商数据管理 
     */    
    function manageCompanyInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($reqType=="supcompanyAdd"){
            $datas['addTime']=time();
            unset($datas['companyId']);
            return $datas;
        }else if($reqType=="supcompanyEdit"){
            $where=["companyId"=>$datas['companyId']];
            $data=[];
            if(isset($datas['company'])){
                $data['company']=$datas['company'];
            }
            if(isset($datas['alias'])){
                $data['alias']=$datas['alias'];
            }
            if(isset($datas['province'])){
                $data['province']=$datas['province'];
            }
            if(isset($datas['city'])){
                $data['city']=$datas['city'];
            }
            if(isset($datas['address'])){
                $data['address']=$datas['address'];
            }
            if(isset($datas['status'])){
                $data['status']=$datas['status'];
            }
            if(isset($datas['remarks'])){
                $data['remarks']=$datas['remarks'];
            }
            if(isset($datas['type'])){
                $data['type']=$datas['type'];
            }
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:46:44 
     * @Desc: 添加供应商信息 
     */    
    function supcompanyAdd(){
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
    function supcompanyOne(){
        $id	=I("id");
        $parameter=[
            'companyId'=>$id,
        ];
        $pListRed="supcompanyList";
        $companyList=$this->Redis->get($pListRed);
        $plist=[];
        if($companyList){
            foreach ($companyList as $company) {
               if($company['companyId']==$id){
                $plist=$company;
                break;
               }
            }
        }
        if(empty($plist)){
            $companyResult=$this->supplierCom->getCompanyList($parameter,true);
            if($companyResult->errCode==0){
                $plist=$companyResult->data;
            }
        }
        if(!empty($plist)){
            $this->ajaxReturn(['errCode'=>0,'info'=>$plist]);
        }
        $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-10 00:02:10 
     * @Desc: 修改供应商信息 
     */    
    function supcompanyEdit(){
        $companyInfo=$this->manageCompanyInfo();
        $updateResult=$this->supplierCom->updateCompany($companyInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
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
        if($reqType){
            $this->$reqType();
        }else{
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->assign('statusType',$this->statusType);
            $this->assign("supplierList",$this->getSupplier());
            $this->returnHtml();
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:51:01 
     * @Desc: 供应商列表 
     */    
    function contactList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=[];
        if($data['companyId']){
            $where['companyId']=$data['companyId'];
        }
        if($data['contact']){
            $where['contact']=['LIKE','%'.$data['contact'].'%'];
        }
        $parameter=[
            'fields'=>"`contactId`,`companyId`,`contact`,`phone`,`email`,`address`,`remarks`,`addTime`,`updateTime`,`status`,company",
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"contactId DESC",
            "joins"=>"LEFT JOIN (SELECT companyId cid,company FROM v_supplier_company WHERE status=1) c ON c.cid=companyId",
        ];
        
        $listResult=$this->supplierCom->getSupplierList($parameter);
        if($listResult){
            $contactRed="supcontactList";
            $this->Redis->set($contactRed,json_encode($listResult['list']),3600);
            $page = new \Think\VPage($listResult['count'], $this->pageSize);
            $pageShow = $page->show();
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->assign('list',$listResult['list']);

            $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('Supplier/supplierTable/contactList'),'page'=>$pageShow]);
        }
        $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:43:33 
     * @Desc: 添加和修改供应商联系人管理 
     */    
    function manageContactInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($reqType=="supcontactAdd"){
            $datas['addTime']=time();
            unset($datas['contactId']);
            return $datas;
        }else if($reqType=="supcontactEdit"){
            $where=["contactId"=>$datas['contactId']];
            $data=[];
            $data['updateTime']=time();
            if(isset($datas['companyId'])){
                $data['companyId']=$datas['companyId'];
            }
            if(isset($datas['contact'])){
                $data['contact']=$datas['contact'];
            }
            if(isset($datas['phone'])){
                $data['phone']=$datas['phone'];
            }
            if(isset($datas['email'])){
                $data['email']=$datas['email'];
            }
            if(isset($datas['address'])){
                $data['address']=$datas['address'];
            }
            if(isset($datas['remarks'])){
                $data['remarks']=$datas['remarks'];
            }
            if(isset($datas['status'])){
                $data['status']=$datas['status'];
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
     * @Date: 2018-05-09 23:59:28 
     * @Desc: 获取单一条供应商联系人信息 
     */    
    function supcontactOne(){
        $id	=I("id");
        $parameter=[
            'contactId'=>$id,
        ];
        $pListRed="supcontactList";
        $contactList=$this->Redis->get($pListRed);
        $plist=[];
        if($contactList){
            foreach ($contactList as $contact) {
               if($contact['contactId']==$id){
                $plist=$contact;
                break;
               }
            }
        }
        if(empty($plist)){
            $contactResult=$this->supplierCom->getUser($parameter);
            if($contactResult->errCode==0){
                $plist=$contactResult->data;
            }
        }
        if(!empty($plist)){
            $this->ajaxReturn(['errCode'=>0,'info'=>$plist]);
        }
        $this->ajaxReturn(['errCode'=>110,'info'=>'无数据']);
    }
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
}