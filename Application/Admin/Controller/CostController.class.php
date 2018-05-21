<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-05-06 09:45:33 
 * @Desc: 成本管理 
 */
class CostController extends BaseController{
    protected $pageSize=15;
    public function _initialize() {
        parent::_initialize();
        $this->projectCom=getComponent('Project');
        $this->configCom=getComponent('Config');
        $this->customerCom=getComponent('Customer');
        $this->costCom=getComponent('Cost');
        Vendor("levelTree.levelTree");
        $this->levelTree=new \levelTree();
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-16 23:35:32 
     * @Desc: 成本控制 
     */    
    function costControl(){
        $reqType=I('reqType');
        if($reqType){
            $this->$reqType();
        }else{
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->returnHtml();
        }
    }
    /** 
     * @Author: vition 
     * @Date: 2018-05-17 00:33:48 
     * @Desc: 获取表单了 
     */    
    function formOne(){
        $form=I('form');
        $this->ajaxReturn(['html'=>$this->fetch("Cost/form/".$form),'errCode'=>0,'error'=>getError(0)]);
    }
    function testList(){
        
        // header('content-type:text/atext/event-streamContent-Type');
        // header('Content-Type: text/event-stream; charset=utf-8');
        // // // header('content-type:text/event-stream');
        // header('cache-control:no-cache');
        
        // while (true) {
        //     $rand=rand(1,10);
        //     if($rand<5){
        //         $time = date("Y-m-d H:i:s");
        //         echo "data: The server time is: {$time}\n\n";
        //     }
        //     ob_flush();
        //     flush();
        //     sleep(1);
        // }
        // $time = date("Y-m-d H:i:s");
        // // ob_start();
        // echo "data: The server time is: {$time}\n\n";
        // echo PHP_EOL;
        // ob_flush();
        // flush();
        // ob_end_flush();
        // flush();
        header("Content-Type:text/event-stream\n\n"); 
        $counter = rand(1, 10);
        while (1) {  
        // Every second, sent a "ping"event.     
        echo "event: ping\n";  
        $curDate = date(DATE_ISO8601);  
        echo 'data: {"time": "' .$curDate . '"}';  
        echo "\n\n";     
        // Send a simple message at randomintervals.     
        $counter--;     
        if (!$counter) {    
            echo'data: This is a message at time ' .
            $curDate. "\n\n";    
            $counter = rand(1, 10);  
        }     
        ob_flush();  
        flush();  
        sleep(1);
        }
    }
}