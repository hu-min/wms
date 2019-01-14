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
	    return $this->selfDB->where(["class"=>"execute","level"=>1])->select();
    }
    function get_class_data($className){
        // $classNameRed="basic_".$className;
        // $classList=$this->Redis->get($classNameRed);
        $classList=false;
        if(!$classList){
            $classList=$this->selfDB->where(["class"=>$className])->select();
            $this->Redis->set($classNameRed,$classList,3600);
        }
        return $classList;
    }
    function clear_cache($className){
        $classNameRed="basic_".$className;
        $this->Redis->set($classNameRed,$classList,1);
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
    /** 
     * @Author: vition 
     * @Date: 2018-05-24 06:43:12 
     * @Desc: 获取费用类型节点 
     */ 
    function getFeeTypeTree(){
        Vendor("levelTree.levelTree");
        $levelTree=new \levelTree();
        $parameter=[
            'where'=>["class"=>"feeType"],
            'page'=>0,
            'pageSize'=>999999,
            'orderStr'=>'level DESC',
        ];
        $feeTypeResult=$this->getList($parameter);
        $feeTypeTree=[];
        $level=[];
        
        $feeTypeArray=$feeTypeResult["list"];
        foreach ($feeTypeArray AS $key => $feeTypeInfo) {
            $level[$feeTypeInfo["level"]][$feeTypeInfo["Pid"]][]= $feeTypeInfo;
            unset($feeTypeArray[$key]);
        }
        $this->Redis->set("feeTypeArray",json_encode($feeTypeResult["list"]),3600);
        asort($level);
        
        $levelTree->setKeys(["idName"=>"basicId","pidName"=>"pId"]);
        $levelTree->setReplace(["name"=>"text","basicId"=>"id"]);
        $levelTree->switchOption(["beNode"=>false,"idAsKey"=>false]);
        $feeTypeTree=$levelTree->createTree($feeTypeResult["list"]);
        return $feeTypeTree;
    }
    function getfeeType($element,$level){
        $option="";
        $strs="";
        for ($i=0; $i < $level; $i++) { 
            $strs.="——";
        }
        if(is_array($element["nodes"])){
            $level++;
            foreach ($element["nodes"] as $key => $value) {
                $option.= $this->getfeeType($value,$level);
            }
        }
        return '<option value="'.$element["id"].'">'.$strs.$element["text"].'</option>'.$option;
    }
}
