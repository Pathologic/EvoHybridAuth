var ehaHelper = {};
(function($){
	ehaHelper = {
         init: function() {
            var workspace = $('#EvoHybridAuth');
            workspace.append('<table id="ehaGrid" width="100%"></table></div>');
            $('#ehaGrid').datagrid({
                url: ehaConfig.url,
                idField: 'id',
                sortName: 'id',
                sortOrder: 'DESC',
                queryParams: {internalKey: ehaConfig.uid},
                columns: ehaGridColumns,
                singleSelect: false,
                checkOnSelect:false,
                fitColumns: true,
                striped: true,
                scrollbarSize: 0,
                onClickRow:function(index){
                    $(this).datagrid('unselectRow',index);
                },
                onDblClickRow:function(index,row){
                    ehaHelper.show(row);
                }
            });
            $('#ehaGrid').datagrid('getPanel').panel('resize');
		},
		escape: function(str) {
			return str
			    .replace(/&/g, '&amp;')
			    .replace(/>/g, '&gt;')
			    .replace(/</g, '&lt;')
			    .replace(/"/g, '&quot;');
		},
        delete: function(id) {
            $.messager.confirm(_ehaLang['delete'],_ehaLang['are_you_sure_to_delete'],function(r){
                if (!r) return;
                $.post(
                    ehaConfig.url+'?mode=remove', 
                    {
                        id:id
                    },
                    function(response) {
                        if(response.success) {
                            $('#ehaGrid').datagrid('reload');
                        } else {
                            $.messager.alert(_ehaLang['error'],_ehaLang['cannot_delete']);
                        }
                    },'json'
                ).fail(function(xhr) {
                    $.messager.alert(_ehaLang['error'],_ehaLang['server_error']+xhr.status+' '+xhr.statusText,'error');
                });
            });
        },
        show: function(row) {
            var table = $('<table width="97%"></table>');
            var data = [];
            Object.keys(row).forEach(function (key) {
                data.push({key: key, value: row[key]});
            });
            $('<div></div>').append(table).window({
                height:600,
                modal:true,
                collapsible: false,
                minimizable: false,
                maximizable: false,
                title:row.identifier+'@'+row.provider,
                onBeforeOpen: function(){
                    table.datagrid({
                        data: data,
                        fit:true,
                        fitColumns:true,
                        columns:[[
                            {field:'key',title:_ehaLang['key'],width:100,fixed:true},
                            {field:'value',title:_ehaLang['value'],formatter:ehaHelper.escape}
                        ]],
                        striped: true,
                        onClickRow:function(index){
                            $(this).datagrid('unselectRow',index);
                        }
                    });
                },
                onOpen: function(){
                    var contentWidth = $('.datagrid-btable',this).width();
                    $(this).window('resize',{
                        width:contentWidth + 30.0
                    })
                }
            });
        }
	}
})(jQuery);
