<?php
/*====================================================================
 * 自定义error错误
 *--------------------------------------------------------------------
 * 李宏东
 * 2014-5-5
 *==================================================================*/
// error_reporting(E_ALL);
//error_reporting(0);
// ini_set('display_errors' ,'On' ); 
// ini_set('error_log' ,'./php_error.log' );
// ini_set('log_errors' ,'On' );
set_error_handler('customError');
register_shutdown_function('fatalErrorHandler');
/**
 * 错误重定向
 * @param $errno  错误码
 * @param $errstr 错误描述
 * @param $errfile  发生错误的文件
 * @param $errline  发生错误的文件行数
 */
function customError($errno, $errstr, $errfile, $errline){
	$arr = [
			'['.date('Y-m-d h-i-s').']',
			serverIP(),
			'|',
			$errstr,
			$errfile,
			'line:'.$errline,
	      ];
	$errorString = '';
	$content = implode(' ',$arr)."\r\n";
	switch($errno){
		case 1: $errorString = 'E_ERROR：';
			sendErrorMail($content);
			break;
		case 2: $errorString = 'E_WARNING：';
//			sendErrorMail($content);
			break;
		case 4: $errorString = 'E_PARSE：';
			sendErrorMail($content, '语法错误');
			break;
		case 8: $errorString = 'E_NOTICE：'; break;
		case 16: $errorString = 'E_CORE_ERROR：'; break;
		case 32: $errorString = 'E_CORE_WARNING：'; break;
		case 64: $errorString = 'E_COMPILE_ERROR：'; break;
		case 128: $errorString = 'E_COMPILE_WARNING：'; break;
		case 256: $errorString = 'E_USER_ERROR：'; break;
		case 512: $errorString = 'E_USER_WARNING：'; break;
		case 1024: $errorString = 'E_USER_NOTICE：'; break;
		case 2048: $errorString = 'E_STRICT：'; break;
		case 4096: $errorString = 'E_RECOVERABLE_ERROR：'; break;
		case 8092: $errorString = 'E_DEPRECATED：'; break;
		case 16384: $errorString = 'E_USER_DEPRECATED：'; break;
		default: $errorString = "Unknown error type：";
	}

//	$errorString .= strip_tags($errstr).' in '.$errfile.' on line '.$errline.'<br/>';
//	error_log($errorString);
}

function fatalErrorHandler(){
	$e = error_get_last();
	switch($e['type']){
		case E_ERROR:
		case E_PARSE:
		case E_CORE_ERROR:
		case E_COMPILE_ERROR:
		case E_USER_ERROR:
		customError($e['type'],$e['message'],$e['file'],$e['line']);
			break;
	}
}

function sendErrorMail($content = '', $title = '致命错误'){
//	sendMail('tony@zhugefang.com', $content, $title);
//	sendMail('yong@zhugefang.com', $content, $title);
//	sendMail('may@zhugefang.com', $content, $title);
//	sendMail('david@zhugefang.com', $content, $title);
}