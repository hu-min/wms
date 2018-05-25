<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-05-09 22:46:55 
 * @Desc: 客户管理 
 */
class CustomerController extends BaseController{
    protected $pageSize=10;
    public function _initialize() {
        parent::_initialize();
        $this->basicCom=getComponent('Basic');
        $this->CustomerCom=getComponent('Customer');
        $this->statusType=["0"=>"未启用","1"=>"启用"];
    }
    //客户公司管理开始
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
     * @Desc: 客户列表 
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
            'fields'=>"`companyId`,`company`,`alias`,`provinceId`,`cityId`,`province`,`city`,`address`,`remarks`,`addTime`,`updateTime`,`status`",
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"companyId DESC",
            "joins"=>["LEFT JOIN v_province p ON p.pid=provinceId","LEFT JOIN v_city c ON c.pid=p.pid AND c.cid=cityId"],
        ];
        
        $listResult=$this->CustomerCom->getCompanyList($parameter);
        if($listResult){
            $companyRed="companyList";
            $this->Redis->set($companyRed,json_encode($listResult['list']),3600);
            $page = new \Think\VPage($listResult['count'], $this->pageSize);
            $pageShow = $page->show();
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->assign('list',$listResult['list']);

            $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('Customer/customerTable/companyList'),'page'=>$pageShow]);
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
     * @Desc: 添加客户信息 
     */    
    function companyAdd(){
        $dataInfo=$this->manageCompanyInfo();
        if($dataInfo){
            $insertResult=$this->CustomerCom->insertCompany($dataInfo);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:59:28 
     * @Desc: 获取单一条客户信息 
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
            $companyResult=$this->CustomerCom->getCompanyList($parameter,true);
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
     * @Desc: 修改客户信息 
     */    
    function companyEdit(){
        $companyInfo=$this->manageCompanyInfo();
        $updateResult=$this->CustomerCom->updateCompany($companyInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
    //客户公司管理结束

    //客户联系人管理开始
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:42:41 
     * @Desc: 客户信息控制 
     */    
    function customerControl(){
        $reqType=I('reqType');
        $this->assign('customerType',$this->customerType);
        if($reqType){
            $this->$reqType();
        }else{
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->returnHtml();
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:51:01 
     * @Desc: 客户列表 
     */    
    function customerList(){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $where=[];
        if($data['company']){
            $where['company']=['LIKE','%'.$data['company'].'%'];
        }
        if($data['contact']){
            $where['contact']=['LIKE','%'.$data['contact'].'%'];
        }
        if(isset($data['type'])){
            $where['type']=$data['type'];
        }
        $parameter=[
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$this->pageSize,
            'orderStr'=>"customerId DESC"
        ];
        
        $listResult=$this->customerCom->getCustomerList($parameter);
        if($listResult){
            $customerRed="customerList";
            $this->Redis->set($customerRed,json_encode($listResult['list']),3600);
            $page = new \Think\VPage($listResult['count'], $this->pageSize);
            $pageShow = $page->show();
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->assign('customerList',$listResult['list']);

            $this->ajaxReturn(['errCode'=>0,'table'=>$this->fetch('Customer/customerTable/customerList'),'page'=>$pageShow]);
        }
        $this->ajaxReturn(['errCode'=>0,'table'=>'无数据','page'=>'']);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:43:33 
     * @Desc: 添加和修改信息管理 
     */    
    function manageCustomerInfo(){
        $reqType=I("reqType");
        $datas=I("data");
        if($reqType=="customerAdd"){
            $datas['addTime']=time();
            unset($datas['customerId']);
            return $datas;
        }else if($reqType=="customerEdit"){
            $where=["customerId"=>$datas['customerId']];
            $data=[];
            if(isset($datas['type'])){
                $data['type']=$datas['type'];
            }
            if(isset($datas['company'])){
                $data['company']=$datas['company'];
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
     * @Desc: 添加客户信息 
     */    
    function customerAdd(){
        $dataInfo=$this->manageCustomerInfo();
        if($dataInfo){
            $insertResult=$this->customerCom->insertCustomer($dataInfo);
            if($insertResult && $insertResult->errCode==0){
                $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
            }
        }
        $this->ajaxReturn(['errCode'=>100,'error'=>getError(100)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-09 23:59:28 
     * @Desc: 获取单一条客户信息 
     */    
    function customerOne(){
        $id	=I("id");
        $parameter=[
            'customerId'=>$id,
        ];
        $pListRed="customerList";
        $customerList=$this->Redis->get($pListRed);
        $plist=[];
        if($customerList){
            foreach ($customerList as $customer) {
               if($customer['customerId']==$id){
                $plist=$customer;
                break;
               }
            }
        }
        if(empty($plist)){
            $customerResult=$this->customerCom->getUser($parameter);
            if($customerResult->errCode==0){
                $plist=$customerResult->data;
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
     * @Desc: 修改客户信息 
     */    
    function customerEdit(){
        $customerInfo=$this->manageCustomerInfo();
        $updateResult=$this->customerCom->updateCustomer($customerInfo);
        $this->ajaxReturn(['errCode'=>$updateResult->errCode,'error'=>$updateResult->error]);
    }
}