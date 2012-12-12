<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Script to view grade audit trail. Based on view.php, part of customsql, copyright 2009 The Open University
 *
 */

global $CFG, $OUTPUT, $PAGE;

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/report/marking/auditlib.php';
require_once '../../lib.php';
require_once $CFG->libdir.'/adminlib.php';
require_once $CFG->libdir.'/validateurlsyntax.php';

$courseid = required_param('courseid', PARAM_INT);
$id       = optional_param('id', 0, PARAM_INT);
$itemid   = optional_param('itemid', 0, PARAM_INT);
$userid   = optional_param('userid', 0, PARAM_INT);

$url = new moodle_url('/grade/report/marking/audit.php', array('courseid'=>$courseid));
if ($id !== 0) {
	$url->param('id', $id);
}
if ($itemid !== 0) {
	$url->param('itemid', $itemid);
}
if ($userid !== 0) {
	$url->param('userid', $userid);
}
$PAGE->set_url($url);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
	print_error('nocourseid');
}

$PAGE->set_pagelayout('incourse');
require_login($course);

$context = get_context_instance(CONTEXT_COURSE, $course->id);
if (!has_capability('moodle/grade:manage', $context)) {
	require_capability('moodle/grade:edit', $context);
}

// security checks!
if (!empty($id)) {
	if (!$grade = $DB->get_record('grade_grades', array('id' => $id))) {
		print_error('invalidgroupid');
	}

	if (!empty($itemid) and $itemid != $grade->itemid) {
		print_error('invaliditemid');
	}
	$itemid = $grade->itemid;

	if (!empty($userid) and $userid != $grade->userid) {
		print_error('invaliduser');
	}
	$userid = $grade->userid;

	unset($grade);

} else if (empty($userid) or empty($itemid)) {
	print_error('missinguseranditemid');
}

if (!$grade_item = grade_item::fetch(array('id'=>$itemid, 'courseid'=>$courseid))) {
	print_error('cannotfindgradeitem');
}

// now verify grading user has access to all groups or is member of the same group when separate groups used in course
if (groups_get_course_groupmode($COURSE) == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
	if ($groups = groups_get_all_groups($COURSE->id, $userid)) {
		$ok = false;
		foreach ($groups as $group) {
			if (groups_is_member($group->id, $USER->id)) {
				$ok = true;
			}
		}
		if (!$ok) {
			print_error('cannotgradeuser');
		}
	} else {
		print_error('cannotgradeuser');
	}
}

// Start the page.
/// Print header
$reportname = get_string('grade_audit', 'gradereport_marking'); // ... which it isn't, but that will do for now.
print_grade_page_head($COURSE->id, 'report', 'marking', $reportname, false, '');

// Output column headings
$time_heading = get_string('heading_time', 'gradereport_marking');
$idnumber_heading = get_string('heading_course_idnumber', 'gradereport_marking');
$fullname_heading = get_string('heading_course_fullname', 'gradereport_marking');
$username_heading = get_string('heading_student_username', 'gradereport_marking');
$student_number_heading = get_string('heading_student_number', 'gradereport_marking');
$student_name_heading = get_string('heading_student_name', 'gradereport_marking');
$item_name_heading = get_string('heading_item_name', 'gradereport_marking');
$final_grade_heading = get_string('heading_final_grade', 'gradereport_marking');
$feedback_heading = get_string('heading_feedback', 'gradereport_marking');
$action_heading = get_string('heading_action', 'gradereport_marking');
$editor_heading = get_string('heading_editor', 'gradereport_marking');

// the SQL query:
$report = 
"SELECT DISTINCT

'{$course->idnumber}' AS '{$idnumber_heading}',
'{$course->fullname}' AS '{$fullname_heading}',
u.username AS '{$username_heading}',
u.idnumber AS '{$student_number_heading}',
CONCAT(u.firstname,' ', u.lastname) AS '{$student_name_heading}',
gi.itemname AS '{$item_name_heading}',
gh.finalgrade AS '{$final_grade_heading}',
gh.feedback AS '{$feedback_heading}',

CASE
  WHEN gh.action=1 THEN 'inserted'
  WHEN gh.action=2 THEN 'edited'
  WHEN gh.action=3 THEN 'deleted'
END AS 'Action',
CONCAT(lu.firstname,' ', lu.lastname) AS '{$editor_heading}',
FROM_UNIXTIME(gh.timemodified, '%Y-%m-%d %H:%i:%s') AS '{$time_heading}'

FROM prefix_grade_grades_history AS gh
JOIN prefix_user AS lu ON lu.id=gh.loggeduser
JOIN prefix_user AS u ON u.id=gh.userid
JOIN prefix_grade_items AS gi ON gh.itemid=gi.id

WHERE u.id='{$userid}' AND gh.itemid='{$itemid}' ORDER BY '{$time_heading}' ASC";

// Clean up on the way in 
grade_audit_delete_old_temp_files();

$csvfilename = NULL; // just the name of the file, e.g. XXXXX.csv
$csvfilepath = NULL; // full path to the file

try {
	list($csvfilepath, $csvfilename) = grade_audit_generate_csv($report, time());
} catch (Exception $e) {
	print_error('queryfailed', 'gradereport_marking', grade_audit_url('index.php'),
			$e->getMessage());
}

// output some details about this report...
$course_details = get_string('grade_audit_course', 'gradereport_marking').$courseid;
$user_details = get_string('grade_audit_user', 'gradereport_marking').$userid;
$item_details = get_string('grade_audit_item', 'gradereport_marking').$itemid;
$sql_query = get_string('grade_audit_query', 'gradereport_marking').$report;

debugging(format_text($course_details.', '.$user_details.', '.$item_details.', <br/>'.$sql_query, FORMAT_HTML), DEBUG_DEVELOPER);

$count = 0;
if (is_null($csvfilepath)) {
    echo html_writer::tag('p', get_string('noaudittrail', 'gradereport_marking'));
} else {
    debugging(format_text('$csvfilepath: '.$csvfilepath, FORMAT_HTML), DEBUG_DEVELOPER);
    debugging(format_text('$csvfilename: '.$csvfilename, FORMAT_HTML), DEBUG_DEVELOPER);
    
    if (!is_readable($csvfilepath)) {
        echo html_writer::tag('p', get_string('filereaderror', 'gradereport_marking'). $csvfilepath);
    } else {
        $handle = fopen($csvfilepath, 'r');

        $table = new html_table();
        $table->head = fgetcsv($handle);

        while ($row = fgetcsv($handle)) {
            $rowdata = array();
            foreach ($row as $value) {
                if (validateUrlSyntax($value, 's+H?S?F?E?u-P-a?I?p?f?q?r?')) {
                    $rowdata[] = '<a href="' . $value . '">' . $value . '</a>';
                } else {
                    $rowdata[] = $value;
                }
            }
            $table->data[] = $rowdata;
            $count += 1;
        }

        fclose($handle);
        echo html_writer::table($table);

        if ($count >= GRADE_AUDIT_MAX_RECORDS) {
            echo html_writer::tag('p', get_string('recordlimitreached', 'gradereport_marking',
                                                  GRADE_AUDIT_MAX_RECORDS),
                                                  array('class' => 'admin_note'));
        }
        echo html_writer::start_tag('p').
             html_writer::tag('a', get_string('downloadthisreportascsv', 'gradereport_marking'),
                              array('href' => new moodle_url(grade_audit_url('download.php'),
                              array('filename' => $csvfilename)))).
             html_writer::end_tag('p');
    }
}

$imglarrow = html_writer::tag('img', '', array('src' => $OUTPUT->pix_url('t/collapsed_rtl'),
                              'class' => 'iconsmall',
                              'alt' => ''));
echo html_writer::start_tag('p').
     $OUTPUT->action_link(new moodle_url(grade_audit_url('index.php?id='.$courseid)), $imglarrow.
                                         get_string('backtomarkingreport', 'gradereport_marking')).
     html_writer::end_tag('p').
     $OUTPUT->footer();

?>