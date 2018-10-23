<?php

//根据电话号码查询联系人信息
//function cmdGetContactInfo($p) {
//	Privilege::chkAction();
//	$friends = new Friends();
//	$data = $friends->getContactInfoByPhone($p['phone']);
//	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $data);
//}

//联系人申请
function cmdAddContactRequest($p) {
	Privilege::chkAction(true);
	$friends = new Friends();
	$data = $friends->addContactRequest($p['friend_id'], $p['content'], $p['type_add']);
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $data);
}

//拒绝联系人申请
function cmdRejectContactRequest($p) {
	Privilege::chkAction(true);
	$friends = new Friends();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $friends->rejectContactRequest($p['friend_id']));
}

//添加联系人
function cmdAddContact($p) {
	Privilege::chkAction(true);
	$fiends = new Friends();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $fiends->addContact($p['friend_id'], $p['name_remark'], $p['type_add']));
}

//获取联系人列表
//function cmdGetContactsList() {
//	Privilege::chkAction();
//	$friends = new Friends();
//	$rt = array('contacts_list' => $friends->getContactsList());
//	return array('err_code' => MessageCode::MSG_0_NO_ERROR, 'result' => $rt);
//}

//获取最近消息通知列表
//function cmdGetRecentMessageList() {
//	Privilege::chkAction();
//	$friends = new Friends();
//	$rt = array('recent_msg_list' => $friends->getRecentMessageList());
//	return array('err_code' => MessageCode::MSG_0_NO_ERROR, 'result' => $rt);
//}

//获取跟自己相关的所有friends消息
function cmdGetNewMessages($p) {
	Privilege::chkAction();
	$friends = new Friends();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $friends->getNewMessages($p['chat_lmt'], $p['friend_request_lmt'], $p['friends_lmt'], $p['member_lmt'], $p['transfer_lmt'], $p['group_lmt'], $p['chat_extend_group_lmt'], $p['chat_time_last_read_json']));
}

//获取当前会话的最新消息，与本地数据库lmt作比较
function cmdGetLastChatMsg($p) {
	Privilege::chkAction();
	$friends = new Friends();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $friends->getLastChatMsg($p['chat_id'], $p['lmt'], $p['time_last_read']));
}

//因为本地数据库未插入chatId,从服务器下载会话所有的消息和更新联系人列表
function cmdGetNewChatMsg($p) {
	Privilege::chkAction();
	$friends = new Friends();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $friends->getNewChatMsg($p['member_id'], $p['chat_member_lmt'], $p['friend_request_lmt'], $p['friends_lmt'], $p['member_lmt'], $p['transfer_lmt']));
}

//发送friend聊天消息
function cmdSendChatMsg($p) {
	Privilege::chkAction(true);
	$friends = new Friends();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $friends->sendMessage($p['member_id'], $p['content'], $p['content_type'], $p['time_last_read']));
}

//接收friend聊天消息
function cmdReceiveMsg($p) {
	Privilege::chkAction();
	$friends = new Friends();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $friends->getLastChatMsg($p['chat_id'], $p['lmt'], $p['time_last_read']));
}

//获取最新的联系人列表
function cmdGetLastFriendsList($p) {
	Privilege::chkAction();
	$friends = new Friends();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $friends->getLastFriendsList($p['friends_lmt'], $p['member_lmt']));
}

//获取最新的好友申请列表
function cmdGetLastFriendRequestList($p) {
	Privilege::chkAction();
	$friends = new Friends();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $friends->getLastFriendRequestList($p['friend_request_lmt'], $p['member_lmt']));
}

//根据关键词(名字/手机)搜索结果
function cmdSearchFriendKeyWord($p) {
	Privilege::chkAction();
	$friends = new Friends();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $friends->getSearchFriendByKeyWord($p['key_word']));
}

//根据手机上的联系人号码匹配会员，返回会员信息
function cmdGetMayKnowFriends($p) {
	Privilege::chkAction();
	$friends = new Friends();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $friends->getMayKnowFriends($p['str_phone']));
}

//根据会员id获取会员聊天信息
function cmdGetMemberChatInfo($p) {
	Privilege::chkAction();
	$friends = new Friends();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $friends->getMemberChatInfo($p['chat_id'], $p['member_id']));
}

//根据会员id获取群组聊天信息
function cmdGetChatGroupInfo($p) {
	Privilege::chkAction();
	$friends = new Friends();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $friends->getChatGroupInfo($p['chat_id']));
}

//聊天静音
function cmdSetChatMute($p) {
	Privilege::chkAction(true);
	$friends = new Friends();
	$friends->setChatMute($p['chat_id'], $p['flag_mute']);
	return array('err_code' => MessageCode::ERR_0_NO_ERROR);
}

//获取会员简介信息
function cmdGetFriendProfileInfo($p) {
	Privilege::chkAction();
	$friends = new Friends();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $friends->getFriendProfileInfo($p['member_id']));
}

//获取好友间转账信息
function cmdGetFriendTransferHistory($p) {
	Privilege::chkAction();
	$t = new Friends();
	$data = $t->getFriendTransferHistory($p['member_id'], $p['page']);
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $data);
}

//根据手机号码获取会员id
function cmdGetMemberIDByPhone($p) {
	Privilege::chkAction();
	$user = new User();
	$rt = [];
	$memberID = $user->getUserIDByPhone($p['phone']);
	if ($memberID) {
		$rt['exist'] = true;
		$rt['member_id'] = $memberID;
	} else {
		$rt['exist'] = false;
	}
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $rt);
}

//备注昵称
function cmdRemarkFriendName($p) {
	Privilege::chkAction(true);
	$friends = new Friends();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $friends->remarkFriendName($p['member_id'], $p['content']));
}

function cmdUpdateChatLastRead($p) {
	Privilege::chkAction(true);
	$friends = new Friends();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $friends->updateChatLastRead($p['chat_id']));
}

//添加转账消息
function cmdAddTransferMsg($p) {
	Privilege::chkAction(true);
	$friends = new Friends();
	$content = $friends->getTransferRemark($p['transfer_id']);
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $friends->sendMessage($p['friend_id'], $content, Friends::MESSAGE_TYPE_3_TRANSFER, $p['time_last_read'], $p['transfer_id']));
}

//创建聊天群组
function cmdBuildGroupChat($p) {
	Privilege::chkAction(true);
	$friends = new Friends();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $friends->buildGroupChat($p['selected_member_json']));
}

//第一次进入时作检查更新会话的消息
function cmdCheckUpdateChatMsg($p) {
	Privilege::chkAction(true);
	$friends = new Friends();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $friends->checkUpdateChatMsg($p['friend_request_lmt'], $p['friends_lmt'], $p['member_lmt'], $p['transfer_lmt'], $p['group_lmt'], $p['chat_extend_group_lmt'], $p['member_id']), $p['chat_id']);
}