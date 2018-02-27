<?php
class levelTree{
    protected $keys=["levelName"=>"level","idName"=>"id","pidName"=>"pid","nodeName"=>"nodes"];
    protected $replaceData=[];
    protected $errorInfo="";
    protected $beNode=true;
    protected $idAsKey=true;
    /** 
     * @Author: vition 
     * @Date: 2018-02-27 10:55:07 
     * @Desc: 生成树结构 
     * @Return:  
     * @Params:  
     * @Example: 
     */    
    function createTree($Datalist){
        if(!$Datalist || empty($Datalist)){
            $this->errorInfo="列表数据不正确";
            return [$this->keys["nodeName"]=>[]];
        }
        $LevelList=[];
        foreach ($Datalist as $value) {
            $LevelList[$value[$this->keys["levelName"]]][]=$this->_replaceKey($value);
        }
        if(!isset($LevelList[1][0][$this->keys["idName"]]) || !isset($LevelList[1][0][$this->keys["pidName"]])){
            $this->errorInfo="数据中的key不存在";
            return [$this->keys["nodeName"]=>[]];
        }
        if($this->beNode){
            return [$this->keys["nodeName"]=>$this->_createTree($LevelList)];
        }
        return $this->_createTree($LevelList);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-27 10:56:48 
     * @Desc: 内部方法，生成树枝 
     * @Return:  
     * @Params:  
     * @Example: 
     */    
    private function _createTree($Datalist,$level=1){
        if(isset($Datalist[$level+1])){
            $temp=$this->_createTree($Datalist,$level+1);
            $tempArr=[];
            foreach ($Datalist[$level] as $key => $value) {
                foreach ($temp as $keySub => $valueSub) {
                    if($value[$this->keys["idName"]]==$valueSub[$this->keys["pidName"]]){
                        if($this->idAsKey){
                            $value[$this->keys["nodeName"]][$valueSub[$this->keys["idName"]]]=$valueSub;
                        }else{
                            $value[$this->keys["nodeName"]][]=$valueSub;
                        }
                    }
                }
                if($this->idAsKey){
                    $tempArr[$value[$this->keys["idName"]]]=$value;
                }else{
                    $tempArr[]=$value;
                }
                
            }
            return $tempArr;
        }else{
            return $Datalist[$level];
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-27 11:11:53 
     * @Desc: 更改基本键名 只作为判断，要修改显示的键名使用setReplace方法
     * @Params:  $param 数组
     * @Example: $levelTree->setKeys(["idName"=>"roleid","pidName"=>"rolePid"]);
     */    
    function setKeys($param=[]){
        foreach ($param as $key => $value) {
            $this->keys[$key]=$value;
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-27 11:20:33 
     * @Desc: 设置每条数据中要替换的键名 
     * @Return: 
     * @Params:  $param 数组 key必须是数据中存在的键名，
     * @Example: $levelTree->setReplace(["roleid"=>"nodeId","roleTitle"=>"nodeTitle","rolePid"=>"nodePid"]);
     */    
    function setReplace($param=[]){
        $this->replaceData=$param;
        foreach ($this->replaceData as $key => $value) {
            $mixed=array_search($key,$this->keys);
            if($mixed){
                $this->keys[$mixed]=$value;
            }
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-27 11:18:35 
     * @Desc:  内部方法，替换数据的键名
     * @Return:  
     * @Params:  
     * @Example: 
     */    
    private function _replaceKey($array){
        if(!empty($this->replaceData)){
            foreach ($this->replaceData as $oKey => $nKey) {
                if(isset($array[$oKey]) && !isset($array[$nKey])){
                    $array[$nKey]=$array[$oKey];
                    unset($array[$oKey]);
                }
            }
        }
        return $array;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-27 23:27:39 
     * @Desc: 开启配置 
     */    
    function switchOption($param=[]){
        foreach ($param as $key => $value) {
            if(property_exists($this,$key)){
                $this->$key=$value;
            }
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-27 11:32:45 
     * @Desc: 获取最后一条错误信息 
     */    
    function lastError(){
        return $this->errorInfo;
    }
}