<?php
define('SYSTEM_DO_NOT_LOGIN', true);
require 'init.php';
require SYSTEM_ROOT.'/sign.functions.php';
set_time_limit(0);
global $m,$today;
	$return = '';
	doAction('cron_1');

	if (option::get('cron_last_do_time') != $today) {
		option::set('cron_last_do_time',$today);
		option::set('cron_last_do','0');
	}

	////////////////

	DoSign('tieba');
	$tcc = 1;

	foreach (unserialize(option::get('fb_tables')) as $value) {
		$return = DoSign($value);
		$tcc++;
	}

	///////////////

	$count = $m->fetch_row($m->query("SELECT COUNT(*) FROM `".DB_NAME."`.`".DB_PREFIX."tieba` WHERE `lastdo` != '".$today."'"));
	doAction('cron_2');
	msg('本次计划任务完毕',false,false);
?>