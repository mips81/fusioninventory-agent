<?php

/*
   ------------------------------------------------------------------------
   FusionInventory
   Copyright (C) 2010-2012 by the FusionInventory Development Team.

   http://www.fusioninventory.org/   http://forge.fusioninventory.org/
   ------------------------------------------------------------------------

   LICENSE

   This file is part of FusionInventory project.

   FusionInventory is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   FusionInventory is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with Behaviors. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   FusionInventory
   @author    David Durieux
   @co-author
   @copyright Copyright (c) 2010-2012 FusionInventory team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      http://www.fusioninventory.org/
   @link      http://forge.fusioninventory.org/projects/fusioninventory-for-glpi/
   @since     2010
 
   ------------------------------------------------------------------------
 */

define ("PLUGIN_FUSINVINVENTORY_VERSION","0.80+1.6");

// Init the hooks of fusinvdeploy
function plugin_init_fusinvinventory() {
   global $PLUGIN_HOOKS,$CFG_GLPI,$LANG;

   // ##### 1. Stop if fusioninventory not activated #####

   $plugin = new Plugin;
   if (!$plugin->isActivated("fusioninventory")) {
      $plugin->getFromDBbyDir("fusinvinventory");
      if (isset($_GET['id'])
            AND isset($_GET['action'])
            AND $_GET['id'] == $plugin->fields['id']
            AND strstr($_SERVER['HTTP_REFERER'], "front/plugin.php")) {
         switch ($_GET['action']) {
            case 'activate':
               addMessageAfterRedirect($LANG['plugin_fusinvinventory']['setup'][17]);
               break;
            case 'uninstall':
               addMessageAfterRedirect($LANG['plugin_fusinvinventory']['setup'][18]);
               glpi_header($CFG_GLPI["root_doc"]."/front/plugin.php");
               break;
         }
      }
      return false;
   }

   // ##### 2. register class #####
   Plugin::registerClass('PluginFusinvinventoryInventory');
      //Classes for rulesengine
   Plugin::registerClass('PluginFusinvinventoryRuleEntity');
   Plugin::registerClass('PluginFusinvinventoryRuleEntityCollection',
                         array('rulecollections_types'=>true));


   // ##### 3. get informations of the plugin #####

   $a_plugin = plugin_version_fusinvinventory();
   if (!class_exists('PluginFusioninventoryModule')) { // if plugin is unactive
      include(GLPI_ROOT . "/plugins/fusioninventory/inc/module.class.php");
   }
   $moduleId = PluginFusioninventoryModule::getModuleId($a_plugin['shortname']);

   // ##### 4. Set in session module_id #####

   $_SESSION["plugin_".$a_plugin['shortname']."_moduleid"] = $moduleId;

   // ##### 5. Set in session XMLtags of methods #####

   $_SESSION['glpi_plugin_fusioninventory']['xmltags']['INVENTORY']
      = 'PluginFusinvinventoryInventory';


   if (!isset($_SESSION['glpi_plugin_fusioninventory']['configuration']['moduletabforms']['fusinvinventory'][$LANG['plugin_fusinvinventory']['title'][0]])) {
      $_SESSION['glpi_plugin_fusioninventory']['configuration']['moduletabforms']['fusinvinventory'][$LANG['plugin_fusinvinventory']['title'][0]] = array('class'=>'PluginFusinvinventoryConfig',
                                                                'submitbutton'=>'plugin_fusinvinventory_config_set',
                                                                'submitmethod'=>'putForm');
   }

   if (!class_exists('PluginFusioninventoryProfile')) { // if plugin is unactive
      include(GLPI_ROOT . "/plugins/fusioninventory/inc/profile.class.php");
   }
   $PLUGIN_HOOKS['change_profile']['fusinvinventory']
      = PluginFusioninventoryProfile::changeprofile($moduleId,$a_plugin['shortname']);

   if (isset($_SESSION["glpiID"])) {

      if (haveRight("configuration", "r") || haveRight("profile", "w")) {// Config page
         if (!class_exists('PluginFusioninventoryConfiguration')) { // if plugin is unactive
            include(GLPI_ROOT . "/plugins/fusioninventory/inc/configuration.class.php");
         }
         $PluginFusioninventoryConfiguration = new PluginFusioninventoryConfiguration();
         $a_tabs = $PluginFusioninventoryConfiguration->defineTabs();
         $PLUGIN_HOOKS['config_page']['fusinvinventory'] = '../fusioninventory/front/configuration.form.php?glpi_tab='.array_search($a_plugin['name'], $a_tabs);
      }

      $PLUGIN_HOOKS['use_massive_action']['fusinvinventory']=1;
      $PLUGIN_HOOKS['pre_item_purge']['fusinvinventory'] = array('Computer' =>'plugin_pre_item_purge_fusinvinventory',
                                                                 'PluginFusioninventoryLock' => array('PluginFusinvinventoryLock', 'deleteLock'));
      $PLUGIN_HOOKS['pre_item_update']['fusinvinventory'] = array('PluginFusioninventoryLock' => array('PluginFusinvinventoryLock', 'deleteLock'));
      $PLUGIN_HOOKS['item_update']['fusinvinventory'] = array('PluginFusinvinventoryAntivirus' => array('PluginFusinvinventoryAntivirus', 'addhistory'));
   }

   $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['add']['fusinvinventory-ruleentity']
                  = '../fusinvinventory/front/ruleentity.form.php';
   $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['search']['fusinvinventory-ruleentity']
                  = '../fusinvinventory/front/ruleentity.php';

   $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['add']['fusinvinventory-blacklist']
                  = '../fusinvinventory/front/blacklist.form.php';
   $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['search']['fusinvinventory-blacklist']
                  = '../fusinvinventory/front/blacklist.php';
   
   // Tabs for each type
   $PLUGIN_HOOKS['headings']['fusinvinventory'] = 'plugin_get_headings_fusinvinventory';
   $PLUGIN_HOOKS['headings_action']['fusinvinventory'] = 'plugin_headings_actions_fusinvinventory';


   $PLUGIN_HOOKS['webservices']['fusinvinventory'] = 'plugin_fusinvinventory_registerMethods';


   // Fil ariane
   $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['fusinvinventory-blacklist']['title'] = $LANG['plugin_fusinvinventory']['menu'][2];
   $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['fusinvinventory-blacklist']['page']  = '/plugins/fusinvinventory/front/blacklist.php';

   $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['fusinvinventory-ruleinventory']['title'] = $LANG['plugin_fusinvinventory']['menu'][1];
   $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['fusinvinventory-ruleinventory']['page']  = '/plugins/fusinvinventory/front/ruleinventory.php';

   $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['fusinvinventory-ruleentity']['title'] = $LANG['plugin_fusinvinventory']['rule'][100];
   $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['fusinvinventory-ruleentity']['page']  = '/plugins/fusinvinventory/front/ruleentity.php';

   $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['fusinvinventory-importxmlfile']['title'] = $LANG['plugin_fusinvinventory']['menu'][0];
   $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['options']['fusinvinventory-importxmlfile']['page']  = '/plugins/fusinvinventory/front/importxml.php';
}



// Name and Version of the plugin
function plugin_version_fusinvinventory() {
   return array('name'           => 'FusionInventory INVENTORY',
                'shortname'      => 'fusinvinventory',
                'version'        => PLUGIN_FUSINVINVENTORY_VERSION,
                'author'         =>'<a href="mailto:d.durieux@siprossii.com">David DURIEUX</a>
                                    & FusionInventory team',
                'homepage'       =>'http://forge.fusioninventory.org/projects/fusioninventory-for-glpi/',
                'minGlpiVersion' => '0.80'// For compatibility / no install in version < 0.78
   );
}



// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_fusinvinventory_check_prerequisites() {
   global $LANG;
   
   if (version_compare(GLPI_VERSION,'0.80','lt') || version_compare(GLPI_VERSION,'0.81','ge')) {
      echo $LANG['plugin_fusioninventory']['errors'][50];
      return false;
   }
   $plugin = new Plugin();
   if (!$plugin->isActivated("fusioninventory")) {
      return false;
   }
   return true;
}



function plugin_fusinvinventory_check_config() {
   return true;
}

?>