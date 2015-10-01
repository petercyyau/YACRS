<?php

$questionTypes['basicquestion'] = array('name'=>'Multiple Choice question', 'class'=>'basicQuestion', 'edit'=>'editBasicQuestion_form');

class basicQuestion extends questionBase
{
    var $stem;
    var $displayStem;
    var $displayTitle;
    var $quType;
    var $options;
    var $correct;

    var $responseValue;

	function __construct($stem, $displayStem, $options)
	{
        $this->stem = $stem;
        $this->displayStem = $displayStem;
        $optCount = 0;
        $cCount = 0;
        $tmpOpts = explode("\n",$options);
        $this->options = array();
        $this->correct = array();
        foreach($tmpOpts as $t)
        {
            $t= trim($t);
        	if((substr($t,0,1)=='*')&&(strlen($t)>1))
            {
        		$optCount++;
                $cCount++;
                $this->options[] = substr($t,1);
                $this->correct[] = true;
            }
            elseif(strlen($t)>0)
            {
        		$optCount++;
                $this->options[] = $t;
                $this->correct[] = false;
            }
        }
        if($cCount == 0)
        {
        	$this->quType = 'MCS';
            $this->correct = false;
        }
        elseif($cCount == $optCount)
        {
        	$this->quType = 'MRS';
            $this->correct = false;
        }
        elseif($cCount == 1)
        {
        	$this->quType = 'MCQ';
        }
        else
        {
        	$this->quType = 'MRQ';
        }
        $this->responseValue = false;
    }

    function allowReview()
    {
        return true;
    }

    function checkResponse($qiID, $resp, $SMSResp=false)
    {
        if($resp == false)
	        $this->responseValue = false;
        else
            $this->responseValue = $resp->value;

        if($SMSResp)
        {
            $SMSResp = trim(strtoupper($SMSResp));
            $this->responseValue = '';
	        if(($this->quType == 'MCS')||($this->quType == 'MCQ'))
	        {
                if(strlen($SMSResp))
                {
                	$SMSResp = substr($SMSResp,0,1);
                    $ans = 'R'.(ord($SMSResp)-ord('A')+1);
                    $this->responseValue = $ans;
                }
	        }
	        else
	        {
		        $onum = 0;
		        foreach($this->options as $o)
		        {
                    $ch = chr(ord('A')+$onum);
                    $onum++;
                	$k = "R$onum";
                    if(strpos($SMSResp, $ch)!==false)
                         $this->responseValue .= $k.',';
                }
                $this->responseValue = trim($this->responseValue,',');
            }
        }
        elseif((isset($_REQUEST['submitans']))&&($_REQUEST['qiID']==$qiID))
        {
            $this->responseValue = '';
	        if(($this->quType == 'MCS')||($this->quType == 'MCQ'))
	        {
                if(isset($_REQUEST['Ans']))
                    $this->responseValue = $_REQUEST['Ans'];
	        }
	        else
	        {
		        $onum = 0;
		        foreach($this->options as $o)
		        {
                    $onum++;
                	$k = "R$onum";
                    if((isset($_REQUEST[$k]))&&($_REQUEST[$k]==$k))
                         $this->responseValue .= $k.',';
                }
                $this->responseValue = trim($this->responseValue,',');
            }
        }
    }

    function getGraphLabels()
    {
        $out = array();
        $onum = 0;
        foreach($this->options as $o)
        {
            $onum++;
            $out['R'.$onum] = $o;
        }
        return $out;
    }

    function render($title='')
    {
        //$out = '<fieldset>';
        //if($this->responseValue == false)
        //    $out .= '<legend>Input:</legend>';
        //else
        //    $out .= '<legend>You answered:</legend>';
        if($this->displayStem)
            $out .= "<p class='stem'>{$this->stem}</p>";
        if($this->displayTitle)
            $out .= "<p class='stem'>{$title}</p>";
        $out .= "<div class='wide buttonlist'>";
        $onum = 0;
        foreach($this->options as $o)
        {
            $onum++;
            $out .= "<label for='R$onum'>";

            if(($this->quType == 'MCS')||($this->quType == 'MCQ'))
            {
                $out .= "<input class='radio' type='radio' name='Ans' id='R$onum' value='R$onum'";
                if($this->responseValue == "R$onum")
                    $out .= " checked='1'";
                if(($this->responseValue !== false)&&(!isset($_REQUEST['doupdate'])))
                    $out .= " disabled='1'";
                $out .= "/>&nbsp;";
            }
            else
            {
                $out .= "<input class='radio' type='checkbox' name='R$onum' id='R$onum' value='R$onum'";
                if($this->responseValue !== false)
                {
                    if(!isset($_REQUEST['doupdate']))
                        $out .= " disabled='1'";
	                if(strpos($this->responseValue,"R$onum")!==false)
	                    $out .= " checked='1'";
                }
                $out .= "/>&nbsp;";
            }
            $out .= $o;
            $out .= "</label>";

        }
        $out .= '</div>';
        //if($this->responseValue == false)
        //{
        //    $out .= "<div class='submit'><input type='submit' name='submitans' value='Submit answer'/></div>";
        //}
        //$out .= '</fieldset>';
        return $out;
    }

    function score($qi, $resp)
    {
        $score = 0;
        if(strlen($resp->value))
        {
            $rs = explode(',', $resp->value);
            //echo '<pre>'; print_r($rs); echo '</pre>';
            if(is_array($this->correct))
            {
                foreach($this->correct as $id=>$sc)
                {
                    //echo "$id => $sc<br/>";
                    if(in_array('R'.($id+1), $rs))
                       $score += $sc;
                }
            }
            elseif(is_array($qi->extras['correct']))
            {
                foreach($qi->extras['correct'] as $id=>$sc)
                {
                    if(in_array('R'.($id+1), $rs))
                       $score += $sc;
                }
            }
        }
        //echo '<pre>'; print_r($this); echo '</pre>';
        //echo '<pre>'; print_r($qi); echo '</pre>';
        //echo '<pre>'; print_r($resp); echo '</pre>';
        return $score;
    }

    function getCorrectStr($qi)
    {
        $ca = array();
        if(is_array($this->correct))
        {
            foreach($this->correct as $id=>$sc)
            {
	            if($sc)
    	            $ca[] = 'R'.($id+1);
            }
        }
        elseif(is_array($qi->extras['correct']))
        {
            foreach($qi->extras['correct'] as $id=>$sc)
            {
                if($sc)
	                $ca[] = 'R'.($id+1);
            }
        }
        return implode(' ',$ca);
    }

    function report($thisSession, $qi, $detailed = false)
    {
	    if(isset($_REQUEST['updateAnotation']))
	    {
	        if(strlen(trim($_REQUEST['newcat'])))
	        {
	            if(!isset($thisSession->extras['categories']))
	                $thisSession->extras['categories'] = array();
	            if(!in_array(trim($_REQUEST['newcat']), $thisSession->extras['categories']))
		            $thisSession->extras['categories'][] = trim($_REQUEST['newcat']);
	            $thisSession->update();
	            $qi->extras['category'] = trim($_REQUEST['newcat']);
	        }
	        else
	            $qi->extras['category'] = trim($_REQUEST['cat']);
	        $qi->extras['correct'] = array();
	        for($n=0; $n<$_REQUEST['optcount']; $n++)
	            $qi->extras['correct'][$n] = isset($_REQUEST['corr_'.$n])?$_REQUEST['corr_'.$n]:0;
	        $qi->update();
	    }
        if(isset($_REQUEST['cdc']))
        {
        	//Create a duplicate question for comparason
            $qu = question::retrieve_question($qi->theQuestion_id);
		    $qi2 = $thisSession->addQuestion($qu);
            //# link question susing $qi->extras['paired'] = array( ids..)
            if(isset($qi->extras['paired']))
            {
                $qi2->extras['paired'] = $qi->extras['paired'];
            }
            else
            {
                $qi2->extras['paired'] = array();
            }
            $qi2->extras['paired'][] = $qi->id;
            //# add (take #) to $qi2 title;
            $qi2->title .= '('.(sizeof($qi2->extras['paired'])+1).')';
            $qi2->update();
        }

	    $labels = $this->getGraphLabels();
        $out = '';
	    $count = array_fill_keys(array_keys($labels), 0);
	    $responses = response::retrieve_response_matching('question_id', $_REQUEST['qiID']);
	    if($responses)
	    {
		    foreach($responses as $r)
		    {
		        if(strlen($r->value))
		        {
			        $votes = explode(',',$r->value);
			        foreach($votes as $v)
			        {
		                $count[$v]++;
			        }
		        }
		    }
	    }
	    if($detailed)
	    {
		    $out .= "<p><a href='responses.php?sessionID={$thisSession->id}&qiID={$qi->id}'>Summary view</a></p>";
		    $out .= "<table border='1'><thead><tr><th>User</th><th>Name</th><th>Response</th></thead><tbody>";
	        if($responses)
	        {
		        foreach($responses as $r)
		        {
		        	$member = sessionMember::retrieve_sessionMember($r->user_id);
			        $out .= "<tr><td>{$member->userID}</td><td>{$member->name}</td><td>{$r->value}</td></tr>";
		        }
	        }
		    $out .= "</table>";
	    }
	    else
	    {
	        if($responses)
	        {
		    	$out .= "<p><a href='responses.php?sessionID={$thisSession->id}&qiID={$qi->id}&display=detail'><b>".sizeof($responses)." response(s).</b></a></p>";
	            $out .= "<img src='chart.php?qiID={$qi->id}'/><br/>";
	        }
		    $out .= "<form action='#' method='POST'>";
            $out .= "<input type='hidden' value='".sizeof($labels)."' name='optcount'/>";
		    $out .= "<table border='1'><thead><tr><th>Response</th><th>Count</th><th>Correct</th></thead><tbody>";
            $ci = 0;
		    foreach($labels as $r=>$txt)
		    {
		        $out .= "<tr><td>{$txt}</td><td>{$count[$r]}</td>";
                if(is_array($this->correct))
                {
                    $out .= "<td><input type='checkbox' value='1'";
                    if($this->correct[$ci])
	                    $out .= " checked='checked'";
                    $out .= " disabled='disabled'/></td>";
                }
                else
                {
                    $out .= "<td><input type='checkbox' value='1' name='corr_$ci'";
                    if((isset($qi->extras['correct'][$ci]))&&($qi->extras['correct'][$ci]))
	                    $out .= " checked='checked'";
                    $out .= "/></td>";
                }
                $out .= "</tr>";
                $ci++;
		    }
		    $out .= "</table>";
		    $out .= "Category: <select name='cat'>";
            $out .= "<option value=''>None</option>";
            if(isset($thisSession->extras['categories']))
            {
                foreach($thisSession->extras['categories'] as $cat)
                {
                    $out .= "<option value='$cat'";
                    if((isset($qi->extras['category']))&&($qi->extras['category']==$cat))
                        $out .= " selected='selected'";
                    $out .= ">$cat</option>";
                }
            }
            $out .= "</select> or new category: <input type='text' name='newcat'/><input type='submit' name='updateAnotation' value='Update'/><input type='submit' name='updateAnotation' value='Update and Next'/></form>";

            //$out .= '<pre>'.print_r($this,1).'</pre>';
            $out .= "<p><a href='{$_SERVER['PHP_SELF']}?sessionID={$thisSession->id}&qiID={$qi->id}&cdc=1'>Create a duplicate comparison question.</a></p>";
	    }
        return $out;
    }

    static function getEditForm()
    {
    	$form = new editBasicQuestion_form();
        return $form;
    }
}

class editBasicQuestion_form extends nbform
{
	var $form_magic_id = '56090c1dea408bff57a005fe239b550c';
	var $sessionID; //hidden
	var $id; //hidden
	var $title; //string
	var $displayStem; //boolean
	var $definition; //memo
	var $multiuse; //boolean
	var $validateMessages;
    static $briefHelp = "<div style='border : 1px solid Blue;margin : 30px;padding: 10px;background-color : #FFFFAA;'><h3>Instructions</h3><p>Add one option per line on the form. Precede options that are to be 'correct' with a *</p><ul>
            <li>If exactly one option is preceded with a * the question will be treated as having a single correct answer with a single selection available.</li>
            <li>If more than one option is preceded with a * and at least one is not, the question will be treated as a multiple response with correct and incorrect selections.</li>
            <li>If no options are preceded with a * the question will be treated as having no correct or incorrect answer with a single selection available.</li>
    		<li>If all options are preceded with a * the question will be treated as having no correct or incorrect answer with multiple selections available.</li>
            </ul><p>Blank lines will be ignored.</p></div>";


	function __construct($readform=true)
	{
		parent::__construct();
		$this->validateMessages = array();
		if($readform)
		{
			$this->readAndValidate();
		}
        else
        {
           $this->displayStem = true;
        }
	}

	function setData($data)
	{
		$this->sessionID = $data->sessionID;
		$this->id = $data->id;
		$this->title = $data->title;
		$this->displayStem = $data->displayStem;
		$this->definition = $data->definition;
		$this->multiuse = $data->multiuse;
	}

	function getData(&$data)
	{
		$data->sessionID = $this->sessionID;
		$data->id = $this->id;
		$data->title = $this->title;
		$data->displayStem = $this->displayStem;
		$data->definition = $this->definition;
		$data->multiuse = $this->multiuse;
		return $data;
	}

	function readAndValidate()
	{
		$isCanceled=false;
		if((isset($_REQUEST['editBasicQuestion_form_code']))&&($_REQUEST['editBasicQuestion_form_code'] == $this->form_magic_id))
		{
			$this->sessionID = $_REQUEST['sessionID'];
			$this->id = $_REQUEST['id'];
			$this->title = stripslashes($_REQUEST['title']);
			$this->displayStem = (isset($_REQUEST['displayStem'])&&($_REQUEST['displayStem']==1)) ? true : false;
			$this->definition = stripslashes($_REQUEST['definition']);
			$this->multiuse = (isset($_REQUEST['multiuse'])&&($_REQUEST['multiuse']==1)) ? true : false;
			if('Cancel' == $_REQUEST['submit'])
				$isCanceled = true;
			$isValid = $this->validate();
			if($isCanceled)
				$this->formStatus = FORM_CANCELED;
			elseif($isValid)
				$this->formStatus = FORM_SUBMITTED_VALID;
			else
				$this->formStatus = FORM_SUBMITTED_INVALID;
		}
		else
			$this->formStatus = FORM_NOTSUBMITTED;
	}

	function validate()
	{
		$this->validateMessages = array();
		// Put custom code to validate $this->sessionID here (to stop hackers using this as a way in.)
		// Put custom code to validate $this->id here (to stop hackers using this as a way in.)
		if(strlen($this->title)>80)
		{
		    $this->title = substr($this->title,0,80);
		    $this->validateMessages['title'] = "This field was too long and has been truncated.";
		}
		// Put custom code to validate $this->title here. Error message in $this->validateMessages['title']
		// Put custom code to validate $this->displayStem here. Put error message in $this->validateMessages['displayStem']
		// Put custom code to validate $this->definition here. Put error message in $this->validateMessages['definition']
		// Put custom code to validate $this->multiuse here. Put error message in $this->validateMessages['multiuse']
		if(sizeof($this->validateMessages)==0)
			return true;
		else
			return false;
	}

	function getHtml()
	{
		$out = '';
		$out .= $this->formStart();
		$out .= $this->hiddenInput('editBasicQuestion_form_code', $this->form_magic_id);
		$out .= $this->hiddenInput('sessionID', $this->sessionID);
		$out .= $this->hiddenInput('id', $this->id);
		$out .= $this->textInput('Title/Stem', 'title', $this->title, $this->validateMessages, 80);
		$out .= $this->checkboxInput('Display stem to participants.', 'displayStem', $this->displayStem, $this->validateMessages);
		$out .= $this->textareaInput('Options:', 'definition', $this->definition, $this->validateMessages, 60 , 6);
		$out .= $this->checkboxInput('This is a generic question to be made available in all my sessions.', 'multiuse', $this->multiuse, $this->validateMessages);
		$out .= $this->submitInput('submit', 'Create', 'Cancel');
		$out .= $this->formEnd(false);
        $out .= editBasicQuestion_form::$briefHelp;
		return $out;
	}

	function post_it()
	{
	    $http = new Http();
	    $http->useCurl(false);
	    $formdata=array('thanks_url'=>'none', 'mymode'=>'webform1.0', 'datafile'=>'editBasicQuestion_form', 'coderef'=>'nsb2x');
	    $formdata['sessionID'] = $this->sessionID;
	    $formdata['id'] = $this->id;
	    $formdata['title'] = $this->title;
	    $formdata['displayStem'] = $this->displayStem;
	    $formdata['definition'] = $this->definition;
	    $formdata['multiuse'] = $this->multiuse;

	    $http->execute('http://culrain.cent.gla.ac.uk/cgi-bin/qh/qhc','','POST',$formdata);
	    return ($http->error) ? $http->error : $http->result;
	}

    function getNewQuestion()
    {
        return new basicQuestion($this->title, $this->displayStem, $this->definition);
    }
}

