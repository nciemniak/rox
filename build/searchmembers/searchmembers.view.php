<?php
/*

Copyright (c) 2007 BeVolunteer

This file is part of BW Rox.

BW Rox is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

BW Rox is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see <http://www.gnu.org/licenses/> or 
write to the Free Software Foundation, Inc., 59 Temple Place - Suite 330, 
Boston, MA  02111-1307, USA.

*/
/**
 * searchmembers view
 *
 * @package searchmembers
 * @author matrixpoint
 */
class SearchmembersView extends PAppView {
    
    private $_model;
            
    public function __construct(Searchmembers $model) {
        $this->_model = $model;
    }

    public function passthroughCSS($req) {
        $loc = PApps::getBuildDir().'searchmembers/'.$req;
        if (!file_exists($loc))
            exit();
        $headers = apache_request_headers();
        // Checking if the client is validating his cache and if it is current.
        if (isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) == filemtime($loc))) {
            // Client's cache IS current, so we just respond '304 Not Modified'.
            header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($loc)).' GMT', true, 304);
        } else {
            // File not cached or cache outdated, we respond '200 OK' and output the image.
            header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($loc)).' GMT', true, 200);
            header('Content-Length: '.filesize($loc));
        }
        header('Content-type: text/css');
        @copy($loc, 'php://output');
        exit();
    }

    public function searchmembers($callbackId, $TGroup, $TabTypicOffer, $MapOff)
    {
        include TEMPLATE_DIR.'apps/searchmembers/index.php';
    }
    public function searchmembers_ajax($TList, $vars)
    {
        include TEMPLATE_DIR.'apps/searchmembers/ajax.php';
    }
	
    public function teaser() {
        require TEMPLATE_DIR.'apps/rox/teaser.php';
    }
    
	/* This adds other custom styles to the page*/
	public function customStyles() {
		$out = '';
		/* 2column layout */
		$out .= '<link rel="stylesheet" href="styles/YAML/screen/custom/bw_basemod_2col.css" type="text/css"/>';
		$out .= '<link rel="stylesheet" href="styles/YAML/screen/custom/index.css" type="text/css"/>';
		return $out;
    }
    public function rightContent() {
	$User = new UserController;
		$User->displayLoginForm();
	}
    public function topMenu($currentTab) {
        require TEMPLATE_DIR.'apps/rox/topmenu.php';
    }
}
?>
