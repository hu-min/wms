<?php
namespace Component\Controller;
// use Common\Controller\BaseController;

class CustomerController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->ccompanyDB = D('Component/CustomerCompany');
        $this->ccontactDB = D('Component/CustomerContact');
    }

    function getCompanyList($parameter=[],$one=false){
        $this->selfDB=$this->ccompanyDB;
        $redisName="cust_companyList";
        $parameter["fields"]="`companyId`,`company`,`alias`,`provinceId`,`cityId`,`province`,`city`,`address`,`remarks`,`addTime`,`updateTime`,`status`,`author`,`examine`,processLevel";
        $joins=["LEFT JOIN v_province p ON p.pid=provinceId","LEFT JOIN v_city c ON c.pid=p.pid AND c.cid=cityId"];
        if(isset($parameter["joins"])){
            if(is_array($parameter["joins"])){
                $parameter["joins"]=array_merge($parameter["joins"],$joins);
            }else{
                array_push($joins,$parameter["joins"]);
                $parameter["joins"]=$joins;
            }
        }else{
            $parameter["joins"]=$joins;
        }
        if($one){
            $itemData=$this->redis_one($redisName,"companyId",$id,"ccompanyDB");
            if(empty($itemData)){
                $result=$this->getOne($parameter);
                if(isset($result["list"])){
                    $itemData=$result["list"]; 
                }
            }
            return $itemData;
        }

        return $this->getList($parameter);
    }
    function insertCompany($parameter){
        $this->selfDB=$this->ccompanyDB;
        return $this->insert($parameter);
    }
    function updateCompany($parameter){
        $this->selfDB=$this->ccompanyDB;
        return $this->update($parameter);
    }
    function getCustomerList($parameter=[],$one=false){
        $this->selfDB=$this->ccontactDB;
        $parameter["fields"]="`contactId`,`companyId`,`contact`,`phone`,`email`,`address`,`remarks`,`addTime`,`updateTime`,`status`,company,`author`,`examine`,processLevel";
        $redisName="cust_contactList";
        $joins=["LEFT JOIN (SELECT companyId cid,company FROM v_customer_company WHERE status=1) c ON c.cid=companyId"];
        if(isset($parameter["joins"])){
            if(is_array($parameter["joins"])){
                $parameter["joins"]=array_merge($parameter["joins"],$joins);
            }else{
                array_push($joins,$parameter["joins"]);
                $parameter["joins"]=$joins;
            }
        }else{
            $parameter["joins"]=$joins;
        }
        if($one){
            $itemData=$this->redis_one($redisName,"contactId",$id,"ccontactDB");
            if(empty($itemData)){
                $result=$this->getOne($parameter);
                if(isset($result["list"])){
                    $itemData=$result["list"]; 
                }
            }
            return $itemData;
        }
        return $this->getList($parameter);
    }
    function insertContact($parameter){
        $this->selfDB=$this->ccontactDB;
        return $this->insert($parameter);
    }
    function updateContact($parameter){
        $this->selfDB=$this->ccontactDB;
        return $this->update($parameter);
    }
}