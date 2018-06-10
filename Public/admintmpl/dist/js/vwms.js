var datas={};
var filesData={};
/** 
 * @Author: vition 
 * @Date: 2018-01-23 00:41:37 
 * @Desc: get方式获取数据 
 */    
function get(url,datas,callBack){
    setLoad()
    asyncs=arguments[3]!=undefined?arguments[3]:true;
    datas["vtabId"]=tabId
    $.ajax({
        url:url,
        type:'get',
        dataType:'json',
        data:datas,
        async:asyncs,
    }).done(function(result) {callBack(result);})
      .always(function() { setLoad();datas={};})
    
    
}
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-01-27 22:43:43 
 * @Desc: post发送 
 */
function post(url,datas,callBack){
    asyncs=arguments[3]!=undefined?arguments[3]:true;
    setLoad()
    datas["vtabId"]=tabId
    $.ajax({
        url:url,
        type:'post',
        dataType:'json',
        data:datas,
        async:asyncs,
    }).done(function(result) {callBack(result);})
    .always(function() { setLoad();datas={};})
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
$(document).on("click",".search-list,.vpage",function(){
    datas={}
    var page=$(this).data('page')
    if(page>0){
        datas['p']=page;
        var url=$(this).parents('.page-div').data("url");
        var con=$(this).parents('.page-div').data("con");
        var reqtype=$(this).parents('.page-div').data("reqtype");
    }else{
        var url=$(this).data("url");
        var con=$(this).data("con");
        var reqtype=$(this).data("reqtype");
    }
    var table=con+"-table";
    var page=con+"-page";
    var count=con+"-count";
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
    searchFun(url,datas,table,page,count);
})
/** 
 * javascript comment 
 * @Author: vition 
 * @Date: 2018-06-02 23:08:58 
 * @Desc: 执行查询数据并插入到对应的表格中 
 */
function searchFun(url,datas,table,page,count){
    get(url,datas,function(result){
        if(result.errCode==0){          
            $(tabId+" ."+table).html(result.table);
            $(tabId+" ."+page).html(result.page);
            $(tabId+" ."+count).html(result.count);
        }else{
            notice(result.errCode,result.error);
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
 * @Desc: 弹出 global-modal 操作 new
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
    get(url,datas,function(result){
        // console.log(result)
        if(result.errCode==0){
            $(tabId+" .global-modal .modal-content").html(result.html);
            $(tabId+" "+vtarget).modal('toggle')
            if(gettype=="Edit"){
                if(fun_is_exits(con+"_setInfo")){
                    eval(con+"_setInfo(result.data)");//对不同的模块设置不同的响应数据
                }
            }else{
                if(fun_is_exits(con+"_initInfo")){
                    eval(con+"_initInfo()");//
                } 
            }
        }
    })
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
        if(((info['status'] == 1 || info['processLevel'] == 2) || info['author'] != userId )  && nodeAuth<7){
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
    datas={}
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
    	eval(con+"_getInfo()");//对不同的id设置不同的发送数据
    }else{
        datas["data"]={}
        $(tabId+" .modal-info").each(function(){
            var name =$(this).attr("name");
            var val =$(this).val();
            var required=$(this).attr("required");
            var title=$(this).attr("title");
            if(required=="required" && val==""){
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
    // console.log(datas);
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
                searchFun(url,datas,table,page,count)
            }
            if($('body').hasClass('modal-open')){
                $(tabId+" .global-modal").modal('toggle')
            }
        }else{
            notice(100,result.error);
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
    var url = $(this).parent(".status-con").data("url");
    var id = $(this).parent(".status-con").data("id");
    var db = $(this).parent(".status-con").data("db");
    var con = $(this).parent(".status-con").data("con");
    var status = $(this).data("status");
    var html = '<div class="v-status-box" style="text-align: center;" data-status="'+status+'"  data-db="'+db+'" data-con="'+con+'" data-url="'+url+'" data-id="'+id+'"><div class="col-sm-3"><button type="button" name="del"  class="btn btn-sm bg-orange submit-status">删除</button></div><div class="col-sm-5"><input type="password" placeholder="输入二级密码" class="form-control input-sm senior-password" /></div><div class="col-sm-3"><button type="button" name="deepDel" class="btn bg-navy btn-sm submit-status">彻底删除</button></div></div>'
    notice(100,html,"删除提示",0)
})
$(document).on("click",".submit-status",function(){
    var statusType = $(this).attr("name")
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
    if(statusType=="deepDel" && senior_pwd == ""){
        alert("彻底删除必须输入二级密码")
        throw "彻底删除必须输入二级密码";
    }
    var data={reqType:"globalStatusEdit",statusType:statusType,id:id,status:status,db:db,seniorPwd:senior_pwd}
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
            searchFun(url,datas,table,page,count);
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
        if(paramMatch){
            var splitArr=paramMatch.split("/");
            var match= window.document.body.innerHTML.match(new RegExp("\/Admin\/"+splitArr[0]+"\/"+splitArr[1]+"[\.a-zA-Z]*","gim"))
            if(match[0]){
                $(document).find(".nodeOn").each(function(){
                    if($(this).attr("href")==match[0]){
                        var result=$(this).parents(".treeview-menu").css("display","block");
                        var nodeOn=$(this);
                        setTimeout(function(){nodeOn.click();},0);
                        return false
                    }
                })
            }
        }
        
    // }
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
        return clSearch.match(/\=([\S\/]*)/)[1];
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
            var tMatch=tController.match(/\/Admin\/([\S\/]*)\./)
            urlParam=getUrlAction()
            if(tMatch!=null && tMatch[1]!=urlParam){
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
function setLoad(){
    if($("#loadwaiting").hasClass("none")){
        $("#loadwaiting").removeClass("none")
    }else{
        $("#loadwaiting").addClass("none")
    }
    if($("#loadwaiting .overlay i").hasClass("fa-spin")){
        $("#loadwaiting .overlay i").removeClass("fa-spin")
    }else{
        $("#loadwaiting .overlay i").addClass("fa-spin")
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
    var seconds = arguments[3] >= 0 ? arguments[3] : 2
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
    $(tabId+" .date-input").each(function(){
        var option =opt ? opt : {theme: '#3C8DBC'}
        var thisId = $(this).attr("id")
        option["elem"] = option["elem"] ? option["elem"] : "#"+thisId
        if(thisId){
            // console.log(thisId)
            laydate.render(option);
        }
    })
}