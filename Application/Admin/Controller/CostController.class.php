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
        header('content-type:text/event-stream');
        header('cache-control:no-cache');
        $time = date("Y-m-d H:i:s");
        
        echo "data: The server time is: {$time}\n\n";
        flush();
    }
}