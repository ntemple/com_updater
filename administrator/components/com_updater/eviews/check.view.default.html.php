<?php
  /* SVN FILE: $Id: check.view.default.html.php 159 2010-04-06 18:49:43Z ntemple $*/
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
  * @version SVN: $Id: check.view.default.html.php 159 2010-04-06 18:49:43Z ntemple $
  *
  */

  defined('_JEXEC') or die();


  $out = '';
  foreach ($file_errors as $file) {
    $out  .= $file['stat'] . "\n";
  }
  
?>
<div class="col50">

  <fieldset class="adminform">
  <legend>Unwriteable Files</legend>
  <p>&nbsp;</p>
  <p>These files have been detected as unwriteable. 
  <p>If <b>any</b> file that will be upgraded (except configuration.php, which is never overwritten) appears below, then the
  updater <i>may</i> <u>fail</u> with a <b>core -1 error</b> when attempting to upgrade your site. </p>
  <form>
    <textarea rows='20' cols='80'><?php echo $out ?></textarea>
  </form>

  <p>Please Note: File permissions reported above may be incorrect on Windows systems. Please see <a href="
  http://bugs.php.net/bug.php?id=27609"i target=_blanl>This thread</a> and <a href="http://bugs.php.net/bug.php?id=44859" target =_blank">this one.</a> Fixed in php 5.3?
  </p>

</div>
