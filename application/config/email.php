<?php defined('BASEPATH') or exit('No direct script access allowed');

// Utilisation des constantes définies dans Config (assurez-vous que config.php est inclus avant ce fichier)
$config['useragent']    = Config::SMTP_USERAGENT;
$config['protocol']     = Config::SMTP_PROTOCOL;
$config['mailtype']     = Config::SMTP_MAILTYPE;
$config['smtp_debug']   = Config::SMTP_DEBUG;
$config['smtp_auth']    = Config::SMTP_AUTH;
$config['smtp_host']    = Config::SMTP_HOST;
$config['smtp_user']    = Config::SMTP_USER;
$config['smtp_pass']    = Config::SMTP_PASS;
$config['smtp_crypto']  = Config::SMTP_CRYPTO;
$config['smtp_port']    = Config::SMTP_PORT;
//$config['from_name']    = Config::FROM_NAME;
//$config['from_address'] = Config::FROM_ADDRESS;
//$config['reply_to']     = Config::REPLY_TO;
$config['crlf']         = Config::SMTP_CRLF;
$config['newline']      = Config::SMTP_NEWLINE;
