<?php //$Id: block_donations.php,v 1.9 2012-12-27 10:41:56 vf Exp $

class block_donations extends block_list {
	
    function init() {
        $this->title = get_string('title', 'block_donations');
    }

	function specialization() {
		global $COURSE, $DB;

        // load userdefined title and make sure it's never empty
        if (empty($this->config->title)) {
            $this->title = get_string('title','block_donations');
        } else {
            $this->title = $this->config->title;
        }
		//load default numberofdonations
		if (empty($this->config->numberofdonations)) {
			$title->config->numberofrecords = $DB->count_records('donationsingle', 'course', $COURSE->id);
		}		
    }

    function get_content() {
        global $CFG, $COURSE, $USER, $DB;

        if($this->content !== NULL) {
            return $this->content;
        }

		if (!isset($this->config->localcourse)) {
	        $this->config->localcourse = true;
	    }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

		$options = array(
						'percentage-asc'	=>	'amountraised/amountneeded ASC',
						'percentage-desc'	=>	'amountraised/amountneeded DESC',
						'amountneeded-asc'	=>	'amountneeded ASC',
						'amountneeded-desc'	=>	'amountneeded DESC',
						'amountraised-asc'	=>	'amountraised ASC',
						'amountraised-desc'	=>	'amountraised DESC'
						);

		$sort = $options[$this->config->sortby];
		$limit = $this->config->numberofdonations;
		
		$now = time();
		if ($this->config->localcourse){
			$select = " course = $COURSE->id AND globalview = 1 AND deadline < ? ";
		} else {
			$select = " globalview = 1 AND deadline > ? ";
		}
		$donations = $DB->get_records_select('donationsingle', $select, array($now), $sort, '*', 0, $limit); 

		foreach($donations as $donation) { 			
			$coursecontext = context_course::instance(CONTEXT_COURSE, $donation->course);
			$courseenrollable = $DB->get_field('course', 'enrollable', array('id' => $donation->course));
			if (!has_capability('moodle/course:view', $coursecontext, $USER->id, false) && !$courseenrollable) continue;

			$amountneeded = $donation->amountneeded;
			$amountraised = $donation->amountraised;
			$length = 60;
			$leftsize = (int)(($amountraised/$amountneeded)*$length);
			$rightsize = (int)((1 - $amountraised/$amountneeded)*$length);
			$percentage = (int)(($amountraised*100)/$amountneeded);

			$this->content->items[] = format_string($donation->name).'   ('.$percentage.'%)'; 
			
			$this->content->icons[] = "
			<a href = \"{$CFG->wwwroot}/mod/donationsingle/view.php?a={$donation->id}\">
			<table border= \"0px\" cellspacing=\"0px\" cellpadding=\"0px\" marginwidth=\"0px\" marginheigth=\"0px\" margin-left=\"0px\" margin-top=\"0px\">
	
				<td><img src= \"{$CFG->wwwroot}/mod/donationsingle/pics/left_end.gif\" height=\"10\" width=\"2\" ></td>
				<td><img src= \"{$CFG->wwwroot}/mod/donationsingle/pics/left.gif\" height=\"10\" width= \"{$leftsize}\"></td>
				<td><img src= \"{$CFG->wwwroot}/mod/donationsingle/pics/cursor_simple.gif\" height=\"10\" width=\"2\"></td>
				<td><img src= \"{$CFG->wwwroot}/mod/donationsingle/pics/right.gif\" height=\"10\" width= \"{$rightsize}\"></td>
				<td><img src= \"{$CFG->wwwroot}/mod/donationsingle/pics/right_end.gif\" height=\"10\" width=\"2\"></td>
	
			</table>
			</a>
			" ;
		}
        return $this->content;
    }

    function applicable_formats() {
        return array('all' => true, 'mod' => false, 'my' => false, 'admin' => false,
                     'tag' => false);
    }

	function preferred_width() {
		// The preferred value is in pixels
		return 400;
	}
	
	function instance_allow_config() {
		return true;
	}

	function instance_config_print() {
        global $CFG, $COURSE, $DB, $OUTPUT;

    /// set up the numberoftags select field
        $numberofdonations = array();
		$limit = $DB->count_records('donationsingle', 'course', $COURSE->id);
        for($i = 1;$i <= $limit; $i++) $numberofdonations[$i] = $i;

        if (is_file($CFG->dirroot .'/blocks/'. $this->name() .'/config_instance.html')) {
            echo $OUTPUT->box_start('center', '', '', 5, 'blockconfigglobal');
            include($CFG->dirroot .'/blocks/'. $this->name() .'/config_instance.html');
            echo $OUTPUT->box_end();
        } else {
            echo $OUTPUT->notification(get_string('blockconfigbad'), str_replace('blockaction=', 'dummy=', qualified_me()));
        }
    }

	function config_save($data) {
		// Default behavior: save all variables as $CFG properties
		foreach ($data as $name => $value) {
			 set_config($name, $value);
		 }
		 return true;
	}
}

?>
