<?php
  /**************************************************************************\
  * phpGroupWare - administration                                            *
  * http://www.phpgroupware.org                                              *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

  /* $Id$ */

  $phpgw_info = array();
  if ($submit) {
     $phpgw_info["flags"] = array("noheader" => True, "nonavbar" => True);
  }
  $phpgw_info["flags"]["currentapp"] = "admin";
  include("../header.inc.php");

  $p = CreateObject('phpgwapi.Template',$phpgw->common->get_tpl_dir('admin'));
  
  function is_odd($n)
  {
     $ln = substr($n,-1);
     if ($ln == 1 || $ln == 3 || $ln == 5 || $ln == 7 || $ln == 9) {
        return True;
     } else {
        return False;
     }
  }
  
  if (! $group_id) {
     Header("Location: " . $phpgw->link("groups.php"));
  }

  if ($submit) {
     $phpgw->db->query("SELECT account_lid FROM phpgw_accounts WHERE account_id=$group_id");
     $phpgw->db->next_record();

     $old_group_name = $phpgw->db->f("account_lid");

     $phpgw->db->query("SELECT count(*) FROM phpgw_accounts WHERE account_lid='" . $n_group . "'");
     $phpgw->db->next_record();

     if ($phpgw->db->f(0) != 0 && $n_group != $old_group_name) {
        $error = lang("Sorry, that group name has already been taking.");
     }

     if (! $error) {
        $phpgw->db->lock(array("accounts","groups","preferences","config","applications","phpgw_hooks","phpgw_sessions","phpgw_acl"));
        $apps = CreateObject('phpgwapi.applications',array(intval($group_id),'g'));
        $apps->read_installed_apps();
        $apps_before = $apps->read_account_specific();
        $apps->account_apps = Array(Array());
        while($app = each($n_group_permissions)) {
          if($app[1]) {
            $apps->add_app($app[0]);
            if(!$apps_before[$app[0]]) {
              $apps_after[] = $app[0];
            }
          }
        }
        $apps->save_apps();

        if($old_group_name <> $n_group) {
          $phpgw->db->query("update groups set group_name='$n_group' where group_id=$group_id");
        }

        $old_group_list = $phpgw->acl->get_ids_for_location("$group_id",1,"phpgw_group","u");
        @reset($old_group_list);
        while($old_group_list && $id = each($old_group_list)) {
          $phpgw->acl->delete("phpgw_group","$group_id",intval($id[1]),"u");
        }

        for ($i=0; $i<count($n_users);$i++) {
          $phpgw->acl->add("phpgw_group","$group_id",$n_users[$i],"u",1);

          // If the user is logged in, it will force a refresh of the session_info
          $phpgw->db->query("update phpgw_sessions set session_info='' where session_lid='$account_lid@" . $phpgw_info["user"]["domain"] . "'",__LINE__,__FILE__);

          // The following sets any default preferences needed for new applications..
          // This is smart enough to know if previous preferences were selected, use them.
          $pref = CreateObject('phpgwapi.preferences',intval($n_users[$i]));
          $t = $pref->get_preferences();

          $docommit = False;
          for ($j=1;$j<count($apps_after) - 1;$j++) {
            if($apps_after[$j]=="admin") {
              $check = "common";
            } else {
              $check = $apps_after[$j];
            }
            if (!$t[$check]) {
              $phpgw->common->hook_single("add_def_pref", $apps_after[$j]);
              $docommit = True;
            }
          }
          if ($docommit) {
            $pref->commit();
          }
        }

        $sep = $phpgw->common->filesystem_separator();

        if ($old_group_name <> $n_group) {
          $basedir = $phpgw_info["server"]["files_dir"] . $sep . "groups" . $sep;
          if (! @rename($basedir . $old_group_name, $basedir . $n_group)) {
            $cd = 39;
          } else {
            $cd = 33;
          }
        } else {
          $cd = 33;
        }

        $phpgw->db->unlock();

        Header("Location: " . $phpgw->link("groups.php","cd=$cd"));
        $phpgw->common->phpgw_exit();
     }
  }

  $p->set_file(array("form"	=> "groups_form.tpl"));
  
  if ($error) {
     $phpgw->common->phpgw_header();
     echo parse_navbar();
     $p->set_var("error","<p><center>$error</center>");
  } else {
     $p->set_var("error","");
  }

  if ($submit) {
     $p->set_var("group_name_value",$n_group_name);

     for ($i=0; $i<count($n_users); $i++) {
        $selected_users[$n_user[$i]] = " selected";
     }
  } else {
     $phpgw->db->query("SELECT account_lid FROM phpgw_accounts WHERE account_id=$group_id");
     $phpgw->db->next_record();

     $p->set_var("group_name_value",$phpgw->db->f("account_id"));

     $group_user = $phpgw->acl->get_ids_for_location($group_id,1,'phpgw_group','u');

     while ($user = each($group_user)) {
        $selected_users[$user[1]] = " selected";
     }

     $apps = CreateObject('phpgwapi.applications',array(intval($group_id),'g'));
     $apps->read_installed_apps();
     $db_perms = $apps->read_account_specific();
  }

  $phpgw->db->query("SELECT * FROM phpgw_accounts WHERE account_id=$group_id");
  $phpgw->db->next_record();

  $p->set_var("form_action",$phpgw->link("editgroup.php"));
  $p->set_var("hidden_vars","<input type=\"hidden\" name=\"group_id\" value=\"" . $group_id . "\">");

  $p->set_var("lang_group_name",lang("group name"));
  $p->set_var("group_name_value",$phpgw->db->f("account_lid"));

  $phpgw->db->query("SELECT count(*) FROM phpgw_accounts WHERE account_status !='L' AND account_type='u'");
  $phpgw->db->next_record();

  if ($phpgw->db->f(0) < 5) {
     $p->set_var("select_size",$phpgw->db->f(0));
  } else {
     $p->set_var("select_size","5");
  }

  $p->set_var("lang_include_user",lang("Select users for inclusion"));
  $phpgw->db->query("SELECT account_id,account_firstname,account_lastname,account_lid FROM phpgw_accounts WHERE "
	  	        . "account_status != 'L' AND account_type='u' ORDER BY account_lastname,account_firstname,account_lid asc");
  while ($phpgw->db->next_record()) {
     $user_list .= "<option value=\"" . $phpgw->db->f("account_id") . "\""
    	            . $selected_users[$phpgw->db->f("account_id")] . ">"
	            . $phpgw->common->display_fullname($phpgw->db->f("account_lid"),
						       $phpgw->db->f("account_firstname"),
						       $phpgw->db->f("account_lastname")) . "</option>";
  }
  $p->set_var("user_list",$user_list);

  $p->set_var("lang_permissions",lang("Permissions this group has"));

  $i = 0;
  reset($phpgw_info["apps"]);
  $sorted_apps = $phpgw_info["apps"];
  @asort($sorted_apps);
  @reset($sorted_apps);
  while ($permission = each($sorted_apps)) {
     if ($permission[1]["enabled"]) {
        $perm_display[$i][0] = $permission[0];
        $perm_display[$i][1] = $permission[1]["title"];
        $i++;
     }
  }

  $perm_html = "";
  for ($i=0;$i<200;) {     // The $i<200 is only used for a brake
     if (! $perm_display[$i][1]) break;
     $perm_html .= '<tr bgcolor="'.$phpgw_info["theme"]["row_on"].'"><td>' . lang($perm_display[$i][1]) . '</td>'
                 . '<td><input type="checkbox" name="n_group_permissions['
                 . $perm_display[$i][0] . ']" value="True"';
     if ($n_group_permissions[$perm_display[$i][0]] || $db_perms[$perm_display[$i][0]]["enabled"]) {
        $perm_html .= " checked";
     }
     $perm_html .= "></td>";
     $i++;

     if ($i == count($perm_display) && is_odd(count($perm_display))) {
        $perm_html .= '<td colspan="2">&nbsp;</td></tr>';
     }

     if (! $perm_display[$i][1]) break;
     $perm_html .= '<td>' . lang($perm_display[$i][1]) . '</td>'
                 . '<td><input type="checkbox" name="n_group_permissions['
                 . $perm_display[$i][0] . ']" value="True"';
     if ($n_group_permissions[$perm_display[$i][0]] || $db_perms[$perm_display[$i][0]]["enabled"]) {
        $perm_html .= " checked";
     }
     $perm_html .= "></td></tr>\n";
     $i++;
  }

  $p->set_var("permissions_list",$perm_html);	
  
  $p->set_var("lang_submit_button",lang("submit changes"));

  $p->pparse("out","form");

  $phpgw->common->phpgw_footer();
?>
