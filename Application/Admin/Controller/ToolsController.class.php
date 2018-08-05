<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-05-06 09:45:33 
 * @Desc: 工具类 
 */
class ToolsController extends BaseController{

    function viewPdf(){
        $title="查看pdf文件";
        $src = I("src");
        $this->assign("title",$title);
        $this->assign("pdfsrc",$src);
        $this->returnHtml();
    }
}