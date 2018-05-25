<?php
namespace Component\Controller;
// use Common\Controller\BaseController;
/** 
 * @Author: vition 
 * @Date: 2018-05-20 22:17:37 
 * @Desc: 基础数据组件 
 */
class BasicController extends BaseController{
    public function _initialize(){
        parent::_initialize();
        $this->selfDB = D('Component/Basic');
    }
    function get_exe_root(){
	    return $this->selfDB->where(["class"=>"exeRoot"])->select();
    }
    function get_class_data($className){
        return $this->selfDB->where(["class"=>$className])->select();
    }
    
    function get_provinces($pid=0){
        $provinceRed="province";
        $provinceList=$this->Redis->get($provinceRed);
        if(!$provinceList){
            $provinceList=M("province")->select();
            $this->Redis->set($provinceRed,$provinceList,3600);
        }
        if($pid==0){
            return $provinceList;
        }else{
            foreach ($provinceList as $province) {
                if($province['pid']==$pid){
                    return  $province;
                }
            }
        }
        
    }
    function get_citys($pid,$cid=0){
        $citysRed="province_".$pid."_city";
        $cityList=$this->Redis->get($citysRed);
        if(!$cityList){
            $cityList=M("city")->where(["pid"=>$pid])->select();;
            $this->Redis->set($citysRed,$cityList,3600);
        }
        if($cid>0){
            foreach ($cityList as $city) {
                if($city['cid']==$cid){
                    return  $city;
                }
            }
            return M("city")->where(["cid"=>$cid])->find(); 
        }else{
            return $cityList;
        }
        
    }
}
