var datas={};
var filesData={};
var uploadData = {}
var tempFiles = {};
var tabId="";//定义当前tab指定的id
var delList = null;//定义要删除的列表位置
var sourceData = {};
var resetData = {};
$.fn.extend({offon:function(){
    var event =  arguments[0]
    var select =  typeof(arguments[1]) == 'string' ? arguments[1] : false
    var callBack = typeof(arguments[1]) == 'function' ? arguments[1] : typeof(arguments[2]) == 'function' ? arguments[2] : function(){}
    if(select){
        return $(this).off(event,select).on(event,select,callBack)
    }else{
        return $(this).off(event).on(event,callBack)
    }
}})
/** 
 * @Author: vition 
 * @Date: 2018-01-23 00:41:37 
 * @Desc: get方式获取数据 
 */    
var get = function(url,indata,callBack){
    asyncs=arguments[3]!=undefined?arguments[3]:true;
    load=arguments[4]!=undefined?arguments[4]:true;
    if(load){setLoad();}
    indata["vtabId"]=tabId
    $.ajax({
        url:url,
        timeout:10000,
        type:'get',
        dataType:'json',
        data:indata,
        async:asyncs,
    }).done(function(result) {
        if(in_array(result.errCode,[405,407])){window.location.reload();}callBack(result);
    }).always(function() { if(load){setLoad();}datas={};})
}
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-01-27 22:43:43 
 * @Desc: post发送 
 */
function post(url,indata,callBack){
    asyncs=arguments[3]!=undefined?arguments[3]:true;
    load=arguments[4]!=undefined?arguments[4]:true;
    if(load){setLoad();}
    
    indata["vtabId"]=tabId
    $.ajax({
        url:url,
        timeout:10000,
        type:'post',
        dataType:'json',
        data:indata,
        async:asyncs,
    }).done(function(result) {
        if(in_array(result.errCode,[405,407])){window.location.reload();}callBack(result);
    }).always(function() {if(load){setLoad();}datas={};})
}
//enter-input class的输入框键盘回车事件
$(document).on("keypress",".enter-input",function(e){
    if(e.keyCode == 13){
        $($(this).data("btn")).click();
    }
})

/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-06-02 23:27:42 
 * @Desc:  所有搜索按钮触发事件
 */
$(document).on("click",".search-list,.vpage,.excel-export",function(){
    datas={}
    var page=$(this).data('page')
    if(page>0){
        datas['p']=page;
        var url=$(this).parents('.page-div').data("url");
        var con=$(this).parents('.page-div').data("con");
        var reqtype=$(this).parents('.page-div').data("reqtype");
        var param=$(this).parents('.page-div').data("param");
        var call=$(this).parents('.page-div').data("call");
    }else{
        var url=$(this).data("url");
        var con=$(this).data("con");
        var reqtype=$(this).data("reqtype");
        var param=$(this).data("param");
        var call=$(this).data("call");
        var isexport=$(this).data("export");
    }
    if(isexport){
        datas.export = isexport;
    }
    var table=con+"-table";
    var page=con+"-page";
    var count=con+"-count";
    if(param){
        datas.param={};
        param.split(",").forEach(function(para){
            paraInfo = para.split(":")
            datas.param[paraInfo[0]] = paraInfo[1]
        });
        // datas.param=param
    }
    datas.reqType=reqtype;

    if(fun_is_exits(con+"_searchInfo")){
        eval(con+"_searchInfo()")//对不同的id设置不同的发送数据
    }else{
        datas['data']={}
        $(tabId+" .search-info").each(function(){
            var name=$(this).attr("name");
            var val=$(this).val();
            if(val!=""){
                datas['data'][name]=val
            }
        })
    }
    searchFun({url:url,datas:datas,table:table,page:page,count:count,call:call,con:con});
})
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-06-02 23:08:58 
 * @Desc: 执行查询数据并插入到对应的表格中 
 */
function searchFun(option){
    var url = option.url
    var datas = option.datas
    var table = option.table
    var page = option.page
    var count = option.count
    var callfun = option.callfun
    var con = option.con
    get(url,datas,function(result){
        if(result.errCode==0){
            if(result.table==""){
                var tdNum = $(tabId+" ."+table).parent("table").find("thead tr th").length
                result.table = '<tr><td colspan="'+tdNum+'">无数据</td></tr>'
            }
            $(tabId+" ."+table).html(result.table);
            $(tabId+" ."+page).html(result.page);
            $(tabId+" ."+count).html(result.count);
            if(fun_is_exits(callfun+"")){
                eval(callfun+"(result.data)")//
            }
            var noData = result.table == '<tr><td colspan="'+tdNum+'">无数据</td></tr>' ? true : false
            // console.log(tabId+" ."+table)
            table_frozen($(tabId+" ."+table),noData);
           
            //这里插入一个js修改table
        }else{
            if(result.url){
                window.location.href = result.url+"&con="+con; 
            }else{
                notice(result.errCode,result.error);
            }
        }
    })
}
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-01-27 18:19:36 
 * @Desc: 所有编辑，添加，触发弹出modal事件 
 */
// $(document).on("click",".info-edit",function(){

//     var target=$(this).data("target");
//     var title=$(this).data("title");
//     var reqtype=$(this).data("reqtype");
//     var show=$(this).data("show");
//     var con=$(this).data("con");
//     con = con ? con : $(this).parent(".status-con").data("con");
//     var url=$(this).data("url");
//     url = url ? url : $(this).parent(".status-con").data("url");
//     var name=$(this).attr("name");
//     if(name){
//         $(target).find(".box-body").html("");
//         datas={}
//         datas.reqType="formOne";
        
//         datas.form=name;
//         get(url,datas,function(result){
//             notice(result.errCode,result.error);
//             if(result.errCode==0){
//                 $(target).find(".box-body").html(result.html);
//             }
            
//         })
//     }
//     $(target).find('.modal-title').text(title)
//     $(target).find('.save-info').text(title)
//     $(target).find('.save-info').data("reqtype",reqtype)
//     if(show=='One'){//编辑要获取数据
//         datas={}
//         var id=$(this).data("id");
//         id = id ? id : $(this).parent(".status-con").data("id");
//         datas.reqType=con+show;
//         datas.id=id
//         get(url,datas,function(result){
            
//             if(result.errCode==0){
//                 if(fun_is_exits(con+"ShowFuns")){
//                     eval(con+"ShowFuns(result.info)");//对不同的模块设置不同的响应数据
//                 }
//             }else{
//                 // alert(result.error);
//                 notice(result.errCode,result.error);
//             }
//         })
//     }else{//新建要重置数据
//         $(target).find(".modal-info").val("");
//         if(fun_is_exits(con+"ReFuns")){i
//             eval(con+"ReFuns()");//对不同的模块的modal数据重置
//         }
//     }
//     datas={};
// })
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-06-02 17:08:13 
 * @Desc: 弹出 modal 操作 new
 */
$(document).on("click",".v-showmodal",function(){
    var vtarget = $(this).data("vtarget")
    vtarget = vtarget ? vtarget : $(this).parent(".status-con").data("vtarget");

    var url = $(this).data("url")
    url = url ? url : $(this).parent(".status-con").data("url");
    var con = $(this).data("con")
    con = con ? con : $(this).parent(".status-con").data("con");
    var gettype = $(this).data("gettype")
    gettype = gettype ? gettype : $(this).parent(".status-con").data("gettype");
    var title = $(this).data("title")
    title = title ? title : $(this).parent(".status-con").data("title");
    
    var id = $(this).data("id")
    id = id ? id : $(this).parent(".status-con").data("id");
    datas.id = id
    datas.gettype = gettype
    datas.title = title
    datas.con = con
    datas.reqType = con+"_modalOne"
    // var hasData = $(tabId+" "+vtarget+" .modal-content").find(".modal-footer .save-info").data("gettype");
    // if(hasData !== undefined && datas.gettype  == 'Add' && hasData == "Add"){
    //     $(tabId+" "+vtarget).modal('toggle');
    //     return false;
    // }
    get(url,datas,function(result){
        // console.log(result)
        if(result.errCode==0){
             
            $(tabId+" "+vtarget+" .modal-content").html(result.html);
            $(tabId+" "+vtarget).modal('toggle')
            // $(tabId+" .global-modal .modal-content").find("[required='required']").each(function(){
            //     $(this).before('<span class="required"></span>')
            // })
            if(gettype=="Edit"){
                
                // $("[required='required']").before('<span class="required"></span>');
                if(fun_is_exits(con+"_setInfo")){
                    eval(con+"_setInfo(result.data)");//对不同的模块设置不同的响应数据
                }
            }
            if(fun_is_exits(con+"_initInfo")){
                // console.log(gettype)
                eval(con+"_initInfo(gettype)");//
            }
        }
    },false)
})
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-01-27 18:19:54 
 * @Desc: 所有状态选择按钮事件 
 */
$(document).on("click",'.status-btn',function(){
    set_status_btn(this)
})
//状态按钮设置函数
function set_status_btn(this_btn,info,userId,nodeAuth){
    var thisIndex = $(this_btn).index()-1;
    $(this_btn).parents(".status-group").children(".status-btn").each(function(index){
        $(this).removeClass("active");
        var i = $(this).find("i")
        if(index == thisIndex){
            $(this).addClass("active");
            if(i.hasClass("fa-square")){
                i.removeClass("fa-square");
                i.addClass("fa-check-square");
            }
        }else{
            if(i.hasClass("fa-check-square")){
                i.addClass("fa-square");
                i.removeClass("fa-check-square");
            }
        }
    })
    $(this_btn).parent(".status-group").children("input[name='status']").val($(this_btn).attr("name"));
    if(info){
        if(((info['status'] == 1 || info['process_level'] == 2) || info['author'] != userId )  && nodeAuth<7){
            $(tabId+" .modal-info").prop("disabled",true)
        }
    }
}
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-01-27 22:09:37 
 * @Desc: 所有重置按钮 
 */
$(document).on("click",'.search-refresh',function(){
    $(this).parents(".search-body").find(".search-info").val("");
    $(this).parents(".search-body").find(".search-info").each(function(){
        if($(this).hasClass("chosen-select")){
            $(this).trigger("chosen:updated");
        }
       
    })
    var con=$(this).data("con")
    if(fun_is_exits(con+"_resetInfo")){
	    eval(con+"_resetInfo()");//弥补不足
    }
})
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-01-27 22:38:31 
 * @Desc: 保存数据、新增或修改 new
 */
$(document).on("click",'.save-info',function(){
    if(datas[tabId]!=undefined){
        var temp = datas[tabId]
        datas=datas[tabId]
    }else{
        datas={}
    }
    
    var self = this
    var url=$(this).data("url");
    var gettype=$(this).data("gettype");
    var con=$(this).data("con");
    var isModal=$(this).data("modal");
    var search=con+"_search";
    // var parent=$(this).parents(".modal").attr("id")
    if($('body').hasClass('modal-open')==false && isModal){
        $('body').addClass('modal-open')
    }
    datas.reqType=con+gettype;
    
    if(fun_is_exits(con+"_getInfo")){
    	eval(con+"_getInfo(this)");//对不同的id设置不同的发送数据
    }else{
        datas["data"]={}
        var global_modal = ""
        if($(this).parents(".global-modal").html()!=undefined){
            global_modal = " .global-modal"
        }
        // console.log(global_modal)
        $(tabId+global_modal+" .modal-info").each(function(){
            var name =$(this).attr("name");
            var required=$(this).attr("required");
            var title=$(this).attr("title");
            if($(this).attr("type")=='checkbox'){
                if($(this).is(":checked")){
                    val = 1;
                }else{
                    val = 0;
                }
            }else{
                var val =$(this).val();
            }
            if(required && (val=="" || val == "0")){
                notice(110,title,"输入异常");
                throw title
            }else{
                datas["data"][name]=val;
            }
        })
    }
    if(JSON.stringify(filesData)!="{}"){
        datas['filesData']=filesData //存在文件上传
    }
    if(JSON.stringify(datas["data"])=="{}"){
        notice(110,"没有更新数据")
    }
    
    post(url,datas,function(result){
        // notice(result.errCode,result.error);
        if(result.errCode==0){
            notice(result.errCode,result.error);
            // var url=$("#"+search).data("url");
            // var con2=$("#"+search).data("con");
            // if(con2==undefined){
            //     con2=con;
            // }
            // // console.log(search);
            url=$(tabId+" .search-list").data("url");
            reqtype=$(tabId+" .search-list").data("reqtype");
            var table=con+"-table";
            var page=con+"-page";
            var count=con+"-count";
            datas.reqType=reqtype;
            if(fun_is_exits(con+"_searchInfo")){
                eval(con+"_searchInfo(result)")//对不同的id设置不同的发送数据
            }else{
                datas['data']={}
                $(tabId+" .search-info").each(function(){
                    var name=$(this).attr("name");
                    var val=$(this).val();
                    if(val!=""){
                        datas['data'][name]=val
                    }
                })
            }
            if(isModal){
                // console.log(isModal);
                searchFun({url:url,datas:datas,table:table,page:page,count:count})
            }
            // console.log($('body').hasClass('modal-open'))
            // console.log($(self).parents(".modal"))
            if($('body').hasClass('modal-open')){
                // console.log($(tabId+" .modal"))
                $(tabId+" .modal").modal('hide')
                // $(tabId+" .modal").modal('toggle')
                // $(self).parents(".modal").modal('toggle')
            }
            if(datas.reqType=="Add"){
                $(self).data("gettype","");
            }
            
            // $(self).parents(".global-modal").find(".modal-content").html("");
        }else{
            notice(result.errCode,result.error);
        }
        
    });
})
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-01-28 22:28:38 
 * @Desc: 上传文件到files 
 */
$(document).on("change",".fileupdate",function(){
    var input=$(this).data("input");
    var imgName=this.files[0].name
    var slefs=$(this)
    var reader = new FileReader();
    reader.readAsDataURL(this.files[0])
    reader.onloadend = function () {  
        $(input).val(imgName);
        filesData[encodeURI(imgName)]=this.result
    };
})
$(document).on("click",'.tree-plus',function(){
    var treeId=$(this).data("id")
    $(treeId).treeview('collapseAll', { silent: true });
})
$(document).on("click",'.tree-minus',function(){
    var treeId=$(this).data("id")
    $(treeId).treeview('expandAll', { silent: true });
})
$(document).on("click",".status-info",function(){
    delList = $(this)
    var url = $(this).parent(".status-con").data("url");
    var id = $(this).parent(".status-con").data("id");
    var db = $(this).parent(".status-con").data("db");
    var con = $(this).parent(".status-con").data("con");
    var status = $(this).data("status");
    var ids = $(this).parents("table").find("tbody input[class='item-checked']:checked").length
    var title = "删除当前行数据"
    if(ids>1){
        var title ="批量删除，已选中 "+ids+" 条数据"
    }
    var html = '<div class="v-status-box" style="text-align: center;" data-status="'+status+'"  data-db="'+db+'" data-con="'+con+'" data-url="'+url+'" data-id="'+id+'"><div class="col-sm-3 col-xs-3"><button type="button" name="del"  class="btn btn-xs bg-orange submit-status">删除</button></div><div class="col-sm-5 col-xs-5"><input type="password" placeholder="输入二级密码" class="form-control input-sm senior-password" /></div><div class="col-sm-3 col-xs-3"><button type="button" name="deepDel" class="btn bg-navy btn-xs submit-status">彻底删除</button></div></div>'
    notice(100,html,title,0)
})
$(document).on("click",".submit-status",function(){
    var statusType = $(this).attr("name")
    var html = $(this).parents(".box-body").html();
    var that = $(this).parents(".box-body");
    var senior_pwd = $(this).parents(".v-status-box").find(".senior-password").val();
    var url = $(this).parents(".v-status-box").data("url")
    url = url ? url : $(this).parent(".status-con").data("url");
    var id = $(this).parents(".v-status-box").data("id")
    id = id ? id : $(this).parent(".status-con").data("id");
    var db = $(this).parents(".v-status-box").data("db")
    db = db ? db : $(this).parent(".status-con").data("db");
    var con = $(this).parents(".v-status-box").data("con")
    con = con ? con : $(this).parent(".status-con").data("con");
    var status = $(this).parents(".v-status-box").data("status");
    status = status ? status : $(this).data("status");
    
    var checkes = delList.parents("table").find("tbody input[class='item-checked']:checked")
    var ids = [];
    if(checkes.length>0){
        checkes.each(function(){
            ids.push($(this).data("id"))
        })
    }
    if(statusType=="deepDel" && senior_pwd == ""){
        notice(100,"彻底删除必须输入二级密码","删除提示",0)
        setTimeout(() => {
            that.html(html);
        }, 2000);
        
        return false;
    }
    var data={reqType:"globalStatusEdit",statusType:statusType,id:id,status:status,db:db,seniorPwd:senior_pwd,ids:ids}
    // console.log(data);
    // return;
    post(url,data,function(result){
        notice(result.errCode,result.error);
        if(result.errCode==0){
            if(fun_is_exits(con+"_searchInfo")){
                eval(con+"_searchInfo(result)")//对不同的id设置不同的发送数据
            }else{
                datas['data']={}
                $(tabId+" .search-info").each(function(){
                    var name=$(this).attr("name");
                    var val=$(this).val();
                    if(val!=""){
                        datas['data'][name]=val
                    }
                })
            }
            var table=con+"-table";
            var page=con+"-page";
            var count=con+"-count";
            datas.reqType=con+"List";
            searchFun({url:url,datas:datas,table:table,page:page,count:count});
        }
    })
})
$(function(){
    /** 
     * javascript comment 
     * @Author: vition 
     * @Date: 2018-03-04 13:42:18 
     * @Desc:  url 中参数响应自动展开level 例如参数 ?action=Article/articleControl
     * action 活动的key
     * Article/articleControl 表示Article控制器的articleControl方法
     */    
    paramMatch=getUrlAction()
    
    // if(search!="" && search.search(/\?action\=\S/)>=0){
        // var paramMatch=search.match(/\=([\S\/]*)/);
        if(!paramMatch){
            paramMatch = "Index/home"
        }
        var splitArr=paramMatch.split("/");
        
        var match= window.document.body.innerHTML.match(new RegExp("\/Admin\/"+splitArr[0]+"\/"+splitArr[1]+"\.html","gm"))
        // console.log(match)
        if(match!=null && match[0]){
            $(document).find(".nodeOn").each(function(){
                if($(this).attr("href")==match[0] && $(this).data("nodeid")>0){
                    var result=$(this).parents(".treeview-menu").css("display","block");
                    var result=$(this).parents(".treeview-menu").prev(".nodeOn").parent(".treeview").addClass("menu-open");
                    var nodeOn=$(this);
                    setTimeout(function(){nodeOn.trigger('click');},0);
                    return false
                }
            })
        }
    // }
    
    $(window).resize(function(){
        // var width = $(".chosen-container").parent(".form-group").width()-$(".chosen-container").parent(".form-group").find("label").width()
        // console.log($(".form-group").width());
        width = $(".form-group .form-control").width()
        // console.log($(".chosen-container").css("display"))
        // console.log("chosenwidth"+$(".chosen-container").width())
        // width = width > 142 ? width : 142
        // console.log($(".chosen-container").parents(".form-inline"))
        // $(".chosen-container").width("100%")
    })
    $(document).on("show.bs.modal", ".modal", function(){
        $(this).draggable({
            cursor: "move",
			handle: '.modal-header'
        });
        $(this).css("background","none");
        $(this).css("display","flex");
        // $(this).css("overflow-x", "scroll");   
        // $(this).css("overflow-y", "scroll");   
        // 防止出现滚动条，出现的话，你会把滚动条一起拖着走的
    });   
    $(document).offon("click",tabId+" .show-media",function(){
        var file = $(this).prev(".upload-file").val();
        if(!file){
            var msg = "当前没有文件"
            notice(110,msg,"文件异常");
            throw msg
        }
        media(file)
    })
    $(document).offon("click",tabId+" .clear-media",function(){
        $(this).parent().children(".upload-file").val("")
    })
    $(document).offon("mouseover click",".approve-group .approve-log,.approve-group .approve-con",function(event){
        if(!$(this).hasClass("disabled") && ($("#approve-log-modal").css("display")=="none" || $("#approve-log-modal").css("display")==undefined)){
            var table = $(this).parents(".approve-group").data("table")
            var id = $(this).parents(".approve-group").data("id")
            var url = $(this).data("url")
            var tableId =  $(tabId+" .global-modal .table-id[name='table-id']").val();
            var indata = {table:table,id:id,tableId:tableId}
            var place = $(tabId+" .global-modal .place-id[name='place-id']").val();
            var apl_id = "approve-log-modal";
            if($("#approve-log-modal").html() == undefined){
                var html='<div class="modal fade in" id="'+apl_id+'" style="display: block; padding-right: 17px;"><div class="modal-dialog" style="top: 10%;"><div class="modal-content"><div class="modal-header"><button type="button" class="close modal-close" data-dismiss="modal" aria-label="关闭"><span aria-hidden="true"> × </span></button><h4 class="modal-title"></h4></div><div class="modal-body"></div><div class="modal-footer"><button type="button" class="btn btn-default pull-left modal-close" data-dismiss="modal">关闭</button></div></div></div></div>'
                $(document).find("body").append(html);
                $(document).on("click","#"+apl_id+" .modal-close",function(){
                    $(this).parents("#"+apl_id).toggleClass("modal fade in")
                    $(this).parents("#"+apl_id).prev(".modal-backdrop").toggleClass("none")
                    $(this).parents("#"+apl_id).css("display","none")
                })           
            }else{
                if(!$("#"+apl_id).hasClass("modal fade in")){
                    $("#"+apl_id).toggleClass("modal fade in")
                }
                $("#"+apl_id).prev(".modal-backdrop").toggleClass("none")
                $("#"+apl_id).css("display","block")
            }
            if($(this).hasClass("approve-log")){
                var title = "审批记录"
                var body = '<p class="text-yellow" style="font-weight: bold;">下一个审批者：<span class="next-examine"></span></p><table class="table table-bordered"><thead><tr><th>操作人</th><th>职务</th><th>状态</th><th>时间</th><th>备注</th></tr></thead><tbody></tbody></table><div><div class="progress progress-striped active"><div class="progress-bar progress-bar-primary" style="width: 0%"></div></div></div>'
            }else{
                var title = "审批操作"
                var body ='<div class="form-group"><label>审批内容</label><textarea class="form-control approve-remark" rows="3" placeholder="如果驳回或拒绝请写明理由"></textarea></div><div style="text-align: right;"> <button type="button" class="btn bg-olive approve-btn btn-sm" data-status="1">通过</button> <button type="button" class="btn bg-orange approve-btn btn-sm" data-status="3">驳回</button> <button type="button" class="btn btn-danger approve-btn btn-sm" data-status="5">拒绝</button></div>';
            }
            $("#"+apl_id+" .modal-title").text(title);
            $("#"+apl_id+" .modal-body").html(body);
            if($(this).hasClass("approve-log")){
                get(url,indata,function(result){
                    // console.log(result)
                    var current = 0;
                    var allProcess = result.allProcess > 0 ? (Number(result.allProcess)) : 0;
                    if(result.errCode == 0){
                        var trHtml = '';
                        result.data.forEach(element => {
                            trHtml+='<tr><td>'+element.user_name+'</td><td>'+element.role_name+'</td><td>'+element.state+'</td><td>'+element.add_time+'</td><td>'+element.remark+'</td></tr>'
                            if(Number(element.status) == 1){
                                current++
                            }
                        });
                        // console.log(result.nextExamine)
                        $("#"+apl_id+" .modal-body .next-examine").text(result.nextExamine)
                        if(result.nextExamine=="已完成" && current != allProcess){
                            current = current > 0 ? current : 1
                            allProcess = current;
                        }
                        var progress = float(current/allProcess)*100;
                        var final = " 未完成";
                        if(progress>=100){
                            progress = 100;
                            $("#"+apl_id+" .modal-body .progress").removeClass("active");
                            final = " 已完成";
                        }
                        
                        $("#"+apl_id+" .modal-body tbody").html(trHtml);
                        $("#"+apl_id+" .modal-body .progress .progress-bar").css("width",progress+"%");
                        if(current>0){
                            $("#"+apl_id+" .modal-body .progress .progress-bar").text("当前进度："+current+" / "+allProcess+final);
                        }
                        
                    }
                },false)
            }else{
                $(document).offon("click","#"+apl_id+" .modal-body .approve-btn",function(){
                    var approve_remark = $("#"+apl_id+" .approve-remark").val()
                    if(($(this).hasClass("bg-orange")  || $(this).hasClass("btn-danger")) && approve_remark == ""){
                        notice(110,$(this).text()+"必须写明理由","操作异常");
                        return false
                    }
                    if($fileInput!=""){
                        indata["file"]={key:$fileInput.attr("name"),file:$fileInput.val()}
                    }
                    indata["remark"] = approve_remark
                    indata["place"] = place;
                    indata["status"] = $(this).data("status")
                    post(url,indata,function(result){
                        if(result.errCode==0){
                            notice(result.errCode,result.error,false,2);
                            $("#"+apl_id+" .modal-close").click();
                            if($('body').hasClass('modal-open')){
                                $(tabId+" .global-modal .modal-content .close").click();
                                
                                // $(tabId+" .search-box .search-list").click();
                            }
                            $(tabId+" .search-body").find(".search-list").click();
                            // console.log($(tabId+" .search-body").find(".search-list"))
                            if($('body').hasClass('modal-open')){
                                setTimeout(() => {
                                    $(tabId).find(".status-con .v-showmodal").each(function(){
                                        if($(this).parent(".status-con").data("id") == tableId){
                                            $(this).click();
                                            return false;
                                        }
                                    })
                                },500)
                            }
                            
                        }else{
                            notice(result.errCode,result.error);
                        }
                    })
                })
            }
            //调整样式    width: 600px;    height: 376px;
            // $("#"+apl_id).css({width:"600px"});
            var width = $(window).width()
            width = width > 600 ? 600 : width; 
            var height = $("#"+apl_id+" .modal-dialog .modal-content").height()
            // height = Number(height)+100
            // console.log(height)
            // console.log($(this).offset().top)
            $("#"+apl_id).css({width:width+"px",left:"50%",top:"50%",height:height+"px",marginTop:"-"+(height/2)+"px",marginLeft:"-"+(width/2)+"px"});
            $("#"+apl_id+" .modal-dialog").css({top:"0px",width:width+"px",height:height+"px",margin:"0px"});
            // console.log($("#approve-log-modal").offset().top)
            // console.log($("#approve-log-modal").height());
        }
        
    })
    $(document).on("mouseout",".approve-group .approve-log",function(evnet){
        // var mouse  = evnet.screenY;
        // var modalTop = $("#approve-log-modal").position().top;
        // var height = $("#approve-log-modal").height();
        var width = $(window).width();
        // console.log(mouse)
        // console.log(modalTop)
        // console.log(height)
        if($("#approve-log-modal").find(".approve-btn").length==0){
            if(width>600){
                $("#approve-log-modal .modal-close").click();
            }else{
            }
            
        }
    })
    $(document).on("click",tabId+" input[class='all-checked']",function(){
        var checked = $(this).is(":checked");
        $(this).parents("table").find("tbody input[class='item-checked']").prop("checked",checked)
    })
    $(document).on("change",tabId+" .search-info[name='pageSize']",function(){
        $(tabId+" .search-list").trigger("click");
    })
    /** 
     * javascript comment 
     * @Author: vition 
     * @Date: 2018-09-28 15:30:15 
     * @Desc: 激活重新提审 
     */    
    $(document).on("click",tabId+" .reset-info-active",function(){
        var title = $(this).text()
        
        if(title=="重新提审"){
            sourceData = {}
            $(this).parents(".modal").find(".modal-info").each(function(){
                var val = $(this).val()
                var name = $(this).attr("name")
                
                var tagType = $(this).get(0).tagName.toLocaleLowerCase()
                if(tagType=="select"){
                    var text = $(this).find("option:selected").text()
                    if($.isArray(val)){
                        sourceData[name] = {key:val.join(","),text:text}
                    }else{
                        sourceData[name] = {key:val,text:text}
                    }
                    
                }else{
                    sourceData[name] = val
                }
                $(this).prop("disabled",false);
                if($(this).hasClass("chosen-select")){
                    $(this).trigger("chosen:updated");
                }
            })
            $(this).text("确认重审");
        }else if(title=="确认重审"){
            $(this).parents(".modal").find(".modal-info").each(function(){
                var val = $(this).val()
                var name = $(this).attr("name")
                var tagType = $(this).get(0).tagName.toLocaleLowerCase()

                if(tagType=="select"){
                    var text = $(this).find("option:selected").text()
                    // sourceData[name] = {key:val,text:text}
                    
                    if(sourceData[name]['key']!==val && !in_array(val,["00:00:00"]) && JSON.stringify(val) != "[]" ){
                        // resetData[name] = {key:val,text:text}
                        if($.isArray(val)){
                            resetData[name] = {key:val.join(","),text:text}
                        }else{
                            resetData[name] = {key:val,text:text}
                        }
                    }
                }else{
                    if(sourceData[name]!==val && !in_array(val,["00:00:00"]) && JSON.stringify(val) != "[]" ){
                        resetData[name] = val
                    }
                }
            })
            // console.log(resetData);throw '';
            if(JSON.stringify(resetData) === "{}"){
                notice(101,"没修改任何数据","输入异常");
            }else{
                var self = this
                var gettype=$(this).data("gettype");
                var con=$(this).data("con");
                var isModal=$(this).data("modal");
                // var search=con+"_search";

                var url = $(this).data('url');
                var db = $(this).data('db');
                var id = $(this).parents('.modal').find(".table-id").val();
                datas.reqType = gettype
                datas.data = {}
                datas.data.datas = resetData
                datas.data.db = db
                datas.data.id = id
                post(url,datas,function(result){
                    console.log(result)
                    if(result.errCode==0){
                        notice(result.errCode,result.error);
                        url=$(tabId+" .search-list").data("url");
                        reqtype=$(tabId+" .search-list").data("reqtype");
                        var table=con+"-table";
                        var page=con+"-page";
                        var count=con+"-count";
                        datas.reqType=reqtype;
                        if(fun_is_exits(con+"_searchInfo")){
                            eval(con+"_searchInfo(result)")//对不同的id设置不同的发送数据
                        }else{
                            datas['data']={}
                            $(tabId+" .search-info").each(function(){
                                var name=$(this).attr("name");
                                var val=$(this).val();
                                if(val!=""){
                                    datas['data'][name]=val
                                }
                            })
                        }
                        if(isModal){
                            searchFun({url:url,datas:datas,table:table,page:page,count:count})
                        }
                        if($('body').hasClass('modal-open')){
                            $(tabId+" .modal").modal('hide')
                        }
                        if(datas.reqType=="Add"){
                            $(self).data("gettype","");
                        }
                    }else{
                        notice(result.errCode,result.error);
                    }
                });
                // console.log(resetData)
            }
        }
    });
})
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-03-04 15:22:16 
 * @Desc: 获取浏览器action的值 
 */
function getUrlAction(){
    var clSearch=window.location.search
    if(clSearch!="" && clSearch.search(/\?action\=/)>=0){
        if(clSearch.indexOf("&")>=0){
            return clSearch.match(/\=([\S\/]*)(&+[\S\/]*)/)[1];
        }else{
            return clSearch.match(/\=([\S\/]*)([\S\/]*)/)[1];
        }
    }
    return false;
}
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-03-04 15:26:33 
 * @Desc: 设置浏览器的url 不刷新 
 */
function setUrlAction(title,newUrl){
    var stateObject = {};
    history.pushState(stateObject,title,"?action="+newUrl);
}
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-03-04 15:40:28 
 * @Desc: 通过nodeId更改url action 
 */
function chUrlAction(nodelId){
    $(document).find(".nodeOn").each(function(elem){
        if(parseInt(nodelId)==parseInt($(this).data("nodeid"))){
            var tController=$(this).attr("href")
            var tMatch = tController.match(/\/Admin\/([\S\/]*)\./)
            var param = tController.match(/\&[\S\/\&]*/)
            urlParam=getUrlAction()
            if(tMatch!=null && tMatch[1]!=urlParam){
                $(".sidebar-menu").find(".nodeOn").removeClass("node-action");
                $(".sidebar-menu").find("a[href='"+tMatch[0]+"html']").addClass("node-action");
                if(param){
                    tMatch[1]+=param[0];
                  }
                setUrlAction(tMatch[1],tMatch[1])
            }
            return false;
        }
    })
}
var dialogMove=false
$(document).on("click",".full-screen",function(e){
    var modalDialog=$(this).parents(".modal-dialog")
    if(modalDialog.hasClass("modal-full")){
        modalDialog.removeClass("modal-full");
        $(this).children("i").addClass("fa-arrows-alt")
        $(this).children("i").removeClass("fa-compress")
    }else{
        modalDialog.addClass("modal-full");
        $(this).children("i").removeClass("fa-arrows-alt")
        $(this).children("i").addClass("fa-compress")
    }
    // $(this).parents(".modal-dialog").css("width","100%");
    // console.log($(this).parents(".modal-dialog").css("width"));
})
//递归获取select option
function getArtClsNode(element,level){
    var option=""
    var strs="";
    for (let index = 0; index < level; index++) {
        strs+="——";
    }
    if(typeof(element.nodes)=='object'){
        level++
        element.nodes.forEach(elementSub => {
            option+=getArtClsNode(elementSub,level);
        });
    }
    return '<option value="'+element.id+'">'+strs+element.text+'</option>'+option;
}
function clearTags(html){
    return $('<p>'+html+'</p>').text()
}
//格式化月份
function formatMonth(month){
    month++;
    if(month<10){
        return "0"+month;
    }
    return month;
}
//格式化日期
function formatDate(date){
    if(date<10){
        return "0"+date;
    }
    return date;
}
//判断函数是否存在
function fun_is_exits(funcName){
    try {
        if (typeof(eval(funcName)) == "function") {
 	   return true;
        }
   } catch(e) {}
   return false;
}
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-06-02 19:14:20 
 * @Desc: 设置加载图标 
 */
function setLoad(timeOut){
    if($("#loadwaiting .overlay i").hasClass("fa-spin")){
        $("#loadwaiting .overlay i").removeClass("fa-spin")
    }else{
        $("#loadwaiting .overlay i").addClass("fa-spin")
        timeOut = timeOut ? timeOut*1000 : 5000;
        setTimeout(function(){
            if($("#loadwaiting").hasClass("fa-spin")){
                setLoad()
            }
        },timeOut);
    }
    if($("#loadwaiting").hasClass("none")){
        $("#loadwaiting").removeClass("none")
    }else{
        $("#loadwaiting").addClass("none")
    }
}
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-05-29 22:20:49 
 * @Desc: 弹出提示框 
 * notice(status,content,title,seconds)
 */
function notice(status){
    ["box-warning","box-danger","box-primary"].forEach(function(col){
        $("#v-notice-window .box-solid").removeClass(col);
    })
    var color="box-warning"
    var title = "";
    var content = "";
    var seconds = arguments[3] >= 0 ? arguments[3] : 3
    if(status==100){
        color = "box-danger"
        title = arguments[2] ? arguments[2] : "错误提示！"
        content = arguments[1] ? arguments[1] : "操作失败，请仔细检查数据"
    }else if(status==0){
        color = "box-primary"
        title = arguments[2] ? arguments[2] : "成功提示！"
        content = arguments[1] ? arguments[1] : "操作成功"
    }else{
        content = arguments[1] ? arguments[1] : "出现异常了，联系下管理员吧！"
        title = arguments[2] ? arguments[2] : "异常提示！"
    }
    if(status==77){
        seconds = arguments[3] >= 0 ? arguments[3] : 0
    }
    $("#v-notice-window .box-solid").addClass(color);
    $("#v-notice-window .box-solid .box-header .box-title").text(title);
    $("#v-notice-window .box-solid .box-body").html(content);
    $("#v-notice-window").removeClass("none");
    if(seconds>0){
        setTimeout(function(){$("#v-notice-window").addClass("none");$("#v-notice-window .box-solid").removeClass(color);},Number(seconds)*1000)
    }else{
        
    }
}
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-06-02 07:51:33 
 * @Desc: 关闭提示框 
 */
$("#v-notice-window .v-close").on("click",function(){
    if(!$("#v-notice-window").hasClass("none")){
        $("#v-notice-window").addClass("none");
    } 
})
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-06-06 23:54:32 
 * @Desc: 整合日期插件 
 */
function init_date(){
    var opt = arguments[0]
    var parental = arguments[1]

    if(typeof(parental)=="object"){
        var $this = parental
    }else{
        parental = parental ? " "+parental : '' 
        var $this = $(tabId+parental);
    }
    $this.find(".date-input").each(function(){
        var option =opt ? opt : {theme: '#3C8DBC'}
        var name = $(this).attr("name")
        var indexNum = $(tabId+" .date-input[name='"+name+"']").length;
        var thisId = tabId.replace("#","")+"-"+name+indexNum
        $(this).attr("id",thisId);
        var type = $(this).data("type")
        if(type){
            option["type"] = type
        }
        option["elem"] = option["elem"] ? option["elem"] : "#"+thisId
        if(thisId){
            laydate.render(option);
        }
    })
}
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-07-14 15:25:20 
 * @Desc: 整合chosen 
 */
function init_chosen(url,reqType,parental){
    // parental = parental ? " "+parental : ''  
    if(typeof(parental)=="object"){
        var $this = parental
    }else{
        parental = parental ? " "+parental : '' 
        var $this = $(tabId+parental);
    }
    $this.find(".chosen-select").each(function(){
        var type = $(this).attr('name')
        var value = $(this).data('value')
        var text = $(this).data('text')
        var req =$(this).data('req');
        reqType = req ? req : reqType;
        var subUrl = $(this).data('url')
        url = subUrl ? subUrl : url;
        var option = {inherit_select_classes:true,search_contains:true,allow_single_deselect:true}

        if(text!=undefined && value!=undefined && reqType!=undefined){
            var ajax_json = {url:url,data:{reqType:reqType,type:type},value:value,text:text}
            var pname = $(this).data('pname')
            var gpname = $(this).data('gpname')
            // var pname = $(this).data('pname')
            if(pname!=undefined){
                ajax_json["pelement"] = $this.find(".chosen-select[name='"+pname+"']").eq($this.find(".chosen-select[name='"+type+"']").index(this))
            }
            if(gpname!=undefined){
                ajax_json["gpelement"] = $this.find(".chosen-select[name='"+gpname+"']").eq($this.find(".chosen-select[name='"+type+"']").index(this))
            }
            // console.log($this)
            var noupdate = $(this).data('noupdate')
            if(noupdate!=undefined){
                ajax_json["noupdate"] = true
            }
            var extra = $(this).data("extra")
            if(extra!=undefined){
                ajax_json["extra"] = extra
            }
            option["ajax_load"] = ajax_json
        }
        var cname = $(this).data('cname')
        if(cname!=undefined){
            option["child"] = $this.find(".chosen-select[name='"+cname+"']")
        }
        var gdson = $(this).data('gdson')
        if(gdson!=undefined){
            option["gdson"] = $this.find(".chosen-select[name='"+gdson+"']")
        }
        var disSearch = $(this).data('dis-search')
        if(disSearch!=undefined){
            option["disable_search"] = true
        }
        $(this).chosen(option)
        var $thisChosen = $(this)
        $(this).next(".chosen-container").offon("dblclick",function(){
            // console.log('dblclick');
            $thisChosen.chosen_ajax();
        })
        if($(this).parents(".form-inline").length>0 && $(window).width() > 750){
            var width = $(this).parents(".form-inline").find(".chosen-fcopy").width();
            if(width){
                $(this).next(".chosen-container").css("width",(Number(width)+22)+"px");
            }
            
            // if($(this).parent(".form-group").parent("div[class^='col-md']").length>0){
            //     $(this).parent(".form-group").css("width","100%")
            //     var all = $(this).parent(".form-group").width()
            //     var babel = $(this).prev(".control-label").width()
            //     $(this).next(".chosen-container").css("width",(Number(all)-Number(babel)-3)+"px")
            // }else{
            //     $(this).next(".chosen-container").css("width","100%");
            //     console.log($(this).parent(".form-group").find("label").width());
            // }           
        }
    })
}
function float(num,place) {
    var result = parseFloat(num);
    if (isNaN(result)) {
    //   alert('传递参数错误，请检查！');
      return false;
    }
    result = Math.round(num * 100) / 100;
    var s_x = result.toString();
    if(place==0){
        return Number(s_x.split(".")[0])
    }
    var pos_decimal = s_x.indexOf('.');
    if (pos_decimal < 0) {
      pos_decimal = s_x.length;
      s_x += '.';
    }
    place = place > 0 ? place : 2 
    while (s_x.length <= pos_decimal + place) {
      s_x += '0';
    }
    return parseFloat(s_x);
  }
var $fileInput = ""//当前文件input
function upload(option){
    var url = option.url
    var urlArr = {}
    if(!url == undefined){
        throw '没有请求网址';
    }
    var el = option.el !=undefined ? option.el : ".upload-file"
    urlArr[tabId+el] = url
    // console.log(urlArr);
    
    $(document).offon("click",tabId+" "+el,function(){
        $fileInput = $(this)
        var onlyread = $(this).attr("onlyread")
        if($(tabId+"-upload-modal").html() == undefined && onlyread ==undefined){
            var html='<div class="modal fade in" id="'+tabId.replace("#","")+'-upload-modal" style="display: block; padding-right: 17px;"><div class="modal-dialog" style="top: 10%;"><div class="modal-content"><div class="modal-header"><button type="button" class="close modal-close" data-dismiss="modal" aria-label="关闭"><span aria-hidden="true"> × </span></button><h4 class="modal-title">文件上传</h4></div><div class="modal-body"><div class="input-group"><input readonly="readonly" class="form-control upload-item" type="text"><div class="input-file none"><input class="upload-item-file" name="upload-file-name" multiple="multiple" type="file"></div><span class="input-group-btn"><button type="button" class="btn btn-info btn-flat load-files-btn"><i class="fa  fa-file"></i> 选择文件 </button></span></div></div><div class="modal-footer"><button type="button" class="btn btn-default pull-left modal-close" data-dismiss="modal">关闭</button><button type="button" class="btn btn-primary upload-file-btn"><i class="fa fa-upload" ></i> 全部上传</button><button type="button" class="btn btn-danger del-file-btn"><i class="fa fa-bomb" ></i> 全部删除</button></div></div></div></div>'
            $(document).find("body").append(html);
            
            $(document).on("click",tabId+"-upload-modal .modal-close",function(){
                $(this).parents(tabId+"-upload-modal").toggleClass("modal fade in")
                $(this).parents(tabId+"-upload-modal").prev(".modal-backdrop").toggleClass("none")
                $(this).parents(tabId+"-upload-modal").css("display","none")
            })
            $(document).offon("click",tabId+"-upload-modal .load-files-btn",function(){
                $(this).parent().prev().find("input").trigger("click")
                var ulHtml ='<ul class="products-list product-list-in-box" style="padding: 5px 10px;max-height: 460px;overflow: auto;"></ul>'
                if($(tabId+"-upload-modal .modal-body .products-list").html() == undefined){
                    $(tabId+"-upload-modal .modal-body").append(ulHtml);
                    
                    $(tabId+"-upload-modal").offon("click",".modal-body .products-list .product-info .delete-file-btn",function(){
                        var name = $(this).parents('li').find("a").attr("name")
                        var reg =RegExp(name+"\[\,\]*")
                        $(tabId+"-upload-modal .upload-item").val($(tabId+"-upload-modal .upload-item").val().replace(reg,''))
                        delete tempFiles[$(this).parents('li').attr("name")]
                        $(this).parents('li').remove();
                    })
                    $(tabId+"-upload-modal").offon("click",".modal-body .products-list .product-info .insert-file-btn",function(){
                        $fileInput.val($(this).attr("name"))
                        if(option.call){
                            option.call(this)
                        }
                    })
                    $(tabId+"-upload-modal").offon("click",".modal-footer .upload-file-btn",function(){
                        if(Object.getOwnPropertyNames(tempFiles).length>0){
                            for (const fileName in tempFiles) {
                                uploadData = new FormData();
                                uploadData.append("file",tempFiles[fileName])
                                $.ajax({
                                    url:urlArr[tabId+el],
                                    type:"post",
                                    data:uploadData,
                                    processData:false,
                                    contentType:false,
                                    xhr:function(){
                                        var xhr = $.ajaxSettings.xhr();
                                        if(xhr.upload){
                                            xhr.upload.addEventListener("progress",function(evt){
                                                var loaded = evt.loaded;
                                                var tot = evt.total;
                                                var per = Math.floor(100*loaded/tot);
                                                $(tabId+"-upload-modal .modal-body .products-list li[name='"+fileName+"']").find(".progress .progress-bar").css("width",per+"%")
                                            },false);
                                            return xhr;
                                        }
                                    }
                                }).done(function(result){
                                    if(result.errCode==0){
                                        $(tabId+"-upload-modal .modal-body .products-list li[name='"+fileName+"']").find(".progress").removeClass("active")
                                        $(tabId+"-upload-modal .modal-body .products-list li[name='"+fileName+"'] .insert-file-btn").attr("name",result.url2)
                                        $(tabId+"-upload-modal .modal-body .products-list li[name='"+fileName+"'] .insert-file-btn").removeClass("none")
                                    }
                                })
                            }
                        }else{
                            notice(110,'当前没有文件','文件输入',3);
                        }
                        
                    })
                    $(tabId+"-upload-modal").offon("click",".modal-footer .del-file-btn",function(){
                        $(tabId+"-upload-modal .modal-body .products-list").html("")
                        $(tabId+"-upload-modal .upload-item").val("")
                        tempFiles = {};
                    })
                }
                $(document).offon("change",tabId+"-upload-modal .upload-item-file",function(){
                    // $(tabId+"-upload-modal .modal-body .products-list").html("")
                    $(tabId+"-upload-modal .upload-item-file").each(function(){
                        var uploadItem = ""
                        var errorMes = "";
                        for (let index = 0; index < this.files.length; index++){
                           
                            var element = this.files[index];
                            // 限制文件不能大于10M
                            var thisFile = element.name.split(".")
                            
                            if(Math.floor(element.size/1024/1024)<=10){
                                var src = window.URL.createObjectURL(element)
                                var fileType = thisFile[thisFile.length-1].toLowerCase()
                                var fname = $.md5(thisFile[thisFile.length-2].toLowerCase())
                                var hasMedia = $(tabId+"-upload-modal .modal-body .products-list").find("li[name="+fname+"]").html();
                                if(hasMedia==undefined && Object.getOwnPropertyNames(tempFiles).length<20){
                                    if(in_array(fileType,["jpeg","jpg","png","gif"])){
                                    
                                    }else if (in_array(fileType,["doc","docx","xls","xlsx","ppt","pptx"])){
                                        src = "/Public/admintmpl/dist/img/office.jpg" 
                                    }else if(in_array(fileType,["pdf"])){
                                        src = "/Public/admintmpl/dist/img/pdf.jpg"
                                    }else{
                                        src = "/Public/admintmpl/dist/img/files.jpg" 
                                    }
                                    if(float(element.size/1024/1024) < 1){
                                        var fileSize = float(element.size/1024)+"K"
                                    }else{
                                        var fileSize = float(element.size/1024/1024)+"M"
                                    }
                                    var liHtml = '<li class="item" name="'+fname+'"><div class="product-img"><img src="'+src+'" alt="Product Image"></div><div class="product-info"><a href="javascript:void(0)" name="'+element.name+'" lass="product-title">'+element.name+'<span class="btn btn-warning btn-flat pull-right insert-file-btn none"><i class="fa fa-link" ></i> 插入 </span><span class="btn btn-danger btn-flat pull-right delete-file-btn"><i class="fa fa-close"></i> 删除 </span></a><span class="product-description">文件大小：'+fileSize+' 文件类型：'+fileType+'</span><div class="progress progress-sm active"><div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"><span class="sr-only">0% Complete</span></div></div></div></li>';
                                    $(tabId+"-upload-modal .modal-body .products-list").append(liHtml);
                                    if(uploadItem!=""){
                                        uploadItem+=","+element.name
                                    }else{
                                        uploadItem+=element.name
                                    }
                                    tempFiles[fname] = element
                                    $(tabId+"-upload-modal .upload-item").val(uploadItem)
                                }else if(Object.getOwnPropertyNames(tempFiles).length>=20){
                                    errorMes+="<p>最多同时只能上传20个文件，"+thisFile[0]+"文件未能上传;</p>"
                                }
                            }else{
                                errorMes+="<p>"+thisFile[0]+"文件超过10M;</p>"
                            }
                            if(errorMes!=""){
                                notice(110,errorMes,'文件超大',5);
                            }
                        }
                    })
                })
            })
        }else if(onlyread ==undefined){
            $(tabId+"-upload-modal").toggleClass("modal fade in")
            $(tabId+"-upload-modal").prev(".modal-backdrop").toggleClass("none")
            $(tabId+"-upload-modal").css("display","block")
        }
    })
}
function read_file(){
    var el = option.el !=undefined ? option.el : ".read_file"
    $(document).offon("click",tabId+" "+el,function(){

    })
}
function in_array(val,array){
    for (var index = 0; index < array.length; index++) {
        if (val == array[index]){
            if(arguments[2]){
                return ++ index
            }
            return true;
        }
    }
    return false;
}
function media(mediafile,title){
    if(!mediafile){
        return false;
    }
    var re = /^http/
    mediafile = re.test(mediafile) ? mediafile : domain()+"/"+mediafile

    var fileArr = mediafile.split(".")
    var suffix = fileArr[fileArr.length-1].toLowerCase();
    var mediaHtml = "";
    var height = $(window).height()*0.7;
    var width = $(window).width();
    if(in_array(suffix,["jpg","jpeg","png","gif","bmp"])){
        //支持的图片格式处理
        if(width<767){
            var style ='width:100%;';
        }else{
            var style ='height: 100%;';
        }
        mediaHtml = '<div style="padding-bottom: 5px;"><button class="img-enlarge"><i class="fa fa-fw fa-search-plus"></i>放大</button><button class="img-reduce"><i class="fa fa-fw fa-search-minus"></i>缩小</button><button class="img-cwise"><i class="fa fa-fw fa-rotate-right"></i>顺时针90°</button><button class="img-acwise"><i class="fa fa-fw fa-rotate-left"></i>逆时针90°</button></div><div class="img-box" style="justify-content: center;height:'+height+'px;display: flex;align-items: center;position: relative;overflow: hidden;"><p style="position: relative;"><img style="'+style+'" src="'+mediafile+'" /></p></div>';
    }else if(suffix=="pdf"){
        //pdf处理
        mediaHtml = '<iframe style="width:100%;height:'+height+'px" src="'+domain()+"/Admin/Tools/viewPdf?src="+mediafile+'" frameborder="0"></iframe>';
    }else if(in_array(suffix,["doc","docx","xls","xlsx","ppt","pptx"])){
        // word excel ppt 调用Office Online 处理
        mediaHtml = '<iframe style="width:100%;height:'+height+'px" src="http://view.officeapps.live.com/op/view.aspx?src='+mediafile+'" frameborder="0"></iframe>';
    }else{
        var msg = "暂时不支持处理"+suffix+"的文件";
        notice(110,msg,"文件类型出错");
        throw msg
    }
    title = title ? title : '文件查看'
    // return false;
    if($("#vmedia-box").html()==undefined){
        
        var html = '<div id="vmedia-box" class="modal fade" aria-hidden="true" data-backdrop="static" ><div class="modal-dialog modal-lg modal-full"><div class="modal-content"><div class="modal-header"><button type="button" class="close modal-close" data-dismiss="modal" aria-label="关闭"><span aria-hidden="true"> × </span></button><h4 class="modal-title">'+title+'</h4></div><div class="modal-body" style="overflow: hidden;">       </div><div class="modal-footer"><button type="button" class="btn btn-default pull-left modal-close" data-dismiss="modal">关闭</button></div></div></div></div>';
        $(document).find("body").append(html);
        $("#vmedia-box").toggle()
        $("#vmedia-box").toggleClass("in")
        $("#vmedia-box").offon("click",".modal-close",function(){
            $("#vmedia-box").toggle();
            $("#vmedia-box").toggleClass("in")
        })
    }else{
        $("#vmedia-box").toggle()
        $("#vmedia-box").toggleClass("in")
    }
    $("#vmedia-box .modal-body").html(mediaHtml)
    // $div_img = $("#vmedia-box").find(".img-box p")
    if(in_array(suffix,["jpg","jpeg","png","gif","bmp"])){
        //如果是图片具备操作
        $("#vmedia-box .modal-body .img-box p").bind("mousedown",function(event){event.preventDefault&&event.preventDefault();
            var $thisImg = $(this) 
            var offset_x=Number($(this).css('left').replace("px",""));
            var offset_y=Number($(this).css('top').replace("px",""));
            var mouse_x=event.pageX;
            var mouse_y=event.pageY;
            $("#vmedia-box .modal-body .img-box").bind("mousemove",function(ev){
                var _x=ev.pageX-mouse_x;
                var _y=ev.pageY-mouse_y;
                var now_x=(offset_x+_x)+"px";
                var now_y=(offset_y+_y)+"px";
                $thisImg.css({top:now_y,left:now_x});
            });
        });
        $("#vmedia-box .modal-body .img-box").bind("mouseup",function(){
            $(this).unbind("mousemove");
        })
        var zoom_n=1;
        $("#vmedia-box .modal-body").offon("click",".img-enlarge",function(){
            zoom_n+=0.1;
            $("#vmedia-box .modal-body .img-box img").css({
                "transform":"scale("+zoom_n+")",
                "-moz-transform":"scale("+zoom_n+")",
                "-ms-transform":"scale("+zoom_n+")",
                "-o-transform":"scale("+zoom_n+")",
                "-webkit-transform":"scale("+zoom_n+")"
            });
        })
        $("#vmedia-box .modal-body").offon("click",".img-reduce",function(){
            zoom_n-=0.1;zoom_n=zoom_n<=0.1?0.1:zoom_n;
            $("#vmedia-box .modal-body .img-box img").css({
                "transform":"scale("+zoom_n+")",
                "-moz-transform":"scale("+zoom_n+")",
                "-ms-transform":"scale("+zoom_n+")",
                "-o-transform":"scale("+zoom_n+")",
                "-webkit-transform":"scale("+zoom_n+")"
            });
        })
        var spin_n=0;
        $("#vmedia-box .modal-body").offon("click",".img-cwise",function(){
            spin_n+=90;
            $("#vmedia-box .modal-body .img-box img").parent("p").css({
                "transform":"rotate("+spin_n+"deg)",
                "-moz-transform":"rotate("+spin_n+"deg)",
                "-ms-transform":"rotate("+spin_n+"deg)",
                "-o-transform":"rotate("+spin_n+"deg)",
                "-webkit-transform":"rotate("+spin_n+"deg)"
            })
        })
        $("#vmedia-box .modal-body").offon("click",".img-acwise",function(){
            spin_n-=90;
            $("#vmedia-box .modal-body .img-box img").parent("p").css({
                "transform":"rotate("+spin_n+"deg)",
                "-moz-transform":"rotate("+spin_n+"deg)",
                "-ms-transform":"rotate("+spin_n+"deg)",
                "-o-transform":"rotate("+spin_n+"deg)",
                "-webkit-transform":"rotate("+spin_n+"deg)"
            })
        })
    }
}
function domain(){
    return window.location.protocol+"//"+window.location.host
}
var set_table_data = function(listData,tableData,statusType,attachCall){
    listData.forEach(element => {
        if(tableData !=undefined && tableData[element]["list"]){
            var list = tableData[element]["list"];
            var template = tableData[element]["template"];
            var allMoney = 0;
            list.forEach(function(listData,rows){
                $(tabId+" .global-modal ."+element+" tbody").append(template);
                var $current = $(tabId+" .global-modal ."+element+" tbody tr").eq(rows);
                $current.find(".serial").text(Number(rows+1));
                attachCall($current,listData,statusType)
                
                // for (var key in listData) {
                //     if(key == "status"){
                //         $current.find("td[name='"+key+"']").text(statusType[listData[key]]);
                //     }else{
                //         $current.find(".modal-info[name='"+key+"']").val(listData[key]);
                //         if(listData["status"] == 1){
                //             $current.find(".modal-info[name='"+key+"']").prop("disabled",true);
                //         }
                        
                //     }
                // }
                // init_date(false,$current);
            });
        }
    });
}
var toAlias = function(url,string){
    get(url,{string:string},function(result){
        // console.log(result)
    })
}
var tableMove = function($box,callback){
    var sort = 0;
    var url = $box.data("url");
    var db = $box.data("db");
    $box.on("click",'.tsort-up',function(){
        var tr = $(this).parents('tr')
        var sort = tr.find('.tsort-input').val();
        var id = tr.find('.tsort-control').data("id")
        if(tr.prev().length>0){
            var data = {}
            tr.insertBefore(tr.prev());
            data = getSortData($(this))    
            changeSort(url,db,data,callback)
        }
    });
    $box.on("click",'.tsort-down',function(){
        var tr = $(this).parents('tr')
        var sort = tr.find('.tsort-input').val();
        var id = tr.find('.tsort-control').data("id")
        if(tr.next().length>0){
            var data = {}
            tr.insertAfter(tr.next());
            data = getSortData($(this))
            changeSort(url,db,data,callback)
        }
    });
    $box.on("focus",'.tsort-input',function(){
        sort = $(this).val();
    });
    $box.on("blur",'.tsort-input',function(){
        var newSort = $(this).val();
        var tr = $(this).parents('tr')
        var id = tr.find('.tsort-control').data("id")
        var data = {};
        data[id] = newSort
        if(newSort!==sort && newSort >=0 ){
            changeSort(url,db,data,callback)
        }
    });
}
var getSortData = function($this){
    var temp = {}
    $this.parents(".table").find(".tsort-control").each(function(index){
        temp[$(this).data("id")] = index
    })
    return temp;
}
var changeSort = function($url,$db,$data,callback){
    datas = {}
    var pageSize = $(tabId+" .search-info[name='pageSize']").val();
    var page = $(tabId+" .dataTables_paginate ul .active a").data("page")
    page = page ? page : 1;
    datas.reqType = "change_sort"
    var s_index = (page - 1 ) * Number(pageSize)
    for (var key in $data) {
        $data[key] = Number($data[key]) + s_index
    }
    datas.data = $data
    datas.db = $db

    post($url,datas,callback)
    $(tabId+" .search-list").trigger("click");
}
var excel_import = function(option){
    var url = option.url
    var urlArr = {}
    if(!url == undefined){
        throw '没有请求网址';
    }
    var el = option.el !=undefined ? option.el : ".excel-import";
    var excel_modal = tabId+"-excel-import-modal";
    var temp_excel = false;
    var db = "";
    urlArr[tabId+el] = url
    $(document).offon("click",tabId+" "+el,function(){
        db = $(this).data("db");
        con = $(this).data("con");
        if($(excel_modal).html() == undefined ){
            var html='<div class="modal fade in" id="'+excel_modal.replace("#","")+'" style="display: block; padding-right: 17px;"><div class="modal-dialog" style="top: 10%;"><div class="modal-content"><div class="modal-header"><button type="button" class="close modal-close" data-dismiss="modal" aria-label="关闭"><span aria-hidden="true"> × </span></button><h4 class="modal-title">文件导入</h4></div><div class="modal-body"><div class="input-group"><input readonly="readonly" class="form-control excel-input" type="text"><div class="input-excel none"><input class="upload-excel" name="upload-excel-name" type="file"></div><span class="input-group-btn"><button type="button" class="btn btn-info btn-flat load-excel-btn"><i class="fa fa-file-excel-o"></i> 选择文件 </button></span></div></div><div class="modal-footer"><button type="button" class="btn btn-default pull-left modal-close" data-dismiss="modal">关闭</button><button type="button" class="btn btn-primary upload-excel-btn"><i class="fa fa-upload" ></i> 导入数据 </button></div></div></div></div>'
            $(document).find("body").append(html);

            $(document).on("click",excel_modal+" .modal-close",function(){
                $(this).parents(excel_modal).toggleClass("modal fade in")
                $(this).parents(excel_modal).prev(".modal-backdrop").toggleClass("none")
                $(this).parents(excel_modal).css("display","none")
            })
            $(document).offon("click",excel_modal+" .load-excel-btn",function(){
                $fileInput = $(this).parent().prev().find("input");
                $fileInput.trigger("click")
                $fileInput.offon("change",function(){
                    if(this.files.length>0){
                        var element = this.files[0];
                        var thisFile = element.name.split(".")
                        if(Math.floor(element.size/1024/1024)<=10){
                            temp_excel = this.files[0]
                            $(this).parents(excel_modal).find(".excel-input").val(element.name);
                        }else{
                            notice(110,"导入文件不能超过10M",'文件超大',5);
                        }
                    }
                })
            })
            $(excel_modal).offon("click",".modal-footer .upload-excel-btn",function(){
                if(temp_excel){
                    var excelData = new FormData();
                    excelData.append("excel",temp_excel);
                    excelData.append("db",db);
                    excelData.append("vtabId",tabId);
                    excelData.append("con",con);
                    $.ajax({
                        url:urlArr[tabId+el],
                        type:"post",
                        data:excelData,
                        processData:false,
                        contentType:false,
                    }).done(function(result){
                        notice(result.errCode,result.error,'导入数据',3);
                        if(result.errCode==0){
                            $(excel_modal).find(".modal-close").trigger("click")
                            $(tabId+" .search-list").trigger("click");
                        }
                    })
                }else{
                    notice(110,'当前没有文件','文件输入',3);
                }
            })
        }else{
            $(excel_modal).toggleClass("modal fade in")
            $(excel_modal).prev(".modal-backdrop").toggleClass("none")
            $(excel_modal).css("display","block")
        }
    })
}
var rest_control = function(info,option,callback){
    if(typeof(info['re_status'])=="string" && Number(info['re_status']) === 0){
        $(tabId+" .modal-footer").find(".reset-info-active").remove();
        var reset_datas = JSON.parse(info['rest_datas']);

        for (var key in reset_datas) {
            // $(tabId+" .modal-info[name='"+key+"']").val(reset_datas[key])
            $(tabId+" .modal-info[name='"+key+"']").addClass("data-reset");
            // console.log(reset_datas)
            // console.log(typeof(reset_datas[key]))
            if(typeof(reset_datas[key])=="object"){
                var text = reset_datas[key].text
            }else{
                var text = reset_datas[key]
            }

            $(tabId+" .modal-info[name='"+key+"']").after('<span class="badge bg-red" style="white-space:pre-wrap;text-align: left;">'+info['reset_user']+' '+info['reset_date']+' 提交修改为：'+text+'</span>')   
        }
        console.log(reset_datas)
    }
}
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-10-25 09:56:02 
 * @Desc: 重新定义表格，冻结带了 frozen 类的元素
 */
var table_frozen = function($this,noData){
    var scrollHtml = "<thead><tr>";
    var scrollCols = [];
    $this.parents('.table-outbox').find(".frozen-table").remove();
    if(noData){
        return false;
    }
    $this.parents('table').find("thead tr:not(.none)").find("th").each(function(index,self){
        if($(self).hasClass("is-frozen") && !$(self).parents("tr").hasClass("none")){
            scrollCols.push(index);
            scrollHtml+=$(self).prop("outerHTML");
        }
    })
    scrollHtml+="</tr></thead><tbody>";
    var trs =""
    // console.log($this.parents('table'))
    // console.log($this.parents('table').find("tbody tr"))
    // console.log($this.parents('table').find("tbody tr").length)
    $this.parents('table').find("tbody tr").each(function(index,self){
        var style  = $(self).attr("style") ? "style='"+$(self).attr("style")+"'" : "" ;
        var tr ="<tr "+style+">"
        $(self).find("td").each(function(td,tdSelf){
            if(in_array(td,scrollCols)){
                $(tdSelf).css({"width":$(tdSelf).width()+"px","height":$(tdSelf).parents("tr").height()+"px"})
                tr += $(tdSelf).prop("outerHTML");
            }
        })
        tr += "</tr>"
        trs += tr
    })
    scrollHtml+=trs+"</tbody>"
    if($this.parents('.table-outbox').html()!=undefined){
        $this.parents('table').before("<table class='table table-bordered table-hover frozen-table' style='width: auto;position: absolute;background: #ffffff;'>"+scrollHtml+"</table>")
        // if($this.parents('.table-outbox').find(".frozen-table").html()==undefined){
        //     $this.parents('table').before("<table class='table table-bordered table-hover frozen-table' style='width: auto;position: absolute;background: #ffffff;'>"+scrollHtml+"</table>")
        // }else{
        //     $this.parents('.table-outbox').find(".frozen-table").html(scrollHtml)
        // }
        
        var tleft = $this.parents('.table-outbox').find(".frozen-table").css("left").replace("px","")
        $this.parents('.table-outbox').scroll(function(){
            left = Number($(this).scrollLeft()+Number(tleft))
            $this.parents('.table-outbox').find(".frozen-table").css("left",left+"px")
        })
        $(tabId+" table tbody tr").on("mouseenter",function(){
            table_tr_active(this,"add")
        })
        $(tabId+" table tbody tr").on("mouseleave",function(){
            table_tr_active(this,"remove")
        })
    }
}
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-10-25 10:28:42 
 * @Desc: 鼠标移动到tr的状态 
 */
var table_tr_active = function($this,type){
    var tableBox = $($this).parents(".table-outbox")
    var tables = tableBox.find("table");
    var tableIndex = $(tables).index($($this).parents("table"))
    var $trs = tableBox.find("table").eq(tableIndex).find("tr");
    var otherTable = tableIndex > 0 ? 0 : 1;
    if(type=="add"){
        tableBox.find("table").eq(otherTable).find("tr").eq($($trs).index($this)).addClass("tr-active");
    }else{
        tableBox.find("table").eq(otherTable).find("tr").eq($($trs).index($this)).removeClass("tr-active");
    }
}
function fsizeFormat(size){
    if(float(size/1024/1024) < 1){
        var fileSize = float(size/1024)+"K";
    }else{
        var fileSize = float(size/1024/1024)+"M";
    }
    return fileSize;
}