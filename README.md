# wms
开发说明：
*Modal.html文件
{$title} 弹窗标题，可以配置
$nodeAuth 当前节点的权限值 1-7
$processAuth['level'] 指定流程的级别
{$controlName} 控制名称，又xx分配
{$gettype} 请求的类型 Add和Edit
{$url} 请求的地址
{$btnTitle} 按钮标题

class：
input 中的modal-info 必须的，添加数据和修改数据时会根据此class获取值
status-group 按钮组
status-btn 中按钮触发事件，
active 表示当前按钮激活

save-info 保存和修改数据触发的按钮

1，更新城市表
2，更新立项关联场地
3，修改点击事件封装
4，修改选择框扩张数据
5，采购表新增预付款日期字段，承接模型字段
6，浮动窗口可拖动
7，采购表修改合同文件字段和新增报价文件字段
8，优化选择框允许局部选择器
9，成本录入

2018-08-06
1，新增支付表和发票表
2，取消浮窗遮罩点击关闭和浮窗移动只限制head
3，新增开发查看文件media组件，支持jpg,png,gif,pdf,office
4，成本录入
5，项目采购成本审核

2018-08-16
1，新建审批记录表
2，基础数据新增报销类别
3，新建个人报销主表和子表
4，个人报销添加和查询修改

2018-08-03
1，新增未登录的情况下粘贴某个url在登录后跳转到该地址的功能
2，更新审核表
3，审批流处理
4，禁止修改超级管理员
5，主页面显示分组和角色

审批流程思路：
1，插入 v_approve_log 审批记录；
2，在审批类型为1（批准）的情况下判断当前审批人在审批流程中的位置，如果位置小于做大位置，那么 v_expense_sub 状态改成2（审批中），如果审批类型为拒绝或者驳回则保留status 为该值；
3，统计 v_expense_sub 数量，判断 v_approve_log 表中该审批人 status = 1 的数量 如果审批数量==申请数量，修改 v_expense 审批级别为当前审批者的place 

4，如果审批状态出现 大于1 则 v_expense 中的status 改为 对应的status 否则除非 place 等于alllevel 否则status = 2 最终状态为1 


审批修改要点
0，查询时需要插入
控制方法中插入  $this->assign("tableName",$this->对应的组件->tableName()); 分配表名
//获取当前节点和对应的审核流程
$nodeId = getTabId(I("vtabId"));
$process = $this->nodeCom->getProcess($nodeId);
$this->assign("place",$process["place"]);
$this->assign("tableName",$this->对应的组件->tableName());

查询列表里需要判断查询权限
1，列表中html 插入 {:approve_btn($tableName,$item['id'],$place,$item['process_level'],$item['status'])} fetch方式的可以带id ajax只能通过js修改id

3，添加记录时需要 插入 $this->ApprLogCom->createApp($this->expenseSubCom->tableName(),$insertResult->data,session("userId"),""); 插入一条审批提交记录

4，更新数据时需要插入 $this->ApprLogCom->updateStatus($this->expenseSubCom->tableName(),$subExpInfo["id"],$expense_id); 判断是否存在驳回的数据并删除，更新状态
5，部分驳回重新提交需要在manager中修改 $datas['status'] = $datas['status'] == 3 ? 0 : $datas['status'];
6,modal 中需要有<input class="modal-info" name="status" value="0" type="hidden">
7，统一 process_level 格式


manager里需要修改


获取最新的审批
需要在节点添加查询的表名

列表新增字段 
记录当前数据使用哪一个流程，下一个审批者id
流程3个字段

当前指定流程id
当前流程位置
下一个审核者id

1，添加数据的时候需要获取当前的审批流，如果存在多个则以添加先后为判断，最后的覆盖最先的。
例子：借支里有两个审批流，A提交给B审核，A提交给C审核，那么默认选中A提交给C审核
2，保存数据的时候会带上流程id，同时查找下一个审核者的id，如果数据涉及到项目，则第一个审批的人为指定项目的项目担当，接下来才执行正常流程

3,6,4,1
1 2 3 4


FIND_IN_SET(用户id,examine) <= process_level 



<input class="modal-info" value="0" name="leader" type="hidden">

//添加时必备数据
$process = $this->nodeCom->getProcess(I("vtabId"));
$datas['process_id'] = $process["processId"];
if($datas["project_id"]>0){ 
    //存在项目，则第一个审批的人是项目主管,examine需要
    $userRole = $this->userCom->getUserInfo($datas['leader']);
    $datas['examine'] = $userRole['roleId'].",".$process["examine"];
    unset($datas['leader']);
}else{
    $datas['examine'] = $process["examine"];
}
$datas['process_level']=$process["place"];


1，清算查阅[查阅，催促审核待开发信息发送功能]
2，按钮修改[修改尺寸和颜色]
3，首页待审批bug[节点混淆导致标签页覆盖]修复
4，新增缓存节点信息和获取


2018-9-7更新
1，月固定支付分两个子菜单，“明细”和“统计”
2，月固定支付统计可以根据年和月来做汇总，统计的数据可以通过查询来跳转到“明细”
3，借支和报销中判断当前审批者是否处于审批者阶段，是的话直接跳过下级的审批
4，新增节点点击data是否带上函数，有则执行该函数

2018-9-8
1，修复节点保存和显示时&等字符被转义；
2，添加节点未启用和删除的颜色区别
3，添加节点单独修改排序，
4，财务管理中，清算审批、清算查阅、机制管理和报销及清算通过白名单筛选，只允许白名单中的人员查看【财务人员也可能分多个，只有在白名单中的才可以查白名单里的】
5，固定支出新增明细文件
