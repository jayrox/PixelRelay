<?php
	/**********************************
	 * PixelRelay
	 * This is the web server side that handles uploads from the Android app PixelRelay
	 * 
	 * PixelRelay is an Android app that allows complete control of automatic image uploads
	 * to any supported web service.
	 * 
	 * This script is only for an example use case.
	 * It is simple by design and it's only purpose is to handle uploads.
	 *
	 * The idea is that you, the user, can control every aspect of the web service.
	 * You are free to use any web service framework, language, platform that you want.
	 *
	 * The following params are sent from the app using POST:
	 *
	 *  version			-- PixelRelay version string
	 *  uploaded_file		-- The binary file uploaded
	 *  user_email			-- The email address associated to the uploader's Google Play account
	 *  user_private_key		-- The private key used to authenticate the upload
	 *  file_host			-- The Image Host preference from the Android app
	 *  file_album			-- The Photo Album preference
	 *  file_name			-- The name of the file within Android
	 *  file_mime			-- The mime type of the file
	 *
	*/

	// Allow albums to be created if they do not exist
	define('ALLOW_ALBUM_CREATION', true);

	// Root upload location
	define('IMAGE_ROOT', '/var/www/example.com/uploads/');

	/* Default album name
	 * Used if:
	 * 1) album name is empty
	 * 2) Album doesn't exist and ALLOW_ALBUM_CREATION is false
	 */
	define('DEFAULT_ALBUM', 'private');

	// Private key to allow uploads
	define('PRIVATE_KEY', 'asdfzxcvqwertyhb');

	// Rename the file after upload
	define('RENAME_UPLOAD', true);

	// If you do not want logging, set to false
	define('LOG_FILE', './logs/test.txt');

	$content = $_POST;
	unset($_POST);

	Header("Content-type", "application/json");
	ob_start();

	logd(date("y-m-d H:i:s"));

	if(!isset($content['user_private_key']) || $content['user_private_key'] !== PRIVATE_KEY)
	{
		$error_code = 'HTTP/1.1 401 Unauthorized';
		header($error_code, TRUE, 401);
		logd("error: {$error_code}");
		logd(print_r($content, true));
		$response = array(
			"error" => 401,
			"code"	=> $error_code,
			"name"	=> ""
			);
		logd("response: ".print_r($response, true));
		echo json_encode($response);
		exit();
	}

	if(! RENAME_UPLOAD)
	{
		$fileparts = explode('/', $content['file_name']);
		$filename = end($fileparts);
	}

	if(RENAME_UPLOAD || trim($filename) == "")
	{
		list($type, $ext) = explode('/', $content['file_mime']);
		$email_md5 = substr(md5($content['user_email']), 0, 10);
		$filename = time()."_".$email_md5."_".generateRandomString(10).".".$ext;
	}

	$album = (! isset($content['file_album']) || trim($content['file_album']) == "") ? DEFAULT_ALBUM : $content['file_album'];

	if(false === strpos($album, '..') and  false === strpos($album, '../') and false === strpos($album, '/'))
	{
		$dir_dest = IMAGE_ROOT.$album;
	}else{
		$dir_dest = IMAGE_ROOT.DEFAULT_ALBUM;
	}

	$filepath = "{$dir_dest}/{$filename}";

	logd("real_path: ".realpath($dir_dest));

	if(! file_exists($dir_dest) && ALLOW_ALBUM_CREATION)
	{
		mkdir($dir_dest, 01766);
		mkdir("{$dir_dest}/thumbs", 01766);
		logd("mkdir: {$dir_dest}");
	}
	elseif(! file_exists($dir_dest) && ! ALLOW_ALBUM_CREATION)
	{
		logd("dest: {$dir_dest}");
		logd("exists: ".(file_exists($dir_dest) ? 'true' : 'false'));
		$dir_dest = IMAGE_ROOT.DEFAULT_ALBUM;
	}
	else
	{
		logd("mkdir: false");
		logd("dest: {$dir_dest}");
		logd("exists: ".(file_exists($dir_dest) ? 'true' : 'false'));
		logd("allow_app_album_creation: ".(ALLOW_ALBUM_CREATION ? 'true' : 'false'));
	}

	$allowedExts = array("gif", "jpeg", "jpg", "png");
	$allowedMime = array(
		"image/gif",
		"image/jpeg",
		"image/jpg",
		"image/pjpeg",
		"image/x-png",
		"image/png",
		"application/octet-stream"
		);
	$mime_type = $content['file_mime'];
	$file_parts = explode(".", $_FILES['uploaded_file']["name"]);
	$extension = end($file_parts);
	if (   in_array($mime_type, $allowedMime)
		&& in_array($extension, $allowedExts))
	{
		if ($_FILES['uploaded_file']["error"] > 0)
		{
			logd("Return Code: ".$_FILES['uploaded_file']["error"]);
		}else{
			if (file_exists($filepath))
			{
				logd("already_exists: true");
				logd("final_dest: {$filepath}");
			}
			logd("temp_filesize: ".filesize($_FILES['uploaded_file']["tmp_name"]));
			logd("is_uploaded: ".is_uploaded_file($_FILES['uploaded_file']["tmp_name"]));

			$move = move_uploaded_file($_FILES['uploaded_file']["tmp_name"],
									   "{$dir_dest}/{$filename}");

			logd("final_dest: {$dir_dest}/{$filename}");
			logd("move: ".($move ? 'true' : 'false'));
		}
		logd("valid: true");
	}else{
		logd("valid: false");
	}
	$image_path = "{$dir_dest}/{$filename}";

	logd("file_array: ".print_r($_FILES, true));
	logd("trimname: {$filename}");
	logd("image_path: {$image_path}");;
	logd("dest: {$dir_dest}");

	logd(print_r($content, true)."\n______________________________________________________");

	header("HTTP/1.1 200 OK", True, 200);
	$response = array(
		"error" => 0,
		"code"	=> "success",
		"name"	=> $filename
		);

	ob_end_clean();

	logd("json response: ".json_encode($response));
	echo json_encode($response);
	exit();

	// HELPERS

	function generateRandomString($length = 10) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, strlen($characters) - 1)];
		}
		return $randomString;
	}

	function logd($str)
	{
		if(!defined("LOG_FILE") || !LOG_FILE || trim(LOG_FILE) == "") return false;
		file_put_contents(LOG_FILE, "{$str}\n", FILE_APPEND | LOCK_EX);
	}
