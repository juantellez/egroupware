<?php
  /**************************************************************************\
  * phpGroupWare                                                             *
  * http://www.phpgroupware.org                                              *
  * The file written by Joseph Engo <jengo@phpgroupware.org>                 *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

  /* $Id$ */

  if (!isset($sessionid) || !$sessionid) {
     Header("Location: login.php");
     exit;
  }

  $phpgw_info["flags"] = array("noheader" => True, "nonavbar" => True, "currentapp" => "home",
                               "enable_message_class" => True, "enable_calendar_class" => True, 
                               "enable_todo_class" => True, "enable_addressbook_class" => True
                               );
  include("header.inc.php");
  // Note: I need to add checks to make sure these apps are installed.

  if ($cd=="yes" && $phpgw_info["user"]["preferences"]["common"]["default_app"]
      && $phpgw_info["user"]["apps"][$phpgw_info["user"]["preferences"]["common"]["default_app"]]) {
     $phpgw->redirect($phpgw->link($phpgw_info["server"]["webserver_url"] . "/"
		  . $phpgw_info["user"]["preferences"]["common"]["default_app"] . "/"));
     exit;
  }
  $phpgw->common->phpgw_header();
  $phpgw->common->navbar();

  $phpgw->common->read_preferences("addressbook");
  $phpgw->common->read_preferences("email");
  $phpgw->common->read_preferences("calendar");
  $phpgw->common->read_preferences("stocks");
  
  $phpgw->db->query("select app_version from applications where app_name='admin'",__LINE__,__FILE__);
  $phpgw->db->next_record();

  if ($phpgw_info["server"]["version"] > $phpgw->db->f("app_version")) {
     echo "<p><b>" . lang("Your are running a newer version of phpGroupWare then your database is setup for")
        . "<br>" . lang("It is recommend that you run setup to upgrade your tables to the current version")
        . "</b>";
  }

  $phpgw->translation->add_app("mainscreen");  
  if (lang("mainscreen_message") != "mainscreen_message*") {
     echo "<center>" . stripslashes(lang("mainscreen_message")) . "</center>";
  }

  if ((isset($phpgw_info["user"]["apps"]["admin"]) &&
       $phpgw_info["user"]["apps"]["admin"]) && 
      (isset($phpgw_info["server"]["checkfornewversion"]) &&
       $phpgw_info["server"]["checkfornewversion"])) {
     $phpgw->network->set_addcrlf(False);
     if ($phpgw->network->open_port("phpgroupware.org",80,30)) {
	 $phpgw->network->write_port("GET /currentversion HTTP/1.0\nHOST: www.phpgroupware.org\n\n");
	 while ($line = $phpgw->network->read_port())
	     $lines[] = $line;
	 $phpgw->network->close_port();
     }

     for ($i=0; $i<count($lines); $i++) {
         if (ereg("currentversion",$lines[$i])) {
            $line_found = explode(":",chop($lines[$i]));
         }
     }
     if ($line_found[1] > $phpgw_info["server"]["version"]) {
        echo "<p>There is a new version of phpGroupWare avaiable. <a href=\""
	   . "http://www.phpgroupware.org\">http://www.phpgroupware.org</a>";
     }
  }

  echo '<p><table border="0" width="100%">';
?>
 <script langague="JavaScript">
    function opennotifywindow()
    {
      window.open("<?php echo $phpgw->link("notify.php")?>", "phpGroupWare", "width=150,height=25,location=no,menubar=no,directories=no,toolbar=no,scrollbars=yes,resizable=yes,status=yes");
    }
 </script>

<?php
  //echo '<a href="javascript:opennotifywindow()">Open notify window</a>';
  
  if ($phpgw_info["user"]["apps"]["stocks"] && $phpgw_info["user"]["preferences"]["stocks"]["enabled"]) {
     include($phpgw_info["server"]["server_root"] . "/stocks/inc/functions.inc.php");
     echo '<tr><td align="right">' . return_quotes($quotes) . '</td></tr>';
  }  

  if ((isset($phpgw_info["user"]["apps"]["email"]) &&
       $phpgw_info["user"]["apps"]["email"]) &&
      (isset($phpgw_info["user"]["preferences"]["email"]["mainscreen_showmail"]) &&
       $phpgw_info["user"]["preferences"]["email"]["mainscreen_showmail"])) {
    echo "<!-- Mailox info -->\n";

    $mbox = $phpgw->msg->login();
    if (! $mbox) {
      echo "Mail error: can not open connection to mail server";
      exit;
    }

  	$mailbox_status = $phpgw->msg->status($mbox,"{" . $phpgw_info["server"]["mail_server"] . ":" . $phpgw_info["server"]["mail_port"] . "}INBOX",SA_UNSEEN);
    if ($mailbox_status->unseen == 1) {
      echo "<tr><td><A href=\"" . $phpgw->link("email/index.php") . "\"> "
	 . lang("You have 1 new message!") . "</A></td></tr>\n";
    }
    if ($mailbox_status->unseen > 1) {
      echo "<tr><td><A href=\"" . $phpgw->link("email/index.php") . "\"> "
	 . lang("You have x new messages!",$mailbox_status->unseen) . "</A></td></tr>";
    }
    echo "<!-- Mailox info -->\n";
  }

  if ($phpgw_info["user"]["apps"]["addressbook"]
  && $phpgw_info["user"]["preferences"]["addressbook"]["mainscreen_showbirthdays"]) {
    echo "<!-- Birthday info -->\n";
    $phpgw->db->query("select DISTINCT ab_firstname,ab_lastname from addressbook where "
      . "ab_bday like '" . $phpgw->common->show_date(time(),"n/d",__LINE__,__FILE__)
      . "/%' and (ab_owner='" . $phpgw_info["user"]["userid"] . "' or ab_access='"
      . "ab_public')");
      while ($phpgw->db->next_record()) {
        echo "<tr><td>" . lang("Today is x's birthday!", $phpgw->db->f("ab_firstname") . " "
	  . $phpgw->db->f("ab_lastname")) . "</td></tr>\n";
      }
      $tommorow = $phpgw->common->show_date(mktime(0,0,0,
      $phpgw->common->show_date(time(),"m"),
      $phpgw->common->show_date(time(),"d")+1,
      $phpgw->common->show_date(time(),"Y")),"n/d" );
      $phpgw->db->query("select ab_firstname,ab_lastname from addressbook where "
                      . "ab_bday like '$tommorow/%' and (ab_owner='"
                      . $phpgw_info["user"]["userid"] . "' or ab_access='public')",__LINE__,__FILE__);
      while ($phpgw->db->next_record()) {
        echo "<tr><td>" . lang("Tommorow is x's birthday.", $phpgw->db->f("ab_firstname") . " "
	  . $phpgw->db->f("ab_lastname")) . "</td></tr>\n";
      }
      echo "<!-- Birthday info -->\n";
  }

  if ($phpgw_info["user"]["apps"]["calendar"]
  && $phpgw_info["user"]["preferences"]["calendar"]["mainscreen_showevents"]) {
    echo "<!-- Calendar info -->\n";
    $now = $phpgw->calendar->splitdate(mktime (0, 0, 0, $phpgw->calendar->today["month"], $phpgw->calendar->today["day"], $phpgw->calendar->today["year"]) - ((60 * 60) * $phpgw_info["user"]["preferences"]["common"]["tz_offset"]));

    echo "<table border=\"0\" width=\"70%\" cellspacing=\"0\" cellpadding=\"0\"><tr><td align=\"center\">"
	. lang(date("F",$phpgw->calendar->today["raw"])) . " " .$phpgw->calendar->today["day"] . ", " . $phpgw->calendar->today["year"] ."</tr></td>"
        . "<tr><td bgcolor=\"".$phpgw_info["theme"]["bg_text"]."\" valign=\"top\">"
	. $phpgw->calendar->print_day_at_a_glance($now)."</td></tr></table>\n";
    echo "<!-- Calendar info -->\n";
  } 

  //$phpgw->common->debug_phpgw_info();
  //$phpgw->common->debug_list_core_functions();
?>
<TR><TD></TD></TR>
</TABLE>
<?php
  include($phpgw_info["server"]["api_dir"] . "/footer.inc.php");
?>

