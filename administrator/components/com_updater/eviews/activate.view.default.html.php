<?php
/* SVN FILE: $Id: activate.view.default.html.php 150 2010-04-02 12:03:24Z ntemple $*/
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
 * @version SVN: $Id: activate.view.default.html.php 150 2010-04-02 12:03:24Z ntemple $
 * 
 */
  
// no direct access
defined('_JEXEC') or die('Restricted access'); 
?>
<table><tr><td align="top">
<fieldset class="adminform" alin="top">
     <legend>Software Activation</legend>
(Never registered? Signup on the right)<br>

<p>In order to download software, you must have created an Intellispire Network Id.  
<p>You received your Network Id in your email when you registered, below..
<p>If you already have an Intellispire Network Id, please enter yoru email and network id, below:

<form action="index.php" method="post">
<input type="hidden" name="option" value="com_updater">
<input type="hidden" name="task"   value="activate">
<table>
<tr><td>Email Address:</td><td><input type="text"   name="email" size="24" value="<?php echo $email ?>"></td><tr>
<tr><td>Network ID:</td><td><input type="text"      name="isnid" size="24"></td><td></tr>
<tr><td>&nbsp;</td><td>(XXX-XXXXX-N1-XXXXXXXX)</td></tr>
</table>
<br>
<input type="submit" value="Activate">
<br>
</form>
<p>&nbsp;</p>
</fieldset>
</td>

<td align="top"> 
<fieldset class="adminform">
     <legend>Sign Up for Your Id</legend>

     <p>If you do not have an Intellispire Network Id (or need it resent to your email address), 
<p>enter your name and email address, below. 
<p>Your id will be created then emailed to you. 

<p>Once you receive the id, use the form above to activate this copy of the software installer.

<form action="index.php" method="post">
<input type="hidden" name="option" value="com_updater">
<input type="hidden" name="task"   value="register">
<table>
<tr><td>Name:</td><td><input type="text"    name="name"  value="<?php echo $name; ?>"></td></tr>
<tr><td>Email:</td><td><input type="text"   name="email" value="<?php echo $email ?>"></td></tr>
</table>
<br>
<input type="submit" value="Register">
<br>
</form>
<p><b>Please Note:</b> you only need one Network Id to activate all of your websites.
</fieldset>
</td></tr></table>

