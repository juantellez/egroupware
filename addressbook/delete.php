<?php
  /**************************************************************************\
  * phpGroupWare - addressbook                                               *
  * http://www.phpgroupware.org                                              *
  * Written by Joseph Engo <jengo@phpgroupware.org>                          *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

  /* $Id$ */

  if ($confirm) {
     $phpgw_flags = array("noheader" => True, "nonavbar" => True);
  }

  $phpgw_flags["currentapp"] = "addressbook";
  include("../header.inc.php");
  
  if (! $con) {
     Header("Location: " . $phpgw->link($phpgw_info["server"]["webserver_url"] . "/addressbook/"));
  }

  if ($confirm != "true") {
     $phpgw->db->query("select owner from addressbook where con='$con'");
     $phpgw->db->next_record();

     if ($phpgw->db->f("owner") != $phpgw->session->loginid)
        Header("Location: " . $phpgw->link($phpgw_info["server"]["webserver_url"] . "/addressbook/"));

     ?>
        <body bgcolor=FFFFFF aLink=0000EE link=0000EE vlink=0000EE>
        <center><?php echo lang_common("Are you sure you want to delete this entry ?"); ?><center>
        <br><center><a href="<?php 
          echo $phpgw->link("view.php","&con=$con&order=$order&sort=$sort&filter=$filter&start=$start&query=$query");
          ?>"><?php echo lang_common("NO"); ?></a> &nbsp; &nbsp; &nbsp; &nbsp;
        <a href="delete.php?sessionid=<?php
            echo $phpgw->session->id . "&con=$con&confirm=true&order=$order&sort="
	       . "$sort&filter=$filter&start=$start&query=$query"; 
            ?>"><?php echo lang_common("YES"); ?></a><center>
     <?php

     //exit;
  } else {

     $phpgw->db->query("delete from addressbook where owner='" . $phpgw->session->loginid
		    . "' and con='$con'");
     Header("Location: " . $phpgw->link($phpgw_info["server"]["webserver_url"]. "/addressbook/",
	    "cd=16&order=$order&sort=$sort&filter=$filter&start=$start&query=$query"));
  }


