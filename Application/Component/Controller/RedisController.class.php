<?php
namespace Component\Controller;
use Think\Controller;
use Think\Cache\Driver\Redis;

class RedisController extends Controller{

    function _initialize()
    {
        $this->redis = new Redis();
        $this->prefix = $this->redis->prefix();
        $this->onlineName = "user_online_";
    }

    function addOnline($userId)
    {
        $this->redis->set($this->onlineName.$userId,time(),300);
    }
    function offline($userId){
        $this->delAll("",$this->onlineName.$userId);
    }
    function rset($name,$value="",$seconds=300)
    {
        return $this->redis->set($name,$value,$seconds);
    }
    function rget($name){
        return $this->redis->get($name);
    }
    function isOnline()
    {

    }

    function onlineList()
    {
        $online = $this->redis->R()->keys($this->prefix.$this->onlineName."*");
        $returnData = ['count'=>0,'ids'=>[]];
        $returnData['count'] = count($online);
        foreach ($online as  $userKey) {
            array_push($returnData['ids'],str_replace($this->prefix.$this->onlineName,'',$userKey));
        }
        return $returnData;
    }
    function getRedisAll($prefix="",$extend=""){
        if(!$prefix){
            $prefix = $this->prefix;
        }
        return $this->redis->R()->keys($prefix.$extend."*");
    }
    function delAll($prefix="",$extend=""){
        if(!$prefix){
            $prefix = $this->prefix;
        }
        $this->redis->R()->delete($this->redis->R()->keys($prefix.$extend."*"));
    }
}