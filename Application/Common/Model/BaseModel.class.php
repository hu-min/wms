<?php
namespace Common\Model;
use Think\Model;

/*
 * 模型基类
 */
class BaseModel extends Model{
    protected $page=1;
    protected $pageNum=20;
    /** 
     * @Author: vition 
     * @Date: 2018-01-14 21:38:53 
     * @Desc: 获取一条数据 
     */    
    public function getOne($parameter=[]){
        $where_arra=$parameter['where']?$parameter['where']:true;
        $fields=$parameter['fields']?$parameter['fields']:'*';
        $noField=$parameter['noField']?$parameter['noField']:false;
        $order=$parameter['order']?$parameter['order']:null;
        $joins=$parameter['joins']?$parameter['joins']:"";
        $having=$parameter['having']?$parameter['having']:"";
        $sum=$parameter['sum']?$parameter['sum']:[];
        if(isset($parameter["where"])){
            $this->where ( $where_arra );
        }else{
            $this->where ( $parameter );
        }
        
        if(is_array($joins)){
            foreach ($joins as $join) {
                $this->join( $join);
            }
        }else{
            $this->join($joins);
        }
        if(!empty($having)){
            $this->having($having);
        }
        if (!empty( $order )) {
            $this->order ( $order );
        }
        foreach ($sum as $key) {
            $fields.=",SUM({$key}) {$key}";
        }
        if (!is_null ( $fields )) {
            $this->field ( $fields ,$noField);
        }    
        return $this->find();
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-14 21:40:12 
     * @Desc: 获取多条数据 
     */    
    public function getList($where_arra , $fields = true, $orderStr = null, $page = 0, $pageNum = 0, $groupBy = null,$joins="",$having=""){
        $page     = $page >= 1 ? $page : $this->page;
        $pageNum = $pageNum >= 1 ? $pageNum : $this->pageNum;
        $fields   = $fields ? $fields : true;
        $this->where($where_arra);
        $this->field ( $fields );
        if(is_array($joins)){
            foreach ($joins as $join) {
                $this->join( $join);
            }
        }else{
            $this->join($joins);
        }
        if($orderStr){
            $this->order($orderStr);
        }
        if(!empty($having)){
            $this->having($having);
        }
        if($groupBy){
            $this->group($groupBy);
        }
        $listData = $this->limit(($page - 1) * $pageNum, $pageNum)->select();

        return $listData;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-14 21:43:21 
     * @Desc: 统计数量 
     */    
    public function countList($where_arra,$joins,$groupBy = null,$having=""){
        if(is_array($joins)){
            foreach ($joins as $join) {
                $this->join( $join);
            }
        }else{
            $this->join($joins);
        }
        if(!empty($having)){
            $this->having($having);
        }
        if($groupBy){
          $subQuery = $this->where($where_arra)->group($groupBy)->buildSql();
          return $this->table($subQuery)->alias('t')->count();
        }else{
          return $this->where($where_arra)->count();
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-14 21:44:19 
     * @Desc: 插入数据 
     */    
    public function insert($data=[]){
        $insertId = false;
        if($this->create($data)){
          $insertId = $this->add();
          if($insertId === false){
              $this->error = '插入数据错误';
          }
        }
        return $insertId;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-14 21:45:06 
     * @Desc: 修改数据 
     */    
    public function modify($where_arra, $data){
        $modFlag = false;
        $modFlag = $this->where($where_arra)->save($data);
        if($modFlag === false){
            $this->error = '更新数据出错';
        }
        return $modFlag;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-01-14 21:45:29 
     * @Desc: 删除数据 
     */    
    public function del($where_arra){
        $modFlag = false;
        $modFlag = $this->where($where_arra)->delete();
        if($modFlag === false){
            $this->error = '删除数据出错';
        }
        return $modFlag;   //删除数据个数
    }
    function sums($where_arra,$fields,$array=[]){
        foreach ($array as $key) {
            $fields.="SUM({$key}) {$key},";
        }
        return $this->where($where_arra)->field(rtrim($fields,","))->find();
    }
}
