<?php
/* SVN FILE: $Id: updater.model.php 164 2010-04-16 16:16:06Z ntemple $*/
/**
*
* ISN - Intellispire Network Client for Joomla! 1.5
* Copyright (c) 2008 Nick Temple, Intellispire
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; version 2
* of the License, and no other version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*
* @category     ISN
* @package      Joomla Client
* @author       Nick Temple <nickt@nicktemple.com>
* @license      GNU/GPL 2.0 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
* @copyright    2008 Intellispire/Nick Temple
* @version SVN: $Id: updater.model.php 164 2010-04-16 16:16:06Z ntemple $
*
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted Access');

jimport( 'joomla.application.component.model' );
jimport( 'joomla.filesystem.file');
jimport( 'joomla.filesystem.folder');

define('SOFTWAREMANIFESTLOCATION', UPDATER_CONFIG . 'manifest.xml');
define('SOFTWAREINSTALLEDSTACHE', UPDATER_CONFIG . 'extensions.xml');
define('UPDATER_FREQUENCY', 60*60*24*1); 
require_once(JPATH_COMPONENT_ADMINISTRATOR . '/lib/isnclient/intellispireNetworkClient.class.php');

class UpdaterModel extends JModel
{

  /** @var JPagination */
  /** @var Manifest */
  private $manifest;
  private $client;

  
  function getClient() {
    $registry =& JFactory::getConfig();

    if ($this->client) return $client;

    $component = &JComponentHelper::getComponent( 'com_updater' );
    $params = new JParameter($component->params);

    $repository = UPDATER_SERVER;
    $channels   = $this->getChannels();

    $isnid      = $registry->getValue('com_updater.isnid',  null);
    $machineid  = $registry->getValue('com_updater.machineid', null);

    $client = new intellispireNetworkClient($repository, $channels, $isnid, $machineid);

    return $client;
  }

  function retrievedownloadlink($package) {
    $client = $this->getClient();

    if ($package == 'joomla.jupgrade') {
      $version = JVERSION;
    } else {
      $version = $this->getInstalledVersion($package);  
    }

    $link = $client->retrievedownloadlink($package, $version);
    return $link;
  }

  
  function getChannels() {
    $component = &JComponentHelper::getComponent( 'com_updater' );
    $params = new JParameter($component->params);
    $channels   = 'joomla,' . $params->get('channels', UPDATER_CHANNELS);
    return $channels;
  }

  function retrievemanifest() {    
    $registry =& JFactory::getConfig();
    $client = $this->getClient();

    /* refresh installed extensions at the same time */
    $ext = installedExtensions::getInstance();
    $ext->load();

    $channels = $this->getChannels();
    $version = $registry->getValue('com_updater.manifest_version', 0 );

    try {
      $result = $client->retrievemanifest($channels, $version);
    } catch (Exception $e) {
      return false;
    }
    $success = false;

    $registry->setValue('com_updater.manifesttime', time() );
    $registry->setValue('com_updater.joomla_updater', UPDATER_VERSION); 

    if ($result['code'] == 403) { 
      $success = false;
    }

    if ($result['code'] == 200) {
      $payload = $result['payload'];
      $registry->setValue('com_updater.validated', 1);
      $registry->setValue('com_updater.manifest_version', $result['fileversion'] );

      $success = JFile::write(SOFTWAREMANIFESTLOCATION, $payload );
    }

    if ($result['code'] == 304) {
      $registry->setValue('com_updater.validated', 1);
      $success = true;
    }

    $this->_storeRegistry();

    return $success;
  }

  function register($name, $email) {
    $client = $this->getClient();
    $msg = $client->register($name, $email);
    return $msg;
  }

  function activate($email, $isnid) {
    $registry =& JFactory::getConfig();
    $registry->setValue('com_updater.isnid',   $isnid);

    $client = $this->getClient();
    $success = $client->activate($email,  'JOOMLA-1.5', phpversion());

    if($success) {
      $registry->setValue('com_updater.validated',  1);
      $this->_storeRegistry();
    }

    return $success;
  }


  # ======================================================

  function markInstalled($package, $iversion = 1) {
    // Make package name "safe"
    $package = str_replace('.', '_', $package);

    $registry =& JFactory::getConfig();
    $registry->setValue('com_updater.' . $package, $iversion );
    $this->_storeRegistry();
  }

  function getInstalledVersion($package) {
    // Make package name "safe"
    $package = str_replace('.', '_', $package);

    $registry =& JFactory::getConfig();
    $installed_version = $registry->getValue('com_updater.' . $package, 0);
    return $installed_version;
  }


  function _storeRegistry() {
    /** @param JRegistry */
    $registry =& JFactory::getConfig();

    $machineid  = $registry->getValue('com_updater.machineid', null);

    if (!$machineid) {
      $client = $this->getClient();
      $machineid = $client->getMachineId();
      $registry->setValue('com_updater.machineid', $machineid);
    }

    $php = $registry->toString('PHP', 'com_updater'  , array('class' => 'ComUpdaterConfig') );
    JFile::write(UPDATER_CONFIG . 'configuration.php', $php);
  }
  /**
  * @desc get the current manifest from disk.
  * @returns Manifest
  */

  function getManifest($reload = false) {

    if( ! $reload && ! empty($this->manifest)) return $this->manifest;


    $registry =& JFactory::getConfig();
    $manifest = new Manifest();

    $lastupdate = $registry->getValue('com_updater.manifesttime', 0 );

    if (! file_exists(SOFTWAREMANIFESTLOCATION) || ($lastupdate + UPDATER_FREQUENCY < time()) ) {
      $this->retrievemanifest();
    }

    $manifest->loadManifest(SOFTWAREMANIFESTLOCATION);
    $this->manifest = $manifest;

    return $manifest;
  }

  protected static function getFlag($variable, $default = 0) {
    $component = &JComponentHelper::getComponent( 'com_updater' );
    $params = new JParameter($component->params);

    $value   = $params->get($variable, 0);

    if ($value) {
      return true;
    } else {
      return false;
    }
  }

  function getFlagForceInstall() {
    return self::getFlag('forceinstall', 0);
  }

  function getFlagForceCopy() {
    $fc =  self::getFlag('forcecopy', 0);
    if (!defined('UPDATER_FORCECOPY')) {
      define('UPDATER_FORCECOPY', $fc);
    }
    return $fc;
  }

  function getFlagDownloadOnly() {
    return self::getFlag('download', 0);
  }
}

/**
* Handles already installed manifests
* @TODO extend to handle packages
*/

class installedExtensions {

  var $data = null;
  var $versions = array();

  private function __construct() { 
  }

  function getManifests($path) {
    global $sd_files;
    $sd_files = array();

    scan_directory_recursively($path, 'xml', 2);
    foreach ($sd_files as $file) {
      $path = $file['file'];    
      if (basename($path) == 'config.xml') continue;  // Ignore config files       
      try {
        $contents = file_get_contents($path);
        $xml  = new SimpleXMLElement($contents);
        $name = strtolower($xml->getName());              
        if ($name == 'install' || $name == 'mosinstall') {
          $xml = simplexml2array($xml);  
          $jid = self::getJidFromXML($xml, "$path:$name");
          $version = $xml['version'];
          $this->versions[$jid] = $version; 
        }
      } catch (Exception $e) {
        // can't read it, do nothing
      }                   
    }
  }



  /**
  * singleton
  * @return installedExtensions 
  */

  static function getInstance() {
    static $self = null;

    if ($self) return $self;
    $self = new installedExtensions();

    if (file_exists(SOFTWAREINSTALLEDSTACHE)) {
      $self->versions = unserialize(file_get_contents(SOFTWAREINSTALLEDSTACHE));
    } else {
      $self->load();
    }
//    print "<pre>\n";
//    print_r($self->versions);
//    print "<pre>\n";

    return $self;
  }

  public function getVersion($jname) {
    if (isset($this->versions[$jname])) {
      return $this->versions[$jname];
    } else {
      return null;
    }
  }

  function load() {  
    // we're ignoring admin templates and components
    $paths = array(
    JPATH_ADMINISTRATOR . '/components/',
    JPATH_ADMINISTRATOR . '/language/',
    JPATH_SITE . '/modules/',
    JPATH_SITE . '/templates/',
    JPATH_SITE . '/plugins/',    
    );

    foreach ($paths as $path) {
      $this->getManifests($path);
    }
    file_put_contents(SOFTWAREINSTALLEDSTACHE, serialize($this->versions));
  }

  function buildJid($item) {
    if (isset($item->folder)) $folder = $item->folder; else $folder = '';    
    if (! $item->type && isset($item->language)) {
      $type = 'language';
    } else {
      $type = $item->type;
    }
    return $this->getJid($type, $item->name, $folder);    
  }

  function getJid($type, $name, $folder ='') {
    $jname = $name;
    $jname = preg_replace('#[/\\\\\. ]+#', '', $jname);
    $jname = str_replace('_', "", $jname);
    $jname = strtolower(str_replace(" ", "", $jname));

    if ($type == 'plugin') {
      $jname = 'plugin_' . $folder . '_'  . $jname;
    } else {
      $jname = $type . '_' . $jname;
    }
    return strtolower(str_replace( ' ', '_', $jname));
  }  

  static function getJidFromXML($xml, $id) {

//    if (!isset($xml['name'])) {
//      print "<pre>\n";
//      print "$id\n";
//      print_r($xml);
//      print "<pre>\n";             
//    }
    
    $name = $xml['name'];
    $type = $xml['@attributes']['type'];

    if ($type == 'plugin') {
      $folder = $xml['@attributes']['group'];
    } else {
      $folder = '';
    }

    if ($type == '' && isset($xml['language'])) {
      $type = 'language';
    }

    return self::getJid($type, $name, $folder);
  }

}

function getComponent($c) {
  if (file_exists(JPATH_SITE . DS . 'administrator' . DS . 'components' . DS . $c)) {
    return 1;
  } else {
    return 0;
  }

}

function testFile($file) {

  global $sd_errors;
  global $sd_writeable;

  static $lastowner;
  static $lastpw;

  $owner = fileowner($file);
  if ($owner != $lastowner) {
    $lastowner = $owner;
    
    if (function_exists('posix_getpwuid')) {
      $pw = posix_getpwuid($owner);
    } else {
      $pw = 'noposix';
    }
    $lastpw = $pw['name']; 
  }
  $perms = substr(sprintf("%o",fileperms($file)),-4);
  if( is_writeable($file)) {
    $writeable = 'T';
  } else {
    $writeable = 'F';
    $sd_writeable = false;
  }

  $stat = "$writeable:$perms:$lastowner:$lastpw:$file";

  $info = array(
  'file'    => $file,
  'ownerid' => $owner,
  'owner'   => $lastpw,
  'perms'   => $perms,
  'write'   => $writeable,
  'stat'    => $stat,
  );

  if ($writeable == 'F') {
    $sd_errors[] = $info;
  }

  return $info;
}

function phpinfo_array($return=false){
  ob_start();
  phpinfo(-1);

  $pi = preg_replace(
  array('#^.*<body>(.*)</body>.*$#ms', '#<h2>PHP License</h2>.*$#ms',
  '#<h1>Configuration</h1>#',  "#\r?\n#", "#</(h1|h2|h3|tr)>#", '# +<#',
  "#[ \t]+#", '#&nbsp;#', '#  +#', '# class=".*?"#', '%&#039;%',
  '#<tr>(?:.*?)" src="(?:.*?)=(.*?)" alt="PHP Logo" /></a>'
  .'<h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
  '#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
  '#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
  "# +#", '#<tr>#', '#</tr>#'),
  array('$1', '', '', '', '</$1>' . "\n", '<', ' ', ' ', ' ', '', ' ',
  '<h2>PHP Configuration</h2>'."\n".'<tr><td>PHP Version</td><td>$2</td></tr>'.
  "\n".'<tr><td>PHP Egg</td><td>$1</td></tr>',
  '<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
  '<tr><td>Zend Engine</td><td>$2</td></tr>' . "\n" .
  '<tr><td>Zend Egg</td><td>$1</td></tr>', ' ', '%S%', '%E%'),
  ob_get_clean());

  $sections = explode('<h2>', strip_tags($pi, '<h2><th><td>'));
  unset($sections[0]);

  $pi = array();
  foreach($sections as $section){
    $n = substr($section, 0, strpos($section, '</h2>'));
    preg_match_all(
    '#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#',
    $section, $askapache, PREG_SET_ORDER);
    foreach($askapache as $m) {
      if (count ($m) > 2) 
        $pi[$n][$m[1]]=(!isset($m[3])||$m[2]==$m[3])?$m[2]:array_slice($m,2);
    }
  }

  return ($return === false) ? print_r($pi) : $pi;
}

function checkFiles() {
  $jdirs = array(
  'administrator',
  'cache',
  'components',
  'images',
  'includes',
  'index.php',
  'installation',
  'language',
  'libraries',
  'logs',
  'media',
  'modules',
  'plugins',
  'templates',
  'tmp',
  'xmlrpc',
  );

  global $sd_errors;

  $tmp_errors = array();

  foreach($jdirs as $dir) {
    if (is_dir(JPATH_SITE. '/'. $dir)) {
      $sd_errors = array();
      scan_directory_recursively(JPATH_SITE . '/' . $dir);
      $tmp_errors = array_merge($sd_errors, $tmp_errors);     
    }
  }

  return $tmp_errors;

}


function scan_directory_recursively($directory, $filter=FALSE, $level = 100)
{

  global $sd_files;
  $directory_tree = array();
  if ($level == 0) return $directory_tree;

  if(substr($directory,-1) == '/')
  {
    $directory = substr($directory,0,-1);
  }
  if(!file_exists($directory) || !is_dir($directory))
  {
    return FALSE;
  }elseif(is_readable($directory))
  {
    $directory_list = opendir($directory);
    while($file = readdir($directory_list))
    {
      if($file != '.' && $file != '..')
      {
        $path = $directory.'/'.$file;
        if(is_readable($path))
        {
          $subdirectories = explode('/',$path);
          if(is_dir($path))
          {              
            $directory_tree[] = array(
            'path'      => $path,
            'name'      => end($subdirectories),
            'kind'      => 'directory',
            'content'   => scan_directory_recursively($path, $filter, $level - 1)
            );
            if ($filter == FALSE) $sd_files[] = testFile($path);
          }elseif(is_file($path))
          {
            $extension = end(explode('.',end($subdirectories)));
            if($filter === FALSE || $filter == $extension)
            {
              $file = array(
              'path'          => $path,
              'name'          => end($subdirectories),
              'extension' => $extension,
              'size'          => filesize($path),
              'kind'          => 'file');

              $directory_tree[] = $file;
              $sd_files[] = testFile($path);
            }

          }
        }
      }
    }
    closedir($directory_list);
    return $directory_tree;
  }else{
    return FALSE;
  }
}

if (!function_exists('simplexml2array')) {
  function simplexml2array($xml) {
    if (get_class($xml) == 'SimpleXMLElement') {
      $attributes = $xml->attributes();
      foreach($attributes as $k=>$v) {
        if ($v) $a[$k] = (string) $v;
      }
      $x = $xml;
      $xml = get_object_vars($xml);
    }
    if (is_array($xml)) {
      if (count($xml) == 0) return (string) $x; // for CDATA
      foreach($xml as $key=>$value) {
        $r[$key] = simplexml2array($value);
      }
      if (isset($a)) $r['@'] = $a;    // Attributes
      return $r;
    }
    return (string) $xml;
  }
}


