<?php
/*****************************************************************************
YACRS Copyright 2013-2015, The University of Glasgow.
Written by Niall S F Barr (niall.barr@glasgow.ac.uk, niall@nbsoftware.com)

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*****************************************************************************/
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('config.php');
require_once('lib/database.php');
require_once('lib/forms.php');
require_once('lib/shared_funcs.php');
include_once('corelib/mobile.php');
include_once('lib/lti_funcs.php');
$template = new templateMerge($TEMPLATE);
if($deviceType=='mobile')
    $template->pageData['modechoice'] = "<a href='{$_SERVER['PHP_SELF']}?mode=computer'>Use computer mode</a>";
else
    $template->pageData['modechoice'] = "<a href='{$_SERVER['PHP_SELF']}?mode=mobile'>Use mobile mode</a>";

$loginError = '';
$uinfo = checkLoggedInUser(true, $loginError);

$template->pageData['pagetitle'] = $CFG['sitetitle'];
$template->pageData['homeURL'] = $_SERVER['PHP_SELF'];
$template->pageData['breadcrumb'] = $CFG['breadCrumb'];
$template->pageData['breadcrumb'] .= '| YACRS';

if($uinfo==false)
{
	$template->pageData['headings'] = "<h1  style='text-align:center; padding:10px;'>Login</h1>";
    if((isset($CFG['ldaphost']))&&($CFG['ldaphost']!=''))
    {
        $template->pageData['loginBox'] = loginBox($uinfo, $loginError);//."<p style='text-align:right;'><a href='join.php'>Or click here for guest/anonymous access</a></p>";
    }
    if(file_exists('logininfo.htm'))
	    $template->pageData['mainBody'] = file_get_contents('logininfo.htm').'<br/>';
    $template->pageData['logoutLink'] = "<p style='text-align:right;'><a href='join.php'>Or click here for guest/anonymous access</a></p>";
}
else
{
    $thisSession = requestSet('sessionID') ? session::retrieve_session(requestInt('sessionID')):false;
    if($thisSession)
    {
        if(checkPermission($uinfo, $thisSession))
        {
            $template->pageData['mainBody'] .= "<a href='runsession.php?sessionID={$thisSession->id}'>Run session</a>";
            header("Location: runsession.php?sessionID={$thisSession->id}");
        }
        elseif(($thisSession->currentQuestion==0)&&($thisSession->ublogRoom>0))
        {
            $template->pageData['mainBody'] .= "<a href='chat.php?sessionID={$thisSession->id}'>Join session</a>";
            header("Location: chat.php?sessionID={$thisSession->id}");
        }
        else
        {
            $template->pageData['mainBody'] .= "<a href='vote.php?sessionID={$thisSession->id}'>Join session</a>";
            header("Location: vote.php?sessionID={$thisSession->id}");
        }
    }
    elseif($ltiSessionID = getLTISessionID())
    {
        if(isLTIStaff())
	    {
	        $template->pageData['mainBody'] .= '<ul>';
            $s = session::retrieve_session($ltiSessionID);
            if($s !== false)
            {
	            $ctime = strftime("%Y-%m-%d %H:%M", $s->created);
	            $template->pageData['mainBody'] .= "<li>Session number: <b>{$s->id}</b> <a href='runsession.php?sessionID={$s->id}'>{$s->title}</a> (Created $ctime) <a href='editsession.php?sessionID={$s->id}'>Edit</a> <a href='confirmdelete.php?sessionID={$s->id}'>Delete</a></li>";
                $template->pageData['mainBody'] .= "<li>To use the teacher control app for this session login with username: <b>{$s->id}</b> and password <b>".substr($s->ownerID, 0, 8)."</b></li>";

            }
            else
            {
                $template->pageData['mainBody'] .= "<li>No session found for this LTI link. To create a new session return to the VLE/LMS and click the link again.</li>";
            }
	        $template->pageData['mainBody'] .= '</ul>';
	    }
        else
        {
            $template->pageData['mainBody'] .= "<a href='vote.php?sessionID={$thisSession->id}'>Join session</a>";
            header("Location: vote.php?sessionID={$thisSession->id}");
        }
    }
    else
    {
	    $template->pageData['mainBody'] = sessionCodeinput();
	    if($uinfo['sessionCreator'])
	    {
	        $template->pageData['mainBody'] .= "<p><b><a href='editsession.php'>Create a new clicker session</a></b></p>";
		    $sessions = session::retrieve_session_matching('ownerID', $uinfo['uname']);
		    $template->pageData['mainBody'] .= '<h2>My sessions (staff)</h2>';
		    if($sessions == false)
		    {
		        $template->pageData['mainBody'] .= "<p>No sessions found</p>";
		    }
		    else
		    {
		        $template->pageData['mainBody'] .= '<ul>';
		        foreach($sessions as $s)
		        {
		            $ctime = strftime("%Y-%m-%d %H:%M", $s->created);
		            $template->pageData['mainBody'] .= "<li>Session number: <b>{$s->id}</b> <a href='runsession.php?sessionID={$s->id}'>{$s->title}</a> (Created $ctime) <a href='editsession.php?sessionID={$s->id}'>Edit</a> <a href='confirmdelete.php?sessionID={$s->id}'>Delete</a></li>";
		        }
		        $template->pageData['mainBody'] .= '</ul>';
		    }
	    }
		$slist = sessionMember::retrieve_sessionMember_matching('userID', $uinfo['uname']);
	    $template->pageData['mainBody'] .= '<h2>My sessions</h2>';
	    $sessions = array();
	    if($slist)
	    {
	        foreach($slist as $s)
	        {
	            $sess = session::retrieve_session($s->session_id);
	            if(($sess)&&($sess->visible))
	                $sessions[] = $sess;
	        }
	    }
	    if(sizeof($sessions) == 0)
	    {
	        $template->pageData['mainBody'] .= "<p>No sessions found</p>";
	    }
	    else
	    {
	        $template->pageData['mainBody'] .= '<ul>';
	        foreach($sessions as $s)
	        {
	            $ctime = strftime("%Y-%m-%d %H:%M", $s->created);
	            $template->pageData['mainBody'] .= "<li><a href='vote.php?sessionID={$s->id}'>{$s->title}</a>";
                if((isset($s->extras['allowFullReview']))&&($s->extras['allowFullReview']))
                     $template->pageData['mainBody'] .= " (<a href='review.php?sessionID={$s->id}'>Review previous answers</a>)";
                $template->pageData['mainBody'] .= "</li>";
	        }
	        $template->pageData['mainBody'] .= '</ul>';
	    }
        $user = userInfo::retrieve_by_username($uinfo['uname']);
        if($user !== false)
        {
	        $template->pageData['mainBody'] .= '<h2>My settings</h2>';
            if((isset($CFG['smsnumber']))&&(strlen($CFG['smsnumber'])))
            {
	            $code = substr(md5($CFG['cookiehash'].$user->username),0,4);
	            if(strlen($user->phone))
	            {
	                $template->pageData['mainBody'] .= "<p>Current phone for SMS: {$user->phone}</p>";
	            }
	            $template->pageData['mainBody'] .= "<p>To associate a phone with your username text \"link {$user->username} $code\" (without quotes) to {$CFG['smsnumber']}.</p>";
            }
	        //$template->pageData['mainBody'] .= '<pre>'.print_r($user,1).'</pre>';

        }
	    if($uinfo['isAdmin'])
	    {
	        $template->pageData['mainBody'] .= '<p style="text-align:right;"><a href="admin.php">YACRS administration</a></p>';
	    }
	    //$template->pageData['mainBody'] .= '<pre>'.print_r($uinfo,1).'</pre>';
		$template->pageData['logoutLink'] = loginBox($uinfo);
    }
}
echo $template->render();

?>
