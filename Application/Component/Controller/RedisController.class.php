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
        $this->redis->set($this->onlineName.$userId,time(),1);
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
}