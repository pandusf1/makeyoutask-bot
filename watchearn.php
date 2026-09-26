<?php
/**
 * =====================================================================
 *  Watch & Earn Bot - makeyoutask.com
 *  Dengan Auto Captcha Solver (Vernuable API - https://vernuable.my.id/)
 *  Jalankan: php watchearn.php
 * =====================================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Jakarta');

$configFile = "config.json";
$cookieFile = "cookies.txt";

// Warna Terminal ANSI
const MERAH  = "\033[0;31m";
const HIJAU  = "\033[0;32m";
const KUNING = "\033[0;33m";
const BIRU   = "\033[0;34m";
const CYAN   = "\033[0;36m";
const PUTIH  = "\033[0;37m";
const BOLD   = "\033[1m";
const DIM    = "\033[2m";
const RESET  = "\033[0m";

const HOST        = "https://makeyoutask.com";
const SCRIPT_NAME = "makeyoutask.com";
const VERSI       = "1.2";

// Statistik sesi
$stat = ['selesai' => 0, 'gagal' => 0, 'mulai' => time()];

// ─────────────────────────────────────────────────────────────────────
// UTILITAS
// ─────────────────────────────────────────────────────────────────────

function clearScreen(): void {
    (PHP_OS_FAMILY === "Windows") ? system('cls') : system('clear');
}

function formatWaktu(int $d): string {
    return sprintf('%02d:%02d:%02d', floor($d / 3600), floor(($d % 3600) / 60), $d % 60);
}

function log_info(string $lvl, string $msg, string $detail = ''): void {
    $jam  = date('H:i:s');
    $warna = match($lvl) {
        'OK'    => HIJAU,
        'ERROR' => MERAH,
        'WARN'  => KUNING,
        'WAIT'  => CYAN,
        'INFO'  => BIRU,
        default => PUTIH,
    };
    $ikon = match($lvl) {
        'OK'    => '✓',
        'ERROR' => '✗',
        'WARN'  => '!',
        'WAIT'  => '~',
        'INFO'  => 'i',
        default => '-',
    };
    echo PUTIH . DIM . "[$jam] " . RESET . $warna . BOLD . "[$ikon $lvl]" . RESET . " " . PUTIH . $msg . RESET;
    if ($detail !== '') {
        echo "\n" . DIM . "         └─ " . KUNING . $detail . RESET;
    }
    echo "\n";
}

function timer_countdown(int $secs, string $prefix = "  Menonton"): void {
    $frames = ['⣾', '⣽', '⣻', '⢿', '⡿', '⣟', '⣯', '⣷'];
    $f      = 0;
    while ($secs > 0) {
        $t = microtime(true);
        while ((microtime(true) - $t) < 1.0) {
            echo CYAN . $prefix . " " . HIJAU . BOLD . formatWaktu($secs) . RESET . " " . PUTIH . $frames[$f % 8] . "\r";
            usleep(120000);
            $f++;
        }
        $secs--;
    }
    echo str_repeat(' ', 60) . "\r";
}

function cek_ekstensi(): void {
    foreach (['curl', 'json'] as $ext) {
        if (!extension_loaded($ext)) {
            echo MERAH . "PHP extension missing: $ext\n" . RESET;
            exit(1);
        }
    }
}

// ─────────────────────────────────────────────────────────────────────
// CAPTCHA SOLVER (VERNUABLE API)
// ─────────────────────────────────────────────────────────────────────

function cloud_solve(string $apikey, string $sitekey, string $pageurl = HOST, string $cdata = ''): array|string {
    if (empty($sitekey)) return "EMPTY_SITEKEY";
    if (empty($pageurl)) $pageurl = HOST;

    $ch = curl_init("https://vernuable.my.id/in.php");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'key'     => $apikey,
            'method'  => 'turnstile',
            'sitekey' => $sitekey,
            'pageurl' => $pageurl,
            'json'    => '1',
        ]),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    ]);
    $request = curl_exec($ch);
    $errno   = curl_errno($ch);
    $err_msg = curl_error($ch);
    curl_close($ch);

    if ($errno || empty($request)) return "CURL_ERROR: " . ($err_msg ?: 'Empty response');

    $id = '';
    $json = json_decode($request, true);
    if (is_array($json)) {
        if (($json['status'] ?? 0) == 1 && !empty($json['request'])) {
            $id = $json['request'];
        } else {
            return $json['request'] ?? ($json['error'] ?? 'ERROR_UNKNOWN');
        }
    } elseif (strpos($request, "OK|") === 0) {
        $id = trim(explode("|", $request)[1]);
    } else {
        $errlist = [
            "ERROR_WRONG_METHOD", "ERROR_KEY_DOES_NOT_EXIST", "ERROR_METHOD_NOT_SPECIFIED",
            "ERROR_NO_SUCH_METHOD", "ERROR_DATABASE_CONNECTION_FAILED", "ERROR_WRONG_USER_KEY",
            "ERROR_ZERO_BALANCE", "ERROR_BAD_PARAMETERS", "ERROR_EMPTY_IMAGE", "ERROR_UNKNOWN",
            "ERROR_BAD_DATA", "ERROR_TOO_MANY_REQUESTS"
        ];
        foreach ($errlist as $e) {
            if (strpos($request, $e) !== false) return $e;
        }
        return "ERROR_UNKNOWN";
    }

    if (empty($id)) return "ERROR_UNKNOWN";

    // Reuse persistent connection handle
    $ch2 = curl_init("https://vernuable.my.id/res.php");
    curl_setopt_array($ch2, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'key'    => $apikey,
            'action' => 'get',
            'id'     => $id,
            'json'   => '1',
        ]),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_TCP_NODELAY    => 1,
        CURLOPT_HTTPHEADER     => ['Connection: keep-alive', 'Accept: application/json'],
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    ]);

    $max_attempts = 45;
    $attempt = 0;
    timer_countdown(3, "  Solving Captcha (Waiting worker)");

    while ($attempt < $max_attempts) {
        $attempt++;
        $result = curl_exec($ch2);

        if (empty($result)) {
            usleep(800000);
            continue;
        }

        $resJson = json_decode($result, true);
        if (is_array($resJson)) {
            if (($resJson['status'] ?? 0) == 1) {
                $token = $resJson['token'] ?? $resJson['request'] ?? '';
                if (!empty($token)) {
                    curl_close($ch2);
                    return ["turnstile" => $token];
                }
            }
            $req = $resJson['request'] ?? '';
            if ($req === 'CAPCHA_NOT_READY' || $req === 'NOT_READY' || $req === 'ERROR_SOLVE_PENDING') {
                usleep(1200000); // 1.2 detik cepat
                continue;
            }
            if (strpos($req, 'ERROR_') === 0 || strpos($req, 'WRONG_') === 0) {
                curl_close($ch2);
                return $req;
            }
        } else {
            if (strpos($result, "OK|") === 0) {
                $token = trim(explode("|", $result)[1]);
                curl_close($ch2);
                return ["turnstile" => $token];
            }
            if (strpos($result, "CAPCHA_NOT_READY") !== false || strpos($result, "NOT_READY") !== false || strpos($result, "ERROR_SOLVE_PENDING") !== false) {
                usleep(1200000);
                continue;
            }
            if (strpos($result, "ERROR_") === 0 || strpos($result, "WRONG_") === 0) {
                curl_close($ch2);
                return trim($result);
            }
        }
        usleep(1200000);
    }
    curl_close($ch2);
    return "ERROR_TIMEOUT";
}

// ─────────────────────────────────────────────────────────────────────
// HTTP REQUEST
// ─────────────────────────────────────────────────────────────────────

function req(string $url, string $method = 'GET', string|array $data = [], array $hdrs = []): array {
    global $cookieFile;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => $hdrs,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    ]);
    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    $resp  = curl_exec($ch);
    $errno = curl_errno($ch);
    $emsg  = curl_error($ch);
    $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hs    = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    if (!$resp || $errno) {
        return ['ok' => false, 'code' => 0, 'body' => '', 'error' => "cURL #$errno: $emsg"];
    }
    return ['ok' => true, 'code' => $code, 'body' => substr($resp, $hs), 'error' => ''];
}

function hdrs_get(string $ref = ''): array {
    $h = [
        'Host: ' . SCRIPT_NAME,
        'sec-ch-ua-platform: "Windows"',
        'upgrade-insecure-requests: 1',
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'sec-fetch-site: ' . ($ref ? 'same-origin' : 'none'),
        'sec-fetch-mode: navigate',
        'sec-fetch-user: ?1',
        'sec-fetch-dest: document',
        'accept-language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
    ];
    if ($ref) $h[] = "referer: $ref";
    return $h;
}

function hdrs_post(string $ref): array {
    return [
        'Host: ' . SCRIPT_NAME,
        'sec-ch-ua-platform: "Windows"',
        'origin: ' . HOST,
        'content-type: application/x-www-form-urlencoded',
        'upgrade-insecure-requests: 1',
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'sec-fetch-site: same-origin',
        'sec-fetch-mode: navigate',
        'sec-fetch-user: ?1',
        'sec-fetch-dest: document',
        "referer: $ref",
        'accept-language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
    ];
}

function hdrs_ajax(string $ref): array {
    return [
        'Host: ' . SCRIPT_NAME,
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'accept: application/json, text/javascript, */*; q=0.01',
        'x-requested-with: XMLHttpRequest',
        'content-type: application/x-www-form-urlencoded; charset=UTF-8',
        'origin: ' . HOST,
        'sec-fetch-site: same-origin',
        'sec-fetch-mode: cors',
        'sec-fetch-dest: empty',
        "referer: $ref",
        'accept-language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
    ];
}

// ─────────────────────────────────────────────────────────────────────
// CONFIG
// ─────────────────────────────────────────────────────────────────────

function baca_config(string $path): array {
    if (!file_exists($path)) {
        echo PUTIH . "Email    : " . KUNING; $em = trim(fgets(STDIN));
        echo PUTIH . "Password : " . KUNING; $pw = trim(fgets(STDIN));
        echo PUTIH . "API Key  : " . KUNING; $ak = trim(fgets(STDIN));
        echo RESET;
        $d = ["email" => $em, "password" => $pw, "apikey" => $ak];
        file_put_contents($path, json_encode($d, JSON_PRETTY_PRINT));
        log_info('OK', "Config disimpan ke $path");
        return $d;
    }
    $c = json_decode(file_get_contents($path), true);
    if (!$c || !isset($c['email'], $c['password'])) {
        echo MERAH . "config.json tidak valid!\n" . RESET;
        exit(1);
    }
    return $c;
}

// ─────────────────────────────────────────────────────────────────────
// LOGIN
// ─────────────────────────────────────────────────────────────────────

function lakukan_login(string $email, string $password, string $apikey): bool {
    log_info('WAIT', "Mengambil halaman login...");
    $res = req(HOST . '/login', 'GET', [], hdrs_get());
    if (!$res['ok']) {
        log_info('ERROR', "Koneksi login gagal: " . $res['error']);
        return false;
    }

    preg_match('/name="csrf_token_name"\s+value="([^"]+)"/', $res['body'], $csrf);
    $token = $csrf[1] ?? '';

    preg_match('/data-sitekey="([^"]+)"/', $res['body'], $site);
    $sitekey = $site[1] ?? '';
    $cf_token = '';

    if (!empty($sitekey)) {
        log_info('WAIT', "Menyelesaikan Captcha Login via Vernuable...");
        $bypass = cloud_solve($apikey, $sitekey, HOST . '/login');
        if (is_array($bypass) && !empty($bypass['turnstile'])) {
            $cf_token = $bypass['turnstile'];
            log_info('OK', "Captcha login berhasil di-bypass!");
        } else {
            log_info('ERROR', "Gagal bypass captcha login: " . (is_string($bypass) ? $bypass : 'Unknown'));
            return false;
        }
    }

    preg_match('/action="([^"]+auth\/login)"/', $res['body'], $act);
    $action = $act[1] ?? (HOST . '/auth/login');

    log_info('WAIT', "Login sebagai: " . KUNING . $email);
    usleep(rand(500000, 1000000));

    $params = [
        'csrf_token_name' => $token,
        'email'           => $email,
        'password'        => $password,
    ];
    if (!empty($cf_token)) {
        $params['cf-turnstile-response'] = $cf_token;
    }

    $res = req($action, 'POST', http_build_query($params), hdrs_post(HOST . '/login'));
    if (!$res['ok']) {
        log_info('ERROR', "Koneksi POST login gagal: " . $res['error']);
        return false;
    }

    if (stripos($res['body'], 'Dashboard | MakeYouTask') !== false || stripos($res['body'], 'dashboard') !== false) {
        log_info('OK', "Login berhasil!");
        return true;
    }
    if (preg_match('/alert-danger[^>]*>.*?<\/i>\s*([^<]+)/s', $res['body'], $f)) {
        log_info('ERROR', "Ditolak server: " . trim($f[1]));
        return false;
    }
    return false;
}

// ─────────────────────────────────────────────────────────────────────
// CEK SESI / DASHBOARD
// ─────────────────────────────────────────────────────────────────────

function cek_sesi(): array|false {
    $res = req(HOST . '/dashboard', 'GET', [], hdrs_get());
    if (!$res['ok'] || empty($res['body'])) return false;

    if (stripos($res['body'], 'action="https://makeyoutask.com/auth/login"') !== false || stripos($res['body'], '/auth/login') !== false) {
        return false;
    }

    $user = 'Member';
    $bal  = '0.00';

    if (preg_match('/class="user-name"[^>]*>([^<]+)/i', $res['body'], $m)) {
        $user = trim($m[1]);
    }
    if (preg_match('/(?:Balance|Saldo)[^<]*<[^>]+>\s*([0-9.,]+)/i', $res['body'], $m)) {
        $bal = trim($m[1]);
    }

    return ['username' => $user, 'balance' => $bal];
}

function proses_watchearn(array &$stat, string $apikey): string {
    log_info('WAIT', "Mengambil halaman Watch & Earn...");
    $res = req(HOST . '/watchearn', 'GET', [], hdrs_get(HOST . '/dashboard'));
    if (!$res['ok']) return 'error';

    // Cek gate
    if (stripos($res['body'], 'Security Verification') !== false && preg_match('/data-sitekey="([^"]+)"/', $res['body'], $sk)) {
        log_info('WAIT', "Security Verification terdeteksi, menyelesaikan Turnstile...");
        $bp = cloud_solve($apikey, $sk[1], HOST . '/watchearn');
        if (is_array($bp) && !empty($bp['turnstile'])) {
            $gate_data = http_build_query(['cf-turnstile-response' => $bp['turnstile']]);
            $res = req(HOST . '/watchearn', 'POST', $gate_data, hdrs_post(HOST . '/watchearn'));
        }
    }

    if (stripos($res['body'], 'No tasks available') !== false || stripos($res['body'], 'Tidak ada tugas') !== false) {
        return 'no_task';
    }

    preg_match('/name="csrf_token_name"\s+value="([^"]+)"/', $res['body'], $csrf);
    $token = $csrf[1] ?? '';

    $durasi = 30;
    if (preg_match('/(?:var timer|data-timer)="?(\d+)"?/', $res['body'], $tm)) {
        $durasi = (int)$tm[1];
    }

    $task_id = '';
    if (preg_match('/(?:adId|taskId|data-id)="?(\d+)"?/', $res['body'], $tid)) {
        $task_id = $tid[1];
    }

    log_info('INFO', "Task ID: " . KUNING . ($task_id ?: "auto") . PUTIH . " | Durasi: " . KUNING . "{$durasi}s");

    if ($task_id) {
        req(HOST . '/watchearn/start_session', 'POST', http_build_query([
            'ad_id' => $task_id, 'timer' => $durasi, 'csrf_token_name' => $token
        ]), hdrs_ajax(HOST . '/watchearn'));
    }

    timer_countdown($durasi, "  🎬 Menonton");

    log_info('WAIT', "Mengklaim reward...");
    $r_claim = req(HOST . '/watchearn/verify', 'POST', http_build_query(['csrf_token_name' => $token]), hdrs_post(HOST . '/watchearn'));

    if (stripos($r_claim['body'], 'success') !== false || stripos($r_claim['body'], 'Success') !== false) {
        log_info('OK', "Reward berhasil diklaim!");
        $stat['selesai']++;
        return 'sukses';
    }

    $stat['gagal']++;
    return 'tidak_diketahui';
}

// ─────────────────────────────────────────────────────────────────────
// MAIN
// ─────────────────────────────────────────────────────────────────────

cek_ekstensi();
clearScreen();

echo BOLD . CYAN . "\n  🎬 WATCH & EARN BOT — makeyoutask.com\n";
echo PUTIH . DIM  . "  Versi " . VERSI . " | Vernuable API Active\n\n" . RESET;

$config   = baca_config($configFile);
$email    = $config['email'];
$password = $config['password'];
$apikey   = $config['apikey'] ?? '';

log_info('WAIT', "Memeriksa sesi login...");
$profil = cek_sesi();

if (!$profil) {
    log_info('WARN', "Sesi tidak aktif. Mencoba login...");
    if (!lakukan_login($email, $password, $apikey)) {
        log_info('ERROR', "Login gagal.");
        exit(1);
    }
    $profil = cek_sesi();
    if (!$profil) {
        log_info('ERROR', "Dashboard tidak terbaca setelah login.");
        exit(1);
    }
}

log_info('OK', "Sesi aktif: " . CYAN . ($profil['username'] ?? 'User') . PUTIH . " | " . HIJAU . ($profil['balance'] ?? '0.00'));

while (true) {
    $hasil = proses_watchearn($stat, $apikey);
    if ($hasil === 'no_task') {
        log_info('WAIT', "Tidak ada tugas. Menunggu 5 menit...");
        timer_countdown(300, "  Menunggu task");
    } else {
        $j = rand(3, 6);
        log_info('INFO', "Jeda {$j} detik sebelum task berikutnya...");
        sleep($j);
    }
}
