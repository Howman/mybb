<?php
define("IN_MYBB", 1);

// Lets pretend we're a level higher
define("IN_ADMINCP", 1);

// Here you can change how much of an Admin CP IP address must match in a previous session for the user is validated (defaults to 3 which matches a.b.c)
define("ADMIN_IP_SEGMENTS", 3);

require_once dirname(dirname(__FILE__))."/inc/init.php";

if(!isset($config['admin_dir']))
{
	$config['admin_dir'] = "admin";
}

//
// TEMPORARY
//
define('MYBB_ADMIN_DIR', MYBB_ROOT."admincp/");
//define('MYBB_ADMIN_DIR', MYBB_ROOT.$config['admin_dir'].'/');

// Check installation (TEMPORARY)
if(!$db->table_exists('adminlog2'))
{
	$db->query("CREATE TABLE ".TABLE_PREFIX."adminlog2 (
	  uid int unsigned NOT NULL default '0',
	  ipaddress varchar(50) NOT NULL default '',
	  dateline bigint(30) NOT NULL default '0',
	  module varchar(50) NOT NULL default '',
	  action varchar(50) NOT NULL default '',
	  data text NOT NULL default '',
	  KEY module (module, action)
	) TYPE=MyISAM;");
}
if(!$db->field_exists('data', 'adminsessions'))
{
	$db->query("ALTER TABLE ".TABLE_PREFIX."adminsessions ADD data TEXT NOT NULL AFTER lastactive;");
}

require_once MYBB_ADMIN_DIR."/inc/class_page.php";
require_once MYBB_ADMIN_DIR."/inc/class_form.php";
require_once MYBB_ADMIN_DIR."/inc/class_table.php";
require_once MYBB_ADMIN_DIR."/inc/functions.php";
require_once MYBB_ROOT."inc/functions_user.php";

$page = new Page;

if($mybb->settings['cpstyle'] && is_dir($config['admindir']."/styles/".$mybb->settings['cpstyle']))
{
	$page->style = $mybb->settings['cpstyle'];
}
else
{
	$page->style = "default";
}

// LANGUAGE LOADING STUFF NEEDS TO GO HERE

$time = time();

if(is_dir(MYBB_ROOT."install") && !file_exists(MYBB_ROOT."install/lock"))
{
	$mybb->trigger_generic_error("install_directory");
}

$ip_address = get_ip();
unset($user);

if($mybb->input['action'] == "logout")
{
	// Delete session from the database
	$db->delete_query("adminsessions", "sid='".$db->escape_string($mybb->input['adminsid'])."'");
	
	$page->show_login("You have successfully been logged out.");
}
elseif($mybb->input['do'] == "login")
{
	$user = validate_password_from_username($mybb->input['username'], $mybb->input['password']);
	if($user['uid'])
	{
		$query = $db->simple_select("users", "*", "uid='".$user['uid']."'");
		$mybb->user = $db->fetch_array($query);
	}
	$fail_check = 1;

	if($mybb->user['uid'])
	{
		$sid = md5(uniqid(microtime()));
		
		// Create a new admin session for this user
		$admin_session = array(
			"sid" => $sid,
			"uid" => $mybb->user['uid'],
			"loginkey" => $mybb->user['loginkey'],
			"ip" => $db->escape_string(get_ip()),
			"dateline" => time(),
			"lastactive" => time()
		);
		$db->insert_query("adminsessions", $admin_session);
	}
}
else
{
	// No admin session - show message on the login screen
	if(!$mybb->input['adminsid'])
	{
		$login_message = "No administration session was found";
	}
	// Otherwise, check admin session
	else
	{
		$query = $db->simple_select("adminsessions", "*", "sid='".$db->escape_string($mybb->input['adminsid'])."'");
		$admin_session = $db->fetch_array($query);

		// No matching admin session found - show message on login screen
		if(!$admin_session['sid'])
		{
			$login_message = "Invalid administration session";
		}
		else
		{
			$admin_session['data'] = @unserialize($admin_session['data']);

			// Fetch the user from the admin session
			$query = $db->simple_select("users", "*", "uid='{$admin_session['uid']}'");
			$mybb->user = $db->fetch_array($query);

			// Login key has changed - force logout
			if(!$mybb->user['uid'] && $mybb->user['loginkey'] != $admin_session['loginkey'])
			{
				unset($user);
			}
			else
			{
				// Admin CP sessions 2 hours old are expired
				if($admin_session['lastactive'] < time()-7200)
				{
					$login_message = "Your administration session has expired";
					$db->delete_query("adminsessions", "sid='".$db->escape_string($mybb->input['adminsid'])."'");
					unset($user);
				}
				// If IP matching is set - check IP address against the session IP
				else if(ADMIN_IP_SEGMENTS > 0)
				{
					$exploded_ip = explode(".", $ip_address);
					$exploded_admin_ip = explode(".", $admin_session['ip']);
					$matches = 0;
					$valid_ip = false;
					for($i = 0; $i < ADMIN_IP_SEGMENTS; ++$i)
					{
						if($exploded_ip[$i] == $exploded_admin_ip[$i])
						{
							++$matches;
						}
						if($matches == ADMIN_IP_SEGMENTS)
						{
							$valid_ip = true;
							break;
						}
					}
					// IP doesn't match properly - show message on logon screen
					if(!$valid_ip)
					{
						$login_message = "Your IP address is not valid for this session";
					}
				}
			}
		}
	}
}

if(!$mybb->user['usergroup'])
{
	$mybbgroups = 1;
}
else
{
	$mybbgroups = $mybb->user['usergroup'].",".$mybb->user['additionalgroups'];
}
$mybb->usergroup = usergroup_permissions($mybbgroups);

if($mybb->usergroup['cancp'] != "yes" || !$mybb->user['uid'])
{
	unset($mybb->user);
}

if($mybb->user['uid'])
{
	$query = $db->query("SELECT * FROM ".TABLE_PREFIX."adminoptions WHERE uid='".$mybb->user['uid']."'");
	$admin_options = $db->fetch_array($query);
	
	if($admin_options['cpstyle'] && is_dir(MYBB_ADMIN_DIR."styles/{$admin_options['cpstyle']}"))
	{
		$style = $admin_options['cpstyle'];
	}

	// Update the session information in the DB
	if($admin_session['sid'])
	{
		$updated_session = array(
			"lastactive" => time(),
			"ip" => $ip_address
		);
		$db->update_query("adminsessions", $updated_session, "sid='".$db->escape_string($mybb->input['adminsid'])."'");
	}
	define("SID", "adminsid={$admin_session['sid']}");	
}
else
{
	if($fail_check == 1)
	{
		$page->show_login("The username and password you entered are invalid or the account is not a valid administrator");
	}
	else
	{
		$page->show_login($login_message);
	}
}

if($rand == 2 || $rand == 5)
{
	$stamp = time()-604800;
	$db->delete_query("adminsessions", "lastactive<'$stamp'");
}

$page->add_breadcrumb_item("Home", "index.php");

// Begin dealing with the modules
$modules_dir = MYBB_ADMIN_DIR."modules";
$dir = opendir($modules_dir);
while(($module = readdir($dir)) !== false)
{
	if(is_dir($modules_dir."/".$module) && !in_array($module, array(".", "..")) && file_exists($modules_dir."/".$module."/module_meta.php"))
	{
		require_once $modules_dir."/".$module."/module_meta.php";
		$meta_function = $module."_meta";
		$initialized = $meta_function();
		if($initialized == true)
		{
			$modules[$module] = 1;
		}
	}
}
closedir($dir);

$current_module = explode("/", $mybb->input['module'], 2);
if($mybb->input['module'] && $modules[$current_module[0]])
{
	$run_module = $current_module[0];
}
else
{
	$run_module = "home";
}

$action_handler = $run_module."_action_handler";
$action_file = $action_handler($current_module[1]);

// Log the action this user is trying to perform
log_admin_action();

require $modules_dir."/".$run_module."/".$action_file;
?>