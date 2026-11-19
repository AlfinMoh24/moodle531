<?php
// Builds a local replica of the ASANA-PM-2026 course structure.
// Run: php tools/build-asana-course.php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->libdir . '/completionlib.php');

global $DB, $USER;
$USER = get_admin();

function t2h(string $text): string {
    $text = trim($text);
    $parts = preg_split('/\n\s*\n/', $text);
    $html = '';
    foreach ($parts as $p) {
        $html .= '<p>' . nl2br(htmlspecialchars($p, ENT_QUOTES, 'UTF-8')) . '</p>';
    }
    return $html;
}

function make_mod($course, $section, $modname, $name, array $extra = []) {
    $m = (object)array_merge([
        'modulename' => $modname,
        'course' => $course->id,
        'section' => $section,
        'visible' => 1,
        'name' => $name,
        'intro' => '',
        'introformat' => FORMAT_HTML,
        'introeditor' => ['text' => '', 'format' => FORMAT_HTML, 'itemid' => 0],
    ], $extra);
    if ($m->modulename === 'quiz' && !isset($m->quizpassword)) {
        $m->quizpassword = '';
        $m->subnet = '';
    }
    try {
        $r = create_module($m);
        echo "OK  [$modname] $name (cmid={$r->coursemodule})\n";
        return $r;
    } catch (Throwable $e) {
        echo "FAIL [$modname] $name : " . $e->getMessage() . "\n";
        return null;
    }
}

// ---- Category ----
$catname = 'Project Management';
$cat = $DB->get_record('course_categories', ['idnumber' => 'CT-100']);
if (!$cat) {
    $cat = core_course_category::create(['name' => $catname, 'idnumber' => 'CT-100', 'parent' => 0, 'visible' => 1]);
    echo "category created id={$cat->id}\n";
} else {
    echo "category exists id={$cat->id}\n";
}

// ---- Course ----
$summary = '<p><strong>Training Project Management dan Task Collaboration Menggunakan Asana: dari Beginner hingga Advanced</strong> adalah program 6 minggu yang membekali peserta dengan kemampuan mengelola proyek end-to-end menggunakan Asana: dari dasar task management, perencanaan dan visualisasi (List, Board, Timeline, Calendar), kolaborasi tim, otomasi dan standardisasi (custom fields, rules, forms, templates), hingga portofolio, goals, workload, dan pelaporan untuk pengambilan keputusan.</p><p>Peserta menyelesaikan kuis bertahap, tugas individu dan kelompok, serta capstone project berbasis proyek nyata. Nilai kelulusan 75/100, dengan sertifikat bagi peserta yang memenuhi kriteria penyelesaian course.</p>';

$course = $DB->get_record('course', ['shortname' => 'ASANA-PM-2026']);
if (!$course) {
    $course = create_course((object)[
        'fullname' => 'Training Project Management dan Task Collaboration Menggunakan Asana: dari Beginner hingga Advanced',
        'shortname' => 'ASANA-PM-2026',
        'idnumber' => 'ASANA-PM-2026',
        'category' => $cat->id,
        'summary' => $summary,
        'summaryformat' => FORMAT_HTML,
        'format' => 'topics',
        'visible' => 1,
        'startdate' => 1794560220,
        'enddate' => 1826096220,
        'enablecompletion' => 1,
        'showactivitydates' => 1,
        'showcompletionconditions' => 1,
    ]);
    echo "course created id={$course->id}\n";
} else {
    echo "course exists id={$course->id}\n";
}

$DB->set_field('course', 'enablecompletion', 1, ['id' => $course->id]);
course_create_sections_if_missing($course, range(0, 7));
rebuild_course_cache($course->id, true);

$sectionnames = [
    0 => 'General',
    1 => '1. Beginner - Dasar Asana dan Konsep Project Management',
    2 => '2. Intermediate - Perencanaan dan Visualisasi Proyek',
    3 => '3. Intermediate - Kolaborasi Tim',
    4 => '4. Advanced - Otomasi, Custom Field, dan Standarisasi',
    5 => '5. Advanced - Portofolio, Goals, Workload, dan Pelaporan',
    6 => '6. Capstone dan Penutup',
    7 => 'Sertifikat',
];
foreach ($sectionnames as $num => $sname) {
    $sec = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $num]);
    if ($sec && trim($sec->name) !== $sname) {
        $DB->set_field('course_sections', 'name', $sname, ['id' => $sec->id]);
    }
}

// Enrol admin as editing teacher.
$enrol = enrol_get_plugin('manual');
$instances = enrol_get_instances($course->id, false);
$inst = reset($instances);
$role = $DB->get_record('role', ['shortname' => 'editingteacher']);
if ($inst && $role && !$DB->record_exists('user_enrolments', ['enrolid' => $inst->id, 'userid' => $USER->id])) {
    $enrol->enrol_user($inst, $USER->id, $role->id);
    echo "admin enrolled as teacher\n";
}

echo "--- activities ---\n";

$pages = [
    0 => ['Cara memakai course ini', "CARA MEMAKAI COURSE INI\n\nCourse ini dirancang berurutan. Ikuti alur berikut agar Anda mendapatkan hasil maksimal.\n\n\t* PRE-TEST (SECTION 0) - 10 soal tanpa nilai.\n\t* SECTION 1-5 - materi bertingkat: bacaan (Book/Page), latihan, dan kuis.\n\t* SECTION 6 (CAPSTONE) - proyek nyata end-to-end secara berkelompok, lalu Ujian Akhir.\n\nYANG PERLU ANDA SIAPKAN\n\n\t* Akun Asana (paket gratis sudah cukup untuk sebagian besar latihan).\n\t* Dua browser atau dua tab: satu untuk Moodle, satu untuk Asana.\n\t* Waktu belajar sekitar 4-6 jam per minggu.\n\nATURAN PENILAIAN\n\n\t* Kuis dapat diulang sesuai pengaturan tiap kuis; nilai tertinggi yang dipakai.\n\t* Tugas dinilai dengan rubrik/marking guide yang terlihat sebelum Anda mengumpulkan.\n\t* Kehadiran minimal 80% menjadi syarat kelulusan."],
    2 => ['List, Board, Timeline, dan Calendar View', "EMPAT CARA MEMANDANG PEKERJAAN YANG SAMA\n\nDi Asana, satu set task dapat dilihat dari beberapa sudut. Pilihan view bukan soal selera, melainkan soal keputusan apa yang ingin Anda ambil.\n\nLIST VIEW - UNTUK EKSEKUSI HARIAN\n\nTampilan tabel berisi task, assignee, due date, dan kolom custom field. Paling efektif untuk rapat harian.\n\nBOARD VIEW - UNTUK ALUR KERJA\n\nTask dikelompokkan per section dalam kolom. Cocok ketika pekerjaan bergerak melalui tahap tetap (To Do -> In Progress -> Review -> Done).\n\nTIMELINE VIEW - UNTUK URUTAN DAN KETERGANTUNGAN\n\nGaris waktu berbasis tanggal. Task tanpa tanggal tidak akan muncul.\n\nCALENDAR VIEW - UNTUK TENGGAT DAN KAPASITAS\n\nMenyoroti apa yang jatuh tempo pada tanggal tertentu.\n\nMILESTONE DAN DEPENDENCY\n\n\t* MILESTONE menandai pencapaian penting tanpa durasi.\n\t* DEPENDENCY menyatakan bahwa task B menunggu task A.\n\nLATIHAN: buka satu project Anda, alihkan ke Timeline. Setiap task yang tidak muncul adalah task yang belum punya tanggal."],
    3 => ['Kolaborasi Tim: Komentar, Akses, Integrasi, dan Approval', "KOLABORASI BUKAN SOAL RAPAT, TAPI SOAL JEJAK KEPUTUSAN\n\nSetiap keputusan harus meninggalkan jejak di tempat pekerjaan itu hidup: task.\n\nKOMENTAR DAN MENTION\n\n\t* Tulis keputusan dan alasannya di komentar task.\n\t* Gunakan @mention hanya untuk orang yang perlu bertindak atau memutuskan.\n\t* Tanggapi pertanyaan dengan jawaban yang menutup: keputusan, penanggung jawab, dan tenggat.\n\nTIM DAN HAK AKSES\n\nAsana memisahkan akses pada level workspace, team, dan project. Beri akses seluas yang dibutuhkan untuk bekerja, sesempit yang memungkinkan untuk data sensitif.\n\nPROJECT PERMISSION\n\n\t* PROJECT ADMIN - mengatur anggota, field, dan aturan.\n\t* EDITOR - membuat dan mengubah task.\n\t* COMMENTER - hanya berkomentar.\n\t* VIEWER - hanya melihat.\n\nINTEGRASI\n\nIntegrasi Google Drive dan Slack mempercepat reaksi, tetapi keputusan tetap harus dicatat di task.\n\nPROOFING DAN APPROVALS\n\nGunakan proofing agar komentar menempel pada bagian desain tertentu, dan approval untuk persetujuan formal."],
    4 => ['Otomasi, Custom Field, Forms, dan Templates', "DARI PEKERJAAN MANUAL KE SISTEM YANG BERJALAN SENDIRI\n\nOtomasi yang baik menghilangkan pekerjaan berulang yang tidak butuh pertimbangan manusia.\n\nCUSTOM FIELDS\n\nCustom field menambahkan data terstruktur: Prioritas, Tim, Anggaran, Status Persetujuan. Gunakan dropdown alih-alih teks bebas.\n\nRULES (OTOMASI)\n\nRule berjalan berdasarkan pemicu: task masuk section, due date lewat, atau custom field berubah.\n\n\t* Saat task dipindah ke \"Review\", assign ke reviewer dan set due date 2 hari.\n\t* Saat due date lewat, tambahkan komentar penanda dan mention pemilik.\n\t* Saat prioritas menjadi Tinggi, tambahkan ke project eskalasi.\n\nFORMS (INTAKE REQUEST)\n\nForm memberi pintu masuk yang rapi dengan field wajib terisi.\n\nTEMPLATES DAN BUNDLES\n\nTemplate menyimpan struktur project yang terbukti berhasil.\n\nTASK APPROVAL DAN RECURRING TASK\n\nApproval memaksa keputusan tercatat; recurring task membuat pekerjaan berkala otomatis."],
    5 => ['Portofolio, Goals, Workload, dan Pelaporan', "DARI PEKERJAAN KE KEPUTUSAN\n\nPORTFOLIOS\n\nPortfolio mengelompokkan beberapa project agar kesehatan pekerjaan terlihat pada tingkat program.\n\nGOALS DAN OKR\n\nSetiap goal punya ukuran keberhasilan dan pemilik. OKR memecahnya menjadi objective dan key result.\n\nWORKLOAD\n\nWorkload memperlihatkan beban tiap anggota berdasarkan jam atau jumlah task.\n\nREPORTING DAN DASHBOARD\n\nDashboard yang baik menjawab satu pertanyaan manajerial, bukan menampilkan semua angka.\n\nASANA AI\n\nAsana AI membantu merangkum status dan menyusun draft. Ketersediaan bergantung pada paket langganan.\n\nMANAJEMEN RISIKO DAN STATUS UPDATE\n\nSimpan risiko dengan tiga kolom: kemungkinan, dampak, dan mitigasi. Status update menjawab: apa yang selesai, apa berikutnya, apa yang menghambat, dan keputusan apa yang dibutuhkan."],
    6 => ['Panduan Capstone (Kelompok)', "CAPSTONE: PROYEK NYATA END-TO-END\n\nCapstone dikerjakan berkelompok (Tim A / Tim B) selama minggu ke-6.\n\nLINGKUP\n\n\t* INISIASI - tujuan proyek, stakeholder, dan matriks RACI.\n\t* PERENCANAAN - WBS, 3 section minimum, 15 task, 3 dependency, 2 milestone, custom field.\n\t* EKSEKUSI & KOLABORASI - komentar keputusan, approval, form intake.\n\t* PELAPORAN - satu dashboard atau status update dengan 3 metrik.\n\nDELIVERABLE\n\n\t* Tautan share project Asana.\n\t* Dokumen ringkasan maksimal 2 halaman (PDF).\n\t* Video presentasi 5 menit.\n\nKRITERIA PENILAIAN\n\nStruktur proyek, kualitas perencanaan, kolaborasi dan jejak keputusan, kualitas pelaporan, dan kejelasan presentasi."],
];

// General section.
make_mod($course, 0, 'attendance', 'Attendance', ['grade' => 100]);
$hasnewsforum = $DB->record_exists_sql("SELECT 1 FROM {forum} WHERE course = ? AND type = 'news'", [$course->id]);
if (!$hasnewsforum) {
    make_mod($course, 0, 'forum', 'Announcements', ['type' => 'news', 'forcesubscribe' => 1]);
}
make_mod($course, 0, 'label', 'Selamat datang di Training Asana Project Managemen...', ['intro' => '<p>Selamat datang di Training Asana Project Management. Pelajari materi secara berurutan, kerjakan kuis, dan selesaikan capstone project.</p>', 'introformat' => FORMAT_HTML]);
make_mod($course, 0, 'page', $pages[0][0], ['content' => t2h($pages[0][1]), 'contentformat' => FORMAT_HTML, 'printintro' => 0, 'printlastmodified' => 1, 'display' => 0, 'revision' => 1]);
make_mod($course, 0, 'quiz', 'Pre-Test: Pemetaan Pengetahuan Awal', ['grade' => 100, 'sumgrades' => 0, 'preferredbehaviour' => 'deferredfeedback', 'attempts' => 0, 'grademethod' => 1, 'questionsperpage' => 1, 'decimalpoints' => 2, 'questiondecimalpoints' => -1, 'shuffleanswers' => 1, 'timeopen' => 0, 'timeclose' => 0, 'timelimit' => 0, 'overduehandling' => 'autoabandon', 'attemptonlast' => 0, 'correctanswershow' => 1]);

// Section 1.
make_mod($course, 1, 'attendance', 'Presensi Beginner - Dasar Asana dan Konsep Project Management', ['grade' => 100]);
$b1 = make_mod($course, 1, 'book', 'Konsep Dasar Project Management', ['numbering' => 1, 'customtitles' => 0]);
$b2 = make_mod($course, 1, 'book', 'Mengenal Asana', ['numbering' => 1, 'customtitles' => 0]);
make_mod($course, 1, 'url', 'Asana Help Center (dokumentasi resmi)', ['externalurl' => 'https://help.asana.com/', 'display' => 0]);
make_mod($course, 1, 'glossary', 'Glossary Istilah Project Management', ['displayformat' => 'dictionary', 'allowduplicates' => 1, 'allowcomments' => 0, 'allowprintview' => 1, 'usedynalink' => 1, 'defaultapproval' => 1, 'globalglossary' => 0, 'visible' => 1, 'showsalphaindex' => 1, 'assessment' => 0]);
make_mod($course, 1, 'quiz', 'Quiz 1: Dasar Asana dan Konsep Project Management', ['grade' => 100, 'sumgrades' => 0, 'preferredbehaviour' => 'deferredfeedback', 'attempts' => 0, 'grademethod' => 1, 'questionsperpage' => 1, 'decimalpoints' => 2, 'questiondecimalpoints' => -1, 'shuffleanswers' => 1, 'timeopen' => 0, 'timeclose' => 0, 'timelimit' => 0, 'overduehandling' => 'autoabandon', 'attemptonlast' => 0, 'correctanswershow' => 1]);

// Section 2.
make_mod($course, 2, 'attendance', 'Presensi Intermediate - Perencanaan dan Visualisasi Proyek', ['grade' => 100]);
make_mod($course, 2, 'page', $pages[2][0], ['content' => t2h($pages[2][1]), 'contentformat' => FORMAT_HTML, 'printintro' => 0, 'printlastmodified' => 1, 'display' => 0, 'revision' => 1]);
make_mod($course, 2, 'quiz', 'Quiz 2: Perencanaan dan Visualisasi Proyek', ['grade' => 100, 'sumgrades' => 0, 'preferredbehaviour' => 'deferredfeedback', 'attempts' => 0, 'grademethod' => 1, 'questionsperpage' => 1, 'decimalpoints' => 2, 'questiondecimalpoints' => -1, 'shuffleanswers' => 1, 'timeopen' => 0, 'timeclose' => 0, 'timelimit' => 0, 'overduehandling' => 'autoabandon', 'attemptonlast' => 0, 'correctanswershow' => 1]);

// Section 3.
make_mod($course, 3, 'attendance', 'Presensi Intermediate - Kolaborasi Tim', ['grade' => 100]);
make_mod($course, 3, 'page', $pages[3][0], ['content' => t2h($pages[3][1]), 'contentformat' => FORMAT_HTML, 'printintro' => 0, 'printlastmodified' => 1, 'display' => 0, 'revision' => 1]);
make_mod($course, 3, 'forum', 'Forum Diskusi (Q&A): Tantangan Kolaborasi di Tim Anda', ['type' => 'general', 'forcesubscribe' => 0]);
make_mod($course, 3, 'quiz', 'Quiz 3: Kolaborasi Tim di Asana', ['grade' => 100, 'sumgrades' => 0, 'preferredbehaviour' => 'deferredfeedback', 'attempts' => 0, 'grademethod' => 1, 'questionsperpage' => 1, 'decimalpoints' => 2, 'questiondecimalpoints' => -1, 'shuffleanswers' => 1, 'timeopen' => 0, 'timeclose' => 0, 'timelimit' => 0, 'overduehandling' => 'autoabandon', 'attemptonlast' => 0, 'correctanswershow' => 1]);

// Section 4.
make_mod($course, 4, 'attendance', 'Presensi - Advanced Otomasi, Custom Field, dan Standarisasi', ['grade' => 100]);
make_mod($course, 4, 'page', $pages[4][0], ['content' => t2h($pages[4][1]), 'contentformat' => FORMAT_HTML, 'printintro' => 0, 'printlastmodified' => 1, 'display' => 0, 'revision' => 1]);
make_mod($course, 4, 'feedback', 'Survei Kebutuhan Otomasi Organisasi', ['anonymous' => 1, 'email_notification' => 0, 'multiple_submit' => 0, 'autonumbering' => 1, 'page_after_submit' => '', 'page_after_submitformat' => FORMAT_HTML]);
make_mod($course, 4, 'quiz', 'Quiz 4: Otomasi dan Standardisasi', ['grade' => 100, 'sumgrades' => 0, 'preferredbehaviour' => 'deferredfeedback', 'attempts' => 0, 'grademethod' => 1, 'questionsperpage' => 1, 'decimalpoints' => 2, 'questiondecimalpoints' => -1, 'shuffleanswers' => 1, 'timeopen' => 0, 'timeclose' => 0, 'timelimit' => 0, 'overduehandling' => 'autoabandon', 'attemptonlast' => 0, 'correctanswershow' => 1]);

// Section 5.
make_mod($course, 5, 'attendance', 'Presensi Advanced - Portofolio, Goals, Workload, dan Pelaporan', ['grade' => 100]);
make_mod($course, 5, 'page', $pages[5][0], ['content' => t2h($pages[5][1]), 'contentformat' => FORMAT_HTML, 'printintro' => 0, 'printlastmodified' => 1, 'display' => 0, 'revision' => 1]);
make_mod($course, 5, 'lesson', 'Lesson: Membaca Dashboard dan Mengambil Keputusan', ['practice' => 0, 'modattempts' => 0, 'usepassword' => 0, 'password' => '', 'dependency' => 0, 'conditions' => '', 'grade' => 100, 'custom' => 0, 'ongoing' => 0, 'usemaxgrade' => 0, 'maxanswers' => 4, 'maxpages' => 0, 'review' => 0, 'nextpagedefault' => 0, 'feedback' => 1, 'minquestions' => 0, 'maxattempts' => 0, 'retake' => 0, 'timed' => 0, 'mediafile' => 0, 'mediaheight' => 100, 'mediawidth' => 650, 'mediaclose' => 0, 'slideshow' => 0, 'width' => 640, 'height' => 480, 'bgcolor' => '#ffffff', 'displayleft' => 0, 'displayleftif' => 0, 'progressbar' => 0, 'allowofflineattempts' => 0]);

// Section 6.
make_mod($course, 6, 'attendance', 'Presensi - CourseTraining Project Management dan Task Collaboration Menggunakan Asana', ['grade' => 100]);
make_mod($course, 6, 'quiz', 'Ujian Akhir (Final Quiz)', ['grade' => 100, 'sumgrades' => 0, 'preferredbehaviour' => 'deferredfeedback', 'attempts' => 0, 'grademethod' => 1, 'questionsperpage' => 1, 'decimalpoints' => 2, 'questiondecimalpoints' => -1, 'shuffleanswers' => 1, 'timeopen' => 0, 'timeclose' => 0, 'timelimit' => 0, 'overduehandling' => 'autoabandon', 'attemptonlast' => 0, 'correctanswershow' => 1]);
make_mod($course, 6, 'forum', 'Forum Pengumuman Penutup', ['type' => 'news', 'forcesubscribe' => 1]);
make_mod($course, 6, 'page', $pages[6][0], ['content' => t2h($pages[6][1]), 'contentformat' => FORMAT_HTML, 'printintro' => 0, 'printlastmodified' => 1, 'display' => 0, 'revision' => 1]);

// Section 7.
make_mod($course, 7, 'questionnaire', 'Questionnaire', []);
make_mod($course, 7, 'customcert', 'Sertifikat', ['requiredtime' => 0]);

rebuild_course_cache($course->id, true);
echo "done. url: {$CFG->wwwroot}/course/view.php?id={$course->id}\n";
