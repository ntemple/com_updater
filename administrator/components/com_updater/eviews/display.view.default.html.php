<?php
  /* SVN FILE: $Id: display.view.default.html.php 162 2010-04-07 04:22:50Z ntemple $*/
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
  * @version SVN: $Id: display.view.default.html.php 162 2010-04-07 04:22:50Z ntemple $
  *
  */

  // no direct access
  defined('_JEXEC') or die('Restricted access');
  require_once('javascript.php');
  
  global $isnid1;
  
  /*
  print "<pre>\n";
  print_r($this->manifest);
  print "</pre>\n";
  */
  
  $zoneid = 2;
  @include('zone.partial.php');

  JHTML::_('behavior.tooltip');
  jimport( 'joomla.html.pane');
  $pane =& JPane::getInstance('Tabs');

?>
<br>                                                                    
<form action="index.php" method="post" name="adminForm">
  <input type="hidden" name="option"   value="com_updater">
  <input type="hidden" name="task">
  <input type="hidden" name="redirect">
  <input type="hidden" name="package">
  <input type="hidden" name="itype">

  <?php  

    echo $pane->startPane('comUpdaterDisplay');
    foreach ($this->manifest->channels as $channel) {
      echo $pane->startPanel( $channel->name, $channel->channel );
      updaterDisplay($channel->packages);
      echo $pane->endPanel();
    }
    echo $pane->endPane();         
  ?>

</form>    

<br>
<br>

<div align="right">com_updater <?php echo UPDATER_HVERSION ?> : <?php echo $isnid1 ?></div>
<?php

  // ============================================================
  function updaterDisplay($items) {
    $ext = installedExtensions::getInstance();

  ?>
  <table class="adminlist">
    <thead>
      <tr>
        <th width="10"> # </th>
        <th width="10">&nbsp;</th>
        <th width="*">
          <?php echo JText::_( 'Program' ); ?>
        </th>
        <th width = "90">
          <?php echo JText::_( 'Release Date' ); ?>
        </th>
        <th width = "90">
          <?php echo JText::_( 'Latest Version' ); ?>
        </th>
        <th width = "90">
          <?php echo JText::_( 'Your Version' ); ?>
        </th>
        <th width = "90">
          <?php echo JText::_( 'Compatibility' ); ?>
        </th>
        <th width = "90">
          <?php echo JText::_( 'Stability'); ?>
        </th>
        <th width = "100">
          <?php echo JText::_( 'Action' ); ?>
        </th>
<!--        
        <th width = "50">
          <?php echo JText::_( '' ); ?>
-->          
        </th> 
      </tr>
    </thead>
    <tfoot>
      <tr width="100%">
        <td colspan="10">&nbsp;
          <?php /*    
            <del class="container"><div class="pagination">
            <?php print $pager->getListFooter(); ?>
            </div></del>
          */ ?>  
        </td>
      </tr>
    </tfoot>
    <tbody>
      <?php
        $i = 0;
        foreach($items as $row) {
          $package = $row->sku;
          $pkg = $row;
          $i++;
          $k = $i % 2;
          // TODO: class could be based on status
        ?>
        <tr class="<?php echo "row$k"; ?>">
          <td><?php echo $i ?></td>
          <td><input type="checkbox" name="packages[]" value="<?php echo $package ?>"></td>
          <td>
            <a href="index.php?option=com_updater&task=details&package=<?php echo $package ?>"><?php echo $row->title; ?></a> <!-- ( <?= $row->jname ?>) --> 
          </td>
          <td align="center">
            <?php echo $row->releasedate; ?>
          </td>
          <td align="center">
            <?php echo $row->version; ?>
          </td>
          <td align="center">
            <?php echo $ext->getVersion($row->jname); ?>
          </td>
          <td align="center">
            <?php
              $compat = explode(',', $row->compatibility); // new for 1.2
              foreach ($compat as $c)
              switch($c) {
                case 'j15native': echo "<img src='components/com_updater/images/compat_15_native.png'><br>"; break;
                case 'j15legacy': echo "<img src='components/com_updater/images/compat_15_legacy.png'><br>"; break;
              }
            ?>
          </td>
          <td align="center">   
            <?php echo $row->getStabilityHTML(); ?>
          </td>
          <td align="center">
            <?php include('action.partial.html.php') ?>
          </td>
<!--          
          <td align="center"><a href="http://www.intellispire.com/web/index.php?option=com_chronocontact&chronoformname=newswversion&packageid=<?php echo $package?>"
              target=_blank><img src='components/com_updater/images/16px-Crystal_Clear_action_find.png'
                title="Click here if you've spotted a newer version!" height="16" width="16"></a>
          </td>
-->          
        </tr>        

        <?php
        }
      ?>
    </tbody>
  </table>

  <?php 
}