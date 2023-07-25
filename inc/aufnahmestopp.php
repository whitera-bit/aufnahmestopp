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

    $db->query("ALTER TABLE `".TABLE_PREFIX."users` ADD `noticeboard_new` int(11) NOT NULL DEFAULT '0';");
    

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

function noticeboard_deactivate()
{
     global $db, $cache;

    require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
    require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

    // Variablen für den Alert im Header entfernen

    find_replace_templatesets('header', '#'.preg_quote('{$aufnahmestopp_new} {$bbclosedwarning}').'#', '{$bbclosedwarning}');
}

   // Hooks
    
    $plugins->add_hook('global_start', 'aufnahmestopp_global');

    function noticeboard_global(){










      
