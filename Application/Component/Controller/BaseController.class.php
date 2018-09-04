<?php
namespace Component\Controller;
// use Think\Cache\Driver\Redis;
/**
 * BaseController 控件基类
 *     公共控制文件
 * 
 * @author vition
 * @date 2017-11-17
 */

class BaseController extends \Common\Controller\BaseController{
    protected $selfDB="";
    /** 
     * @Author: vition 
     * @Date: 2018-01-14 22:16:12 
     * @Desc: 疯狂的入口 
     */    
    function __call($fun,$argu){
        $thisClass=get_class($this);
        $thisClass=explode("\\",$thisClass);
        $className=str_replace("Controller","",$thisClass[count($thisClass)-1]);
        $method=str_replace($className,"",$fun);
        if(method_exists(__CLASS__,$method)){
            return $this->$method($argu[0]);
        }
        return false;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-04-01 12:50:02 
     * @Desc: 统一获取列表 
     */    
    function getList($parameter=[]){
        $res=$this->initRes();
        $where=$parameter['where']?$parameter['where']:true;
        $fields=$parameter['fields']?$parameter['fields']:"*";
        $orderStr=$parameter['orderStr']?$parameter['orderStr']:null;
        $page=$parameter['page']?$parameter['page']:0;
        $pageNum=$parameter['pageSize']?$parameter['pageSize']:0;
        $groupBy=$parameter['groupBy']?$parameter['groupBy']:null;
        $joins=$parameter['joins']?$parameter['joins']:"";
        $having=$parameter['having']?$parameter['having']:"";
        $count=$this->selfDB->countList($where,$joins,$groupBy,$having);
        $classList=$this->selfDB->getList($where , $fields, $orderStr, $page, $pageNum, $groupBy,$joins,$having);
        // $this->log($this->selfDB->_sql());
        
        if($classList){
            return ['list'=>$classList,'count'=>$count];
        }
        return false;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-04-01 12:50:15 
     * @Desc: 获取单一行 
     */    
    function getOne($parameter=[]){
        $res=$this->initRes();
        if($parameter && isset($parameter["where"])){
            $where=$parameter['where']?$parameter['where']:true;
            $fields=$parameter['fields']?$parameter['fields']:"*";
            $orderStr=$parameter['orderStr']?$parameter['orderStr']:null;
            $joins=$parameter['joins']?$parameter['joins']:null;
            $having=$parameter['having']?$parameter['having']:null;
            $sum=$parameter['sum']?$parameter['sum']:null;
            $classList=$this->selfDB->getOne(['where'=>$where,'fields'=>$fields,"joins"=>$joins,"having"=>$having,"sum"=>$sum]);
        }else{
            $classList=$this->selfDB->getOne($parameter);
        }
        
        if($classList){
            return ['list'=>$classList];
        }
        return false;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-04-01 12:50:26 
     * @Desc: 插入数据 
     */    
    function insert($parameter){
        $res=$this->initRes();
        $insertResult=$this->selfDB->insert($parameter);
        if($insertResult){
            $res->errCode=0;
            $res->error=getError(0);
            $res->data=$insertResult;
            return $res;
        }
        $res->errCode=111;
        $res->error=getError(111);
        return $res;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-04-01 12:50:33 
     * @Desc: 更新数据 
     */    
    function update($parameter){

        $res=$this->initRes();
        if($parameter && isset($parameter["where"]) && isset($parameter["data"])){
            $insertResult=$this->selfDB->modify($parameter["where"],$parameter["data"]);
        }else{
            $insertResult=$this->selfDB->save($parameter);
        }
        
        if($insertResult){
            $res->errCode=0;
            $res->error=getError(0);
            return $res;
        }
        $res->errCode=114;
        $res->error=getError(114);
        return $res;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-07-19 00:59:31 
     * @Desc: 删除数据 
     */    
    public function del($where_arra){
        $res=$this->initRes();
        $modFlag = false;
        $modFlag = $this->selfDB->where($where_arra)->delete();
        if($modFlag){
            $res->errCode=0;
            $res->error=getError(0);
            return $res;
        }
        $res->errCode=113;
        $res->error=getError(113);
        return $res;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-07 23:02:26 
     * @Desc: M方法 
     */    
    function M(){
        return $this->selfDB;
    }
    function tableName(){
        return $this->selfDB->getTableName();
    }
    /** 
     * @Author: vition 
     * @Date: 2018-06-02 00:34:18 
     * @Desc: 从redis中获取单一数据，没有就查指定数据库id 
     */    
    function redis_one($redisName,$key,$id,$db=false){
        
        $listRedisName=$redisName;
        $listRedis=$this->Redis->get($listRedisName);
        $itemData=[];
        if($listRedis){
            foreach ($listRedis as $item) {
                if($item[$key]==$id){
                    $itemData=$item;
                    break;
                }
            }
        }
        if(empty($itemData)){
            if($db){
                $questResult=$this->selfDB = $this->$db;
            }
            $questResult=$this->getOne(["where"=>[$key=>$id]]);
            if($questResult->errCode==0){
                $itemData=$questResult["list"];
            }
        }
        return $itemData;
    }
    function startTrans(){
        $this->selfDB->startTrans();
    }
    function commit(){
        $this->selfDB->commit();
    }
    function rollback(){
        $this->selfDB->rollback();
    }
}
