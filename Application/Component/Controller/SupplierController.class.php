<?php
namespace Component\Controller;
// use Common\Controller\BaseController;

class SupplierController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->companyDB = D('Component/SupplierCompany');
        $this->contactDB = D('Component/SupplierContact');
    }

    function getCompanyList($parameter=[],$one=false){
        $this->selfDB=$this->companyDB;
        $redisName="sup_companyList";
        if($one){
            $itemData=$this->redis_one($redisName,"companyId",$id,"companyDB");
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
        $this->selfDB=$this->companyDB;
        return $this->insert($parameter);
    }
    function updateCompany($parameter){
        $this->selfDB=$this->companyDB;
        return $this->update($parameter);
    }
    function getSuprContList($parameter=[],$one=false){
        $this->selfDB=$this->contactDB;
        $redisName="sup_contactList";
        if($one){
            $itemData=$this->redis_one($redisName,"contactId",$id,"contactDB");
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
        $this->selfDB=$this->contactDB;
        return $this->insert($parameter);
    }
    function updateContact($parameter){
        $this->selfDB=$this->contactDB;
        return $this->update($parameter);
    }
}