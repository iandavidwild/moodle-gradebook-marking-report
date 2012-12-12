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
 * Script to download the CSV version of a grade audit history report,
 * based on file of the same name from report_customsql, copyright 2009 The Open University
 *
 */

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/report/marking/auditlib.php';

global $USER;

$filename = required_param('filename', PARAM_TEXT);

// Functions to sanity check filename...

// 1. Sanity check the filename as we only want to download marking reports...
$parts=explode(".",$filename);
$extension = $parts[count($parts)-1];

if(strcmp($extension, 'csv') != 0) {
    die();
}

// 2. Ensure the filename doesn't contain '..' anywhere
$dot_result = preg_match('/\.\./', $filename);
if($dot_result) {
    die();
}

// 3. If we get this far then use Moodle's standard sanity checking...
$downloadfilename = clean_filename(strip_tags($filename));

// You also need to be logged in...
require_login();

// 4. Check whether the user is a guest
if ($USER->username=='guest'){
    die();
}

$context = get_context_instance(CONTEXT_SYSTEM);

$path = 'grade_audit/temp';
$filepath = $CFG->dataroot.'/'.$path.'/'.$downloadfilename;

if (!is_readable($filepath)) {
    print_error('unknowndownloadfile', 'gradereport_marking');
}

header('Content-Disposition: attachment; filename="gradeaudit-'.$filename.'"');
header('Content-Type: text/csv; charset=UTF-8');
readfile($filepath);
