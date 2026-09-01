<?php
$file = 'app/Services/SipintuSyncService.php';
$content = file_get_contents($file);

$search = "return sprintf(
            'Siswa: %d baru, %d diperbarui, %d tidak berubah, %d konflik, %d perlu pemetaan, %d tidak ditemukan, %d error. '
            .'Guru: %d baru, %d diperbarui, %d tidak berubah, %d error.',
            \$s['created'],
            \$s['updated'],
            \$s['unchanged'],
            \$s['conflicts'],
            \$s['needs_mapping'],
            \$s['errors'],
            \$t['created'],
            \$t['updated'],
            \$t['unchanged'],
            \$t['errors'],
        );";

$replace = "return sprintf(
            'Siswa: %d baru, %d diperbarui, %d tidak berubah, %d konflik, %d perlu pemetaan, %d dilewati, %d error. '
            .'Guru: %d baru, %d diperbarui, %d tidak berubah, %d dilewati, %d error.',
            \$s['created'],
            \$s['updated'],
            \$s['unchanged'],
            \$s['conflicts'],
            \$s['needs_mapping'],
            \$s['skipped'],
            \$s['errors'],
            \$t['created'],
            \$t['updated'],
            \$t['unchanged'],
            \$t['skipped'],
            \$t['errors']
        );";

$searchCRLF = str_replace("\n", "\r\n", $search);
$replaceCRLF = str_replace("\n", "\r\n", $replace);

$content = str_replace($search, $replace, $content);
$content = str_replace($searchCRLF, $replaceCRLF, $content);

file_put_contents($file, $content);
echo "Done.\n";
