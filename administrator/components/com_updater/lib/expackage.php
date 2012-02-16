<?php
/**
* @version        $Id:plugin.php 6961 2007-03-15 16:06:53Z tcp $
* @package        JPackageMan
* @subpackage    Installer
* @copyright    Copyright (C) 2007 Toowoomba City Council/Sam Moffatt
* @copyright     Copyright (C) 2005-2007 Open Source Matters (Portions)
* @license        GNU/GPL, see LICENSE.php
* Joomla! is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

if (!defined('EXPACKAGE_MANIFEST_PATH'))
{
  define('EXPACKAGE_MANIFEST_PATH', JPATH_ADMINISTRATOR . '/components/com_updater/manifest' );
}
require_once(JPATH_ADMINISTRATOR . '/components/com_updater/lib/installer/helper.php');


/**
* PackagePackage installer
*
* @package      JPackageMan
* @subpackage   Installer
* @since        1.5
*/
class JInstallerExpackage extends JObject
{
  /**
  * Constructor
  *
  * @access    protected
  * @param    object    $parent    Parent object [isJInstaller instance]
  * @return    void
  * @since    1.5
  */
  function __construct(&$parent)
  {
    $this->parent =& $parent;
  }


  /**
  * Custom install method
  *
  * @access    public
  * @return    boolean    True on success
  * @since    1.5
  */
  function install()
  {
    global $installresults;

    $installresults = array();
    $out = array();
    // Get the extension manifest object
    $manifest =& $this->parent->getManifest();
    $this->manifest =& $manifest->document;

    $manifestpath = $this->parent->getPath('manifest');  
    $sigpath      = str_replace('.xml', '.sig', $manifestpath);
    $mf = new JEXPackageManifest($manifestpath);
    
    
    switch($mf->verify) {
      case 'good':    $msg = "Package {$mf->name} verified with openssl. Installing."; break;
      case 'unknown': 
      case 'error':   $msg = "Package {$mg->name} could not be checked. To verify, please install openssl. Installing."; break;
      case 'bad':     $this->parent->abort(JText::_('Package').' '.JText::_('Install').' Failed: '.JText::_('Signature mismatch.'));
    }

    $installresults['verify'] = $mf->verify;
    $installresults['check']  = $msg;
    $out[] = "<div class='msg'>$msg</div>";
          
    # TODO verify signature

    /**
    * ---------------------------------------------------------------------------------------------
    * Manifest Document Setup Section
    * ---------------------------------------------------------------------------------------------
    */
    // Set the extensions name    

    $name = $mf->name;
    $name = JFilterInput::clean($name, 'cmd');
    $this->set('name', $name);
    $description = $mf->description;

    /**
    * Load the local installer class
    */
    // If there is an install file, lets load it.
    $installScriptElement =& $this->manifest->getElementByPath('installfile');
    $installClass = $name . 'PkgInstallHooks';
    if (is_a($installScriptElement, 'JSimpleXMLElement'))
    {
      if (!class_exists($installClass, false))
      {
        include_once($this->parent->getPath('source').DS.$installScriptElement->data());
        call_user_func(array($installClass, 'onLoad'));
      }
    }

    if (class_exists($installClass))
    {
      call_user_func(array($installClass, 'beforeInstall'));
    }

    /**
    * ---------------------------------------------------------------------------------------------
    * Filesystem Processing Section
    * ---------------------------------------------------------------------------------------------
    */    
    $element =& $this->manifest->getElementByPath('files');

    if (is_a($element, 'JSimpleXMLElement') && count($element->children()))
    {

      if ($folder = $element->attributes('folder'))
      {
        $source = $this->parent->getPath('source').DS.$folder;
      }
      else
      {
        $source = $this->parent->getPath('source');
      }

      foreach ($mf->filelist as $oFile) {
        /** @var JEXExtension */
        $oFile = $oFile;
        $file = $source . DS . $oFile->filename;;
        $dest  = $oFile->dest;

        $md5 = md5(file_get_contents($file));
        if ($md5 != $oFile->md5) {
          $out[] = "<div class='msg'>Skipping File: " . basename($file) . " signature does not match.</div>";
          continue;
        }

        if ($dest)
        {
          isJInstallerHelper::extract($file, $dest, true);
          $out[] = "<div class='msg'>Installed: " . basename($file) . " ($md5)</div><br>\n";
          continue;// we're done with the tarball
        }

        $package = isJInstallerHelper::unpack($file); 
        if (!$package) {
          $out = "<div class='msg'>Can't extract package: " . basename($file) . ". file skipped.";
          continue;
        }

        // Use core installer                        
        $installer =& JInstaller::getInstance();
        $success = $installer->install($package['dir']);
        $msg = "<div class='msg'>Installed: " . basename(basename($file)) . " Signature good: ($md5)</div><br>\n";
        if ($installer->message) $msg .= $installer->message;     
        $out[] = $msg;  

        if (!is_file($package['packagefile'])) {
          $package['packagefile'] = $config->getValue('config.tmp_path').DS.$package['packagefile'];
        }
        JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
        // End core Installer

        if (!$success)
        {
          $out[] = JText::_('Package').' '.JText::_($install_type).': '. JText::_('There was an error installing an extension:' ) . basename($file); 
        }
        $out[] = "<hr>\n";
      }   // foreach
    }
    else
    {
      // Do Nothing - nothing to install
    }


    /**
    * ---------------------------------------------------------------------------------------------
    * Finalization and Cleanup Section
    * ---------------------------------------------------------------------------------------------
    */
    if (class_exists($installClass))
    {
      call_user_func(array($installClass, 'afterInstall'));
    }
    // Lastly, we will copy the manifest file to its appropriate place.    
    @copy($manifestpath, EXPACKAGE_MANIFEST_PATH . '/' . basename($manifestpath));
    @copy($sigpath, EXPACKAGE_MANIFEST_PATH . '/' . basename($sigpath));
    
       
    $msg ='';
    foreach ($out as $txt) {
      $msg .= "<p class='out'>$txt</p>\n";      
    }
    $this->parent->message = $msg;
    $installresults['out'] = $out;
    $installresults['msg'] = $out;
    
    return true;
  }

  function uninstall()
  {
    return false;  // Currently, we cannot uninstall packages
  }
   
}

if (!class_exists('JPkgInstallHooks')) {
  class JPkgInstallHooks extends JObject
  {

    static function onLoad()
    {
      return true;
    }

    static function beforeInstall()
    {
      return true;
    }

    static function afterInstall()
    {
      return true;
    }

    static function writelog() {
      return false;
    }
  }
}

if (!class_exists('JEXPackageManifest'))
{
  class JEXPackageManifest extends JObject
  {
    var $name  = '';
    var $creationDate  = '';
    var $author  = '';
    var $authorEmail  = '';
    var $authorUrl  = '';
    var $copyright  = '';
    var $version  = '';
    var $packager  = '';
    var $packagerurl  = '';
    var $certificate  = '';
    var $component  = '';
    var $files  = '';
    var $installfile = '';
    var $sig;
    var $verify = 0;

    var $filelist = Array();
    var $manifest_file = '';



    function __construct($xmlpath='')
    {
      if (strlen($xmlpath))
      {
        $this->loadManifestFromXML($xmlpath);
      }
    }

    function loadManifestFromXML($xmlfile)
    {
      $this->manifest_file = JFile::stripExt(basename($xmlfile));
      $xml = JFactory::getXMLParser('Simple');

      if (!$xml->loadFile($xmlfile))
      {
        $this->_errors[] = 'Failed to load XML File: ' . $xmlfile;
        return false;
      }
      else
      {        
        $xml = $xml->document;        

        $this->name = $xml->name[0]->data();
        $this->author = $xml->author[0]->data();
        $this->authorEmail = $xml->authorEmail[0]->data();
        $this->authorUrl = $xml->authorUrl[0]->data();
        $this->copyright = $xml->copyright[0]->data();
        $this->version = $xml->version[0]->data();
        $this->packager = $xml->packager[0]->data();
        $this->packagerurl = $xml->packagerurl[0]->data();
        $this->certificate = $xml->certificate[0]->data();
//      $this->component = $xml->component[0]->data();     @todo: cleanup and document package format
        $this->description = $xml->description[0]->data();

        if (isset($xml->files[0]->file) && count($xml->files[0]->file))
        {
          foreach ($xml->files[0]->file as $file)
          {
            $this->filelist[] = new JEXExtension($file);
          }
        }

        // Verify the package signature        
        $sigpath      = str_replace('.xml', '.sig', $xmlfile);
        $signature    = @file_get_contents($sigpath);        
        $this->verify = 'unknown';
        if (function_exists('openssl_verify')) {
          $data = @file_get_contents($xmlfile);
          $cert = @file_get_contents(JPATH_ADMINISTRATOR . '/components/com_updater/certs/' . $this->certificate);
          $pubkeyid = openssl_get_publickey($cert);
          $ok = openssl_verify($data, base64_decode($signature), $pubkeyid);
          openssl_free_key($pubkeyid);          

          if ($ok == 1) {
            $this->verify = "good";
          } elseif ($ok == 0) {
            $this->verify = 'bad';
          } else {
            $this->verify = 'error';
          }
/*          
          $this->data = $data;
          $this->cert = $cert;
          $this->signature = $signature;
          $this->pubkeyid = $pubkeyid;
*/          
        }
        return true;
      }
    }

  }

  class JEXExtension extends JObject
  {
    var $filename = '';
    var $md5;
    var $version;
    var $jid;
    var $dest;


    function __construct($element=null)
    {
      if ($element && is_a($element, 'JSimpleXMLElement'))
      {
        $this->md5 = $element->attributes('md5');
        $this->jid = $element->attributes('jid');
        $this->version = $element->attributes('version');
        $this->dest = $element->attributes('dest');
        $this->filename = $element->data();
      }
    }
  }
}

/* Example Manifest */
/*
name
creationDate
author
authorEmail
copyright
authorUrl
version
packager
packagerurl
certificate
component
files

<install type="expackage" version="1.5" method="upgrade">
<name>comprofiler</name>
<creationDate>2010-03-04</creationDate>
<author>Beat and JoomlaJoe</author>
<authorEmail>beat@joomlapolis.com</authorEmail>
<copyright>Copyright 2004-2010 Beat, MamboJoe/JoomlaJoe and CB team on joomlapolis.com . This component is released under the GNU/GPL version 2 License and parts under Community Builder Free License. All copyright statements must be kept and derivate work must prominently duly acknowledge original work on web interface and on website where downloaded.</copyright>
<authorUrl>www.joomlapolis.com</authorUrl>
<version>1.2.2</version>
<packager>Intellispire Network</packager>
<packagerurl>http://www.intellispire.com/</packagerurl>
<certificate>intellispire.pem</certificate>
<component>com_comprofiler.zip</component>
<description><![CDATA[Joomla/Mambo Community Builder 1.2.2 native for Joomla! 1.5.3 - 1.5.15, 1.0.0 - 1.0.15 and Mambo 4.5.0 - 4.6.5.]]></description>
<files>
<file md5='2d74385ffb9f1a96879b3d1f790388e0' version='1.2.2' jid='module_cblogin'>mod_cblogin.zip</file>
<file md5='4f9c9972764ce95ada5ef1e73495dfad' version='1.2.2' jid='module_cbworkflows'>mod_comprofilerModerator.zip</file>
<file md5='8f5a55cf4e009dc0da1eed9a48330327' version='1.2.2' jid='module_cbonline'>mod_comprofilerOnline.zip</file>
<file md5='acfecd0da99f69441d9f4f31679c9050' version='1.2.2' jid='component_comprofiler'>com_comprofiler.zip</file>
</files>
</install>
*/

