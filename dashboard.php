<?php
// Sends each signed-in user to the dashboard for their role.
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'client', 'staff');
$to = ['admin' => 'admin.php', 'client' => 'client.php', 'staff' => 'staff.php'];
header('Location: ' . $to[$_SESSION['role']]);
exit;
