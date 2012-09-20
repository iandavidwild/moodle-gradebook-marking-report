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

$filename = required_param('filename', PARAM_TEXT);

require_login();

$context = get_context_instance(CONTEXT_SYSTEM);

$path = 'grade_audit/temp';
$filepath = $CFG->dataroot.'/'.$path.'/'.$filename;

if (!is_readable($filepath)) {
    print_error('unknowndownloadfile', 'gradereport_marking');
}

header('Content-Disposition: attachment; filename="gradeaudit.csv"');
header('Content-Type: text/csv; charset=UTF-8');
readfile($filepath);
