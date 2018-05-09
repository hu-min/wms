<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-05-09 22:46:55 
 * @Desc: 客户管理 
 */
class CustomerController extends BaseController{
    protected $pageSize=15;
    public function _initialize() {
        parent::_initialize();
        $this->customerCom=getComponent('Customer');
        $this->customerType=["1"=>"企业","2"=>"个人"];
    }
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