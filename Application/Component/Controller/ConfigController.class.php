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
        $redsName = "config_".$name;
        $val = $this->Redis->get($redsName);
        
        if($val){
            return $val;
        }else{
            $parameter=[
                "where"=>["name"=>$name,"status"=>1],
                "fields"=>"value",
                'one'=>true,
            ];
            $this->log($parameter);
            $configRes=$this->getOne($parameter);
        }

        if($configRes){
    
            if($configRes){
                $this->Redis->set($redsName,$configRes,3600);
                return $configRes;
            }
            if($json_decode){
                $this->Redis->set($redsName,json_decode($configRes["value"],true),3600);
                return json_decode($configRes["value"],true);
            }
            $this->Redis->set($redsName,$configRes["value"],3600);
            return $configRes["value"];
        }
        return false;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-12 09:35:57 
     * @Desc: 根据name设置配置项值 
     */    
    function set_val($name,$val){
        $valJson=$val;
        if(is_array($val)){
            $valJson=json_encode($val,JSON_UNESCAPED_UNICODE);
        }
        $nameRes=$this->get_val($name);
        if(!$nameRes){
            $result=$this->insert(["name"=>$name,"value"=>$valJson,"status"=>1]);
        }else if(!is_null($nameRes)){//存在则修改
            $result=$this->update(["where"=>["name"=>$name],"data"=>["value"=>$valJson]]);
        }
        $this->redisCom->delAll("","config_".$name); //清空一下redis 的缓存
        return $result;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-12-13 23:00:27 
     * @Desc: 判断服务器是否锁定 
     */    
    function is_web_lock(){
        $name = "web_lock";
        $val = $this->Redis->get("config_".$name);
        
        if($val){
            return $val;
        }
        $parameter=[
            "where"=>["name"=>$name,"status"=>1],
            "fields"=>"value",
            'one'=>true,
        ];
        $configRes=$this->getOne($parameter);
        
        if(!$configRes){
            $configRes = ['open'];
        }
        $this->Redis->set("config_".$name,$configRes,3600);
        return $configRes;
    }
}