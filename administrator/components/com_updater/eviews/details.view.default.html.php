<?php
  /* SVN FILE: $Id: details.view.default.html.php 162 2010-04-07 04:22:50Z ntemple $*/
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
  * @version SVN: $Id: details.view.default.html.php 162 2010-04-07 04:22:50Z ntemple $
  * 
  */

  defined('_JEXEC') or die();
  require_once('javascript.php');
  $pkg = $this->package;
  $pkg->remote();
  //  print "<pre>\n";
  //  print_r($pkg);
  //  print "</pre>\n"
?>

<?php
  $zoneid = 4;
  @include('zone.partial.php');
?>
<div class="col50">
  <fieldset class="adminform">
    <legend>Software Details</legend>

    <table class="admintable" cellspacing="1">
      <tr>
        <td width="100%" class="key">
          <?php echo JText::_( 'Package' ) ?>
        </td>
        <td>
          <?php echo $pkg->name; ?>
        </td>				
      </tr>
      <tr>
      <td width="100%" class="key">
        <?php echo JText::_( 'Title' ) ?>
      </td>
      <td><?php echo $pkg->title ?></td>
      <tr>
        <td width="100%" class="key"><?php echo JText::_( 'Version' ) ?></td>
        <td><?php echo $pkg->version ?></td>
      </tr>
      <tr>
        <td width="100%" class="key"><?php echo JText::_( 'Stability' ) ?></td>
        <td><?php echo $pkg->getStabilityHTML();  ?>
      </tr>

      <tr>
        <td width="100%" class="key"><?php echo JText::_( 'Links' ) ?></td>
        <td><?php
            $links = array();

            if ($pkg->link_support) { $links[] = "<a href='$pkg->link_support' target='ex'>Support</a>";}
            if ($pkg->link_extdir)  { $links[] = "<a href='$pkg->link_extdir' target='ex'>J.E.D.</a>";}
            if ($pkg->link_home)    { $links[] = "<a href='$pkg->link_home' target='ex'>Project Home</a>";}
            $links[] = "<a href='http://www.intellispire.com/web/index.php?option=com_isndisplay&sku={$pkg->sku}' target='ex'>Intellispire Extension Directory</a>";

            echo implode (" &raquo; ", $links);
          ?>
        </td>
      </tr>

      <tr>
        <td width="100%" class="key"><?php echo JText::_( 'Action' ) ?></td>
        <td><?php $buyinstall = true; include('action.partial.html.php') ?></td>             
      </tr>
      <?php if ($pkg->credits > 0) { ?>
        <tr>
          <td width="100%" class="key"><?php echo JText::_( 'Price' ) ?></td>
          <td>$<?php echo $pkg->credits; ?></td>
        </tr>
        <?php } ?>

    </table>
  </fieldset>
</div>

<div class="col50">
  <fieldset class="adminform">
    <legend>Description</legend>
    <table class="adminlist">
      <tr class="row0">
        <td><?php echo $pkg->description ?></td>
      </tr>
    </table>
  </fieldset>
</div>
<form action="index.php" method="post" name="adminForm">
  <input type="hidden" name="option"   value="com_updater">
  <input type="hidden" name="task">
  <input type="hidden" name="redirect">
  <input type="hidden" name="package">
  <input type="hidden" name="itype"">
</form>

<?php
  $zoneid = 3;
  @include('zone.partial.php');
?>
