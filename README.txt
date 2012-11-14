Gradebook Marking Report
------------------------

The Marking Report is a customisation of the standard Grader report but also contains a grade history report, based on the Ad-hoc Database Queries report from the Open University.

HOW TO INSTALL
--------------

1. Download all files and copy them into a folder called 'marking' in your Moodle's ./grade/report folder.
2. Copy audit.gif to your theme's ./pix/t folder.
3. From the Settings block, select on Site administration->Notifications to complete the installation.

The Marking report and grade edit pages will need to be styled. Here is some basic styling, which you can add to your theme's 'grade.css' file:

.path-grade-report-marking table#user-grades td.cell span.gradepass {background-color: #C2EBBD;}
.path-grade-report-marking table#user-grades td.cell span.gradefail {background-color: #EBC4BD;}
.path-grade-report-marking table#user-grades td.clickable {cursor: pointer;}
.path-grade-report-marking .markingreportoverlay {background-color:#EEEEEE;border:1px solid black;padding:10px;}
.path-grade-report-marking form {text-align: left;}
.path-grade-report-marking .moving {background-color: #E8EEF7;}
.path-grade-report-marking .gradetreebox {width:70%;padding-bottom:15px;}
.path-grade-report .buttons {text-align:center;}
.path-grade-report-marking .idnumber {margin-left: 15px;}
.path-grade-report-marking .movetarget {position: relative;width: 80px;height: 16px;}
.path-grade-report-marking ul#grade_tree {width: auto;}
.path-grade-report-marking ul#grade_tree li {list-style: none;}
.path-grade-report-marking ul#grade_tree li.category {margin-bottom: 6px;}
.path-grade-report-marking .iconsmall {margin-left: 4px;}