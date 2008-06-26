<?php


class MemberPage extends PageWithActiveSkin
{
    protected function getPageTitle()
    {
        $member = $this->member;
        return "".$member->Username." - Profile";
    }
    
    
    protected function getTopmenuActiveItem()
    {
        return 'profile';
    }
    
    
    protected function getSubmenuItems()
    {
        $items = array();
        if (APP_User::isBWLoggedIn()) {
            $username = $_SESSION['Username'];
            return array(
                array('profile', "members/$username", 'Profile'),
                array('visitors', "myvisitors", 'My visitors'),
                array('mypreferences', 'mypreferences', 'My Preferences'),
                array('editmyprofile', 'editmyprofile', 'Edit My Profile'),
                array('comments', "members/$username/comments", 'View Comments(n)'),
                array('gallery', "gallery/show/user/$username", 'Photo Gallery')
            );
        } else {
            $username = 'Boris';
            return array(
                array('profile', "members/$username", 'Profile'),
                array('comments', "members/$username/comments", 'View Comments(n)'),
                array('gallery', "gallery/show/user/$username", 'Photo Gallery')
            );
        }
        return $items;
    }
    
    
    protected function teaserContent()
    {
		$member = $this->member;
		
		$lang = $this->model->get_profile_language();
		$profile_language = $lang->id;
		$profile_language_code = $lang->ShortCode;
		//$profile_language = $_SESSION['IdLanguage'];
        ?>
        <div id="teaser"  class="clearfix" >
          <div id="teaser_l" >
            <div id="pic_main" >
              <div id="img1" >
                <a href="myphotos.php?action=viewphoto&IdPhoto=<?php //TOIMPLEMENT:echo $member->getProfilePictureID()  
?>"  title="No picture for admin (He is ugly) but the update picture comment works !" >
                  <img src="memberphotos"  alt="ProfilePicture" >
                </a>
              </div>
              <div id="pic_sm1" >
                <a href="member.php?action=previouspicture&photorank=0&cid=<?=$member->id?>" >
                  <img name="pic_sm1"  src="memberphotos"  width="30"  height="30"  border="0" />
                </a>
              </div>
              <div id="pic_sm2">
                <img name="pic_sm2"  src="memberphotos"  width="30"  height="30"  border="0" >
              </div>
              <div id="pic_sm3" >
                <a href="member.php?action=nextpicture&photorank=0&cid=<?=$member->id?>" >
                  <img name="pic_sm3"  src="memberphotos"  width="30"  height="30"  border="0" >
                </a>
              </div>
            </div>
          </div>
          <div id="teaser_gmap" >
            <img src="http://maps.google.com/staticmap?zoom=4&maptype=mobile&size=350x120&center=48.1333333,-1.2&markers=48.1333333,-1.2,blue&key=" >
          </div>
          <div id="teaser_r" >
            <div id="profile-info" >
              <div id="username" >
                <strong><?php echo $member->Username ?></strong>
                <?php echo $member->name() ?>  
                <br />
              </div>
            </div>
             
            <div id="navigation-path" >
               <!--<A href="country/" >Country</A>-->
               	<h2><strong><a href="country/<?php echo  $member->countryCode()."/".$member->region()."/".$member->city() ?>" ><?php echo  $member->city() ?></a></strong>
				(<A href="country/<?php echo  $member->countryCode()."/".$member->region() ?>" ><?php echo  $member->region() ?></A>)
               	<strong><A href="country/<?php echo $member->countryCode() ?>" ><?php echo  $member->country() ?></A></strong></h2>
               <?php
               /*
              <A href="../country/<?php echo $member->countrycode()?>" ><?php $member->country()?></A>
               > 
              <A href="../country/<?php echo $member->countrycode()?>/<?php echo $member->region()?>" ><?php echo $member->region()?></A>
               > 
              <A href="../country/<?php echo $member->countrycode()?>/<?php echo $member->region()?>/<?php echo $member->city()?>" ><?php echo $member->city()?>�</A>
               */
               ?>
            </DIV>
            <DIV id="profile-info" >
              <!--<IMG src="images/neverask.gif"  class="float_left"  title="No, sorry"  width="30"  height="30"  alt="neverask" >-->
              <TABLE>
                <TBODY>
                  <TR>
                    <TD>
                       <?php 
                       		$words = $this->getWords();
	                       $comments_count = $member->count_comments(); 
	                       echo $words->get('NbComments',  $comments_count['all']);
	                       echo " (".$words->get('NbTrusts',  $comments_count['positive']).")";
	                    ?>
                      <BR>
                      <?php
	                      $agestr = "";
	                      if($member->age == "hidden") 
	                      	$agestr .= $words->get("AgeHidden");
	                      else 
	                      	$agestr= $words->get('AgeEqualX', "hidden" );
	                      	
	                     echo $agestr;
                      ?> 
                      <?php $occupation = $member->get_trad("Occupation", $profile_language);
                      		if($occupation != null) echo ", ".$occupation; ?>
                    </td>
                    <td>
                       <?=$words->get('ProfileVersionIn');?>:
                       <?php 
                       $languages = $member->get_profile_languages(); 
                       foreach($languages as $language) { 
                       	?>
						<A href="<?=PVars::getObj('env')->baseuri."members/".$member->Username."/".$language?>" >
							<IMG height="11px"  width="16px"  src="<?=PVars::getObj('env')->baseuri?>bw/images/flags/<?=$language?>.png"  alt="<?=$language?>.png" >                        	
                      	</A>                       	
                       <?php } ?>
                       
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <?php
    }
}


?>