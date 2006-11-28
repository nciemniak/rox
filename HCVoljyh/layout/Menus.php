<?php
//------------------------------------------------------------------------------
// This function display the main menu
//------------------------------------------------------------------------------
function mainmenu($link="",$tt="") {
  global $title ;
	if ($tt!="") $title=$tt ;
  echo "\n<div align=\"center\" id=\"header\">" ;
  echo "\n<ul>\n" ;

  if (IsLogged()) {	
    echo "<li><a" ;
	  if ($link=="Main.php") {
	    echo " id=current " ;
	  }
	  else {
	    echo " href=\"Main.php\" ";
	  }
	  echo " title=\"first page.\">",ww('Welcome'),"</a></li>\n" ;
	}
	else {
    echo "<li><a" ;
	  if ($link=="Main.php") {
	    echo " id=current " ;
	  }
	  else {
	    echo " href=\"Main.php\" ";
	  }
	  echo " title=\"Login Page.\">",ww('Login'),"</a></li>\n" ;


    echo "<li><a" ;
	  if ($link=="Whatisthis.php") {
	    echo " id=current " ;
	  }
	  else {
	    echo " href=\"Whatisthis.php\" ";
	  }
	  echo " title=\"What is this ?\">",ww('Whatisthis'),"</a></li>\n" ;
	}
	
	
	
	
  echo "<li><a" ;
	if ($link=="MembersByCountries.php") {
	  echo " id=current " ;
	}
	else {
	  echo " href=\"MembersByCountries.php\" ";
	}
	echo " title=\"Members by countries\">",ww('MembersByCountries'),"</a></li>\n" ;

  echo "<li><a" ;
	if ($link=="Search.php") {
	  echo " id=current " ;
	}
	else {
	  echo " href=\"Search.php\" ";
	}
	echo " title=\"Search Page\">",ww('SearchPage'),"</a></li>\n" ;

  echo "<li><a" ;
	if ($link=="Faq.php") {
	  echo " id=current " ;
	}
	else {
	  echo " href=\"Faq.php\" ";
	}
	echo " title=\"Frequently asked questions.\">",ww('faq'),"</a></li>\n" ;

  echo "<li><a" ;
	if ($link=="Feedback.php") {
	  echo " id=current " ;
	}
	else {
	  echo " href=\"Feedback.php\" ";
	}
	echo " title=\"Contact us\">",ww('ContactUs'),"</a></li>\n" ;

  if (IsLogged()) {	
	
    echo "<li><a" ;
	  if ($link=="Groups.php") {
	    echo " id=current " ;
	  }
	  else {
	    echo " href=\"Groups.php\" method=post ";
	  }
	  echo " title=\"Groups in this organization\">",ww('Groups'),"</a></li>\n" ;

    echo "<li><a" ;
	  if (strstr($link,"MyMessages.php")!==False) {
	    echo " id=current " ;
	  }
	  else {
	    echo " href=\"MyMessages.php\" method=post ";
	  }
		if (isset($_SESSION['MessageNotRead']) and ($_SESSION['MessageNotRead']>0)) {
	    echo " title=\"My messages\">",ww('MyMessagesNotRead',$_SESSION['MessageNotRead']),"</a></li>\n" ;
		}
		else {
	    echo " title=\"My messages\">",ww('MyMessages'),"</a></li>\n" ;
		}

    echo "<li><a" ;
	  if ($link=="EditMyProfile.php") {
	    echo " id=current " ;
	  }
	  else {
	    echo " href=\"EditMyProfile.php\" method=post ";
	  }
	  echo " title=\"Modify my profile\">",ww('EditMyProfile'),"</a></li>\n" ;

	
    echo "<li><a" ;
	  if ($link=="MyPreferences.php") {
	    echo " id=current " ;
	  }
	  else {
	    echo " href=\"MyPreferences.php\" method=post ";
	  }
	  echo " title=\"My preferences\">",ww('MyPreferences'),"</a></li>\n" ;

		VolMenuAdd($link,$tt) ; // This will add the volunteer menu feature if any are needed
		
    echo "<li><a" ;
	  if ($link=="Main.php?action=Logout") {
	    echo " id=current " ;
	  }
	  else {
	    echo " href=\"Main.php?action=logout\" method=post ";
	  }
	  echo " title=\"Logout\">",ww('Logout'),"</a></li>\n" ;

	}
	

  echo "</ul>\n</div>\n" ;
	
	// anomalie : les 2 ligne ssuivantes sont n�c�ssaires pour provoquer un retour � la ligne
  echo "\n<table width=100%><tr><td align=left>&nbsp;</td></table>" ;
  echo "\n<table width=100%><tr><td align=left>&nbsp;</td></table>" ;
} // end of mainmenu

//------------------------------------------------------------------------------
// This function display the Profile menu
//------------------------------------------------------------------------------
function ProfileMenu($link="",$tt="",$MemberUsername="") {
  global $title ;
	if ($MemberUsername=="") {
	  $cid=$_SESSION['IdMember'] ;
	}
	else {
	  $cid=$MemberUsername ;
	}
	if ($tt!="") $title=$tt ;
  echo "\n<div align=\"center\" id=\"header\">" ;
  echo "\n<ul>\n" ;

  echo "<li><a" ;
	if ($link=="Main.php") {
	  echo " id=current " ;
	}
	else {
	  echo " href=\"Main.php\" method=post ";
	}
	echo " title=\"Back to main page\">",ww('Welcome'),"</a></li>\n" ;


  if (IsLogged()) {	
    echo "<li><a" ;
	  if (strstr($link,"Member.php")!==False) {
	    echo " id=current " ;
	  }
	  else {
	    echo " href=\"Member.php?cid=".$cid."\" ";
	  }
	  echo " title=\"Member page.\">",ww('MemberPage'),"</a></li>\n" ;
	}
	
  echo "<li><a" ;
	if ($link=="MembersByCountries.php") {
	  echo " id=current " ;
	}
	else {
	  echo " href=\"MembersByCountries.php\" ";
	}
	echo " title=\"Members by countries\">",ww('MembersByCountries'),"</a></li>\n" ;

  echo "<li><a" ;
	if ($link=="Search.php") {
	  echo " id=current " ;
	}
	else {
	  echo " href=\"Search.php\" ";
	}
	echo " title=\"Search Page\">",ww('SearchPage'),"</a></li>\n" ;

  echo "<li><a" ;
	if ($link=="ContactMember.php") {
	  echo " id=current " ;
	}
	else {
	  echo " href=\"ContactMember.php?cid=".$cid."\" ";
	}
	echo " title=\"Contact This Member.\">",ww('ContactMember'),"</a></li>\n" ;

  if (IsLogged()) {	
    echo "<li><a" ;
	  if (strstr($link,"ViewComments.php")!==False) {
	    echo " id=current " ;
	  }
	  else {
	    echo " href=\"ViewComments.php?cid=".$cid."\" ";
	  }
	  echo " title=\"View comments\">",ww('ViewComments'),"</a></li>\n" ;

    echo "<li><a" ;
	  if (strstr($link,"AddComments.php")!==False) {
	    echo " id=current " ;
	  }
	  else {
	    echo " href=\"AddComments.php?cid=".$cid."\" ";
	  }
	  echo " title=\"Add comments\">",ww('AddComments'),"</a></li>\n" ;

		VolMenuAdd($link,$tt) ; // This will add the volunteer menu feature if any are needed

    echo "<li><a" ;
	  if ($link=="Main.php?action=Logout") {
	    echo " id=current " ;
	  }
	  else {
	    echo " href=\"Main.php?action=logout\" method=post ";
	  }
	  echo " title=\"Logout\">",ww('Logout'),"</a></li>\n" ;

	}
	

  echo "</ul>\n</div>\n" ;
	
	// anomalie : les 2 ligne ssuivantes sont n�c�ssaires pour provoquer un retour � la ligne
  echo "\n<table width=100%><tr><td align=left>&nbsp;</td></table>" ;
  echo "\n<table width=100%><tr><td align=left>&nbsp;</td></table>" ;
} // end of ProfileMenu

//------------------------------------------------------------------------------
// This function display the Messages menu
//------------------------------------------------------------------------------
function MessagesMenu($link="",$tt="",$MemberUsername="") {
  global $title ;
	if ($MemberUsername=="") {
	  $cid=$_SESSION['IdMember'] ;
	}
	else {
	  $cid=$MemberUsername ;
	}
	if ($tt!="") $title=$tt ;
  echo "\n<div align=\"center\" id=\"header\">" ;
  echo "\n<ul>\n" ;

  echo "<li><a" ;
	if ($link=="Main.php") {
	  echo " id=current " ;
	}
	else {
	  echo " href=\"Main.php\" method=post ";
	}
	echo " title=\"Back to main page\">",ww('Welcome'),"</a></li>\n" ;


  if (IsLogged()) {	
    echo "<li><a" ;
	  if (strstr($link,"MyMessages.php?action=NotRead")!==False) {
	    echo " id=current " ;
	  }
	  else {
	    echo " href=\"MyMessages.php?action=NotRead\"";
	  }
	  echo " title=\"messages not reads.\">",ww('MyMessagesNotRead',$_SESSION['NbNotRead']),"</a></li>\n" ;

    echo "<li><a" ;
	  if (strstr($link,"MyMessages.php?action=Received")!==False) {
	    echo " id=current " ;
	  }
	  else {
	    echo " href=\"MyMessages.php?action=Received\"";
	  }
	  echo " title=\"messages not reads.\">",ww('MyMessagesReceived'),"</a></li>\n" ;

		echo "<li><a" ;
	  if (strstr($link,"MyMessages.php?action=Sent")!==False) {
	    echo " id=current " ;
	  }
	  else {
	    echo " href=\"MyMessages.php?action=Sent\"";
	  }
	  echo " title=\"messages sent\">",ww('MyMessagesSent'),"</a></li>\n" ;

		echo "<li><a" ;
	  if (strstr($link,"MyMessages.php?action=Draft")!==False) {
	    echo " id=current " ;
	  }
	  else {
	    echo " href=\"MyMessages.php?action=Draft\"";
	  }
	  echo " title=\"messages draft.\">",ww('MyMessagesDraft'),"</a></li>\n" ;

		VolMenuAdd($link,$tt) ; // This will add the volunteer menu feature if any are needed

    echo "<li><a" ;
	  if ($link=="Main.php?action=Logout") {
	    echo " id=current " ;
	  }
	  else {
	    echo " href=\"Main.php?action=logout\" method=post ";
	  }
	  echo " title=\"Logout\">",ww('Logout'),"</a></li>\n" ;

	}
	

  echo "</ul>\n</div>\n" ;
	
	// anomalie : les 2 ligne ssuivantes sont n�c�ssaires pour provoquer un retour � la ligne
  echo "\n<table width=100%><tr><td align=left>&nbsp;</td></table>" ;
  echo "\n<table width=100%><tr><td align=left>&nbsp;</td></table>" ;
} // end of MessagesMenu

//------------------------------------------------------------------------------
function VolMenuAdd($link="",$tt="") {

  if (HasRight("Words")) {
    echo "<li><a" ;
	  if ($link=="AdminWords.php") {
	    echo " id=current " ;
	  }
	  else {
	    echo " href=\"AdminWords.php\" method=post ";
	  }
	  echo " title=\"Words managment\">AdminWord</a></li>\n" ;
	}

  if (HasRight("Accepter")) {
    echo "<li><a" ;
	  if ($link=="AdminAccepter.php") {
	    echo " id=current " ;
	  }
	  else {
	    echo " href=\"AdminAccepter.php\" method=post ";
	  }
	  echo " title=\"Accepting members\">AdminAccepter</a></li>\n" ;
	}

  if (HasRight("Grep")) {
    echo "<li><a" ;
	  if ($link=="AdminGrep.php") {
	    echo " id=current " ;
	  }
	  else {
	    echo " href=\"AdminGrep.php\" method=post ";
	  }
	  echo " title=\"Greping files\">AdminGrep</a></li>\n" ;
	}
	
  if (HasRight("Group")) {
    echo "<li><a" ;
	  if ($link=="AdminGroups.php") {
	    echo " id=current " ;
	  }
	  else {
	    echo " href=\"AdminGroups.php\" method=post ";
	  }
	  echo " title=\"Grepping file\">AdminGroups</a></li>\n" ;
	}
}

?>