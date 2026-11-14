<?php
// Shows the signed-in client their OWN submitted ID photo (nobody else's).
require_once __DIR__ . '/includes/bootstrap.php';
require_role('client');
ensure_shop_tables();
$st = db()->prepare('SELECT id_doc_file FROM users WHERE id = ?');
$st->execute([(int)$_SESSION['user_id']]);
$file = (string)$st->fetchColumn();
$path = __DIR__ . '/private/ids/' . $file;
if (!preg_match('/^id-[a-f0-9]{24}\.(jpg|png|webp)$/', $file) || !is_file($path)) { http_response_code(404); exit('No ID photo on file.'); }
$mime = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'][pathinfo($file, PATHINFO_EXTENSION)];
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($path);
