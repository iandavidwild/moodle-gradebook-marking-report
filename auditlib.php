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
 * Library code for the grade item audit trail. Based on locallib.php,
 * from report_customsql module copyright 2009 open university
 *
 */

define('GRADE_AUDIT_MAX_RECORDS', 5000);

function grade_audit_execute_query($sql, $params = null,
                                        $limitnum = GRADE_AUDIT_MAX_RECORDS) {
    global $CFG, $DB;

    $sql = preg_replace('/\bprefix_(?=\w+)/i', $CFG->prefix, $sql);
    // Note: throws Exception if there is an error
    return $DB->get_recordset_sql($sql, $params, 0, $limitnum);
}

/**
 * Extract all the placeholder names from the SQL.
 * @param string $sql The sql.
 * @return array placeholder names
 */
function grade_audit_get_query_placeholders($sql) {
    preg_match_all('/(?<!:):[a-z][a-z0-9_]*/', $sql, $matches);
    return $matches[0];
}

function grade_audit_generate_csv($sql, $timenow) {
    global $DB;

    $rs = grade_audit_execute_query($sql);

    $csvfilepath = null;
    $csvfilename = null;
    
    foreach ($rs as $row) {
        if (!$csvfilepath && !$csvfilename) {
            list($csvfilepath, $csvfilename) = grade_audit_temp_cvs_name($timenow);

            if (!file_exists($csvfilepath)) {
                $handle = fopen($csvfilepath, 'w');
                grade_audit_start_csv($handle, $row);
            } else {
                $handle = fopen($csvfilepath, 'a');
            }
        }

        $data = get_object_vars($row);
        
        grade_audit_write_csv_row($handle, $data);
    }
    $rs->close();

    if (!empty($handle)) {
        fclose($handle);
    }

    return array($csvfilepath, $csvfilename);
}

function grade_audit_temp_cvs_name($timestamp) {
    global $CFG;
    $path = 'grade_audit/temp';
    make_upload_directory($path);
    $filename = strftime('%Y-%m-%d--%H-%M-%S', intval($timestamp)).'.csv';
    $filepath = $CFG->dataroot.'/'.$path.'/'.$filename;
    return array($filepath, $filename);
}

function grade_audit_url($relativeurl) {
    global $CFG;
    return $CFG->wwwroot.'/grade/report/marking/'.$relativeurl;
}

function grade_audit_capability_options() {
    return array(
        'report/grade_audit:view' => get_string('anyonewhocanveiwthisreport', 'grade_audit'),
        'moodle/site:viewreports' => get_string('userswhocanviewsitereports', 'grade_audit'),
    );
}

function grade_audit_bad_words_list() {
    return array('ALTER', 'CREATE', 'DELETE', 'DROP', 'GRANT', 'INSERT', 'INTO',
                 'TRUNCATE', 'UPDATE');
}

function grade_audit($string) {
    return preg_match('/\b('.implode('|', grade_audit_bad_words_list()).')\b/i', $string);
}

function grade_audit_pretify_column_names($row) {
    $colnames = array();
    foreach (get_object_vars($row) as $colname => $ignored) {
        $colnames[] = str_replace('_', ' ', $colname);
    }
    return $colnames;
}

function grade_audit_write_csv_row($handle, $data) {
    global $CFG;
    $escapeddata = array();
    foreach ($data as $value) {
        $value = str_replace('%%WWWROOT%%', $CFG->wwwroot, $value);
        $escapeddata[] = '"'.str_replace('"', '""', $value).'"';
    }
    fwrite($handle, implode(',', $escapeddata)."\r\n");
}

function grade_audit_start_csv($handle, $firstrow) {
    $colnames = grade_audit_pretify_column_names($firstrow);
    
    grade_audit_write_csv_row($handle, $colnames);
}

/**
 * Delete files that were last accessed over 24 hours ago.
 *
 * @return int
 */
function grade_audit_delete_old_temp_files() {
    global $CFG;

    $count = 0;
   
    $files = glob($CFG->dataroot.'/grade_audit/temp/*.csv');
    if (empty($files)) {
        return;
    }
    foreach ($files as $file) {
        $last_access_time = filectime($file);
        $time_now = time();
        if( ($time_now - $last_access_time) > 86400) {
            if(unlink($file) ) {
                $count += 1;
            }
        }
    }

    return $count;
}
