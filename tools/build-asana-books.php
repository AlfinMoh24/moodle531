<?php
// Adds the book chapters and lesson content for the ASANA-PM-2026 replica.
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');
global $DB;
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

$course = $DB->get_record('course', ['shortname' => 'ASANA-PM-2026'], '*', MUST_EXIST);

$books = [
    'Konsep Dasar Project Management' => [
        ['1.1 Siklus Proyek: Dari Inisiasi ke Penutupan', "MENGAPA SIKLUS PROYEK PENTING\n\nProject bukan aktivitas tunggal, melainkan rangkaian fase yang saling bergantung. Tanpa siklus yang jelas, tim mudah terjebak mengerjakan hal yang salah pada waktu yang salah. Siklus proyek memberi Anda peta: apa yang harus diputuskan, siapa yang terlibat, dan bukti apa yang harus dihasilkan di setiap fase.\n\nLIMA FASE STANDAR\n\n\t* INISIASI - merumuskan masalah atau peluang, tujuan, dan batas awal. Output: project charter ringkas dan sponsor yang jelas.\n\t* PERENCANAAN - memecah lingkup menjadi deliverable dan task, menyusun jadwal, anggaran, risiko, serta rencana komunikasi.\n\t* EKSEKUSI - tim mengerjakan task. Fokus manajer proyek bergeser dari menyusun rencana menjadi menghilangkan hambatan.\n\t* MONITORING DAN PENGENDALIAN - membandingkan realisasi dengan baseline: progres, biaya, kualitas, risiko.\n\t* PENUTUPAN - serah terima deliverable, dokumentasi pelajaran yang dipetik, pembubaran tim, dan penutupan kontrak.\n\nKESALAHAN YANG SERING TERJADI\n\n\t* Melewati inisiasi sehingga tujuan proyek tidak pernah disepakati.\n\t* Menulis rencana terlalu detail di awal, lalu tidak pernah memperbaruinya.\n\t* Menunda penutupan sehingga tim tidak pernah belajar dari proyek sebelumnya.\n\nPraktik di Asana: satu project Asana biasanya merepresentasikan satu proyek. Section dapat memetakan fase, sedangkan milestone menandai keputusan penting di akhir setiap fase."],
        ['1.2 Scope: Batas Pekerjaan yang Disepakati', "APA ITU SCOPE\n\nScope adalah batas pekerjaan yang disepakati: apa yang termasuk dan - sama pentingnya - apa yang tidak termasuk. Scope yang tidak dinyatakan secara eksplisit akan diisi oleh asumsi masing-masing pihak.\n\nTIGA KOMPONEN SCOPE\n\n\t* SCOPE STATEMENT - tujuan proyek, deliverable utama, dan kriteria penerimaan.\n\t* WORK BREAKDOWN STRUCTURE (WBS) - dekomposisi deliverable menjadi paket kerja yang dapat diestimasi dan di-assign.\n\t* BATASAN DAN ASUMSI - hal yang tidak boleh dilanggar dan hal yang dianggap benar sampai terbukti sebaliknya.\n\nMENGENDALIKAN PERUBAHAN SCOPE\n\nPerubahan scope bukan hal buruk; yang buruk adalah perubahan tanpa kendali. Terapkan alur sederhana: permintaan diajukan tertulis, dampak dihitung, pemilik keputusan menyetujui atau menolak, lalu keputusan dicatat.\n\nSCOPE CREEP\n\nScope creep adalah penambahan kecil yang berulang tanpa persetujuan formal. Penangkalnya adalah baseline yang disepakati plus kebiasaan mencatat setiap permintaan baru sebagai task terpisah."],
        ['1.3 Stakeholder: Memetakan Kepentingan dan Pengaruh', "DEFINISI\n\nStakeholder adalah siapa pun yang terpengaruh oleh, atau dapat memengaruhi, hasil proyek: sponsor, pengguna akhir, tim teknis, vendor, tim legal, hingga manajemen yang menerima laporan.\n\nMATRIKS PENGARUH DAN KEPENTINGAN\n\n\t* Pengaruh tinggi, kepentingan tinggi - kelola secara aktif (manage closely).\n\t* Pengaruh tinggi, kepentingan rendah - jaga kepuasan (keep satisfied).\n\t* Pengaruh rendah, kepentingan tinggi - jaga informasi (keep informed).\n\t* Pengaruh rendah, kepentingan rendah - pantau (monitor).\n\nLANGKAH PRAKTIS\n\n\t* Daftarkan semua kandidat stakeholder tanpa menyaring.\n\t* Nilai pengaruh dan kepentingan masing-masing pada skala 1-5.\n\t* Tentukan strategi komunikasi: frekuensi, kanal, dan bentuk laporan.\n\t* Tinjau ulang daftar ini di setiap akhir fase.\n\nJEBAKAN UMUM\n\nMelupakan pihak yang tidak terlibat rapat harian, seperti tim dukungan pelanggan atau tim legal, sering berujung pada penolakan di akhir proyek."],
        ['1.4 RACI: Siapa Mengerjakan, Siapa Memutuskan', "EMPAT PERAN\n\n\t* RESPONSIBLE - pihak yang mengerjakan pekerjaan.\n\t* ACCOUNTABLE - satu orang yang menanggung jawaban akhir dan berwenang memutuskan.\n\t* CONSULTED - pihak yang dimintai masukan sebelum keputusan diambil.\n\t* INFORMED - pihak yang diberi tahu setelah keputusan atau progres.\n\nATURAN YANG MEMBUAT RACI BERGUNA\n\n\t* Satu deliverable, satu Accountable. Bila ada dua, keputusan akan macet.\n\t* Jangan menempatkan satu orang di semua kolom.\n\t* Bedakan tegas Consulted dan Informed.\n\t* Tempelkan RACI ke deliverable, bukan ke aktivitas harian.\n\nPENERAPAN DI ASANA\n\nGunakan assignee untuk Responsible. Simpan Accountable di custom field (misalnya \"Owner Keputusan\"). Untuk proyek besar, satu baris RACI dapat dijadikan satu task dengan subtask per deliverable."],
    ],
    'Mengenal Asana' => [
        ['1.5 Anatomi Asana: Workspace, Team, Project, Task', "HIERARKI OBJEK\n\n\t* WORKSPACE - organisasi tempat semua pekerjaan berada.\n\t* TEAM - kelompok orang dengan akses ke project tertentu.\n\t* PROJECT - wadah pekerjaan dengan tujuan tertentu.\n\t* SECTION - kolom pengelompokan di dalam project, umumnya merepresentasikan fase atau status.\n\t* TASK - unit kerja dengan assignee, due date, deskripsi, lampiran, dan komentar.\n\t* SUBTASK - pecahan task yang dapat di-assign terpisah.\n\nELEMEN PENTING PADA TASK\n\n\t* ASSIGNEE - satu orang penanggung jawab utama.\n\t* DUE DATE - tenggat; tanpa ini task tidak akan muncul di Timeline.\n\t* COLLABORATORS - pihak yang mengikuti task tanpa menjadi penanggung jawab.\n\t* KOMENTAR - riwayat diskusi dan keputusan.\n\t* ATTACHMENT - berkas pendukung.\n\nPRAKTIK PENAMAAN YANG SEHAT\n\nGunakan pola Kata kerja + objek + hasil. Contoh: \"Susun brief kampanye Q3\", bukan \"Kampanye\"."],
        ['1.6 Kebiasaan Harian: My Tasks, Inbox, Komentar, Due Date', "MY TASKS\n\nMy Tasks adalah daftar pribadi Anda. Tinjau setiap pagi, tentukan tiga prioritas hari itu, lalu tunda sisanya secara sadar.\n\nINBOX\n\nInbox menampilkan notifikasi yang melibatkan Anda: mention, komentar baru, perubahan assignee, atau permintaan persetujuan. Proses Inbox sampai kosong setiap hari.\n\nKOMENTAR DAN MENTION\n\n\t* Tulis keputusan di komentar, bukan di chat pribadi.\n\t* Gunakan @mention hanya untuk orang yang perlu bertindak atau memberi keputusan.\n\t* Beri komentar yang menutup pertanyaan.\n\nDUE DATE YANG REALISTIS\n\n\t* Tetapkan tanggal berdasarkan kapasitas, bukan harapan.\n\t* Pecah task besar menjadi subtask dengan tenggat bertahap.\n\t* Bila tenggat bergeser, ubah tanggal dan tulis alasannya di komentar."],
    ],
];

foreach ($books as $bookname => $chapters) {
    $book = $DB->get_record('book', ['course' => $course->id, 'name' => $bookname]);
    if (!$book) {
        echo "book not found: $bookname\n";
        continue;
    }
    $existing = $DB->count_records('book_chapters', ['bookid' => $book->id]);
    if ($existing > 0) {
        echo "book already has chapters: $bookname ($existing)\n";
        continue;
    }
    $pagenum = 1;
    foreach ($chapters as $ch) {
        $rec = (object)[
            'bookid' => $book->id,
            'pagenum' => $pagenum,
            'subchapter' => 0,
            'title' => $ch[0],
            'content' => t2h($ch[1]),
            'contentformat' => FORMAT_HTML,
            'hidden' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
            'importsrc' => '',
        ];
        $DB->insert_record('book_chapters', $rec);
        echo "chapter added: $bookname -> $ch[0]\n";
        $pagenum++;
    }
}

// Enable manual completion on all activities of the replica course.
$DB->execute("UPDATE {course_modules} SET completion = 1 WHERE course = ?", [$course->id]);
rebuild_course_cache($course->id, true);
purge_all_caches();
echo "done\n";
