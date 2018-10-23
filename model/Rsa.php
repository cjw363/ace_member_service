<?php

class Rsa
{
	private static $PRIVATE_KEY = '-----BEGIN PRIVATE KEY-----
MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAPTGG3yRb42sT90H
vl5ubtDMr7oDxTHzmmeyoULufYntcsJVOOVw8bCRmJaK+f6z/hJENn5uwIiPMhPC
VkAgWscuV6Z6r4gxJmBzGsO13s4SkGmD9MBy+hGcEm0I6w7C87C/Vg8QwdX8yNHh
iqGBbBgHKJiP6pqDtP6yPaiVktslAgMBAAECgYB0bn/Io02S8HIUy4gsVw9zVsoI
C58TgbLivL+knNkucLpz4iHsUetFeBxDD9yW4XtrqPLa9Ue0LZk+eOSaIEnNIv4j
o1IinVhER3ukK/uJzwr51p4OR7vM0lOjdAL+9UlE4JvrAqrKeI9su+RKoF4xqWM5
MR2+ry9dSqY9vi/rCQJBAP1WhAhG2/s7s1CJ+ekce6Y1WpkE6KbYIH36UnELQO7u
nIyhf0lwtITn/9Re3C1BWTTLpq6d1SVLe/t7iNiJuzMCQQD3WI3Uwd8lQW4/8tvF
ThMz5nDoA6jHAfW7TORp3fkWKD3Aa8Yaq4kCmH7JpMtqqTnXKwLkenr+WR2XcRGP
oFBHAkAigglkEzrdwukO/GxlO3MAVd4sX8XNDD2Iy3M1YMIMicYbRdhPyaFdRTjM
1csKAw/CqEbhHDCvbtPJkq82R7SPAkEA2j1fP0ckPboCrhf5g5iE5vk/y+dWpuj6
yZ1puGNroPo2qi4tqGCLzieBTyfBd8YCy/AeaDwNg5hbvMC+Du0ThwJBALFIjVD9
Su55cJudJ7GMGM314XFaxt/QjSAtQZwyTvejAOGS6SYwFwY1UV1fE/PwvOEv/SxE
f95IIWiaGrCxkUY=
-----END PRIVATE KEY-----';
	/**
	 *返回对应的私钥
	 */
	private static function getPrivateKey(){

		$privKey = self::$PRIVATE_KEY;

		return openssl_pkey_get_private($privKey);
	}

	/**
	 * 私钥加密
	 */
	public static function privEncrypt($data)
	{
		if(!is_string($data)){
			return null;
		}
		return openssl_private_encrypt($data,$encrypted,self::getPrivateKey())? base64_encode($encrypted) : null;
	}


	/**
	 * 私钥解密
	 */
	public static function privDecrypt($encrypted)
	{
		if(!is_string($encrypted)){
			return null;
		}
		return (openssl_private_decrypt(base64_decode($encrypted), $decrypted, self::getPrivateKey()))? $decrypted : null;
	}
}

?>