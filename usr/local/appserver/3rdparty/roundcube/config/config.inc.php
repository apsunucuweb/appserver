<?php
$config['db_dsnw'] = 'mysql://roundcube:roundcube_pass@localhost/roundcube';
$config['default_host'] = 'localhost';
$config['smtp_server'] = 'localhost';
$config['smtp_port'] = 25;
$config['imap_conn_options'] = array(
    'ssl' => array('verify_peer' => false, 'verify_peer_name' => false),
);
$config['des_key'] = 'rcmDESkey1234567890123456';
$config['plugins'] = array('archive', 'zipdownload');
?>