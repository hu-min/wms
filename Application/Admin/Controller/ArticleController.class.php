<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-03-03 16:20:00 
 * @Desc: 文章管理 
 */
class ArticleController extends BaseController{
    protected $pageSize=15;

    public function _initialize() {
        parent::_initialize();
        $this->articleCom=getComponent('Article');
    }
    /** 
     * @Author: vition 
     * @Date: 2018-03-03 16:21:16 
     * @Desc: 文章控制 
     */    
    function articleControl(){
        $reqType=I('reqType');
        if($reqType){
            $this->$reqType();
        }else{
            $this->assign('url',U(CONTROLLER_NAME.'/'.ACTION_NAME));
            $this->returnHtml();
        }
    }
}