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
    //供应商配置开始    
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
    function supTypeEdit(){
        $supTypeInfo=$this->manageSupTypeInfo();
        $updateResult=$this->basicCom->updateBasic($supTypeInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    //供应商配置结束

    //供应商公司管理开始
    function companyControl(){
        $reqType=I('reqType');
        $this->assign('statusType',$this->statusType);
        if($reqType){
            $this->$reqType();
        }else{
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->assign("province",$this->basicCom->get_provinces());
            $this->returnHtml();
        }
    }

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
        $parameter=[
            'where'=>$where,
            'fields'=>"`companyId`,`type`,`company`,`alias`,`provinceId`,`cityId`,`province`,`city`,`address`,`remarks`,`addTime`,`updateTime`,`status`",
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"companyId DESC",
            "joins"=>["LEFT JOIN v_province p ON p.pid=provinceId","LEFT JOIN v_city c ON c.pid=p.pid AND c.cid=cityId"],
        ];
        
        $listResult=$this->supplierCom->getCompanyList($parameter);
        if($listResult){
            $companyRed="companyList";
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
     * @Desc: 添加和修改信息管理 
     */    
    function manageCompanyInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($reqType=="companyAdd"){
            $datas['addTime']=time();
            unset($datas['companyId']);
            return $datas;
        }else if($reqType=="companyEdit"){
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
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:46:44 
     * @Desc: 添加供应商信息 
     */    
    function companyAdd(){
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
    function companyOne(){
        $id	=I("id");
        $parameter=[
            'companyId'=>$id,
        ];
        $pListRed="companyList";
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
    function companyEdit(){
        $companyInfo=$this->manageCompanyInfo();
        $updateResult=$this->supplierCom->updateCompany($companyInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    //供应商公司管理结束

    //供应商联系人管理开始
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:42:41 
     * @Desc: 供应商信息控制 
     */    
    function contactControl(){
        $reqType=I('reqType');
        if($reqType){
            $this->$reqType();
        }else{
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->assign('statusType',$this->statusType);
            $companyHtml="";
            $companyList=$this->supplierCom->find_company();
            foreach ($companyList["list"] as $company) {
                $companyHtml.="<option value='{$company['companyId']}'>{$company['company']}</option>";
            }
            $this->assign('companyList',$companyHtml);
            $this->returnHtml();
        }
    }
    function findCompanyList(){
        $key=I("companykey");
        $companyRes=$this->supplierCom->find_company(urldecode($key));
        if($companyRes){
            $this->ajaxReturn(['errCode'=>0,'data'=>$companyRes["list"]]);
        }
        $this->ajaxReturn(['errCode'=>110,'error'=>""]);
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
            $contactRed="contactList";
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
     * @Desc: 添加和修改信息管理 
     */    
    function manageContactInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($reqType=="contactAdd"){
            $datas['addTime']=time();
            unset($datas['contactId']);
            return $datas;
        }else if($reqType=="contactEdit"){
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
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:46:44 
     * @Desc: 添加供应商信息 
     */    
    function contactAdd(){
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
     * @Desc: 获取单一条供应商信息 
     */    
    function contactOne(){
        $id	=I("id");
        $parameter=[
            'contactId'=>$id,
        ];
        $pListRed="contactList";
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
    function contactEdit(){
        $contactInfo=$this->manageContactInfo();
        $updateResult=$this->supplierCom->updateContact($contactInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
}