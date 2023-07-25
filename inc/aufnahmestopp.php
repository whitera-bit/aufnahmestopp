<?php

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}


// Informationen für den Plugin Manager

function aufnahmestopp_info() 
{
	return array(
		"name"			=> "Automatischer Aufnahmestopp",
		"description"	=> "Ein Plugin, das eine automatische Aufnahmestopp Übersicht erstellt, basierend auf den vorher definierten Beschränkungen",
		"author"		=> "white_rabbit",
		"authorsite"	=> "https://epic.quodvide.de/member.php?action=profile&uid=2",
		"version"		=> "1.0",
		"compatibility" => "18*"
	);
}

// Installation

function aufnahmestopp_install() 
  {
    global $db, $cache, $mybb;

    // DB-Tabelle erstellen

    $db->query("CREATE TABLE ".TABLE_PREFIX."aufnahmestopp_cat(
        `caid` int(10) NOT NULL AUTO_INCREMENT,
        `type` VARCHAR(255) NOT NULL,
        `title` VARCHAR(2500) NOT NULL,
        `shortdescription` VARCHAR(600),
        PRIMARY KEY (`nid`),
        KEY `nid` (`nid`)
    )
     ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1
    "); 

    $db->query("CREATE TABLE ".TABLE_PREFIX."aufnahmestopp(
        `aid` int(10) NOT NULL AUTO_INCREMENT,
        `type` VARCHAR(255) NOT NULL,
        `title` VARCHAR(2500) NOT NULL,
        `shortdescription` VARCHAR(600),
        PRIMARY KEY (`nid`),
        KEY `nid` (`nid`)
    )
     ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1
    "); 
    
    
    // Tabellenerweiterung der users-Tabelle für die Index Nachricht

    $db->query("ALTER TABLE `".TABLE_PREFIX."users` ADD `aufnahmestopp_new` int(11) NOT NULL DEFAULT '0';");
    

    // Einstellungen ACP

    $setting_group = array(
        'name'          => 'aufnahmestopp',
        'title'         => 'Automatischer Aufnahmestopp',
        'description'   => 'Einstellungen für den automatischen Aufnahmestopp',
        'disporder'     => 1,
        'isdefault'     => 0
    );
        
    $gid = $db->insert_query("settinggroups", $setting_group); 

    $setting_array = array(
        'aufnahmestopp_visible' => array(
            'title' => 'Zugriff auf die Übersicht',
            'description' => 'Welche Gruppen können die Übersicht sehen?',
            'optionscode' => 'groupselect',
            'value' => '4', // Default
            'disporder' => 0
        ),

    $setting_array = array(
        'aufnahmestopp_exception' => array(
            'title' => 'Ausnahmen',
            'description' => 'Welche Gruppen werden nicht in den Aufnahmestopp gezählt?',
            'optionscode' => 'groupselect',
            'value' => '4', // Default
            'disporder' => 0
        ),

     $setting_array = array(
        'aufnahmestopp_notification' => array(
            'title' => 'Neue Aufnahmestopps anzeigen',
            'description' => 'Sollen neue Aufnahmestopps im Header angezeigt werde?',
            'optionscode' => 'yesno',
            'value' => '0', // Default
            'disporder' => 0
        ),
     );

foreach($setting_array as $name => $setting)
    {
        $setting['name'] = $name;
        $setting['gid']  = $gid;
        $db->insert_query('settings', $setting);
    }

rebuild_settings();


    function aufnahmestopp_is_installed()
    {
        global $db;
        if($db->table_exists("aufnahmestopp"))
        {
            return true;
        }
        return false;
    }

    function aufnahmestopp_uninstall()
      {
    global $db;

        // DB löschen
        if($db->table_exists("aufnahmestopp"))
        {
            $db->drop_table("aufnahmestopp");
        }
    
       
        // Einstellungen löschen
        $db->delete_query('settings', "name LIKE 'aufnahmestopp%'");
        $db->delete_query('settinggroups', "name = 'aufnahmestopp'");
    
        rebuild_settings();
    
        // Templates löschen
        $db->delete_query("templategroups", "prefix = 'aufnahmestopp'");
        $db->delete_query("templates", "title LIKE '%aufnahmestopp%'");
    
        // CSS löschen
        require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
          	$db->delete_query("themestylesheets", "name = 'aufnahmestopp.css'");
          	$query = $db->simple_select("themes", "tid");
          	while($theme = $db->fetch_array($query)) 
            {
          		update_theme_stylesheet_list($theme['tid']);
          	}


      }

    function aufnahmestopp_activate()
{
    global $db, $cache;
    
    require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
    require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

    // Variable für den Alert im Header

    find_replace_templatesets('header', '#'.preg_quote('{$bbclosedwarning}').'#', '{$aufnahmestopp_new} {$bbclosedwarning}');
}

function aufnahmestopp_deactivate()
{
     global $db, $cache;

    require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
    require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

    // Variablen für den Alert im Header entfernen

    find_replace_templatesets('header', '#'.preg_quote('{$aufnahmestopp_new} {$bbclosedwarning}').'#', '{$bbclosedwarning}');
}

   // Hooks
    
    $plugins->add_hook('global_start', 'aufnahmestopp_global');

    function aufnahmestopp_global(){

	global $db, $mybb, $templates, $aufnahmestopp_new, $new_aufnahmestopp, $aufnahmestopp_read, $lang;

	 // Information zu neuem Aufnahmestopp auf dem Index
	    
	 if(is_member($mybb->settings['aufnahmestopp'])) {
	
	        $lang->load('aufnahmestopp');
	
	        $uid = $mybb->user['uid'];
	
	        $aufnahmestopp_read = "<a href='misc.php?action=aufnahmestopp_read&read={$uid}' original-title='als gelesen markieren'><i class=\"fas fa-trash\" style=\"float: right;font-size: 14px;padding: 1px;\"></i></a>";
	
		 // User hat Info auf dem Index gelesen
		
		            if ($mybb->get_input ('action') == 'aufnahmestopp_read') {
		
		                $this_user = intval ($mybb->user['uid']);
		
		                $as_uid = intval ($mybb->user['as_uid']);
		                $read = $mybb->input['read'];
		                if ($read) {
		                    if($as_uid == 0){
		                        $db->query ("UPDATE ".TABLE_PREFIX."users SET aufnahmestopp_new = 1  WHERE (as_uid = $this_user) OR (uid = $this_user)");
		                    }elseif ($as_uid != 0){
		                        $db->query ("UPDATE ".TABLE_PREFIX."users SET aufnahmestopp_new = 1  WHERE (as_uid = $as_uid) OR (uid = $this_user) OR (uid = $as_uid)");
		                    }
		                    redirect("index.php");
		                }
		            }
		    }
		
		    $select = $db->query ("SELECT * FROM " . TABLE_PREFIX ."aufnahmestopp WHERE visible = 1");
		    $row_cnt = mysqli_num_rows ($select);
		    if ($row_cnt > 0) {
		        $select = $db->query ("SELECT aufnahmestopp_new FROM " . TABLE_PREFIX . "users 
		        WHERE uid = '" . $mybb->user['uid'] . "' LIMIT 1");
		
		
		        $data = $db->fetch_array ($select);
		        if (isset($data['aufnahmestopp_new']) == '0') {
		
		            eval("\$new_aufnahmestopp = \"" . $templates->get ("aufnahmestopp_alert") . "\";");
		
		        }
		            
		    }
		
		}
		
		
		// WER IST ONLINE Anzeige
		
		
		$plugins->add_hook("fetch_wol_activity_end", "aufnahmestopp_online_activity");
		$plugins->add_hook("build_friendly_wol_location_end", "aufnahmestopp_online_location");
		
		function aufnahmestopp_online_activity($user_activity) {
		global $parameters;
		
		    $split_loc = explode(".php", $user_activity['location']);
		    if($split_loc[0] == $user['location']) {
		        $filename = '';
		    } else {
		        $filename = my_substr($split_loc[0], -my_strpos(strrev($split_loc[0]), "/"));
		    }
		    
		    switch ($filename) {
		        case 'aufnahmestopp':
		        if($parameters['action'] == "" && empty($parameters['site'])) {
		            $user_activity['activity'] = "aufnahmestopp";
		        }
		        if($parameters['action'] == "overview" && empty($parameters['site'])) {
		            $user_activity['activity'] = "overview";
		        }
		        if($parameters['action'] == "free" && empty($parameters['site'])) {
		            $user_activity['activity'] = "free";
		        }
		        if($parameters['action'] == "taken" && empty($parameters['site'])) {
		            $user_activity['activity'] = "taken";
		        }
		        if($parameters['action'] == "add" && empty($parameters['site'])) {
		            $user_activity['activity'] = "add";
		        }
		        if($parameters['action'] == "edit" && empty($parameters['site'])) {
		            $user_activity['activity'] = "edit";
		        }
		        break;
		    }
		      
		return $user_activity;
		}
		
		function aufnahmestopp_online_location($plugin_array) {
		global $mybb, $theme, $lang;
		
		    if($plugin_array['user_activity']['activity'] == "aufnahmestopp") {
		        $plugin_array['location_name'] = "Betrachtet die <a href=\"aufnahmestopp.php\">Anschlagstafel</a>.";
		    }
			if($plugin_array['user_activity']['activity'] == "overview") {
				$plugin_array['location_name'] = "Studiert die <a href=\"aufnahmestopp.php?action=overview\">Aufträge</a>.";
			}
		    if($plugin_array['user_activity']['activity'] == "free") {
				$plugin_array['location_name'] = "Sieht sich freie <a href=\"aufnahmestopp.php?action=free\">Aufträge</a> an.";
			}
		    if($plugin_array['user_activity']['activity'] == "taken") {
				$plugin_array['location_name'] = "Sieht sich vergebene <a href=\"aufnahmestopp.php?action=taken\">Aufträge</a> an.";
			}
		    if($plugin_array['user_activity']['activity'] == "finished") {
				$plugin_array['location_name'] = "Sieht sich erledigte <a href=\"aufnahmestopp.php?action=finished\">Aufträge</a> an.";
			}
		    if($plugin_array['user_activity']['activity'] == "add") {
				$plugin_array['location_name'] = "Pinnt einen neuen Auftrag an.";
			}
		    if($plugin_array['user_activity']['activity'] == "edit") {
				$plugin_array['location_name'] = "Bessert Fehler auf einem Auftrag aus.";
			}
		
		return $plugin_array;
		}








      
