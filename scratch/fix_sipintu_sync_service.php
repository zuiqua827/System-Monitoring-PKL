<?php

$file = 'app/Services/SipintuSyncService.php';
$content = file_get_contents($file);

$search = "        return sprintf(
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

$searchStr = str_replace("\r\n", "\n", $search);

$replaceStr = "        return sprintf(
            'Siswa: %%d baru, %%d diperbarui, %%d tidak berubah, %%d konflik, %%d perlu pemetaan, %%d dilewati, %%d error. '
            .'Guru: %%d baru, %%d diperbarui, %%d tidak berubah, %%d dilewati, %%d error.',
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
            \$t['errors'],
        );";

// Fix line endings in search string to match file
if (strpos($content, "\r\n") !== false) {
    $searchStr = str_replace("\n", "\r\n", $searchStr);
    $replaceStr = str_replace("\n", "\r\n", $replaceStr);
}

// Ensure the format string matches literally in PHP
$replaceStr = str_replace("%%d", "%d", $replaceStr);

$content = str_replace($searchStr, $replaceStr, $content);

file_put_contents($file, $content);

echo "Replaced.";
