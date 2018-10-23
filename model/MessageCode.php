<?php
/**
 * 在这里定义错误编号，编号对应的内容在前端定义，考虑到多语言环境，需要使用strings.xml文件来区分不同的语言
 * 系统级消息，数字要求3位
 * 应用级消息，数字要求4位
 **/

class MessageCode {

	const ERR_0_NO_ERROR = 0;
	const ERR_1_ERROR = 1; //表示出错，不指明是哪种错误

	const ERR_100_SYSTEM_ERROR = 100;
	const ERR_101_INVALID = 101;
	const ERR_102_SESSION_TIMEOUT = 102;
	const ERR_103_SUBMIT_REPEAT = 103;
	const ERR_104_NOT_SUPPORTED_APK_VERSION = 104;

	const ERR_200_NO_PRIVILEGE = 200;

	const ERR_500_NOT_ALLOW_LOGIN = 500;

	const ERR_505_FUNCTION_NOT_RUNNING = 505; //功能未开放

	//应用级
	const ERR_1000_INACTIVE_ACCOUNT = 1000;
	const ERR_1001_LOGGED_ON_OTHER_DEVICE = 1001;
	const ERR_1002_LOGIN_FAIL = 1002;
	const ERR_1004_GESTURE_EXPIRED = 1004;
	const ERR_1006_PASSWORD_INVALID = 1006;
	const ERR_1007_NEW_PASSWORD_INVALID = 1007;
	const ERR_1008_NEW_PASSWORD_TOO_SIMPLE = 1008;

	//其他
	const ERR_1704_NOT_ALLOW_MEMBER_TO_WITHDRAW = 1704;
	const ERR_1705_NOT_ALLOW_MEMBER_TO_DEPOSIT = 1705;
	const ERR_1707_SECURITY_CODE_INVALID = 1707;
	const ERR_1708_CANNOT_FIND_THE_MEMBER = 1708;
	const ERR_1709_NOT_ENOUGH_BALANCE = 1709; //余额不足
	const ERR_1710_BANK_ACCOUNT_EXISTS = 1710; //用户银行已存在
	const ERR_1711_SMS_OVER_DAILY_LIMIT = 1711; //短信验证超过每日限制

	const ERR_1714_EXCEED_SINGLE_TRANSFER_CASH_CAP = 1714; //超出最大现金转账限制
	const ERR_1715_EXCEED_TRANSFER_CASH_CAP_PER_DAY = 1715; //超出每天最大现金转账限制
	const ERR_1721_ONLY_TRANSFER_TO_MEMBER = 1721; //控制只能转账给Member (Member Transfer To Member)
	const ERR_1722_FORBIDDEN_TO_TRANSFER_TO_SELF = 1722; //不能转账给自己
	const ERR_1723_OVER_MAX_BALANCE_LIMIT = 1723; //超过了最大余额限制
	const ERR_1724_OVER_MAX_AMOUNT = 1724;//错过配置最大值
	const ERR_1725_OVER_BILL_ID_LENGTH = 1725;
	const ERR_1726_CANNOT_FIND_THE_PARTNER = 1726;

	const ERR_1750_INVALID_ACCEPT_CODE = 1750;

	const ERR_1822_OVER_ONE_TIME_LIMIT = 1822;//超出单次最大限制
	const ERR_1823_OVER_ONE_DAY_LIMIT = 1823;//超出每天最大限制
	const ERR_1824_OVER_MAX_BALANCE = 1824;//超出配置额度
	const ERR_1825_OVER_LOAN_CONFIG_LIMIT = 1825;
	const ERR_1826_OVER_LOAN_LIMIT = 1826;
	const ERR_1827_OVER_MEMBER_MAX_LIMIT = 1827;
	const ERR_1828_INPUT_AMOUNT_TIMES = 1828;

	const ERR_1830_OVER_MEMBER_COUNT = 1830; //超过会员数量限制
	const ERR_1831_OVER_CREDIT = 1831; //超过额度限制
	const ERR_1832_SERVICE_CHARGE_NOT_MATCH = 1832; //服务费用不匹配
	const ERR_1833_NEED_GREATER_THAN_SERVICE_CHARGE = 1833; //需要大于服务费用
	const ERR_1834_CAN_NOT_LESS_THAN_MIN_LOAN = 1834; //不能小于最低贷款金额
	const ERR_1836_CAN_NOT_GREATER_THAN_MAX_LOAN = 1836; //不能大于最大贷款金额
	const ERR_1837_CONFIG_EXPIRED = 1837; //配置异常
	const ERR_1838_OVER_RETURN_LOAN = 1838; //配置异常

	const ERR_1910_INVALID_NUMBER = 1910; //Invalid Number
	const ERR_1911_TOO_MANY_STARS = 1911; //Invalid Number (Too Many Stars)
	const ERR_1912_DETAILS_MISMATCH = 1912; //Details Mismatch
	const ERR_1913_INVALID_TYPE = 1913; //Invalid Type/Sub Type
	const ERR_1914_WRONG_TIMES = 1914; //Wrong Times
	const ERR_1915_OVER_SPECIAL_MAX_TIMES = 1915; //Over Special Max Times
	const ERR_1916_BETTING_AMOUNT_MISMATCH = 1916; //Betting Amount Mismatch
	const ERR_1917_WRONG_DETAIL_NUMBER = 1917;  //Wrong Detail Number
	const ERR_1918_OVER_MULTIPLE_MAX_NUMBER = 1918;  //Over Multiple Max Numbers

}