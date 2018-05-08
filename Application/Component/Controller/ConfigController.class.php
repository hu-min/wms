<?php
namespace Component\Controller;
// use Common\Controller\BaseController;

class ConfigController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Config');
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-08 22:04:13 
     * @Desc: 根据name获取配置项指定值 
     */    
    function get_val($name,$json_decode=true){
        $parameter=[
            "where"=>["name"=>$name,"status"=>1],
            "fields"=>"value",
        ];
        $configRes=$this->selfDB->getOne($parameter);
        if($configRes){
            if($json_decode){
                return json_decode($configRes["value"],true);
            }
            return $configRes["value"];
        }
        return false;
    }
}