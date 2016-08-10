<?php
include('Net/SFTP.php');
//stream_resolve_include_path('Crypt/RC4.php');
echo 'start';
$sftp = new Net_SFTP('192.168.212.69');
if (!$sftp->login('root', 'wh70ll')) {
    exit('Login Failed');
}

echo $sftp->pwd() . "\r\n";
ob_flush();
flush();
//sleep(1);
//$sftp->get('/home/Backup/hey/to_ZTE/20160214/wd.tar.gz', 'wd.tar.gz');
//print_r($sftp->nlist());
echo 'end';