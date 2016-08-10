<?php
function format_date(){
	return date('Y-m-d H:i:s',time());
}

function mb_unserialize($serial_str) {
	$out = preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $serial_str );
	return unserialize($out);
}

function verify_check($code, $id = ''){
	$verify = new \Think\Verify();
	return $verify->check($code, $id);
}