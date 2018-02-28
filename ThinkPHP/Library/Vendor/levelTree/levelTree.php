<?php
class levelTree{
    protected $keys=["levelName"=>"level","idName"=>"id","pidName"=>"pid","nodeName"=>"nodes"];
    protected $replaceData=[];
    /** 
     * uAttr 子ul的属性
     * aLiAttr      独立（无下级）li的属性
     * aAAttr       独立（无下级）li a的属性
     * aIAttr       独立（无下级）li i的属性
     * aSpanAttr    独立（无下级）li span的属性
     * nLiAttr      节点（有下级）li 的属性
     * nAAttr       节点（有下级）li a的属性
     * nIAttr       节点（有下级）li i的属性
     * nSpanAttr    节点（有下级）li span的属性
     * nArAttr      节点（有下级）li span 箭头的属性
     * nArIAttr     节点（有下级）li i 箭头的属性
     */    
    protected $htmlAttr=["uAttr"=>"","aLiAttr"=>"","aAAttr"=>"","aIAttr"=>"","aSpanAttr"=>"","nLiAttr"=>"","nAAttr"=>"","nIAttr"=>"","nSpanAttr"=>"","nArAttr"=>"","nArIAttr"=>""];
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
        if(!array_key_exists($this->keys["idName"],$LevelList[1][0]) || !array_key_exists($this->keys["pidName"],$LevelList[1][0])){
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
        if(array_key_exists($level+1,$Datalist)){
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
                if(array_key_exists($oKey,$array) && !array_key_exists($nKey,$array)){
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
     * @Date: 2018-02-28 15:36:10 
     * @Desc: tree转换成html ul 结构 
     * @Return:  
     * @Params:  
     * @Example: 
     */    
    function tree2Html($nodes,$outerUl=false){
        if($outerUl){
            return "<ul>".$this->_tree2Html($nodes)."</ul>";
        }
        return $this->_tree2Html($nodes);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-28 18:31:49 
     * @Desc: 内部 tree2Html
     * @Return:  
     * @Params:  
     */    
    private function _tree2Html($nodes){
        
        if(array_key_exists($this->keys["nodeName"],$nodes)){
            $html="";
            foreach ($nodes[$this->keys["nodeName"]] as $key => $nodeSub) {
                if(!array_key_exists($this->keys["nodeName"],$nodeSub)){
                    $html.='<li '.$this->_replaceVal($this->htmlAttr["aLiAttr"],$nodeSub).'><a '.$this->_replaceVal($this->htmlAttr["aAAttr"],$nodeSub).'><i '.$this->_replaceVal($this->htmlAttr["aIAttr"],$nodeSub).'></i> <span  '.$this->_replaceVal($this->htmlAttr["aSpanAttr"],$nodeSub).'>'.$nodeSub["nodeTitle"].'</span></a></li>';
                }else{
                    $html.='<li '.$this->_replaceVal($this->htmlAttr["nLiAttr"],$nodeSub).'>
                    <a '.$this->_replaceVal($this->htmlAttr["nAAttr"],$nodeSub).'>
                        <i '.$this->_replaceVal($this->htmlAttr["nIAttr"],$nodeSub).'></i> <span '.$this->_replaceVal($this->htmlAttr["nSpanAttr"],$nodeSub).'>'.$nodeSub["nodeTitle"].'</span>
                        <span '.$this->_replaceVal($this->htmlAttr["nArAttr"],$nodeSub).'>
                            <i '.$this->_replaceVal($this->htmlAttr["nArIAttr"],$nodeSub).'></i>
                        </span>
                    </a>';
                    $html.='<ul '.$this->_replaceVal($this->htmlAttr["uAttr"],$nodeSub).'>';
                    $html.=$this->_tree2Html($nodeSub);
                    $html.='</ul>';
                }
                
            }
            return $html;
        }else{
            $this->errorInfo="节点中不存在".$this->keys["nodeName"]."键名";
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-28 15:56:10 
     * @Desc: 设置treeHtml里 标签的属性值 
     * @Return:  
     * @Params:  
     * @Example: 
     */    
    function setHtmlAttr($attr=[]){
        foreach ($attr as $key => $value) {
            $this->htmlAttr[$key]=$value;
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-02-28 15:49:49 
     * @Desc:  替换 htmlAttr 中可能存在的键名对应当前节点键名的值
     * @Params:  
     */    
    private function _replaceVal($Attrs,$nodes){
        
        preg_match_all("/{\[([\S]+)\]}/",$Attrs,$match);
        if(!empty($match[0])){
            foreach ($match[1] as $key=>$value) {
                $exp=explode("|",$value);
                if(count($exp)>1){
                    $value=$exp[0];
                }
                if(array_key_exists($value,$nodes)){
                    if(count($exp)>1){
                        
                        if($nodes[$value]==""){
                            
                            $match[1][$key]="#";
                        }else{
                            $match[1][$key]=eval("return ".$exp[1]."('{$nodes[$value]}');");
                        }
                        
                        $match[0][$key]="'\{\[".$value."\|".$exp[1]."\]\}'";
                    }else{
                        $match[0][$key]="'\{\[".$value."\]\}'";
                        $match[1][$key]=$nodes[$value];
                    }
                    
                }else{
                    
                    $this->errorInfo="节点中不存在".$value."键名";
                    unset($match[0][$key]);
                    unset($match[1][$key]);
                    $Attrs=preg_replace("/[\S]+=['\"]+{[".$value."]}['\"]+/","",$Attrs);
                }
            }
            return preg_replace($match[0],$match[1],$Attrs);
        }
        return $Attrs;
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