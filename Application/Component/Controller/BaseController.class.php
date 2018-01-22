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
    /** 
     * @Author: vition 
     * @Date: 2018-01-14 22:16:12 
     * @Desc: 疯狂的入口 
     */    
    function __call($fun,$argu){
        return '不存在';
    }
}
