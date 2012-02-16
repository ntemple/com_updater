<?php
/**
* @version    $Id: helper.php 16 2009-08-31 21:45:04Z ntemple $
* @package    Joomla.Framework
* @subpackage  Installer
* @copyright  Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
* @copyright  Copyright (C) 2008 Intellispire.
* @license    GNU/GPL, see LICENSE.php
* Joomla! is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.path');
jimport('joomla.filesystem.archive');

// require_once('pclzip.lib.php');
require_once('archive_tar.php');

/**
* Installer helper class - heavily modified.
*
* @static
* @author    Louis Landry <louis.landry@joomla.org>
* @author    Nick Temple <nick@intellispire.com>
* @package    Joomla.Framework
* @subpackage  Installer
* @since    1.5
*/
class isJInstallerHelper
{

  /**
  * Installs a package
  */
  function installPackage($url, $update = false, $downloadonly)
  {

      // Download the package at the URL given
      try {
          $p_file = isJInstallerHelper::downloadPackage($url);
      }
      catch(Exception $e) {
          JError::raiseWarning($e->getCode(), $e->getMessage());
          return false;
      }

      if ($downloadonly) return true; // we're done

      $config =& JFactory::getConfig();
      $tmp_dest       = $config->getValue('config.tmp_path');

      // Unpack the downloaded package file
      $package = isJInstallerHelper::unpack($tmp_dest.DS.$p_file);
      if (! $package) {
        JError::raiseWarning("-2", "Could not unpack file: " . basename($url));
        return false;
      }
      
      $installer = new isJInstaller();
      // never static

      $result = false;

      try {
          if ($update) {
              $installer->setOverwrite(true);
              $result = $installer->update($package['dir']);
          } else {
              $result = $installer->install($package['dir']);
          }
      }
      catch(Exception $e) {
          // Do nothing on error - result is false
      }

      // cleanup
      isJInstallerHelper::cleanupInstall($package['packagefile'], $package['dir']);
      // JFile::delete($tmp_dest.DS.$p_file);

      $msg = '';

      if (!$result) {
          // There was an error installing the package
          $msg .= JText::sprintf('INSTALLEXT', JText::_($package['type']), JText::_('Error'));
      } else {
          // Package installed sucessfully
          $msg .= JText::sprintf('INSTALLEXT', JText::_($package['type']), JText::_('Success'));
      }

      return $result;
  }


  /**
  * @static
  * @param string URL of file to download
  * @param string Download target filename [optional]
  * @return mixed Path to downloaded package or boolean false on failure
  * @since 1.5
  *
  * NOTE: in the standard installer, redirects are followed.
  * In this version, redirects are not explicitly supported.
  *
  * In addition, we assume the filename is the one given to us,
  * with or without additional paramaters.
  *
  * These requirements are met by the server.
  *
  */

  static function downloadPackage($url, $target = false) {

      if (!$url) {
        throw new Exception("Cannot find download location for package.");
      }

      // parse the filename
      $file = parse_url($url, PHP_URL_PATH);
      $file = basename($file);
      list($fname, $ext) = self::splitFilename($file);
      $fname  = preg_replace("/[^a-z0-9-]/", "-", strtolower($fname));
      $ext    = preg_replace("/[^a-z0-9-]/", "-", strtolower($ext));
      $filename = $fname . '.' . $ext;

      switch ($ext) {
      case 'tar':
      case 'gz':
      case 'zip':
      case 'bz2':
      case 'tgz':
          break;
      default:
          throw new Exception('Error: unsupported filetype: ' . $ext );
      }

      if (! $target) {
          // Does it make sense to gernerate a tmpname for this?
          $config =& JFactory::getConfig();
          $target =  $config->getValue('config.tmp_path').DS. $filename;
      }

      $buffer = self::url_retrieve_curl($url);

      // sanity check, no packages should be less than 1k
      if (! isset($buffer) ) {
          throw new Exception('Could not download package file, buffer is empty.');
      }

      // Write buffer to file
      // TODO: check for error - need docs on JFile::write
      $ret = JFile::write($target, $buffer);
      if ($ret != strlen($buffer)) {
          throw new Exception('Buffer write failure:' . $target);
      }

      return $filename;
  }

  static function safe_ini_get($string) {
      $value = strtolower(ini_get($string));

      switch ($value) {
      case 'on':
      case '1':
      case 'yes':
      case 'true':
          return 1;
          default:
          return 0;
      }
  }

  static function splitFilename($filename)
  {
      $pos = strrpos($filename, '.');
      if ($pos === false) {
          // dot is not found in the filename
          return array($filename, '');
          // no extension
      } else {
          $basename = substr($filename, 0, $pos);
          $extension = substr($filename, $pos+1);
          return array($basename, $extension);
      }
  }


  static function url_retrieve_curl($url, $timeout = 30) {

      if (! function_exists('curl_version')) {
          throw new Exception('Curl not loaded, cannot retrieve file.');
      }

      $ch = curl_init();
      $timeout = $timeout;
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

      // Getting binary data
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
      $contents = curl_exec($ch);
      curl_close($ch);

      return $contents;
  }

  /**
  *  Better archiver than is available in the core.
  */
  static function archive_extract($archivename, $extractdir)
  {
    // guess type based on $archivename
    $parts = explode('.', $archivename);
    $ext = strtolower(array_pop($parts));

    if ($ext == 'zip') {
//      if (defined('UPDATER_FORCECOPY') && constant('UPDATER_FORCECOPY') ) {
           // Internal Joomla archiver.  Eats RAM.
           return JArchive::extract($archivename, $extractdir);
//      }
/*
      // Trying pclZip for the extraction.
      $archive = new PclZip($archivename);
      $result = $archive->extract($extractdir);
      if ($result)
        return true;
      else
        return false;
*/
    } else {
        // A a tar, tgz, gz or bz2 file

        // Tar.php is many times faster, and can actually handle
        // large archives (like Joomla.tgz itself), where the built-in
        // Joomla! archiver blows chunks and may never return
        $archive = new ISArchive_Tar($archivename);
        return $archive->extract($extractdir);
    }

  }

  /**
  * UPDATER SPECIFIC
  *
  * Extracts a file and copies the results to a specific directory
  * Supports .gz .tar .tar.gz and .zip
  *
  * WARNING: There is always the possibility to overwrite the
  * wrong stuff. Be VERY CAREFUL.
  *
  * @static
  * @param string $filename The uploaded archive filename
  * @param string $path     The base path to copy the files
  * @return boolean True on success, False on error
  * @since 1.5.6
  */
  static function extract($p_filename, $path, $overwrite = true)
  {
    /* If we forcecopy, then we extract to a temp directory,
       then copy the files over to the new directory.
    */
    // if (defined('UPDATER_FORCECOPY') && constant('UPDATER_FORCECOPY') ) {

      // Path to the archive
      $archivename = $p_filename;

      // Temporary folder to extract the archive into
      $tmpdir = uniqid('extract_');

      // Clean the paths to use for archive extraction
      $extractdir = JPath::clean(dirname($p_filename).DS.$tmpdir);
      $archivename = JPath::clean($archivename);

      // do the unpacking of the archive
      $result = self::archive_extract($archivename, $extractdir);

      if ($result === false ) {
          return false;
      }

      JFolder::copy($extractdir, JPATH_ROOT . DS . $path, null, $overwrite);
      JFolder::delete($extractdir);
      $result = true;

    // } else {
    //   // Otherwise we just extract directly to the  directory.
    //   $result = self::archive_extract($p_filename, JPATH_ROOT . DS . $path);
    // }

    return $result;
  }



  /**
  * Unpacks a file and verifies it as a Joomla element package
  * Supports .gz .tar .tar.gz and .zip
  *
  * @static
  * @param string $p_filename The uploaded package filename or install directory
  * @return boolean True on success, False on error
  * @since 1.5
  */
  static function unpack($p_filename)
  {
    // Path to the archive
    $archivename = $p_filename;

    // Temporary folder to extract the archive into
    $tmpdir = uniqid('install_');

    // Clean the paths to use for archive extraction
    $extractdir = JPath::clean(dirname($p_filename).DS.$tmpdir);
    $archivename = JPath::clean($archivename);

    // do the unpacking of the archive
    $result = self::archive_extract($archivename, $extractdir);

    if ($result === false ) {
        return false;
    }

    /*
    * Lets set the extraction directory and package file in the result array so we can
    * cleanup everything properly later on.
    */
    $retval['extractdir'] = $extractdir;
    $retval['packagefile'] = $archivename;

    /*
    * Try to find the correct install directory.  In case the package is inside a
    * subdirectory detect this and set the install directory to the correct path.
    *
    * List all the items in the installation directory.  If there is only one, and
    * it is a folder, then we will set that folder to be the installation folder.
    */
    $dirList = array_merge(JFolder::files($extractdir, ''), JFolder::folders($extractdir, ''));

    if (count($dirList) == 1) {
        if (JFolder::exists($extractdir.DS.$dirList[0])) {
            $extractdir = JPath::clean($extractdir.DS.$dirList[0]);
        }
    }

    /*
    * We have found the install directory so lets set it and then move on
    * to detecting the extension type.
    */
    $retval['dir'] = $extractdir;

    /*
    * Get the extension type and return the directory/type array on success or
    * false on fail.
    */
    if ($retval['type'] = isJInstallerHelper::detectType($extractdir)) {
        return $retval;
    } else {
        return false;
    }
  }

  /**
  * Method to detect the extension type from a package directory
  *
  * @static
  * @param string $p_dir Path to package directory
  * @return mixed Extension type string or boolean false on fail
  * @since 1.5
  */
  static function detectType($p_dir)
  {
      // Search the install dir for an xml file
      $files = JFolder::files($p_dir, '\.xml$', 1, true);

      if (count($files) > 0) {

          foreach($files as $file)
          {
              $xmlDoc = & JFactory::getXMLParser();
              $xmlDoc->resolveErrors(true);

              if (!$xmlDoc->loadXML($file, false, true)) {
                  // Free up memory from DOMIT parser
                  unset($xmlDoc);
                  continue;
              }
              $root = & $xmlDoc->documentElement;
              if (!is_object($root) || ($root->getTagName() != "install" && $root->getTagName() != 'mosinstall')) {
                  unset($xmlDoc);
                  continue;
              }

              $type = $root->getAttribute('type');
              // Free up memory from DOMIT parser
              unset($xmlDoc);
              return $type;
          }

          JError::raiseWarning(1, JText::_('ERRORNOTFINDJOOMLAXMLSETUPFILE'));
          // Free up memory from DOMIT parser
          unset($xmlDoc);
          return false;
      } else {
          JError::raiseWarning(1, JText::_('ERRORNOTFINDXMLSETUPFILE'));
          return false;
      }
  }

  /**
  * Gets a file name out of a url
  *
  * @static
  * @param string $url URL to get name from
  * @return mixed String filename or boolean false if failed
  * @since 1.5
  */
  static function getFilenameFromURL($url)
  {
      if (is_string($url)) {
          $parts = explode('/', $url);
          return $parts[count($parts) - 1];
      }
      return false;
  }

  /**
  * Clean up temporary uploaded package and unpacked extension
  *
  * @static
  * @param string $p_file Path to the uploaded package file
  * @param string $resultdir Path to the unpacked extension
  * @return boolean True on success
  * @since 1.5
  */
  static function cleanupInstall($package, $resultdir)
  {
    $config =& JFactory::getConfig();

    // Does the unpacked extension directory exist?
    if (is_dir($resultdir)) {
        JFolder::delete($resultdir);
    }
    // Is the package file a valid file?
    if (is_file($package)) {
        JFile::delete($package);
    } else if (is_file(JPath::clean($config->getValue('config.tmp_path').DS.$package))) {
        // It might also be just a base filename
        JFile::delete(JPath::clean($config->getValue('config.tmp_path').DS.$package));
    }
  }

  /**
  * Splits contents of a sql file into array of discreet queries
  * queries need to be delimited with end of statement marker ';'
  * @param string
  * @return array
  */
  static function splitSql($sql)
  {
      $db =& JFactory::getDBO();
      return $db->splitSql($sql);
  }

}
