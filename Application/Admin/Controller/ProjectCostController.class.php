<?php
namespace Admin\Controller;

/** 
 * @Author: vition 
 * @Date: 2018-11-16 11:26:07 
 * @Desc: 项目成本控制器 
 */
class ProjectCostController extends BaseController{
    public function _initialize() {
        $this->statusLabel[10] = 'maroon';
        $this->statusType[10] = '草稿';
        parent::_initialize();
        $this->projectCom=getComponent('Project');
        $this->pOfferCom=getComponent('ProjectOffer');
        $this->pCostCom=getComponent('ProjectCost');
        $this->pCostSubCom=getComponent('ProjectCostSub');
        

        // $this->supplierCom=getComponent('Supplier');
        // $this->purchaCom=getComponent('Purcha');
        // $this->fieldCom=getComponent('Field');
        // $this->filesCom=getComponent('ProjectFiles');
        // $this->ReceCom=getComponent('Receivable');
        // $this->whiteCom=getComponent('White');
        // $this->InvoiceCom=getComponent('Invoice');
        // $this->payCom=getComponent('Pay');
        // $this->processArr=["0"=>"沟通","1"=>"完结","2"=>"裁决","3"=>"提案","4"=>"签约","5"=>"LOST","6"=>"筹备","7"=>"执行","8"=>"完成"];
        // $this->dateArr=["0"=>"立项日期","1"=>"提案日期","2"=>"项目日期","3"=>"结束日期"];

        // Vendor("levelTree.levelTree");
        // $this->levelTree=new \levelTree();
    }
    /** 
     * @Author: vition 
     * @Date: 2018-11-16 11:27:03 
     * @Desc: 项目报价 
     */    
    function project_offer(){
        $reqType=I('reqType');
        $this->assign("controlName","project_offer");
        $this->assign("listType","offer");
        $this->assign("tableName",$this->pOfferCom->tableName());
        
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function project_offer_modalOne($listType='offer',$fixedTitle=false){
        $title = "新增报价";
        $btnTitle = "添加数据";
        $gettype = I("gettype");
        $export = I('export');
   
        if($listType == 'offer'){
            $offerCostCom = $this->pOfferCom;
            $parent_id = 'parent_oid';
            $joins = [
                "LEFT JOIN (SELECT project_id c_project_id , section c_section,flag c_flag,cost_total,profit,profit_ratio,user_id c_user_id FROM v_project_cost ) c ON c.c_project_id = project_id AND c.c_section = section AND c.c_flag = flag",
                "LEFT JOIN (SELECT userId, userName cuser_name FROM v_user ) cu ON cu.userId = c_user_id ",
            ];
        }else{
            $offerCostCom = $this->pCostCom;
            $parent_id = 'parent_cid';
            $joins = [
                "LEFT JOIN (SELECT id oid,project_id o_project_id , section o_section,flag o_flag,total,tax_rate,user_id o_user_id FROM v_project_offer ) o ON o.o_project_id = project_id AND o.o_section = section AND o.o_flag = flag",
                "LEFT JOIN (SELECT userId, userName ouser_name FROM v_user ) ou ON ou.userId = o_user_id ",
            ];
        }
        $this->assign('costClassArr',$this->Com ->get_option('costClass'));
        $this->assign('moduleArr',$this->Com ->get_option('module'));
        $this->assign('unitArr',$this->Com ->get_option('unit'));
        $this->assign('projectArr',$this->Com ->get_option('project','',[$listType.'_user'=>session('userId')]));
        $this->assign('userArr',$this->Com->get_option("user"));

        $resultData=[];
        $id = I("id");
        $roleId = session('roleId');
        if($gettype=="Edit"){
            $this->assign('projectArr',$this->Com ->get_option('project'));
            $title = "编辑报价";
            $btnTitle = "保存提交";
            $redisName="project_offerList";
            $param = [
                'fields' => "*,FIND_IN_SET({$roleId},examine) place",
                'where'=>['id'=>$id],
                'joins' => $joins,
            ];
            $resultData = $offerCostCom->getOne($param)['list'];
          
            if($listType == 'offer'){
                $parent_idStr = ' `parent_oid` = "'.$resultData['id'].'"';
            }else{
                $parent_idStr = ' ( `parent_oid` = "'.$resultData['oid'].'" OR `parent_cid` = "'.$resultData['id'].'") ';;
            }

            $where = [
                '_string' => $parent_idStr,
                'read_type' => 1,
            ];
            if($listType == "cost"){
                $where['read_type'] = ['EGT',1];
            }
            $sParam =[
                'fields'=>"*",
                'where'=>$where,
                'pageSize'=>9999999,
                'orderStr'=>"class_sort ASC , sort ASC",
                'joins' => [
                    'LEFT JOIN (SELECT basicId, name cost_class_name FROM v_basic WHERE class ="costClass" ) bc ON bc.basicId = cost_class',
                    'LEFT JOIN (SELECT classify module_id , GROUP_CONCAT(companyId) scompany_ids , GROUP_CONCAT(company) scompany_names FROM (SELECT classify FROM v_project_cost_sub WHERE '.$parent_idStr.' AND `read_type` >= 1) pc LEFT JOIN (SELECT module,companyId,company FROM v_supplier_company) sc ON FIND_IN_SET(pc.classify,sc.module) GROUP BY pc.classify) m ON m.module_id = classify',
                    'LEFT JOIN (SELECT contactId,companyId,contact scontact_name FROM v_supplier_contact ) suc ON suc.companyId = scompany_id AND suc.contactId = scompany_cid ',
                    'LEFT JOIN (SELECT basicId unit_id, name unit_name FROM v_basic WHERE class ="unit" ) un ON un.unit_id = unit',
                    'LEFT JOIN (SELECT basicId aunit_id, name aunit_name FROM v_basic WHERE class ="unit" ) aun ON aun.aunit_id = act_unit',
                    'LEFT JOIN (SELECT basicId mid, name module_name FROM v_basic WHERE class ="module" ) mo ON mo.mid = m.module_id',
                ],
            ];
            $subResult = $this->pCostSubCom->getList($sParam);
            // $this->log($this->pCostSubCom->_sql());
            if($subResult){
                $resultData['list'] = $subResult['list'];
            }else{
                $resultData['list'] = [];
            }
            
            if($export){
                $sql = explode("LIMIT",$this->pCostSubCom->M()->_sql())[0];
                $this->Redis->set(md5($sql),$sql,300);
                $this->ajaxReturn(['sql'=>md5($sql),'url'=>U(CONTROLLER_NAME.'/excel_export')."?sql=".md5($sql)]);
            }
            // echo $this->pCostSubCom->M()->_sql();exit;
            // $resultData=[];
        }
        $resultData['panel'] = $this->fetch('ProjectCost/projectcostTable/panel');
        $resultData['item'] = $this->fetch('ProjectCost/projectcostTable/item');
        $title = $fixedTitle ? $fixedTitle : $title;
        $modalPara=[
            "data"=>$resultData,
            "title"=>$title,
            "btnTitle"=>$btnTitle,
            "template"=>"project_offerModal",
        ];
        $this->modalOne($modalPara);
    }
    function project_offer_export($excelData){
        return $this->pcost_control_export($excelData);
    }
    function project_costCon_export($excelData){
        return $this->pcost_control_export($excelData);
    }
    function pcost_control_export($excelData){
        
        $schema=[
            'cost_class_name' => ['name'=>'分类'],
            'item_content' => ['name'=>'项目内容'],
            'num' => ['name'=>'数量'],
            'unit_name' => ['name'=>'单位'],
            'act_num' => ['name'=>'数量'],
            'aunit_name' => ['name'=>'单位'],
            'price' => ['name'=>'单价'],
            'total' => ['name'=>'合计'],
        ];
        foreach ($excelData as $index => $val) {
            foreach ($val as $key => $value) {
                if($key=="status"){
                    $excelData[$index][$key] = $this->statusType[$value];
                }
            }
        }
        $con = I('con');
        $template = "offer-template";
        if($con == "pcost_control" || $con == "project_costCon"){
            $template = "costCon-template";
        }
        $exportData = ['data'=>$excelData,'schema'=> $schema,'fileName'=>'报价数据','template'=>'Public/excel_template/'.$template.'.xlsx','callback'=>'pcost_control_exportcall'];
        return $exportData ;
        // print_r($excelData);exit;
    }
    /** 
     * @Author: vition 
     * @Date: 2018-12-04 13:39:17 
     * @Desc: 根据模板导出数据 
     */    
    function pcost_control_exportcall($excelData,&$objExcel,&$objActSheet,&$newFileName){
        $con = I('con');
        if($con == "project_offer"){
            $parent_idStr = "parent_oid";
            $nameEX = "【报价】".date('Ymd', time()) ;
            $offerCostCom = $this->pOfferCom;
        }else{
            $parent_idStr = "parent_cid";
            $nameEX = "【成本对照】".date('Ymd', time()) ;
            $offerCostCom = $this->pCostCom;
        }
        $param = [
            'fields' => '*',
            'where' => ['id'=>$excelData[0][$parent_idStr]],
            'one' => true,
            'joins' => [
                'LEFT JOIN (SELECT projectId,code project_code,name project_name,FROM_UNIXTIME(project_time,"%Y/%m/%d") project_date,DATE_FORMAT(DATE_ADD(FROM_UNIXTIME(project_time,"%Y/%m/%d"),INTERVAL days day),"%Y/%m/%d") end_date,province,customer_com,city FROM v_project ) p ON p.projectId = project_id ',
                "LEFT JOIN (SELECT companyId company_id,company customer_com_name FROM v_customer_company ) c ON c.company_id = p.customer_com",
                "LEFT JOIN (SELECT pid ,province province_name FROM v_province ) pr ON pr.pid =  p.province",
                "LEFT JOIN (SELECT cid ctid ,city city_name,pid cpid FROM v_city ) ct ON ct.ctid = p.city AND ct.cpid =  p.province",
            ]
        ];
        $projectCostData = $offerCostCom ->getOne($param);
        $newFileName = $projectCostData['project_name'].$nameEX ;
        // if($con == "pcost_control" || $con == "project_costCon"){
        //     $projectCostData = $this->pCostCom->getOne($param);
        //     $newFileName = $projectCostData['project_name']."【成本对照】".date('Ymd', time()) ;
        // }else{
        //     $projectCostData = $this->pOfferCom->getOne($param);
        //     $newFileName = $projectCostData['project_name']."【报价】".date('Ymd', time()) ;
        // }
        
        // print_r($projectCostData);exit;
        $objActSheet->setTitle ( '运营报价表' );
        $objActSheet->setCellValue ( 'D1', $projectCostData['customer_com_name']);
        $objActSheet->setCellValue ( 'D3', date("Y/m/d") );
        $objActSheet->setCellValue ( 'D5', $projectCostData['project_name'] );
        $objActSheet->setCellValue ( 'D6', $projectCostData['project_date'].'-'.$projectCostData['end_date'] );
        $objActSheet->setCellValue ( 'D7', $projectCostData['province_name'].'-'.$projectCostData['city_name'] );
        $objActSheet->setCellValue ( 'B9', $projectCostData['project_name']);
        $cost_class = "";
        $item_count = [];
        $item_cost_count = [];
        $all_count = 0;
        $all_cost_count = 0;
        $sRow = 13;
        $countRow = 0;
        $styleThinBlackBorderOutline = array(
            'borders' => array(
                'allborders' => array( //设置全部边框
                    'style' => \PHPExcel_Style_Border::BORDER_THIN //粗的是thick
                ),
            ),
        );
        foreach ($excelData as $subData) {
            // $rolAhpa = "K";
            if($cost_class !=  $subData['class_sort']+"-"+ $subData['cost_class']){
                
                $objActSheet->mergeCells("B{$sRow}:K{$sRow}");
                $objActSheet->getStyle("B{$sRow}")->getFont()->setBold(true);
                $objActSheet->getStyle( "B{$sRow}:K{$sRow}")->getFill()->getStartColor()->setARGB("FF969696");
                if($con == "pcost_control" || $con == "project_costCon"){
                    $objActSheet->getStyle( "N{$sRow}:Q{$sRow}")->getFill()->getStartColor()->setARGB("FF969696");
                }
                $title = num_alpha($subData["class_sort"]);
                $objActSheet->setCellValue ( "B{$sRow}", $title."、".$subData["cost_class_name"]);
                $cost_class = $subData['class_sort']+"-"+ $subData['cost_class'];
                
                $countRow = $sRow + 1;
                $sRow+=2;
            }
            
            $classCol = "D";
            if($subData["class_sub"]>1){
                $objActSheet->mergeCells("C{$sRow}:C".($sRow+$subData["class_sub"]-1));
                $objActSheet->setCellValue("C{$sRow}",$subData["class_notes"]);
                $objActSheet->getStyle("C{$sRow}")->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            }elseif($subData["class_sub"] == 1){
                $objActSheet->mergeCells("C{$sRow}:D{$sRow}");
                $classCol = "C";
            }
            
            $objActSheet->getStyle( "B{$sRow}:K{$sRow}")->applyFromArray($styleThinBlackBorderOutline);
            $objActSheet->getStyle("B{$sRow}:K{$sRow}")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objActSheet->setCellValue("B{$sRow}",$title.$subData["sort"]);
            $objActSheet->setCellValue($classCol."{$sRow}",$subData["module_name"]);
            $objActSheet->setCellValue("E{$sRow}",$subData["item_content"]);
            $objActSheet->setCellValue("F{$sRow}",$subData["num"]);
            $objActSheet->setCellValue("G{$sRow}",$subData["unit_name"]);
            $objActSheet->setCellValue("H{$sRow}",$subData["act_num"]);
            $objActSheet->setCellValue("I{$sRow}",$subData["aunit_name"]);
            $objActSheet->setCellValue("J{$sRow}",$subData["price"]);
            $objActSheet->setCellValue("K{$sRow}",$subData["total"]);
            if($con == "pcost_control" || $con == "project_costCon"){
                $objActSheet->getStyle("N{$sRow}:Q{$sRow}")->applyFromArray($styleThinBlackBorderOutline);
                $objActSheet->getStyle("N{$sRow}:Q{$sRow}")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objActSheet->setCellValue("N{$sRow}",$subData["cost_price"]);
                $objActSheet->setCellValue("O{$sRow}",$subData["cost_total"]);
                $objActSheet->setCellValue("P{$sRow}",$subData["profit_ratio"]."%");
                $objActSheet->setCellValue("Q{$sRow}",$subData["profit"]);
                
            }
            
            $sRow++;
            $item_count[$countRow] += round($subData['total'],2);
            $item_cost_count[$countRow] += round($subData['cost_total'],2);
            $all_count += round($subData['total'],2);
            $all_cost_count += round($subData['cost_total'],2);
        }
        // print_r($item_count);exit;
        foreach ($item_count as $row => $countVal) {
            $objActSheet->mergeCells("B{$row}:J{$row}");
            $objActSheet->getStyle("K{$row}")->getFont()->setBold(true);
            $objActSheet->getStyle("K{$row}")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objActSheet->getStyle( "B{$row}:K{$row}")->getFill()->getStartColor()->setARGB("FFC0C0C0");

            $objActSheet->getStyle( "B{$row}:K{$row}")->applyFromArray($styleThinBlackBorderOutline);
            $objActSheet->setCellValue("K{$row}",$countVal);

            if($con == "pcost_control" || $con == "project_costCon"){
                $objActSheet->getStyle("N{$row}:Q{$row}")->getFill()->getStartColor()->setARGB("FFC0C0C0");
                $objActSheet->getStyle("N{$row}:Q{$row}")->applyFromArray($styleThinBlackBorderOutline);
                $objActSheet->getStyle("O{$row}")->getFont()->setBold(true);
                $objActSheet->getStyle("N{$row}:Q{$row}")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objActSheet->setCellValue("O{$row}",$item_cost_count[$row]);
            }
        }
        // exit;
        $sRow++;
        $objActSheet->getStyle( "B{$sRow}:K{$sRow}")->getFill()->getStartColor()->setARGB("FF000000");
        $sRow++;
        $objActSheet->mergeCells("B{$sRow}:I".($sRow+2));
        $objActSheet->getStyle("B{$sRow}:K".($sRow+2))->applyFromArray($styleThinBlackBorderOutline);
        $objActSheet->getStyle("J{$sRow}:K".($sRow+3))->getFont()->setBold(true);
        $objActSheet->getStyle("J{$sRow}:K".($sRow+3))->getFont()->setSize(11);
        $objActSheet->getStyle("J{$sRow}:K".($sRow+3))->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objActSheet->setCellValue("K{$sRow}",$all_count);
        $objActSheet->setCellValue("J{$sRow}",'以上合计');

        if($con == "pcost_control" || $con == "project_costCon"){
            $objActSheet->getStyle("N{$sRow}:Q".($sRow+2))->applyFromArray($styleThinBlackBorderOutline);
            $objActSheet->getStyle( "N".($sRow-1).":Q".($sRow-1))->getFill()->getStartColor()->setARGB("FF000000");
            $objActSheet->getStyle("O{$sRow}:Q{$sRow}")->getFont()->setBold(true);
            $objActSheet->getStyle("O{$sRow}:Q{$sRow}")->getFont()->setSize(11);
            $objActSheet->getStyle("O{$sRow}:Q{$sRow}")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objActSheet->setCellValue("O{$sRow}",$all_cost_count);
            $objActSheet->setCellValue("P{$sRow}",$projectCostData['profit_ratio']."%");
            $objActSheet->setCellValue("Q{$sRow}",$projectCostData['profit']);
        }
        $sRow++;
        $all_rateCount = round($all_count*($projectCostData['tax_rate']/100),2);
        $objActSheet->setCellValue("J{$sRow}","增值税{$projectCostData['tax_rate']}%");
        $objActSheet->setCellValue("K{$sRow}",$all_rateCount);
        $sRow++;
        $objActSheet->setCellValue("J{$sRow}",'总计');
        $objActSheet->setCellValue("K{$sRow}",round($all_count+$all_rateCount,2));
    }
    function pcost_control_import(){
        exit;
    }
    //导入项目报价 自定义调用函数
    function pcost_control_importcall($objPHPExcel){
        $objActSheet = $objPHPExcel->getSheet(0);
        $highestRow = $objActSheet->getHighestRow();
        $project_name = $objActSheet->getCell("D5")->getValue();//获取活动名称，和项目名称一致，不然会出错
        $merges = $objActSheet->getMergeCells();
        $cost_class = [];
        $sub_class = [];
        foreach ($merges as $mCells) {
            $rows = explode(":", preg_replace("/[A-Z]*/","",$mCells));
            $cols = explode(":", preg_replace("/[0-9]*/","",$mCells));
            if(count($rows) < 2 || count($cols)<2 ){
                // 异常
                $this->ajaxReturn(['errCode'=>408,'error'=>getError(408)]);
            }
            $colsCha = ord($cols[1]) - ord($cols[0]);
            if($colsCha>=7 && $rows[0]==$rows[1] && $rows[1]>12){
                $className = $objActSheet->getCell("B".$rows[1])->getValue();
                if($className){
                    array_push($cost_class,[$rows[1],$className]);
                }

            }else if($cols[0] == "C" && $cols[1] == "C" && $rows[1] > $rows[0]){
                array_push($sub_class,$rows);
            }
        }
        print_r($cost_class);
        print_r($sub_class);
        exit;
    }
    function project_offerList($type='offer'){
        $data=I("data");
        $p=I("p")?I("p"):1;
        $roleId = session("roleId");
        $user_id = session('userId');
        $where=[];
        $nodeAuth = $this->nodeAuth[CONTROLLER_NAME.'/'.ACTION_NAME];

        $joins = [
            "LEFT JOIN (SELECT projectId,code project_code,name project_name,user_id p_user_id FROM v_project ) p ON p.projectId = project_id ",
            "LEFT JOIN (SELECT userId, userName user_name FROM v_user ) u ON u.userId = user_id ",
            
        ];
        if($type == 'offer'){
            $offerCostCom = $this->pOfferCom;
            $where["_string"] = "(user_id = {$user_id} OR c_user_id = {$user_id}) OR (FIND_IN_SET({$roleId},examine) <= process_level AND FIND_IN_SET({$roleId},examine) > 0)";
            $joins2 = [
                "LEFT JOIN (SELECT project_id c_project_id , section c_section,flag c_flag,cost_total,profit,profit_ratio,user_id c_user_id FROM v_project_cost ) c ON c.c_project_id = project_id AND c.c_section = section AND c.c_flag = flag",
                "LEFT JOIN (SELECT userId, userName cuser_name FROM v_user ) cu ON cu.userId = c_user_id ",
            ];
            $listTemplate = 'project_offerList';
        }else{
            $where["_string"] = "user_id = {$user_id} OR (FIND_IN_SET({$roleId},examine) <= process_level AND FIND_IN_SET({$roleId},examine) > 0)";
            $offerCostCom = $this->pCostCom;
            $joins2 = [
                "LEFT JOIN (SELECT project_id o_project_id , section o_section,flag o_flag,total,tax_rate,user_id o_user_id FROM v_project_offer ) o ON o.o_project_id = project_id AND o.o_section = section AND o.o_flag = flag",
                "LEFT JOIN (SELECT userId, userName ouser_name FROM v_user ) ou ON ou.userId = o_user_id ",
            ];
            $listTemplate = 'project_costList';
            // $joins
        }
        $joins3 = [
            "LEFT JOIN (SELECT table_id tid , SUBSTRING_INDEX( GROUP_CONCAT(user_id),',',-1) tuserid,SUBSTRING_INDEX(GROUP_CONCAT(remark),',',-1) aremark FROM v_approve_log WHERE status > 0 AND effect = 1 AND table_name ='".$offerCostCom->tableName()."' GROUP BY table_id ORDER BY add_time DESC) ap ON ap.tid=id",
            "LEFT JOIN (SELECT userId auser_id,userName approve_name FROM v_user) au ON au.auser_id = ap.tuserid",
        ];
        $joins = array_merge($joins2,$joins,$joins3);
        // if($nodeAuth < 7){
        //     $where["_string"] = "FIND_IN_SET({$roleId},examine) <= process_level AND FIND_IN_SET({$roleId},examine) > 0";
        //     // $where['user_id'] = session('userId');
        // }
        // if($type == 'offer'){
        //     if($nodeAuth < 7){
        //         $where['user_id'] = session('userId');
        //     }
        // }elseif($type == 'cost'){
        //     if($nodeAuth < 7){
        //         // $map['user_id'] = [["EQ",session('userId')],["EQ",NULL],"OR"];
        //         $map['user_id'] = session('userId');
        //         $map['_logic'] = 'or';
        //         $where['_complex'] = $map;
        //     }
        // }elseif($type == 'contrast'){
        //     if($nodeAuth < 7){
        //         $where["_string"] = "FIND_IN_SET({$roleId},examine) <= process_level AND FIND_IN_SET({$roleId},examine) > 0";
        //         // $where['user_id'] = session('userId');
        //     }
        // }
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : $this->pageSize;
        $parameter=[
            'fields'=>"*,FROM_UNIXTIME(add_time,'%Y-%m-%d') add_time,FIND_IN_SET({$roleId},examine) place",
            'where'=>$where,
            'page'=>$p,
            'pageSize'=>$pageSize,
            'orderStr'=>"id DESC",
            "joins"=> $joins,
        ];
        $listResult=$offerCostCom->getList($parameter);
        // $this->log($offerCostCom->_sql());
        // echo $this->pCostCom->M()->_sql();exit;
        // $this->
        // if($type == 'offer'){
        //     $listTemplate = 'project_offerList';
        // }else if($type == 'cost'){
        //     $listTemplate = 'project_costList';
        // }
        $this->tablePage($listResult,'ProjectCost/projectcostTable/'.$listTemplate,$listTemplate,$pageSize);
    }
    function project_offerMange($param){
        $reqType = $param['reqType'] ? $param['reqType'] : I("reqType");
        $datas = $param['data'] ? $param['data'] : I("data");

        if(isset($datas['cost_total']) && $datas['cost_total']>0){
            $total = $datas['total'] > 0 ? $datas['total'] : 0;
            $datas['profit'] = round($total - $datas['cost_total'],2);
            $datas['profit_ratio'] = $total == 0 ? -100 : round($datas['profit'] / $total,2)*100;
        }
        if($reqType=="project_offerAdd"){
            $datas['status']=1;
            $datas['add_time'] = time();
            $datas['user_id'] = session("userId");
            unset($datas['id']);
            return $datas;
        }else if($reqType=="project_offerEdit"){
            $where=["id"=>$datas['id']];
            $data=[];
            foreach (['class_notes','class_sort','cost_class','sort','classify','item_content','num','unit','act_num','act_unit','price','total','status','class_sub','cost_price','cost_total','profit','profit_ratio','scompany_id','scompany_cid','flag','auth_user_id','read_type'] as $key) {
                if(isset($datas[$key])){
                    $data[$key]=$datas[$key];
                }
            }
            $data['update_time']=time();
            return ["where"=>$where,"data"=>$data];
        }
        return "";
    }
    //添加报价
    function project_offerAdd($type="offer"){

        // exit;
        extract($_POST);
        $isInsert = false;
        $pResult = $this->projectCom->getOne(['where'=>['project_id' => $data['project_id']],'fields'=>'leader','one'=>true]);
        $param = [
            'fields' => 'id',
            'where' => ['project_id' => $data['project_id']],
            'pageSize' => 99999999,
        ];
        $pOfferData = [];
        $offerCostCom = $this->pOfferCom;
        $parent_idStr = 'parent_oid';
        // if($type == 'offer'){
        //     $offerCostCom = $this->pOfferCom;
        //     $parent_idStr = 'parent_oid';
        // }else{
        //     $offerCostCom = $this->pCostCom;
        //     $parent_idStr = 'parent_cid';
        // }
        $hasData = $offerCostCom->getList($param);
        
        if($hasData){
            $pOfferData['section'] = $hasData['count'] + 1;
        }else{
            $pOfferData['section'] = 1;
        }
        foreach (['project_id', 'total', 'flag', 'tax_rate'] as $key) {
            if(isset($data[$key])){
                $pOfferData[$key] = $data[$key];
            }
        }
        $pOfferData['user_id'] = session('userId');
 
        $pOfferData['add_time'] = time();

        //添加时审批流数据
        $examines = getComponent('Process')->getExamine(I("vtabId"),$pResult['leader']);
        $pOfferData['process_id'] = $examines["process_id"];
        $pOfferData['examine'] = $examines["examine"];
        $pOfferData['process_level'] = $examines["process_level"];
        $pOfferData['status'] = $examines["status"];
        if($type=="cost"){
            $pOfferData['process_id'] = 0;
            $pOfferData['examine'] = '';
            $pOfferData['process_level'] = 0;
            $pOfferData['status'] = 1;
        }
  
        $offerCostCom->startTrans();
        // $pOfferData['status'] = $status ? $status : $pOfferData['status'];
        $pInsertResult = $offerCostCom->insert($pOfferData);
        if($type=="cost" && isset($pInsertResult->errCode) && $pInsertResult->errCode==0){
            $offerCostCom->commit();
            $costInResut =$this->pOfferCom->toCost($pInsertResult->data,I("vtabId"));
            return ['oid'=>$pInsertResult->data,'cid'=>$costInResut->data];
        }
        if(isset($pInsertResult->errCode) && $pInsertResult->errCode==0){
            $this->pCostSubCom->startTrans();
            $parent_id = $pInsertResult->data;//
            foreach ($data['list'] as  $subData) {
                $infoData = $this->project_offerMange(['data'=>$subData]);
                $infoData[$parent_idStr] = $parent_id;
                $insertResult = $this->pCostSubCom->insert($infoData);
                if(isset($insertResult->errCode) && $insertResult->errCode==0){
                    $isInsert = true;
                }else{
                    $isInsert = false;
                    $offerCostCom->rollback();
                    $this->pCostSubCom->rollback();
                    break;
                }
                // print_r($infoData);
            }
            if($isInsert){
                
                // $this->ApprLogCom->createApp($offerCostCom->tableName(),$parent_id,session("userId"),"");
                $addData = [
                    'examine'=>$pOfferData['examine'],
                    'title'=>session('userName')."添加了项目报价",
                    'desc'=>"<div class=\"gray\">".date("Y年m月d日",time())."</div> <div class=\"normal\">".session('userName')."添加了项目报价，@你了，点击进入围观吧！</div>",
                    'url'=>C('qiye_url')."/Admin/Index/Main.html?action=ProjectCost/project_offer",
                    'tableName'=>$offerCostCom->tableName(),
                    'tableId'=>$parent_id,
                    'nowhite'=>"nowhite",
                ];
                $this->add_push($addData);

                $offerCostCom->commit();
                $this->pCostSubCom->commit();
            }
        }
        
        $this->ajaxReturn(['errCode'=>$insertResult->errCode,'error'=>$insertResult->error]);
        // print_r($pOfferData);
    }
    //报价编辑
    function project_offerEdit(){
        extract($_POST);
        $this->pOfferCom->startTrans();
        
        $pOfferData = [
            'where' => ['id'=>$data['id']],
            'data' => [
                'project_id' => $data['project_id'],
                'tax_rate' => $data['tax_rate'],
                'total' => $data['total'],
                'update_time' => time(),
            ]
        ];
        $parent_id = $data['id'];
        // print_r($pOfferData);exit;
        $this->pOfferCom->startTrans();
        $this->pCostSubCom->startTrans();
        
        if($status){
            $pOfferData['data']['status'] = $status;
        }
  
        $pInsertResult = $this->pOfferCom->update($pOfferData);

        foreach ($data['list'] as  $subData) {
            if( $subData['id']>0){//编辑
                $infoData = $this->project_offerMange(['data'=>$subData]);
                $upateResult = $this->pCostSubCom->update($infoData);
                // print_r($infoData);
            }else{//新增
                $infoData = $this->project_offerMange(['data'=>$subData,'reqType'=>'project_offerAdd']);
                $infoData['parent_oid'] = $parent_id;
                $upateResult = $this->pCostSubCom->insert($infoData);
                // print_r($infoData);
            }
        }
        $this->pOfferCom->commit();
        $this->pCostSubCom->commit();
        $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
    }
    function  project_costControl(){
        $reqType=I('reqType');
        $this->assign("controlName","pcost_control");
        $this->assign("listType","cost");
        $this->assign("tableName",$this->pCostCom->tableName());
        
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function pcost_controlList(){
        $this->project_offerList('cost');
    }
    function  pcost_control_modalOne(){
        $this->project_offer_modalOne('cost','查看/编辑成本');
    }
    function pcost_controlAdd(){
        $result = $this->project_offerAdd('cost');
        $_POST['data']['id'] = $result['cid'];
        $_POST['data']['oid'] = $result['oid'];
        $this->pcost_controlEdit(true);
    }
    function pcost_controlEdit($insert = false){
        extract($_POST);
 
        // $this->pCostCom->startTrans();
        
        $pCostData = [
            'where' => ['id'=>$data['id']],
            'data' => [
                'cost_total' => $data['cost_total'],
                'profit' => $data['total'] - $data['cost_total'],
                'profit_ratio' => round((($data['total'] - $data['cost_total']) / $data['total'])*100,2) ,
                'update_time' => time(),
            ]
        ];
        $parent_id = $data['id'];
        $parent_oid = $data['oid'];
        // print_r($pCostData);exit;
        $pOfferData = [
            'where' => ['id'=>$data['oid']],
            'data' => [
                'total' => $data['total'],
                'tax_rate' => $data['tax_rate'],
                'update_time' => time(),
            ]
        ];
        // $this->pCostCom->getOne($pCostData);
        $this->pOfferCom->startTrans();
        $this->pCostCom->startTrans();
        $this->pCostSubCom->startTrans();
        // if($status){
        //     $pCostData['data']['status'] = $status;
        // }
        $pResult = $this->projectCom->getOne(['where'=>['project_id' => $data['id']],'leader'])['list'];
        
        //添加时审批流数据
        $examines = getComponent('Process')->getExamine(I("vtabId"),$pResult['leader']);
        $pCostData['data']['process_id'] = $examines["process_id"];
        $pCostData['data']['examine'] = $examines["examine"];
        $pCostData['data']['process_level'] = $examines["process_level"];
        $pCostData['data']['status'] = $examines["status"];

        // print_r($pCostData);exit;
        $pInsertResult = $this->pOfferCom->update($pOfferData);
        $pInsertResult = $this->pCostCom->update($pCostData);

        foreach ($data['list'] as  $subData) {
            if( $subData['id']>0){//编辑
                $infoData = $this->project_offerMange(['data'=>$subData,'reqType'=>'project_offerEdit']);
                if($infoData['data']['read_type'] == 1){
                    $infoData['data']['parent_cid'] = $parent_id;
                    $infoData['data']['parent_oid'] = $parent_oid;
                }else{
                    $infoData['data']['parent_cid'] = $parent_id;
                }
                $upateResult = $this->pCostSubCom->update($infoData);
                // print_r($infoData);
            }else{//新增
                $infoData = $this->project_offerMange(['data'=>$subData,'reqType'=>'project_offerAdd']);
                if($infoData['data']['read_type'] == 1){
                    $infoData['data']['parent_cid'] = $parent_id;
                    $infoData['data']['parent_oid'] = $parent_oid;
                }else{
                    $infoData['data']['parent_cid'] = $parent_id;
                }               
                $upateResult = $this->pCostSubCom->insert($infoData);
                // print_r($infoData);
            }
        }
        // exit;
        // $this->ApprLogCom->updateStatus($this->pCostCom->tableName(),$data['id']);
        $this->pOfferCom->commit();
        $this->pCostCom->commit();
        $this->pCostSubCom->commit();
        $addData = [
            'examine'=>$pCostData['data']['examine'],
            'title'=>session('userName')."添加了项目报价成本",
            'desc'=>"<div class=\"gray\">".date("Y年m月d日",time())."</div> <div class=\"normal\">".session('userName')."添加了项目报价成本，@你了，点击进入审批吧！</div>",
            'url'=>C('qiye_url')."/Admin/Index/Main.html?action=ProjectCost/project_costContrast",
            'tableName'=>$this->pCostCom->tableName(),
            'tableId'=>$parent_id,
            'nowhite' => 'nowhite',
        ];
        if(!$insert){
            // $addData['noappr'] = 'noappr';
        }
        $this->add_push($addData);

        $this->ajaxReturn(['errCode'=>0,'error'=>getError(0)]);
    }
    /** 
     * @Author: vition 
     * @Date: 2018-11-20 17:12:34 
     * @Desc: 成本对照 
     */    
    function project_costContrast(){
        $reqType=I('reqType');
        $this->assign("controlName","project_costCon");
        $this->assign("listType","contrast");
        $this->assign("tableName",$this->pCostCom->tableName());
        
        if($reqType){
            $this->$reqType();
        }else{
            $this->returnHtml();
        }
    }
    function project_costConList(){
        $this->project_offerList('contrast');
    }
    function  project_costCon_modalOne(){
        $this->project_offer_modalOne('contrast','查看成本对照');
    }
}