<?php 
include dirname(__FILE__).'/../plan/preferences.php';
$ECHO_MODE = 'html'; //输出类型
require_once dirname(__FILE__).'/../plan/include.php';
	
	$output = true;
	
	//验证必填字段
	$tabType = get_request_param('tab_type');
	if (!isset($tabType)) {
		ResultHandle::fieldValidNotEmptyErrToJsonAndOutput('tab_type', $output);
		return;
	}
	
	$ptrId = get_request_param('from_id');
	if (!EBModelBase::checkDigit($ptrId, $outErrMsg, 'from_id')) {
		$json = ResultHandle::fieldValidNotDigitErrToJsonAndOutput('from_id', $output);
		return;
	}
	
	$embed = 1;
	if ($tabType==11 || $tabType==20 || $tabType==1 || $tabType==2) { //操作日志
		include dirname(__FILE__).'/../operaterecord/list.php';
	} else if ($tabType==3) { //关联任务
		//from_type 0：新建 1：计划转任务（from_id=任务编号） 2：拆分子任务（from_id=父任务编号）
		$fromType = get_request_param('from_type');
		$json = get_associate_task_of_plan($ptrId, $fromType, true, get_request_param('request_order_by'));
	} else if ($tabType==4) { //关联用户
		include dirname(__FILE__).'/../shareuser/list.php';
	} else if ($tabType==5) { //附件
		//不通过php服务端，直接通过eb rest api获取
	}
	
	if (isset($json))
		$results = get_results_from_json($json, $tmpObj);
	
	$userId = $_SESSION[USER_ID_NAME]; //当前用户的编号
?>
<div class="sidepage-tab-page-content">
	<div class="sidepage-tab-page-container mCustomScrollbar"  data-mcs-theme="dark-3">
		<div class="col-xs-12 sidepage-tab-page-header"></div>
		<!-- 预留位置 -->
		<div class="ebtw-clear"></div>
	</div>
	
	<div class="sidepage-tab-page-part2">
		<!-- 评论 -->
		<div class="sidepage-tab-page-discuss discuss2">
			<textarea placeholder="输入评论内容，Ctrl+Enter提交"></textarea>
			<button type="button" class="btn btn-primary discuss-submit pull-right">提  交</button>
			<!-- <button type="button" class="btn btn-default pull-right clear-content">清  空</button> -->
			<div class="sidepage-tab-page-attachment">
				<div class="m1 ebtw-file-upload">
					<div><span class="glyphicon glyphicon-paperclip"></span> 上传附件</div>
					<input type="file" class="file_upload_input" name="up_file"><!-- file控件name字段必要，否则不能上传文件 -->
				</div>
				 
				<div class="m2 ebtw-file-upload-list">
					<ul>
					</ul>
				</div>
				
				<div class="ebtw-clear"></div>
			</div>
		</div>
	</div>
	
	<!-- 评审 -->
	<div class="sidepage-tab-page-part3">
		<div class="sidepage-tab-page-discuss discuss2">
			<textarea placeholder="输入评审说明，Ctrl+Enter提交"></textarea>
			<button type="button" class="btn btn-danger approval-submit-reject pull-right" data-approval-type="reject">评审拒绝</button>
			<button type="button" class="btn btn-primary approval-submit-pass pull-right" data-approval-type="pass">评审通过</button>
			<!-- <button type="button" class="btn btn-default pull-right clear-content">清  空</button> -->
		</div>
	</div>	
</div>
<script type="text/javascript">
var resizeEventDiscuss2 = 'discuss2';
var registerSomeEventExecuted = false;
var tabType = <?php echo $tabType;?>;
var ptrId = '<?php echo $ptrId;?>';
var ptrType = <?php echo $PTRType;?>;
var rootUrl = '<?php echo $ROOT_URL;?>';
var subTypeOfTabBadges = 'plan_0';

//定义函数：计算已占用高度
function calculateRootHeight2() {
	var $element2 = $sidepageContainer.find('.sidepage-tab-page-part2');
	var $element3 = $sidepageContainer.find('.sidepage-tab-page-part3');
	var height = 0;
	
	if ($element2.css('display')!='none')
		height += $element2.outerHeight(true);
	if ($element3.css('display')!='none')
		height += $element3.outerHeight(true);
		
	return height;
}

//定义函数：设置列表区域最大高度
function adjustSidepageTabPageContainerHeight(resizeEventSuffixName) {
	adjustContainerHeight3UsingE($('#content-height-input'), $sidepageContainer.find('.sidepage-tab-page-container'), calculateRootHeight2(), true, resizeEventSuffixName);
}

function registerSomeEvent() {
	registerSomeEventExecuted = true;
	
	//textarea自适应高度
	var executed = false;
	$sidepageContainer.find('.sidepage-tab-page-discuss.discuss2>textarea').autoHeight(function(oldH, newH) {
		if (oldH!=newH) {
			executed = true;
			adjustSidepageTabPageContainerHeight(resizeEventDiscuss2);
		}
	});
	//防止第一次执行时初始值与新值相等而导致列表区域没有设置设置高度
	if (!executed)
		adjustSidepageTabPageContainerHeight(resizeEventDiscuss2);
	
	//注册事件-点击日期标签 折叠/展开
	bindStretchClick($sidepageContainer.find('.date-mark'), null, function($This) {
		return $This.next().find("li>div")
	}, function($This, executeValues) {
		$This.parent().find('li:not(:first)').css('display', executeValues[0]);
	});
	
	//注册事件-标签页子项(编辑、删除等)管理操作
	registerSidepageTabItemActions($sidepageContainer, tabType, ptrId, ptrType, TabBadgesDatas, subTypeOfTabBadges);
	//注册事件-点击图片附件文件，自动打开浏览
	registerZoom('.attachment-link .open-resource[data-ext-type="2"]', 'data-open-url');
	
	//注册事件-管理关联用户操作(删除等)及对应工具栏
	registerDeleteShareUsersAction('<?php echo $userId;?>', $sidepageContainer, ptrId, ptrType);
	
	//负责人、参与人、共享人、关注人 
	var personTypes = <?php echo createShareUserTypesScript();?>;
	//注册事件-点击添加关联用户按钮
	registerAddShareUsersAction('<?php echo $userId;?>', $sidepageContainer, ptrId, ptrType, personTypes, rootUrl, function() {
		refreshTabBadges(subTypeOfTabBadges, 'sidepage-tab', [TabBadgesDatas[tabType], TabBadgesDatas[20]]); //刷新Tab角标数值
	});	
}

$(document).ready(function() {
	var opTypeClass = '<?php echo get_request_param('op_type_class');?>';

	//注册事件-清除评论的输入内容
// 	$sidepageContainer.find('.sidepage-tab-page-discuss .clear-content').click(function() {
// 		$(this).parent().find('textarea').val('').trigger('input');
// 	});
	
	//加载标签页数据
	var tabTypeDict = {11:SideTabTypes['opr'], 2:SideTabTypes['opr'], 1:SideTabTypes['opr'], 3:SideTabTypes['ass'], 4:SideTabTypes['su'], 5:SideTabTypes['att'], 20:SideTabTypes['opr']};
	<?php if ($tabType!=5) {?>
		var datas = '<?php echo escapeQuotes(strictJson(json_encode($results?$results:'')));?>';
		logjs_info(datas);
		var allowedActionsDict = {singlePtr:true, ptrId:ptrId, ptrType:ptrType};
		allowedActionsDict[ptrId] = allowedActions;
		loadSidepageTabData('<?php echo $userId;?>', planCreateUid, allowedActionsDict, isDeleted, $sidepageContainer, ptrType, tabTypeDict[tabType], datas, rootUrl);
	<?php }?>

	//自定义滚动条
	customScrollbarUsingE($sidepageContainer.find('.sidepage-tab-page-container'), 30, true);
	//滚动条移动至最底部
	setTimeout(function() {
		$sidepageContainer.find('.sidepage-tab-page-container').mCustomScrollbar('scrollTo', 'bottom');
	}, 200);
	
	//评论/回复界面
	if (opTypeClass==1 && isDeleted!=1) {
		$sidepageContainer.find('.sidepage-tab-page-part2').css('display', 'block');

		//注册事件-点击新增/删除附件按钮
		var attaType = 3; //评论附件
		var onlyOne = true;
		var loadExist = false;
		var isEdit = false;
		var isOnlyView = false;
		registerAttachmentActions(isEdit, isOnlyView, ptrType, ptrId, attaType, '#sidepage-tab-content .ebtw-file-upload', function(result, resourceId) {
			//重新设置列表区域最大高度
			adjustSidepageTabPageContainerHeight(resizeEventDiscuss2);
		}, loadExist, onlyOne, '#sidepage-tab-content .ebtw-file-upload-list', '.attachment-remove', function() {
			//重新设置列表区域最大高度
			adjustSidepageTabPageContainerHeight(resizeEventDiscuss2);
		});
		
		//注册事件-“评论回复”[Ctrl+Enter回车]提交
		var doingDiscussSubmit = false;
		registerEnterKeyToWork($sidepageContainer, true, '.sidepage-tab-page-part2 .sidepage-tab-page-discuss.discuss2 textarea', function($textInputElement) {
			if (doingDiscussSubmit) {
				logjs_info('miss duplicate discuss submit');
				return;
			}
			
			doingDiscussSubmit = true;
			//触发点击“保存”事件
			$textInputElement.parent().find('.discuss-submit').trigger('click');
			setTimeout(function(){
				doingDiscussSubmit = false;
				}, 5000);
		});
		
		//点击提交评论
		$sidepageContainer.find('.discuss-submit').click(function() {
			//评论内容
			var content = $(this).prev('textarea').val().trim();
			if (!checkContentLength(0, 'discuss', content))
			    return false;
			
			var $liElements = $sidepageContainer.find('.sidepage-tab-page-attachment .ebtw-file-upload-list li');
			var fileCount = $liElements.length;
			if (content.length==0 && fileCount==0) {
				layer.msg('请输入评论内容或选择附件', {icon:5});
				return;
			}
			
			if (fileCount>0) { //有附件
				var i=0;
				var fromType = 10 + parseInt(ptrType);
				var title = '';
				var liElements = new Array();
				$liElements.each(function(){
					liElements.push(this);
				});
				
				executeSendfile(fromType, ptrId, attaType, liElements, fileCount, i, title, false, function(title, close, i, total, fileName, result, resourceId) {
					var codeMap = $.jqEBMessenger.errCodeMap;
					if (result.code==codeMap.OK.code) {
						saveReplyOrDiscuss(ptrId, ptrType, content, resourceId, fileName, function(result) {
							layer.msg('发表评论成功');
							refreshTabBadges(subTypeOfTabBadges, 'sidepage-tab', [TabBadgesDatas[tabType], TabBadgesDatas[5], TabBadgesDatas[20]]); //刷新Tab角标数值
							$sidepageContainer.parent().find('#sidepage-tab'+tabType).trigger('click'); //模拟点击刷新Tab内容页面
						}, function(reason) {
							layer.msg('发表评论内容失败', {icon: 2});
						});
					} else {
						layer.msg('上传评论附件失败', {icon: 2});
					}
				});
			} else { //只有评论内容
				saveReplyOrDiscuss(ptrId, ptrType, content, null, null, function(result) {
					layer.msg('发表评论成功');
					refreshTabBadges(subTypeOfTabBadges, 'sidepage-tab', [TabBadgesDatas[tabType], TabBadgesDatas[20]]); //刷新Tab角标数值
					$sidepageContainer.parent().find('#sidepage-tab'+tabType).trigger('click'); //模拟点击刷新Tab内容页面
				}, function(reason) {
					layer.msg('发表评论内容失败', {icon: 2});
				});
			}
		});
	} else if (opTypeClass==3) {
		if (approvalUser.valid_flag==1 && ($.inArray(4, allowedActions)>-1 || $.inArray(5, allowedActions)>-1)) {
			$sidepageContainer.find('.sidepage-tab-page-part3').css('display', 'block');
			
			//点击提交评审结果
			$sidepageContainer.find('.approval-submit-pass, .approval-submit-reject').click(function() {
				var $contentElement = $(this).parent().find('textarea'); 
				var content = $contentElement.val().trim();
				if (content.length==0) {
					layer.msg('请输入说明内容', {icon:5});
					$contentElement.focus();
					return;
				}
				
				var title;;
				var allowedAction;
				var actionType;
				var approvalType = 	$(this).attr('data-approval-type');
				if(approvalType=='pass') {
					allowedAction = 4;
					actionType = 2;
					title = '评审通过';
				} else if (approvalType=='reject') {
					allowedAction = 5;
					actionType = 3;
					title = '评审拒绝';
				}
				
				//执行提交
				layer.confirm('真的要<span class="'+(actionType==2?'ebtw-color-primary':'ebtw-color-urgent')+'">'+title+'</span>吗?', function(index) {
					var loadIndex = layer.load(2);
					approvalAction(actionType, ptrType, ptrId, content, true, function(result) {
						layer.close(loadIndex);
						
						//只允许发表一次有效评审；评审完成后过滤"评审通过"和"评审拒绝"功能代码
						var index = allowedActions.indexOf(4);
						if (index > -1)
							allowedActions.splice(index, 1);
						//"评审拒绝"功能代码
						index = allowedActions.indexOf(5);
						if (index > -1)
							allowedActions.splice(index, 1);
						
						layer.msg('提交'+title+'成功');
	 					refreshTabBadges(subTypeOfTabBadges, 'sidepage-tab', [TabBadgesDatas[tabType], TabBadgesDatas[20]]); //刷新Tab角标数值
	 					$sidepageContainer.parent().find('#sidepage-tab'+tabType).trigger('click'); //模拟点击刷新Tab内容页面						
					}, function(err) {
						layer.close(loadIndex);
						layer.msg('提交'+title+'失败', {icon: 2});
					});
					
					layer.close(index);
				});
			});
		}		
	}

	<?php if ($tabType!=5) {?>
	if (!registerSomeEventExecuted)
		registerSomeEvent();
	<?php }?>
});
</script>