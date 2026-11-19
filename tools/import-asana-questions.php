<?php
// Imports the ASANA-PM-2026 questions (exported from the MB2NL demo site via MCP)
// into the local quizzes.
//
// Run: php tools/import-asana-questions.php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

global $DB;
$USER = get_admin();

$course = $DB->get_record('course', ['shortname' => 'ASANA-PM-2026'], '*', MUST_EXIST);
$qdir = __DIR__ . '/questions';

$map = [
    ['quiz' => 'Pre-Test: Pemetaan Pengetahuan Awal', 'files' => ['quiz_0.xml']],
    ['quiz' => 'Quiz 1: Dasar Asana dan Konsep Project Management', 'files' => ['quiz_1.xml']],
    ['quiz' => 'Quiz 2: Perencanaan dan Visualisasi Proyek', 'files' => ['quiz_2.xml']],
    ['quiz' => 'Quiz 3: Kolaborasi Tim di Asana', 'files' => ['quiz_3.xml']],
    ['quiz' => 'Quiz 4: Otomasi dan Standardisasi', 'files' => ['quiz_4.xml']],
    ['quiz' => 'Ujian Akhir (Final Quiz)', 'files' => ['quiz_5.xml', 'quiz_6.xml']],
];

function xml_names(string $file): array {
    $doc = simplexml_load_file($file);
    $names = [];
    foreach ($doc->question as $q) {
        $names[] = (string)$q->name->text;
    }
    return $names;
}

foreach ($map as $item) {
    $quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $item['quiz']]);
    if (!$quiz) {
        echo "quiz not found: {$item['quiz']}\n";
        continue;
    }
    $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    $category = question_get_default_category($context->id, true);

    $existing = $DB->count_records_sql(
        "SELECT COUNT(1) FROM {question_versions} qv
           JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
          WHERE qbe.questioncategoryid = ?", [$category->id]);

    $names = [];
    if ($existing == 0) {
        foreach ($item['files'] as $file) {
            $path = $qdir . '/' . $file;
            if (!file_exists($path)) {
                echo "missing file $path\n";
                continue;
            }
            $qformat = new qformat_xml();
            $qformat->setCategory($category);
            $qformat->setContexts([$context]);
            $qformat->setCourse($course);
            $qformat->setFilename($path);
            $qformat->setRealfilename($file);
            $qformat->setMatchgrades('error');
            $qformat->setCatfromfile(false);
            $qformat->setContextfromfile(false);
            $qformat->setStoponerror(true);
            $qformat->importpreprocess();
            $qformat->importprocess();
            $names = array_merge($names, xml_names($path));
            echo "imported {$item['quiz']} <- $file\n";
        }
    } else {
        echo "questions already exist for {$item['quiz']} ({$existing})\n";
        foreach ($item['files'] as $file) {
            $names = array_merge($names, xml_names($qdir . '/' . $file));
        }
    }

    // Add imported questions to the quiz in XML order (skip already added).
    $slots = $DB->count_records('quiz_slots', ['quizid' => $quiz->id]);
    if ($slots == 0) {
        foreach ($names as $name) {
            $sql = "SELECT qv.questionid
                      FROM {question_versions} qv
                      JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                      JOIN {question} q ON q.id = qv.questionid
                     WHERE qbe.questioncategoryid = ? AND q.name = ?
                  ORDER BY qv.version DESC";
            $qid = $DB->get_field_sql($sql, [$category->id, $name], IGNORE_MISSING);
            if (!$qid) {
                echo "  ! question not found after import: $name\n";
                continue;
            }
            quiz_add_quiz_question($qid, $quiz);
        }
        $sumgrades = (float)$DB->get_field_sql('SELECT SUM(maxmark) FROM {quiz_slots} WHERE quizid = ?', [$quiz->id]);
        $DB->set_field('quiz', 'sumgrades', $sumgrades, ['id' => $quiz->id]);
        echo "  added " . count($names) . " slots to {$item['quiz']}\n";
    } else {
        echo "  quiz already has $slots slots\n";
    }
}

rebuild_course_cache($course->id, true);
purge_all_caches();
echo "done\n";
