<?php
require 'init.php';

if (ROLE != 'user' && ROLE != 'admin') {
	msg('权限不足');
}

if (ROLE != 'admin' && stristr(strip_tags($_GET['mod']), 'admin:')) {
	msg('权限不足');
}

switch (strip_tags($_GET['mod'])) {
	case 'admin:plugins':
		if (isset($_GET['dis'])) {
			inactivePlugin($_GET['dis']);
			header("Location: ".SYSTEM_URL.'index.php?mod=admin:plugins&ok');
		}
		elseif (isset($_GET['act'])) {
			activePlugin($_GET['act']);
			header("Location: ".SYSTEM_URL.'index.php?mod=admin:plugins&ok');
		}
		elseif (isset($_GET['uninst'])) {
			uninstallPlugin($_GET['uninst']);
			header("Location: ".SYSTEM_URL.'index.php?mod=admin:plugins&ok');
		}
		header("Location: ".SYSTEM_URL.'index.php?mod=admin:plugins&ok');
		break;
	
	case 'admin:set':
		global $m;
		@option::set('system_url',$_POST['system_url']);
		@option::set('cron_limit',$_POST['cron_limit']);
		@option::set('tb_max',$_POST['tb_max']);
		@option::set('footer',str_ireplace('\'', '\\\'', $_POST['footer']));
		@option::set('enable_reg',$_POST['enable_reg']);
		@option::set('protect_reg',$_POST['protect_reg']);
		@option::set('yr_reg',$_POST['yr_reg']);
		@option::set('icp',$_POST['icp']);
		@option::set('protector',$_POST['protector']);
		@option::set('trigger',$_POST['trigger']);
		@option::set('fb',$_POST['fb']);
		@option::set('cloud',$_POST['cloud']);
		@option::set('dev',$_POST['dev']);
		@option::set('pwdmode',$_POST['pwdmode']);
		if (empty($_POST['fb_tables'])) {
			@option::set('fb_tables',NULL);
		} else {
			$fb_tables = explode("\n",$_POST['fb_tables']);
			$fb_tab = array();
			$n= 0;
			foreach ($fb_tables as $value) {
				$n++;
				$sql = str_ireplace('{VAR-DB}', DB_NAME, str_ireplace('{VAR-TABLE}', trim(DB_PREFIX.$value), file_get_contents(SYSTEM_ROOT.'/setup/template.table.sql')));
				$m->query($sql);
				$fb_tab[$n] .= trim($value);
			}
			@option::set('fb_tables', serialize($fb_tab));
		}
		doAction('admin_set_save');
		header("Location: ".SYSTEM_URL.'index.php?mod=admin:set&ok');
		break;

	case 'admin:tools':
		switch (strip_tags($_GET['setting'])) {
		case 'optim':
			global $m;
			$rs=$m->query("SHOW TABLES FROM ".DB_NAME);
			while ($row = $m->fetch_row($rs)) {
				$m->query('OPTIMIZE TABLE  `'.DB_NAME.'`.`'.$row[0].'`');
		    }
			break;
		
		case 'fixdoing':
			option::set('cron_isdoing',0);
			break;

		case 'reftable':
			option::set('freetable',getfreetable());
			break;

		default:
			msg('未定义操作');
			break;
		}
		header("Location: ".SYSTEM_URL.'index.php?mod=admin:tools&ok');
		break;

	case 'admin:users':
		switch (strip_tags($_POST['do'])) {
			case 'cookie':
				foreach ($_POST['user'] as $value) {
					$m->query("UPDATE `".DB_NAME."`.`".DB_PREFIX."users` SET  `ck_bduss` =  '' WHERE  `".DB_PREFIX."users`.`id` = ".$value);
				}
				doAction('admin_users_cookie');
				break;
			
			case 'clean':
				foreach ($_POST['user'] as $value) {
					CleanUser($value);
				}
				doAction('admin_users_clean');
				break;

			case 'delete':
				foreach ($_POST['user'] as $value) {
					DeleteUser($value);
				}
				doAction('admin_users_delete');
				break;

			case 'add':
				$name = isset($_POST['name']) ? strip_tags($_POST['name']) : '';
				$mail = isset($_POST['mail']) ? strip_tags($_POST['mail']) : '';
				$pw   = isset($_POST['pwd']) ? strip_tags($_POST['pwd']) : '';
				$role = isset($_POST['role']) ? strip_tags($_POST['role']) : 'user';

				if (empty($name) || empty($mail) || empty($pw)) {
					msg('添加用户失败：请正确填写账户、密码或邮箱');
				}
				$x=$m->once_fetch_array("SELECT COUNT(*) AS total FROM `".DB_NAME."`.`".DB_PREFIX."users` WHERE name='{$name}'");
				if ($x['total'] > 0) {
					msg('添加用户失败：用户名已经存在');
				}
				$m->query('INSERT INTO `'.DB_NAME.'`.`'.DB_PREFIX.'users` (`id`, `name`, `pw`, `email`, `role`, `t`, `ck_bduss`) VALUES (NULL, \''.$name.'\', \''.EncodePwd($pw).'\', \''.$mail.'\', \''.$role.'\', \''.getfreetable().'\', NULL);');
				doAction('admin_users_add');
				header("Location: ".SYSTEM_URL.'index.php?mod=admin:users&ok');
				break;

			default:
				msg('未定义操作');
				break;
			}
		header("Location: ".SYSTEM_URL.'index.php?mod=admin:users&ok');
		break;

	case 'baiduid':
		if (isset($_GET['delete'])) {
			$m->query("UPDATE  `".DB_NAME."`.`".DB_PREFIX."users` SET  `ck_bduss` =  NULL WHERE  `".DB_PREFIX."users`.`id` =".UID.";");
		}
		elseif (isset($_GET['bduss'])) {
			$m->query("UPDATE  `".DB_NAME."`.`".DB_PREFIX."users` SET  `ck_bduss` =  '".strip_tags($_GET['bduss'])."' WHERE  `".DB_PREFIX."users`.`id` =".UID.";");
			Clean();
			header("Location: ".SYSTEM_URL."?mod=baiduid");
		}
		doAction('baiduid_set');
		header("Location: ".SYSTEM_URL."?mod=baiduid");
		break;

	case 'showtb':
		if (isset($_GET['set'])) {
			$x=$m->fetch_array($m->query('SELECT * FROM  `'.DB_NAME.'`.`'.DB_PREFIX.TABLE.'` WHERE  `uid` = '.UID.' LIMIT 1'));
			$f=$x['tieba'];
			foreach ($_POST['no'] as $x) {
				preg_match('/(.*)\[(.*)\]/', $x, $v);
				$m->query("UPDATE `".DB_NAME."`.`".DB_PREFIX.TABLE."` SET `no` =  '{$v[1]}' WHERE  `".DB_PREFIX.TABLE."`.`id` = {$v[2]} ;");
			}
			header("Location: ".SYSTEM_URL.'index.php?mod=showtb&ok');
		}
		elseif (isset($_GET['ref'])) {
			  $n=0;
			  $o=option::get('tb_max');
			  $ch = curl_init(); 
			  curl_setopt($ch, CURLOPT_URL, 'http://tieba.baidu.com/mo/?tn=bdFBW&tab=favorite'); //登陆地址 
			  curl_setopt($ch, CURLOPT_COOKIESESSION, true); 
			  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
			  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			  curl_setopt($ch, CURLOPT_COOKIE, "BDUSS=".BDUSS);
			  curl_setopt($ch, CURLOPT_USERAGENT, 'Phone '.mt_rand());
			  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
			  curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-FORWARDED-FOR:183.185.2.".mt_rand(1,255)));
			  curl_setopt($ch, CURLOPT_HEADER, false);  
			  $ch = curl_exec($ch);
			  preg_match_all('/<td>(.*?).\<a href=\"\/mo\/(.*?)\"\>(.*?)\<\/a\>\<\/td\>/', $ch, $list);
			  $f = '';
			  foreach ($list[3] as $v) {
			  	$osq = $m->query("SELECT * FROM `".DB_NAME."`.`".DB_PREFIX.TABLE."` WHERE `uid` = ".UID." AND `tieba` = '{$v}';");
				if($m->num_rows($osq) == 0) {
					$n++;
					if (!empty($o) && ROLE != 'admin' && $n > $o) {
						msg('当前贴吧数量超出系统限定，无法继续列出贴吧');
					}
					$m->query("INSERT INTO `".DB_NAME."`.`".DB_PREFIX.TABLE."` (`id`, `uid`, `tieba`, `no`, `lastdo`) VALUES (NULL, '".UID."', '{$v}', 0, 0);");
				}
			  }
			header("Location: ".SYSTEM_URL.'index.php?mod=showtb');
		}
		elseif (isset($_GET['clean'])) {
			CleanUser(UID);
			header("Location: ".SYSTEM_URL.'index.php?mod=showtb');
		}
		doAction('showtb_set');
		break;

		case 'set':
			$m->query("UPDATE `".DB_NAME."`.`".DB_PREFIX."users` SET  `options` =  '".serialize($_POST)."' WHERE  `id` = ".UID);
			doAction('set_save');
			header("Location: ".SYSTEM_URL.'index.php?mod=set&ok');
			break;

	default:
		msg('未定义操作');
		break;
}
?>