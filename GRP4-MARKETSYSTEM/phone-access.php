<?php
// Shows the address to type on a phone (and a QR code) so the site can be opened from a phone on the same Wi-Fi.
require_once __DIR__ . '/includes/bootstrap.php';
$onHost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
if (!$onHost && ($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); exit('Open this page on the computer that runs XAMPP, or sign in as admin.'); }

function lan_ip(): string {
    $s = @stream_socket_client('udp://8.8.8.8:53', $errno, $errstr, 1);        // no data is sent; it only shows which network address this computer uses
    if ($s) {
        $name = stream_socket_get_name($s, false); fclose($s);
        $ip = $name ? explode(':', $name)[0] : '';
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $ip !== '127.0.0.1') { return $ip; }
    }
    $ip = gethostbyname(gethostname());
    return (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $ip !== '127.0.0.1') ? $ip : '';
}
$port = (int)($_SERVER['SERVER_PORT'] ?? 80);
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$ip   = lan_ip();
$url  = $ip ? 'http://' . $ip . ($port !== 80 ? ":$port" : '') . $base . '/' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Open on phone | AIOS</title>
  <link rel="stylesheet" href="css/style.css">
  <?php include __DIR__ . '/includes/head-extra.php'; ?>
  <style> .phone-page { max-width: 760px; margin: 0 auto; } .phone-url { font: 800 1.5rem var(--font-head); word-break: break-all; color: var(--navy); } .phone-page code { background: var(--mist); padding: .15rem .45rem; border-radius: 6px; word-break: break-all; } .phone-page ol { padding-left: 1.2rem; } .phone-page li { margin: .5rem 0; } .qr { width: 220px; height: 220px; border: 1px solid var(--line); border-radius: 12px; background: #fff; } </style>
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <main class="section shop-page phone-page">
    <h1>Open AIOS on your phone</h1>
    <?php if (!$url): ?>
      <p class="form-error">Could not find this computer's network address. Open Command Prompt, type <code>ipconfig</code>, and look for "IPv4 Address".</p>
    <?php else: ?>
      <div class="card">
        <p class="muted">On your phone (same Wi-Fi as this computer), open this address:</p>
        <p class="phone-url"><?= htmlspecialchars($url) ?></p>
        <img class="qr" src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?= urlencode($url) ?>" alt="QR code for <?= htmlspecialchars($url) ?>">
        <p class="muted">Scan the QR code with the phone camera. (The QR picture needs internet. If it does not show, just type the address.)</p>
      </div>
    <?php endif; ?>

    <h2 class="more-h">Steps</h2>
    <ol>
      <li>Keep <strong>Apache</strong> and <strong>MySQL</strong> running in XAMPP on this computer.</li>
      <li>Connect the phone to the <strong>same Wi-Fi</strong> as this computer.</li>
      <li>Open the address above on the phone.</li>
      <li>If it does not open, Windows Firewall is probably blocking Apache. Open <strong>Command Prompt as administrator</strong> and run:<br>
        <code>netsh advfirewall firewall add rule name="Apache HTTP 80" dir=in action=allow protocol=TCP localport=80</code><br>
        (If your site uses another port, replace 80.) Also make sure the Wi-Fi network is set to <strong>Private</strong>.</li>
    </ol>

    <h2 class="more-h">Add it to the phone's home screen</h2>
    <ul>
      <li><strong>Android (Chrome):</strong> open the site, tap the &#8942; menu, then <strong>Add to Home screen</strong>.</li>
      <li><strong>iPhone (Safari):</strong> open the site, tap the Share button, then <strong>Add to Home Screen</strong>.</li>
    </ul>

    <div class="card">
      <p><strong>Good to know</strong></p>
      <ul class="plain-list">
        <li>This works only while this computer is on and on the same network. To use it from anywhere you need web hosting.</li>
        <li>Anyone on the same Wi-Fi can open the site, so use a network you trust and change the demo admin password (<code>6969</code>) before showing it to others.</li>
        <li>The phone must open the address with <code>http://</code> (not https).</li>
      </ul>
    </div>
  </main>
</body>
</html>
