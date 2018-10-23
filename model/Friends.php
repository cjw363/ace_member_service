<?php

use validation\Validator as V;

class Friends extends \Base {
	const DB_RUN = "im_run";
	const STATUS_1_REQUEST_PENDING = 1;
	const STATUS_2_REQUEST_ACCEPTED = 2;
	const STATUS_4_REQUEST_REJECTED = 4;

	const CHAT_TYPE_1_FRIEND = 1;//1 Friend 2 Group
	const CHAT_TYPE_2_GROUP = 2;

	const MESSAGE_STATUS_1_NEW = 1;//1 new 新信息 2 read 已读
	const MESSAGE_STATUS_2_READ = 2;

	const MESSAGE_TYPE_1_TEXT = 1;//文本
	const MESSAGE_TYPE_3_TRANSFER = 3;//转账
	const MESSAGE_TYPE_4_VOICE = 4;//语音
	const MESSAGE_TYPE_99_SYSTEM_MSG = 99;//系统消息

	const FLAG_1_MUTE_NOTIFICATIONS_YES = 1;//是否静音通知
	const FLAG_2_MUTE_NOTIFICATIONS_NO = 2;//是否静音通知

	const STATUS_1_SYS_NOTIFICATION_PENDING = 1;
	const STATUS_2_SYS_NOTIFICATION_SENT = 2;//已发送
	const STATUS_3_SYS_NOTIFICATION_USER_RECEIVED = 3;//User Received

	const TYPE_ADD_1_SCAN_QR_CODE = 1;//扫描二维码
	const TYPE_ADD_2_SCAN_MY_QR_CODE = 2;//被扫描二维码
	const TYPE_ADD_3_SEARCH_VIA_PHONE = 3;//查找手机号码方式
	const TYPE_ADD_4_SEARCH_MY_PHONE = 4;//被查找手机号码方式

	const NOTIFICATION_TYPE_1_TRADE = 1;//交易提醒
	const NOTIFICATION_TYPE_2_REPAYMENT = 2;//还款提醒
	const NOTIFICATION_TYPE_6_FRIEND_REQUEST = 6;//朋友申请

	const FLAG_VERIFY_INVITATION_1_YES = 1;//邀请是否需要验证 1 Yes 2 No
	const FLAG_VERIFY_INVITATION_2_NO = 2;

	public function __construct() {
		parent::__construct(self::DB_RUN);
	}

	//根据电话号码获取用户信息
	//	public function getContactInfoByPhone($phone) {
	//		$phone = Utils::formatPhone2($phone);
	//		if (!V::phone($phone) || !V::max_length($phone, 20)) throw new TrantorException('Phone');
	//		$sql1 = "select t.member_id,t.status,t.content,t2.id,t2.name,t2.phone,t2.country_code,t2.level from tbl_friend_request t left join db_system_run.tbl_member t2 on(t2.id=t.member_id or t2.id=t.friend_id) where t2.phone=" . qstr($phone) . " and (t.member_id=" . qstr($_SESSION['UID']) . " or t.friend_id=" . qstr($_SESSION['UID']) . ")";
	//		$contactInfo = $this->getLine($sql1);
	//		if ($contactInfo) {
	//			$contactInfo['is_application'] = true;
	//			return $contactInfo;
	//		} else {
	//			$sql2 = "select name,id,phone,country_code,level from db_system_run.tbl_member where phone=" . qstr($phone);
	//			$contactInfo = $this->getLine($sql2);
	//			if ($contactInfo) {
	//				$contactInfo['is_application'] = false;
	//				return $contactInfo;
	//			} else {
	//				return "";
	//			}
	//		}
	//	}

	//添加联系人申请信息
	public function addContactRequest($friendID, $content, $typeAdd) {
		if (!V::over_num($friendID, 0)) throw new TrantorException('FriendID');
		if (!V::over_num($typeAdd, 0)) throw new TrantorException('TypeAdd');

		$sql1 = 'select friend_id,status from tbl_friend_request where member_id = ' . qstr($_SESSION['UID']) . " and friend_id=$friendID order by lmt desc limit 1";
		$sql2 = "insert into tbl_friend_request (time,member_id,friend_id,content,type_add,status,time_complete) values(now()," . qstr($_SESSION['UID']) . "," . qstr($friendID) . "," . qstr($content) . "," . qstr($typeAdd) . "," . qstr(self::STATUS_1_REQUEST_PENDING) . ",now())";
		$sql4 = "insert into tbl_notification (time,user_type,user_id,type,content,status,time_sent,lmt) values (now()," . qstr(User::USER_TYPE_1_MEMBER) . ",$friendID," . qstr(self::NOTIFICATION_TYPE_6_FRIEND_REQUEST) . ",''," . qstr(self::STATUS_2_SYS_NOTIFICATION_SENT) . ",now(),now())";

		$request = $this->getLine($sql1);//查找一条申请最新记录

		if ($request['friend_id'] == $friendID) {//已经有申请记录了
			if ($request['status'] == self::STATUS_4_REQUEST_REJECTED) {//判断是否被拒绝了，是的话插入新申请记录
				$this->execute($sql2);
			} else {//不是，更新状态
				$sql3 = "update tbl_friend_request set time = now(),status =" . qstr(self::STATUS_1_REQUEST_PENDING) . " where member_id =" . qstr($_SESSION['UID']) . " and friend_id = $friendID order by lmt desc limit 1";
				$this->execute($sql3);
			}
		} else {
			$this->execute($sql2);
		}
		$this->execute($sql4);//生成好友申请通知
		return $this->affected_rows() > 0;
	}

	//拒绝联系人申请
	public function rejectContactRequest($friendID) {
		if (!V::over_num($friendID, 0)) throw new TrantorException('FriendID');

		$this->execute("update tbl_friend_request set time_complete = now(),status=" . qstr(self::STATUS_4_REQUEST_REJECTED) . " where member_id =$friendID and friend_id = " . qstr($_SESSION['UID']) . " order by lmt desc limit 1");
		return $this->affected_rows() > 0;
	}

	//添加联系人
	public function addContact($friendID, $nameRemark, $typeAdd) {
		if (!V::over_num($friendID, 0)) throw new TrantorException('FriendID');
		if (!V::over_num($typeAdd, 0)) throw new TrantorException('TypeAdd');
		if (!V::required($nameRemark)) throw new TrantorException('NameRemark');

		$myTypeAdd = 0;
		if ($typeAdd == self::TYPE_ADD_3_SEARCH_VIA_PHONE) $myTypeAdd = self::TYPE_ADD_4_SEARCH_MY_PHONE; else if ($typeAdd == self::TYPE_ADD_1_SCAN_QR_CODE) $myTypeAdd = self::TYPE_ADD_2_SCAN_MY_QR_CODE;

		$sql1 = "insert into tbl_friends (member_id,friend_id,name_remark,time_add,type_add) values (" . qstr($_SESSION['UID']) . ",$friendID," . qstr($nameRemark) . ",now()," . qstr($myTypeAdd) . "),($friendID," . qstr($_SESSION['UID']) . "," . qstr($_SESSION['NAME']) . ",now()," . qstr($typeAdd) . ")";
		$sql2 = "update tbl_friend_request set status = " . qstr(self::STATUS_2_REQUEST_ACCEPTED) . " , time_complete=now() where ((member_id = $friendID and friend_id = " . qstr($_SESSION['UID']) . ") or (member_id=" . qstr($_SESSION['UID']) . " and friend_id=$friendID)) order by lmt desc limit 1";

		$result1 = $this->execute($sql1) && $this->execute($sql2);

		//添加成功发起聊天
		$result2 = $this->_addHelloFriendMsg($friendID, $nameRemark);
		return $result1 && $result2;
	}

	//获取联系人列表
	//	public function getContactsList() {
	//		$sql = "select f.name_remark name,f.friend_id,m.phone from tbl_friends f left join db_system_run.tbl_member m on f.friend_id=m.id where f.member_id = " . qstr($_SESSION['UID']);
	//		$data = $this->getArray($sql);
	//		return $data;
	//	}

	private function _getChatID($friendID) {
		if (!V::over_num($friendID, 0)) throw new TrantorException('FriendID');
		//先判断两人之间有没有会话记录
		$sql1 = "select c.id from (select t1.chat_id from tbl_chat_member t1 left join tbl_chat_member t2 on t1.chat_id=t2.chat_id where t1.member_id=" . qstr($_SESSION['UID']) . " and t2.member_id=$friendID ) t LEFT JOIN tbl_chat c ON c.id=t.chat_id where c.chat_type=" . qstr(self::CHAT_TYPE_1_FRIEND);
		$chatID = $this->getOne($sql1);
		if (empty($chatID)) {
			//创建会话
			$sql2 = "insert into tbl_chat(time,chat_type,time_last_member_join,time_last_message) values (now()," . qstr(self::CHAT_TYPE_1_FRIEND) . ",now(),now())";
			$this->execute($sql2);
			$chatID = $this->insert_id();
			//增加会话成员
			$sql3 = "insert into tbl_chat_member(chat_id,member_id,time_join,flag_mute_notifications) values ($chatID," . qstr($_SESSION['UID']) . ",now()," . qstr(self::FLAG_2_MUTE_NOTIFICATIONS_NO) . "),($chatID,$friendID,now()," . qstr(self::FLAG_2_MUTE_NOTIFICATIONS_NO) . ")";
			$this->execute($sql3);
		}
		return $chatID;
	}

	//发送消息
	public function sendMessage($friendID, $content, $contentType, $timeLastRead, $transferID = 0) {
		if (!V::over_num($friendID, 0)) throw new TrantorException('FriendID');
		if (!V::over_num($contentType, 0)) throw new TrantorException('ContentType');
		if (!is_numeric($transferID)) throw new TrantorException('TransferID');
		if (!V::required($content)) throw new TrantorException('Content');

		$chatID = $this->_getChatID($friendID);

		//插入聊天内容
		$sql1 = "insert into tbl_chat_content(time,chat_id,member_id,type,content,transfer_id) values (now(),$chatID," . qstr($_SESSION['UID']) . "," . qstr($contentType) . "," . qstr($content) . ",$transferID)";
		$this->execute($sql1);

		$this->execute("update tbl_chat set time_last_message=now(),lmt=now() where id=$chatID");//更新会话时间

		$rt = array();
		if ($timeLastRead) {
			$sql2 = "SELECT id,chat_id,time,member_id,type,content,transfer_id,lmt FROM tbl_chat_content  WHERE (type<>" . qstr(self::MESSAGE_TYPE_99_SYSTEM_MSG) . " OR (member_id=" . qstr($_SESSION['UID']) . " AND type=" . qstr(self::MESSAGE_TYPE_99_SYSTEM_MSG) . ")) and chat_id=$chatID and lmt>=" . qstr($timeLastRead);
		} else {
			$sql2 = "SELECT id,chat_id,time,member_id,type,content,transfer_id,lmt FROM tbl_chat_content  WHERE (type<>" . qstr(self::MESSAGE_TYPE_99_SYSTEM_MSG) . " OR (member_id=" . qstr($_SESSION['UID']) . " AND type=" . qstr(self::MESSAGE_TYPE_99_SYSTEM_MSG) . ")) and chat_id=$chatID and lmt>=(SELECT time_last_read FROM tbl_chat_member WHERE chat_id =$chatID AND member_id =" . qstr($_SESSION['UID']) . ")";
		}
		$sql3 = "select id,time,chat_type,time_last_member_join,time_last_message,lmt from tbl_chat where id=$chatID";
		$sql4 = "select id,time,user_id,currency,amount,fee,to_user_id,status,remark,lmt from db_transfer_run.tbl_transfer where id=$transferID";

		//更新读取时间
		$sqlUpdateChatMember = "update tbl_chat_member set time_last_read =now() where chat_id=$chatID and member_id=" . qstr($_SESSION['UID']);
		$this->execute($sqlUpdateChatMember);
		$sql5 = "select chat_id,$friendID member_id,time_join,flag_mute_notifications,time_last_read,lmt from tbl_chat_member where chat_id=$chatID and member_id=" . qstr($_SESSION['UID']);
		$sql6 = 'select id,date,time,type,sub_type,currency,amount,remark,lmt from db_system_run.tbl_member_transaction where member_id = ' . qstr($_SESSION['UID']) . ' and type = ' . Transaction::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW . ' and sub_type = ' . Transaction::TRANSACTION_SUB_TYPE_221_TRANSFER_OUT . ' and biz_id = ' . qstr($transferID);

		$rt['chat_content_list'] = $this->getArray($sql2);
		$rt['chat_id_list'] = $this->getArray($sql3);
		$rt['transfer_list'] = $this->getArray($sql4);
		$rt['chat_member_list'] = $this->getArray($sql5);
		$rt['chat_transaction_list'] = $this->getArray($sql6);

		if ($transferID >0){
			//插入 tbl_notification
			$transactionID = $rt['chat_transaction_list'][0]['id'];
			$sql7 = "insert into tbl_notification (time,user_type,user_id,type,content,transaction_id,status,time_sent,lmt) values (now()," . qstr(User::USER_TYPE_1_MEMBER) . ",".qstr($_SESSION['UID'])."," . qstr(self::NOTIFICATION_TYPE_1_TRADE) . ",'',".qstr($transactionID). "," . qstr(self::STATUS_2_SYS_NOTIFICATION_SENT) . ",now(),now())";
			$this->execute($sql7);
		}

		return $rt;
	}

	//添加系统打招呼消息
	private function _addHelloFriendMsg($friendID, $nameRemark) {
		if (!V::over_num($friendID, 0)) throw new TrantorException('FriendID');
		if (!V::required($nameRemark)) throw new TrantorException('NameRemark');

		$chatID = $this->_getChatID($friendID);

		$message1 = "You have added $nameRemark as your friend. Start chatting!";//自己看到的
		$message2 = "I've accepted your friend request. Now let's chat!";//对方看到的

		//插入聊天内容
		$sql4 = "insert into tbl_chat_content(time,chat_id,member_id,type,content,transfer_id) values (now(),$chatID," . qstr($_SESSION['UID']) . "," . qstr(self::MESSAGE_TYPE_99_SYSTEM_MSG) . "," . qstr($message1) . ",0),(now(),$chatID,$friendID," . qstr(self::MESSAGE_TYPE_99_SYSTEM_MSG) . "," . qstr($message2) . ",0)";

		$this->execute($sql4);
		$this->execute("update tbl_chat set time_last_message=now() where id=$chatID");//更新会话时间

		return $this->affected_rows() > 0;
	}

	//获取跟自己相关的所有friends消息
	public function getNewMessages($chatLmt, $friendRequestLmt, $friendsLmt, $memberLmt, $transferLmt, $groupLmt, $chatExtendGroupLmt, $chatLastReadJson) {
		if (!V::date($chatLmt, 'null')) throw new TrantorException('ChatLmt');
		if (!V::date($friendRequestLmt, 'null')) throw new TrantorException('FriendRequestLmt');
		if (!V::date($friendsLmt, 'null')) throw new TrantorException('FriendsLmt');
		if (!V::date($memberLmt, 'null')) throw new TrantorException('MemberLmt');
		if (!V::date($transferLmt, 'null')) throw new TrantorException('TransferLmt');
		if (!V::date($groupLmt, 'null')) throw new TrantorException('GroupLmt');
		if (!V::date($chatExtendGroupLmt, 'null')) throw new TrantorException('ChatExtendGroupLmt');

		$rt = array();
		$sqlChat = "SELECT m1.id,m1.time,m1.chat_type,m1.time_last_member_join,m1.time_last_message,m1.lmt FROM (SELECT chat_id FROM tbl_chat_member WHERE member_id=" . qstr($_SESSION['UID']) . ") m2 LEFT JOIN tbl_chat m1 ON m1.id=m2.chat_id";

		$sqlChatMember = "SELECT m1.chat_id, m2.member_id, m1.time_join, m1.flag_mute_notifications, m1.time_last_read,m1.lmt FROM (SELECT * FROM tbl_chat_member WHERE member_id = " . qstr($_SESSION['UID']) . ") m1 LEFT JOIN (SELECT chat_id ,member_id FROM tbl_chat_member WHERE member_id <> " . qstr($_SESSION['UID']) . ") m2 ON m1.chat_id = m2.chat_id ";

		$sqlFriendRequest = "SELECT f.*,m.name member_name FROM (SELECT id,time,member_id,content,type_add,status,time_complete,lmt FROM tbl_friend_request  WHERE friend_id =" . qstr($_SESSION['UID']) . ") f LEFT JOIN db_system_run.tbl_member m ON f.member_id=m.id";

		$sqlFriends = "SELECT friend_id,name_remark name,time_add,type_add,lmt FROM tbl_friends WHERE member_id=" . qstr($_SESSION['UID']);

		$sqlNotification = "select id,time,type,content,now() time_receive,lmt from tbl_notification where user_type=" . qstr(User::USER_TYPE_1_MEMBER) . " and user_id=" . qstr($_SESSION['UID']) . " and status =" . qstr(self::STATUS_2_SYS_NOTIFICATION_SENT) . " and time_user_receive ='0000-00-00 00:00:00'";

		$sqlMember = "SELECT m.id,m.phone,e.email,p.portrait,m.lmt FROM (SELECT DISTINCT CASE WHEN member_id=" . qstr($_SESSION['UID']) . " THEN friend_id WHEN friend_id=" . qstr($_SESSION['UID']) . " THEN member_id END id FROM tbl_friend_request WHERE (member_id=" . qstr($_SESSION['UID']) . " and status=" . qstr(self::STATUS_2_REQUEST_ACCEPTED) . ") OR friend_id=" . qstr($_SESSION['UID']) . ") f LEFT JOIN db_system_run.tbl_member m ON f.id=m.id LEFT JOIN (SELECT user_id,email,is_email_verified,date_of_birth,register_time FROM db_system_run.tbl_user_extend GROUP BY user_id) e ON m.id=e.user_id LEFT JOIN db_system_run.tbl_user_portrait p ON e.user_id=p.user_id";

		$sqlTransfer = "SELECT id,time,user_id,currency,amount,fee,to_user_id,status,remark,lmt FROM db_transfer_run.tbl_transfer WHERE user_type=" . Transfer::TRANSFER_TYPE_1_MEMBER . ' and to_user_type=' . Transfer::TRANSFER_TYPE_1_MEMBER . ' and (user_id=' . qstr($_SESSION['UID']) . ' or to_user_id=' . qstr($_SESSION['UID']) . ')';

		$sqlGroup = "SELECT g1.id,g1.time,g1.member_id,g1.chat_id,g1.lmt FROM tbl_group g1 LEFT JOIN (SELECT id,chat_id FROM tbl_group WHERE member_id = " . qstr($_SESSION['UID']) . " ) g2 ON g1.id = g2.id ";

		$sqlChatExtendGroup = "SELECT g1.chat_id,g1.group_name,g1.owner_id,g1.flag_verify_invitation,g1.group_notice,g1.lmt FROM tbl_chat_extend_group g1 LEFT JOIN (SELECT chat_id FROM tbl_group WHERE member_id = " . qstr($_SESSION['UID']) . " ) g2 ON g1.chat_id = g2.chat_id ";

		if ($chatLastReadJson) {//根据本地的最后读取时间，获取新消息
			$chatLastReadArr = json_decode($chatLastReadJson);
			$sqlArr1 = '';
			$sqlArr2 = '';
			for ($i = 0, $count = count($chatLastReadArr); $i < $count; $i++) {
				$sqlArr1[] = "SELECT id,chat_id,time,member_id,type,content,transfer_id,lmt FROM tbl_chat_content WHERE (type <> " . qstr(self::MESSAGE_TYPE_99_SYSTEM_MSG) . " OR ( member_id =" . qstr($_SESSION['UID']) . " AND type =" . qstr(self::MESSAGE_TYPE_99_SYSTEM_MSG) . " )) AND chat_id=" . qstr($chatLastReadArr[$i]->chat_id) . " AND lmt >= " . qstr($chatLastReadArr[$i]->time_last_read);
				$sqlArr2[] = " id <>" . qstr($chatLastReadArr[$i]->chat_id);
			}
			$ChatContentUnread = $this->getArray(implode(" union all ", $sqlArr1));

			$sqlChat2 = "SELECT m1.id,m1.chat_id,m1.time,m1.member_id,m1.type,m1.content,m1.transfer_id,m1.lmt FROM (select id from tbl_chat where" . implode(" and ", $sqlArr2) . ") m3 left join (SELECT chat_id,time_last_read FROM tbl_chat_member WHERE member_id=" . qstr($_SESSION['UID']) . ") m2 on m3.id=m2.chat_id LEFT JOIN tbl_chat_content m1 ON m1.chat_id=m2.chat_id WHERE (m1.type<>" . qstr(self::MESSAGE_TYPE_99_SYSTEM_MSG) . " OR (m1.member_id=" . qstr($_SESSION['UID']) . " AND m1.type=" . qstr(self::MESSAGE_TYPE_99_SYSTEM_MSG) . ")) AND m1.lmt>=m2.time_last_read";
			$ChatContentUnread2 = $this->getArray($sqlChat2);//这里不为空，说明本地数据库有未保存下来chat_id，可能情况出现在别的地方登陆
			$rt['chat_content_list'] = array_merge($ChatContentUnread, $ChatContentUnread2);//最终未读数据

		} else {//本地没有保存读取时间
			$sqlChatContent = "SELECT m1.id,m1.chat_id,m1.time,m1.member_id,m1.type,m1.content,m1.transfer_id,m1.lmt FROM (SELECT chat_id,time_last_read FROM tbl_chat_member WHERE member_id=" . qstr($_SESSION['UID']) . ") m2 LEFT JOIN tbl_chat_content m1 ON m1.chat_id=m2.chat_id WHERE (m1.type<>" . qstr(self::MESSAGE_TYPE_99_SYSTEM_MSG) . " OR (m1.member_id=" . qstr($_SESSION['UID']) . " AND m1.type=" . qstr(self::MESSAGE_TYPE_99_SYSTEM_MSG) . ")) AND m1.lmt>=m2.time_last_read";
			$rt['chat_content_list'] = $this->getArray($sqlChatContent);
		}

		//更新读取时间
		$sqlUpdateChatMember = "update (SELECT chat_id FROM tbl_chat_member WHERE member_id=" . qstr($_SESSION['UID']) . ") m2 LEFT JOIN tbl_chat_member m1 ON m1.chat_id=m2.chat_id SET m1.time_last_read=now() WHERE m1.member_id=" . qstr($_SESSION['UID']);
		$this->execute($sqlUpdateChatMember);

		if ($chatLmt) $sqlChat .= " where lmt>" . qstr($chatLmt);
		if ($friendRequestLmt) $sqlFriendRequest .= " where f.lmt>" . qstr($friendRequestLmt);
		if ($friendsLmt) $sqlFriends .= " and lmt>" . qstr($friendsLmt);
		if ($memberLmt) $sqlMember .= " where m.lmt>" . qstr($memberLmt);
		if ($transferLmt) $sqlTransfer .= " and lmt>" . qstr($transferLmt);
		if ($groupLmt) $sqlGroup .= " where g1.lmt>" . qstr($groupLmt);
		if ($chatExtendGroupLmt) $sqlChatExtendGroup .= " where g1.lmt>" . qstr($chatExtendGroupLmt);

		$rt['chat_id_list'] = $this->getArray($sqlChat);
		$rt['chat_member_list'] = $this->getArray($sqlChatMember);
		$rt['friend_request_list'] = $this->getArray($sqlFriendRequest);
		$rt['friends_list'] = $this->getArray($sqlFriends);
		$rt['notification_list'] = $this->getArray($sqlNotification);
		$rt['member_list'] = $this->getArray($sqlMember);
		$rt['transfer_list'] = $this->getArray($sqlTransfer);
		$rt['group_list'] = $this->getArray($sqlGroup);
		$rt['chat_extend_group_list'] = $this->getArray($sqlChatExtendGroup);

		//更新官方消息读取时间
		$sqlUpdateNtfReceive = "update tbl_notification set time_user_receive=now(),status=" . qstr(self::STATUS_3_SYS_NOTIFICATION_USER_RECEIVED) . " where user_type = " . qstr(User::USER_TYPE_1_MEMBER) . " and user_id = " . qstr($_SESSION['UID']) . " and status = " . qstr(self::STATUS_2_SYS_NOTIFICATION_SENT) . "AND time_user_receive ='0000-00-00 00:00:00'";
		$this->execute($sqlUpdateNtfReceive);
		return $rt;
	}

	//获取当前会话的最新消息，与本地数据库lmt作比较
	public function getLastChatMsg($chatID, $lmt, $timeLastRead) {
		if (!V::over_num($chatID, 0)) throw new TrantorException('ChatID');
		if (!V::date($lmt, 'null')) throw new TrantorException('Lmt');
		$rt = array();

		$sqlChat = "SELECT id,time,chat_type,time_last_member_join,time_last_message,lmt FROM tbl_chat WHERE id= $chatID and lmt>" . qstr($lmt);

		if ($timeLastRead) {
			$sqlChatContent = "SELECT id,chat_id,time,member_id,type,content,transfer_id,lmt FROM tbl_chat_content  WHERE (type<>" . qstr(self::MESSAGE_TYPE_99_SYSTEM_MSG) . " OR (member_id=" . qstr($_SESSION['UID']) . " AND type=" . qstr(self::MESSAGE_TYPE_99_SYSTEM_MSG) . ")) and chat_id=$chatID and lmt>=" . qstr($timeLastRead);
		} else {
			$sqlChatContent = "SELECT id,chat_id,time,member_id,type,content,transfer_id,lmt FROM tbl_chat_content  WHERE (type<>" . qstr(self::MESSAGE_TYPE_99_SYSTEM_MSG) . " OR (member_id=" . qstr($_SESSION['UID']) . " AND type=" . qstr(self::MESSAGE_TYPE_99_SYSTEM_MSG) . ")) and chat_id=$chatID and lmt>=(SELECT time_last_read FROM tbl_chat_member WHERE chat_id =$chatID AND member_id =" . qstr($_SESSION['UID']) . ")";
		}

		//更新读取时间
		$sqlUpdateChatMember = "update tbl_chat_member set time_last_read =now() where chat_id=$chatID and member_id=" . qstr($_SESSION['UID']);
		$this->execute($sqlUpdateChatMember);
		$sqlChatMember = "select m1.chat_id,m2.member_id,m1.time_join,m1.flag_mute_notifications,m1.time_last_read,m1.lmt from tbl_chat_member m1 left join tbl_chat_member m2 on m1.chat_id=m2.chat_id where m1.chat_id = $chatID and m1.member_id = " . qstr($_SESSION['UID']) . " and m2.member_id<>" . qstr($_SESSION['UID']);

		$rt['chat_id_list'] = $this->getArray($sqlChat);
		$rt['chat_content_list'] = $this->getArray($sqlChatContent);
		$rt['chat_member_list'] = $this->getArray($sqlChatMember);

		$transferID = "";
		foreach ($rt['chat_content_list'] as $l) {
			$transferID .= $l['transfer_id'] . ',';
		}
		$transferID = substr($transferID, 0, -1);
		$rt['transfer_list'] = array();
		if ($transferID) {
			$sqlTransfer = "select id,time,user_id,currency,amount,fee,to_user_id,status,remark,lmt from db_transfer_run.tbl_transfer where id in ($transferID)";
			$rt['transfer_list'] = $this->getArray($sqlTransfer);
		}

		$sqlMute = 'select t1.flag_mute_notifications from tbl_chat_member t1 where chat_id=' . qstr($chatID) . ' and member_id=' . qstr($_SESSION['UID']);
		$rt['flag_mute_notifications'] = $this->getOne($sqlMute);

		return $rt;
	}

	//因为本地数据库未插入chatId,从服务器下载会话所有的消息和更新联系人列表
	public function getNewChatMsg($memberID, $chatMemberLmt, $friendRequestLmt, $friendsLmt, $memberLmt, $transferLmt) {
		if (!V::over_num($memberID, 0)) throw new TrantorException('MemberID');
		if (!V::date($chatMemberLmt, 'null')) throw new TrantorException('ChatMemberLmt');
		if (!V::date($friendRequestLmt, 'null')) throw new TrantorException('FriendRequestLmt');
		if (!V::date($friendsLmt, 'null')) throw new TrantorException('FriendsLmt');
		if (!V::date($memberLmt, 'null')) throw new TrantorException('MemberLmt');
		if (!V::date($transferLmt, 'null')) throw new TrantorException('TransferLmt');
		$rt = array();
		$chatID = $this->_getChatID($memberID);

		$sqlChat = "SELECT id,time,chat_type,time_last_member_join,time_last_message,lmt FROM tbl_chat WHERE id= $chatID ";

		$sqlChatContent = "SELECT id,chat_id,time,member_id,type,content,transfer_id,lmt FROM tbl_chat_content  WHERE (type<>" . qstr(self::MESSAGE_TYPE_99_SYSTEM_MSG) . " OR (member_id" . qstr($_SESSION['UID']) . " AND type=" . qstr(self::MESSAGE_TYPE_99_SYSTEM_MSG) . ")) and chat_id=$chatID ";

		$sqlChatMember = "SELECT m1.chat_id,m1.member_id,m1.time_join,m1.flag_mute_notifications,m1.time_last_read,m1.lmt FROM tbl_chat_member m1 LEFT JOIN (SELECT chat_id FROM tbl_chat_member WHERE member_id=" . qstr($_SESSION['UID']) . ") m2 ON m1.chat_id=m2.chat_id WHERE m1.member_id<>" . qstr($_SESSION['UID']);

		$sqlFriendRequest = "SELECT f.*,m.name member_name FROM (SELECT id,time,member_id,content,type_add,status,time_complete,lmt FROM tbl_friend_request  WHERE friend_id =" . qstr($_SESSION['UID']) . ") f LEFT JOIN db_system_run.tbl_member m ON f.member_id=m.id";

		$sqlFriends = "SELECT friend_id,name_remark name,time_add,type_add,lmt FROM tbl_friends WHERE member_id=" . qstr($_SESSION['UID']);

		$sqlMember = "SELECT m.id,m.phone,e.email,p.portrait,m.lmt FROM (SELECT DISTINCT CASE WHEN member_id=" . qstr($_SESSION['UID']) . " THEN friend_id WHEN friend_id=" . qstr($_SESSION['UID']) . " THEN member_id END id FROM tbl_friend_request WHERE (member_id=" . qstr($_SESSION['UID']) . " and status=" . qstr(self::STATUS_2_REQUEST_ACCEPTED) . ") OR friend_id=" . qstr($_SESSION['UID']) . ") f LEFT JOIN db_system_run.tbl_member m ON f.id=m.id LEFT JOIN (SELECT user_id,email,is_email_verified,date_of_birth,register_time FROM db_system_run.tbl_user_extend GROUP BY user_id) e ON m.id=e.user_id LEFT JOIN db_system_run.tbl_user_portrait p ON e.user_id=p.user_id";

		$sqlTransfer = "SELECT id,time,user_id,currency,amount,fee,to_user_id,status,remark,lmt FROM db_transfer_run.tbl_transfer WHERE user_type=" . Transfer::TRANSFER_TYPE_1_MEMBER . ' and to_user_type=' . Transfer::TRANSFER_TYPE_1_MEMBER . ' and (user_id=' . qstr($_SESSION['UID']) . ' or to_user_id=' . qstr($_SESSION['UID']) . ')';

		//更新读取时间
		$sqlUpdateChatMember = "update tbl_chat_member set time_last_read =now() where chat_id=$chatID and member_id<>" . qstr($_SESSION['UID']);
		$this->execute($sqlUpdateChatMember);

		if ($chatMemberLmt) $sqlChatMember .= " and lmt>" . qstr($chatMemberLmt);
		if ($friendRequestLmt) $sqlFriendRequest .= " where f.lmt>" . qstr($friendRequestLmt);
		if ($friendsLmt) $sqlFriends .= " and lmt>" . qstr($friendsLmt);
		if ($memberLmt) $sqlMember .= " where m.lmt>" . qstr($memberLmt);
		if ($transferLmt) $sqlTransfer .= " and lmt>" . qstr($transferLmt);

		$rt['chat_id_list'] = $this->getArray($sqlChat);
		$rt['chat_content_list'] = $this->getArray($sqlChatContent);
		$rt['chat_member_list'] = $this->getArray($sqlChatMember);
		$rt['friend_request_list'] = $this->getArray($sqlFriendRequest);
		$rt['friends_list'] = $this->getArray($sqlFriends);
		$rt['member_list'] = $this->getArray($sqlMember);
		$rt['transfer_list'] = $this->getArray($sqlTransfer);
		return $rt;
	}

	//获取最新的联系人列表
	public function getLastFriendsList($friendsLmt, $memberLmt) {
		if (!V::date($friendsLmt, 'null')) throw new TrantorException('FriendsLmt');
		if (!V::date($memberLmt, 'null')) throw new TrantorException('MemberLmt');
		$rt = array();
		$sqlFriends = "SELECT friend_id,name_remark name,time_add,type_add,lmt FROM tbl_friends WHERE member_id=" . qstr($_SESSION['UID']);

		$sqlMember = "SELECT m.id,m.phone,e.email,p.portrait,m.lmt FROM (SELECT DISTINCT CASE WHEN member_id=" . qstr($_SESSION['UID']) . " THEN friend_id WHEN friend_id=" . qstr($_SESSION['UID']) . " THEN member_id END id FROM tbl_friend_request WHERE (member_id=" . qstr($_SESSION['UID']) . " and status=" . qstr(self::STATUS_2_REQUEST_ACCEPTED) . ") OR friend_id=" . qstr($_SESSION['UID']) . ") f LEFT JOIN db_system_run.tbl_member m ON f.id=m.id LEFT JOIN (SELECT user_id,email,is_email_verified,date_of_birth,register_time FROM db_system_run.tbl_user_extend GROUP BY user_id) e ON m.id=e.user_id LEFT JOIN db_system_run.tbl_user_portrait p ON e.user_id=p.user_id";

		if ($friendsLmt) $sqlFriends .= " and lmt>" . qstr($friendsLmt);
		if ($memberLmt) $sqlMember .= " where m.lmt>" . qstr($memberLmt);

		$rt['friends_list'] = $this->getArray($sqlFriends);
		$rt['member_list'] = $this->getArray($sqlMember);
		return $rt;
	}

	//获取最新的好友申请列表
	public function getLastFriendRequestList($friendRequestLmt, $memberLmt) {
		if (!V::date($friendRequestLmt, 'null')) throw new TrantorException('FriendRequestLmt');
		if (!V::date($memberLmt, 'null')) throw new TrantorException('MemberLmt');
		$rt = array();

		$sqlFriendRequest = "SELECT f.*,m.name member_name FROM (SELECT id,time,member_id,content,type_add,status,time_complete,lmt FROM tbl_friend_request  WHERE friend_id =" . qstr($_SESSION['UID']) . ") f LEFT JOIN db_system_run.tbl_member m ON f.member_id=m.id";

		$sqlMember = "SELECT m.id,m.phone,e.email,p.portrait,m.lmt FROM (SELECT DISTINCT CASE WHEN member_id=" . qstr($_SESSION['UID']) . " THEN friend_id WHEN friend_id=" . qstr($_SESSION['UID']) . " THEN member_id END id FROM tbl_friend_request WHERE (member_id=" . qstr($_SESSION['UID']) . " and status=" . qstr(self::STATUS_2_REQUEST_ACCEPTED) . ") OR friend_id=" . qstr($_SESSION['UID']) . ") f LEFT JOIN db_system_run.tbl_member m ON f.id=m.id LEFT JOIN (SELECT user_id,email,is_email_verified,date_of_birth,register_time FROM db_system_run.tbl_user_extend GROUP BY user_id) e ON m.id=e.user_id LEFT JOIN db_system_run.tbl_user_portrait p ON e.user_id=p.user_id";

		if ($friendRequestLmt) $sqlFriendRequest .= " where f.lmt>" . qstr($friendRequestLmt);
		if ($memberLmt) $sqlMember .= " where m.lmt>" . qstr($memberLmt);

		$rt['friend_request_list'] = $this->getArray($sqlFriendRequest);
		$rt['member_list'] = $this->getArray($sqlMember);
		return $rt;
	}

	//根据关键词(名字/手机)搜索结果
	public function getSearchFriendByKeyWord($keyWord) {
		if (!isset($keyWord)) throw new TrantorException('KeyWord');
		$sql = "SELECT m.id,m.name,m.content,m.portrait,t.member_id,t.type_add,t.status,IF(t.id is null,'false','true') is_application FROM (SELECT m1.id,m1.name,m1.phone content,p.portrait FROM db_system_run.tbl_member m1 left join db_system_run.tbl_user_portrait p on m1.id=p.user_id WHERE ( m1.name LIKE '%$keyWord%' OR m1.phone LIKE '%-$keyWord%') AND m1.id <> " . qstr($_SESSION['UID']) . " LIMIT 10) m LEFT JOIN (SELECT CASE WHEN t1.member_id = " . qstr($_SESSION['UID']) . " THEN t1.friend_id WHEN t1.friend_id = " . qstr($_SESSION['UID']) . " THEN t1.member_id END id, t1.member_id, t1.type_add, t1.status FROM tbl_friend_request t1 WHERE ( t1.friend_id = " . qstr($_SESSION['UID']) . " OR t1.member_id = " . qstr($_SESSION['UID']) . " ) AND t1.lmt = (SELECT MAX(lmt) FROM tbl_friend_request t2 WHERE ( t2.friend_id = " . qstr($_SESSION['UID']) . " AND t2.member_id = t1.member_id ) OR ( t2.friend_id = t1.friend_id AND t2.member_id = " . qstr($_SESSION['UID']) . " ))) t ON m.id = t.id";

		$rt = array();
		$rt['search_friends_list'] = $this->getArray($sql);
		return $rt;
	}

	//根据手机上的联系人号码匹配会员，返回会员信息
	public function getMayKnowFriends($phoneStr) {
		if (!V::required($phoneStr)) throw new TrantorException('PhoneStr');

		$phones = explode(",", $phoneStr);
		$uid = $_SESSION['UID'];
		$rejected = self::STATUS_4_REQUEST_REJECTED;

		$sql = "SELECT tm.id, tm.phone, tm.name FROM db_system_run.tbl_member tm LEFT JOIN (SELECT CASE WHEN member_id='$uid' THEN friend_id WHEN friend_id='$uid' THEN member_id END id FROM tbl_friend_request WHERE (member_id='$uid' OR friend_id='$uid') AND status!=$rejected) t2 ON t2.id = tm.id WHERE t2.id IS NULL and tm.id<>$uid";
		$sqlPartArr = '';
		for ($i = 0, $phonesLen = count($phones); $i < $phonesLen; $i++) {
			$sqlPartArr[] = "tm.phone LIKE " . qstr("%$phones[$i]");
		}
		$sqlPart = ' AND (' . implode(' OR ', $sqlPartArr) . ')';
		$mayKnowArr = $this->getArray($sql . $sqlPart);

		return ['may_know_friends_list' => $mayKnowArr];
	}

	public function getMemberChatInfo($chatID, $memberID) {
		if (!V::over_num($chatID, 0)) throw new TrantorException('ChatID');
		if (!V::over_num($memberID, 0)) throw new TrantorException('MemberID');
		$sql = 'select flag_mute_notifications from tbl_chat_member t1 where chat_id=' . qstr($chatID) . ' and member_id=' . qstr($_SESSION['UID']);
		$rs = array();
		$rs['flag_mute_notifications'] = $this->getOne($sql);
		$sql2 = 'select t1.name,t2.portrait from db_system_run.tbl_member t1 left join db_system_run.tbl_user_portrait t2 on(t2.user_type = '.User::USER_TYPE_1_MEMBER.' and t2.user_id = t1.id) where id = ' . qstr($memberID);
		$rs2 = $this->getLine($sql2);
		$rs['name'] = $rs2['name'];
		$rs['portrait'] = $rs2['portrait'];
		return $rs;
	}

	public function getChatGroupInfo($chatID) {
		if (!V::over_num($chatID, 0)) throw new TrantorException('ChatID');
		$sql1 = 'select flag_mute_notifications from tbl_chat_member t1 where chat_id=' . qstr($chatID) . ' and member_id=' . qstr($_SESSION['UID']);
		$rs = array();
		$rs['flag_mute_notifications'] = $this->getOne($sql1);
		$sql2 = 'select member_id from tbl_group where chat_id = ' . qstr($chatID);
		$memberIDArr = $this->getArray($sql2);
		$id = "";
		foreach ($memberIDArr as $v) {
			$id .= $v['member_id'] . ',';
		}
		if (!$id) throw new TrantorException('MemberID');
		$id = substr($id, 0, -1);
		$sql3 = 'select t1.id member_id,t1.name,t2.portrait from db_system_run.tbl_member t1 left join db_system_run.tbl_user_portrait t2 on(t2.user_type = '.User::USER_TYPE_1_MEMBER.' and t2.user_id = t1.id) where id in (' . $id . ')';
		$rs['member_info_list'] = $this->getArray($sql3);
		$sql4 = 'select owner_id from tbl_chat_extend_group where chat_id = ' . qstr($chatID);
		$rs['is_owner'] = $this->getOne($sql4) == $_SESSION['UID'];
		return $rs;
	}

	public function setChatMute($chatID, $flagMute) {
		if (!V::over_num($chatID, 0)) throw new TrantorException('ChatID');
		if (!in_array($flagMute, [self::FLAG_1_MUTE_NOTIFICATIONS_YES, self::FLAG_2_MUTE_NOTIFICATIONS_NO])) throw new TrantorException('Flag Mute Notifications');
		$sql = 'update tbl_chat_member set flag_mute_notifications=' . qstr($flagMute) . ' where chat_id=' . qstr($chatID) . ' and member_id=' . qstr($_SESSION['UID']);
		$this->execute($sql);
	}

	//获取会员简介信息
	public function getFriendProfileInfo($memberID) {
		if (!V::over_num($memberID, 0)) throw new TrantorException('MemberID');
		$uid = qstr($_SESSION['UID']);
		$rejected = qstr(self::STATUS_4_REQUEST_REJECTED);

		$sql = "select m.id,m.name,m.country_code,m.phone,p.portrait,r1.member_id,r1.status ,IF(r1.id is null,'false','true') is_application,f1.name_remark,f1.type_add from db_system_run.tbl_member m left join db_system_run.tbl_user_portrait p on m.id = p.user_id left join (select  CASE WHEN r.member_id=$uid THEN r.friend_id WHEN r.friend_id=$uid THEN r.member_id END id , r.member_id, r.status from tbl_friend_request r where (( r.member_id = $memberID and r.friend_id = $uid ) or ( r.friend_id = $memberID and r.member_id = $uid ))and r.status<>$rejected ) r1 on m.id=r1.id left join (SELECT f.name_remark,f.type_add,f.friend_id FROM tbl_friends f WHERE f.member_id=$uid AND f.friend_id=$memberID ) f1 on m.id=f1.friend_id WHERE m.id = $memberID";

		return $this->getLine($sql);
	}

	//获取好友间转账信息列表
	public function getFriendTransferHistory($memberID, $page) {
		if (!V::over_num($memberID, 0)) throw new TrantorException('MemberID');
		$sql = "SELECT t1.id,t1.user_id, t1.time, t1.amount, t1.currency, t1.status,t2.phone source_phone,t3.phone target_phone FROM db_transfer_run.tbl_transfer t1 LEFT JOIN db_system_run.tbl_member t2 ON(t2.id = t1.user_id) LEFT JOIN db_system_run.tbl_member t3 ON(t3.id = t1.to_user_id) WHERE (t1.user_type = " . Transfer::TRANSFER_TYPE_1_MEMBER . ' AND t1.user_id = ' . $_SESSION['UID'] . ' AND t1.to_user_type = ' . Transfer::TRANSFER_TYPE_1_MEMBER . ' AND t1.to_user_id = ' . $memberID . ') or (t1.user_type = ' . Transfer::TRANSFER_TYPE_1_MEMBER . ' AND t1.user_id = ' . $memberID . ' AND t1.to_user_type = ' . Transfer::TRANSFER_TYPE_1_MEMBER . ' AND t1.to_user_id = ' . $_SESSION['UID'] . ')' . ' ORDER BY TIME DESC';
		return $this->getPageArray($sql, $page);
	}

	//备注昵称
	public function remarkFriendName($memberID, $content) {
		if (!V::over_num($memberID, 0)) throw new TrantorException('MemberID');
		if (!V::required($content)) throw new TrantorException('Content');

		if ($content) {
			$this->execute("UPDATE tbl_friends SET name_remark =" . qstr($content) . " WHERE friend_id=$memberID AND member_id=" . qstr($_SESSION['UID']));
		}
		return $this->affected_rows() > 0;
	}

	//更新消息最后阅读
	public function updateChatLastRead($chatID) {
		if (!V::over_num($chatID, 0)) throw new TrantorException('ChatID');

		//更新读取时间
		$this->execute("update tbl_chat_member set time_last_read =now() where chat_id=$chatID and member_id=" . qstr($_SESSION['UID']));
		return $this->affected_rows() > 0;
	}

	public function getTransferRemark($transferID) {
		if (!V::over_num($transferID, 0)) throw new TrantorException('TransferID');
		$sql = 'select t1.remark,t2.name from db_transfer_run.tbl_transfer t1 left join db_system_run.tbl_member t2 on(t1.to_user_id=t2.id) where t1.id=' . qstr($transferID);
		$rs = $this->getLine($sql);
		$remark = $rs['remark'];
		if (!$rs['remark'] && $rs['name']) {
			$remark = "Transfer to " . $rs['name'];
		}
		return $remark;
	}

	public function buildGroupChat($selectedMemberJson) {
		if (!V::required($selectedMemberJson)) throw new TrantorException('SelectedMemberJson');

		$members = json_decode($selectedMemberJson);
		//判断是否已存该群组
		$membersLen = count($members);
		if ($membersLen >= 1) {
			//创建会话
			$sql1 = "insert into tbl_chat(time,chat_type,time_last_member_join,time_last_message) values (now()," . qstr(self::CHAT_TYPE_2_GROUP) . ",now(),now())";
			$this->execute($sql1);
			$chatID = $this->insert_id();

			if ($chatID) {
				$sql2 = "insert into tbl_chat_member(chat_id,member_id,time_join,flag_mute_notifications) values";
				$sql3 = "insert into tbl_group (time,member_id,chat_id) values";
				$groupName = '';
				for ($i = 0; $i < $membersLen; $i++) {
					//增加会话成员
					$sql2 .= " ($chatID, " . qstr($members[$i]->memberId) . ",now()," . qstr(self::FLAG_2_MUTE_NOTIFICATIONS_NO) . "),";
					$sql3 .= " (now(), " . qstr($members[$i]->memberId) . ",$chatID),";
					$groupName .= $members[$i]->name;
					if ($i != $membersLen -1){
						$groupName .= ",";
					}
				}
				$result1 = $this->execute(substr($sql3, 0, strlen($sql3) - 1));
				$result2 = $this->execute(substr($sql2, 0, strlen($sql2) - 1));

				$result3 = $this->execute("insert into tbl_chat_extend_group (chat_id,group_name,owner_id,flag_verify_invitation,group_notice) values ($chatID," . qstr($groupName) . "," . qstr($_SESSION['UID']) . "," . qstr(self::FLAG_VERIFY_INVITATION_2_NO) . ",'')");

				if ($result1 && $result2 && $result3) {
					$this->_addHelloGroupMsg($chatID);
					return array('chat_id' => $chatID, 'group_name' => $groupName);
				}
			}
		}
		return 0;
	}

	//增加创建群组后打招呼的系统消息
	private function _addHelloGroupMsg($chatID) {
		if (!V::over_num($chatID, 0)) throw new TrantorException('ChatID');

		$message1 = "You're group manager, and now you can start group chat";//$creatorID
		$message2 = "Welcome to the group, and now you can start group chat";//$relateIDs，exclude_id
		$message3 = "Welcome XXX to join the group";//$relateIDs

		$members = $this->getArray("select member_id from tbl_chat_member where chat_id=$chatID");

		$sql2 = "INSERT INTO tbl_chat_content (time,chat_id,member_id,type,content) values";
		for ($i = 0, $membersLen = count($members); $i < $membersLen; $i++) {
			$sql2 .= " (now(),$chatID," . qstr($members[$i]['member_id']) . "," . qstr(self::MESSAGE_TYPE_99_SYSTEM_MSG) . "," . qstr(($members[$i]['member_id'] == $_SESSION['UID']) ? $message1 : $message2) . "),";
		}
		$result = $this->execute(substr($sql2, 0, strlen($sql2) - 1));
		if ($result) {
			$this->execute("update tbl_chat set time_last_message=now() where id=$chatID");//更新会话时间
		}
		return $this->affected_rows() > 0;
	}

	//第一次进入时作检查更新会话的消息
	public function checkUpdateChatMsg($friendRequestLmt, $friendsLmt, $memberLmt, $transferLmt, $groupLmt, $chatExtendGroupLmt, $memberID = 0, $chatID = 0) {
		if (!is_numeric($memberID)) throw new TrantorException('MemberID');
		if (!is_numeric($chatID)) throw new TrantorException('ChatID');
		if (!V::date($friendRequestLmt, 'null')) throw new TrantorException('FriendRequestLmt');
		if (!V::date($friendsLmt, 'null')) throw new TrantorException('FriendsLmt');
		if (!V::date($memberLmt, 'null')) throw new TrantorException('MemberLmt');
		if (!V::date($transferLmt, 'null')) throw new TrantorException('TransferLmt');
		if (!V::date($groupLmt, 'null')) throw new TrantorException('GroupLmt');
		if (!V::date($chatExtendGroupLmt, 'null')) throw new TrantorException('ChatExtendGroupLmt');

		if ($memberID) {
			$chatID = $this->_getChatID($memberID);
		}
		if ($chatID) {
			$chatType = $this->_getChatType($chatID);
			if ($chatType == self::CHAT_TYPE_1_FRIEND) {

			} else if ($chatType == self::CHAT_TYPE_2_GROUP) {

			}
		}
	}

	//根据chatID,判断会话类型
	private function _getChatType($chatID) {
		if (!V::over_num($chatID, 0)) throw new TrantorException('ChatID');

		return $this->execute("select chat_type from tbl_chat where id=$chatID");
	}
}