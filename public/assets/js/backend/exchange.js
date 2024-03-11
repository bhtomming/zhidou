// const { Config } = require("parse");

define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        dhlist: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    list_url: 'exchange/dhlist?',
                }
            });

            var table = $("#dhlist");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.list_url,
                pk: 'did',
                sortName: 'did',
                columns: [
                    [
                        // {checkbox: true},
                        {field: 'did', title:'编号', sortable: true},
                        {field: 'userid', title: '用户ID', operate: 'LIKE'},
                        {field: 'name', title: '收件人姓名', operate: 'LIKE'},
                        {field: 'cardNo', title: '收件人电话', operate: 'LIKE'},
                        {field: 'address', title: '收件地址', operate: 'LIKE'},
                        {field: 'money', title: '扣除积分', operate: 'LIKE'},
                        {field: 'status', title: '状态', operate: 'LIKE',formatter:statusFormatter},
                        {field: 'code', title: '发货单号', operate: 'LIKE'},
                        {field: 'remark', title: '说明', operate: 'LIKE'},
                        {field: 'operate', title: __('Operate'),formatter:operateFormatterdhList,events:operateEvents},

                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

function statusFormatter(value,row,index){
    return value == 1 ? '已发货':'其他';
}
function operateFormatterdhList(value,row,index){
    var result = "";
    if(row.status == 0){
        result += '<button id="send" class="btn btn-default">发货</button>';
    }
    return result
}
window.operateEvents = {
    "click #send":function(e,value,row,index){
        window.location.href = Config.moduleurl+'/exchange/dhfh?did='+row.did;
    },
}


