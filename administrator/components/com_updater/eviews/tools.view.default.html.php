<?php
  /* SVN FILE: $Id: tools.view.default.html.php 164 2010-04-16 16:16:06Z ntemple $*/
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
  * @version SVN: $Id: tools.view.default.html.php 164 2010-04-16 16:16:06Z ntemple $
  *
  */

  defined('_JEXEC') or die();
  

  if ( defined('_JLEGACY')) {  $mode = 'Off';  } else { $mode = 'On'; }
  $diagnostics = array(
  'Updater Version' => UPDATER_HVERSION . "-" . UPDATER_REVISON . "(". UPDATER_VERSION . ")",
  'CB Installed' => getComponent('com_comprofiler'),
  'VirtueMart Installed' => getComponent('com_virtuemart'),
  'FTP Mode' => UPDATER_FTP_ENABLED,
  'Legacy' => $mode,
  'File 1' => $finfo1['stat'],
  'File 2' => $finfo2['stat'],
  'Core'   => $finfo5['stat'],
  'Temp' => $finfo3['stat'],
  'Site' => $finfo4['stat'],
  );


  $wanted = array('PHP Version', 'System', 'Server API',
  'allow_url_fopen', 'memory_limit', 'register_globals',
  'cURL support',
  'Client API version', 'MYSQL_MODULE_TYPE', 'MYSQLI_SOCKET',
  'PDO drivers', 'session.save_path'
  );

  foreach ($info as $module => $array) {
    foreach ($wanted as $name) {
      if (isset($array[$name])) $diagnostics[$name] = $array[$name];
    }
  }

  $out = '';
  foreach ($diagnostics as $name => $value) {
    $out .= "$name: $value\n";
  }

 
  
?>
<div class="col50">
  <fieldset class="adminform">
    <legend>Basic Diagnostics</legend>
    <p>
    When you contact support, please copy &amp; paste the data below into your
    support ticket - this will help us to more easily diagnose any issues.</p>
    <form>
      <textarea rows='20' cols='80'><?php echo $out ?></textarea>
    </form>
  </fieldset>

</div>
