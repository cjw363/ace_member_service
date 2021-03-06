<?php

class Constant {

	const EPSILON = 0.0001;

	const YES = 1;
	const NO = 2;

	const BEGINNING = 1;
	const DEPOSIT = 2;
	const WITHDRAW = 3;

	public static $waitLock = 10; // 等待锁的次数，如果获取不到，则放弃，否则会影响守护进程的执行

	const MSG_LEVEL_1_NOTIFICATION = 1;
	const MSG_LEVEL_2_WARNING = 2;
	const MSG_LEVEL_3_ERROR = 3;

	const STATUS_1_ACTIVE = 1;
	const STATUS_3_INACTIVE = 3;
	const STATUS_4_TO_SET = 4;
	const STATUS_6_TO_VERIFY_CERTIFICATE = 6;

	const MAIN_DB_RUN = "system_run"; //定义主DB

	const ACTIVITY_15_RESET_PASSWORD = 15;
	const ACTIVITY_16_UPDATE_PASSWORD = 16;

	const ACTIVITY_46_NEW_MEMBER = 46;
	const ACTIVITY_47_MEMBER_LIST = 47;
	const ACTIVITY_49_SETTING = 49;
	const ACTIVITY_50_PASSWORD = 50;
	const ACTIVITY_51_LOGOUT = 51;

	const FUNCTION_100_REGISTER = 100;
	const FUNCTION_102_MEMBER_LOGIN_ANDROID = 102;
	const FUNCTION_111_MEMBER_DEPOSIT = 111;
	const FUNCTION_112_MEMBER_WITHDRAW = 112;
	const FUNCTION_113_MEMBER_TO_MEMBER = 113;
	const FUNCTION_114_MEMBER_TO_NON_MEMBER = 114;
	const FUNCTION_115_MEMBER_TO_PARTNER = 115;
	const FUNCTION_116_MEMBER_TO_MERCHANT = 116;
	const FUNCTION_118_RECEIVE_TO_ACCOUNT = 118;
	const FUNCTION_119_MEMBER_EXCHANGE = 119;
	const FUNCTION_121_MEMBER_TOP_UP_SHOW_PIN_CODE = 121;
	const FUNCTION_122_MEMBER_TOP_UP_SEND_SMS = 122;
	const FUNCTION_131_MEMBER_PAY_EDC_BILL = 131;
	const FUNCTION_132_MEMBER_PAY_WSA_BILL = 132;
	const FUNCTION_133_MEMBER_PAY_ONLINE = 133;
	const FUNCTION_135_PAY_BILL_TO_PARTNER=135;
	const FUNCTION_141_MEMBER_BUY_LOTTO=141;

	const STATUS_3_COMPLETED = 3;
	const STATUS_1_PENDING = 1;

	const TYPE_2_DEPOSIT = 2;
	const TYPE_3_WITHDRAW = 3;

	const VERIFIED_TYPE_1_PHONE = 1;
	const VERIFIED_TYPE_2_PHONE_CONTACT = 2;
	const VERIFIED_TYPE_3_ID = 3;
	const VERIFIED_TYPE_4_FINGERPRINT = 4;

	const PARTNER_8888_SAMRITHISAK=8888;

	const SEX_1_MALE=1;
	const SEX_2_FEMALE=2;

	const NATIONALITY_855_CAMBODIANS=855;
	const NATIONALITY_84_VIETNAMESE=84;
	const NATIONALITY_86_CHINESE=86;
	const NATIONALITY_66_THAIS=66;
	const NATIONALITY_60_MALAYSIANS=60;
	const NATIONALITY_62_INDONESIANS=62;
	const NATIONALITY_33_FRENCH_CITIZENS=33;

	const COUPON_STATUS_0_DEFAULT=0;
	const COUPON_STATUS_1_UNUSED=1;
	const COUPON_STATUS_2_USED=2;
	const COUPON_STATUS_3_EXPIRE=3;
	const COUPON_STATUS_4_UNUSED_EXPIRE=4;

}