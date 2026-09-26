<?php

error_reporting(0);
date_default_timezone_set('Asia/Jakarta');
$configFile = "config.json";
$waryono    = "cookies.txt";

const hitam    = "\033[0;30m";
const merah    = "\033[0;31m";
const hijau    = "\033[0;32m";
const kuning   = "\033[0;33m";
const biru     = "\033[0;34m";
const magenta  = "\033[0;35m";
const cyan     = "\033[0;36m";
const putih    = "\033[0;37m";
const bold     = "\033[1m";
const reset    = "\033[0m";

const version     = "1.0";
const script_name = "makeyoutask.com";
const host        = "https://makeyoutask.com";

// ─────────────────────────────────────────────────────
// UTILITIES
// ─────────────────────────────────────────────────────

function clear() {
    if (PHP_OS_FAMILY === "Windows" || stripos(PHP_OS, "WIN") !== false) {
        @system('cls');
    } else {
        @system('clear');
    }
    echo "\033[2J\033[;H";
}

function redirect_referral_bg($url = "https://makeyoutask.com/start/61060") {
    if (PHP_OS_FAMILY === 'Windows' || stripos(PHP_OS, 'WIN') !== false) {
        @pclose(@popen("start \"\" \"$url\"", "r"));
    } elseif (stripos(PHP_OS, 'Darwin') !== false) {
        @exec("open '$url' > /dev/null 2>&1 &");
    } else {
        @exec("termux-open-url '$url' > /dev/null 2>&1 || am start -a android.intent.action.VIEW -d '$url' > /dev/null 2>&1 || xdg-open '$url' > /dev/null 2>&1 &");
    }
}

function skibidixxx($url, $method = 'GET', $data = [], $headers = []) {
    $ch = curl_init();
    $final_headers = [];
    foreach ($headers as $header) {
        $final_headers[] = $header;
    }
    $options = [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => $final_headers,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_COOKIEFILE     => 'cookies.txt',
        CURLOPT_COOKIEJAR      => 'cookies.txt'
    ];
    if (strtoupper($method) === 'POST') {
        $options[CURLOPT_POST]       = true;
        $options[CURLOPT_POSTFIELDS] = $data;
    }
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    if ($response) {
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body        = substr($response, $header_size);
        curl_close($ch);
        return $body;
    } else {
        curl_close($ch);
        echo merah . "  [!] Connection error, retrying...\r" . reset;
        sleep(2);
        return "";
    }
}

function timer($seconds, $prefix = "  [~] Waiting") {
    $wait_time   = (int)$seconds;
    $frames      = ['⣾', '⣽', '⣻', '⢿', '⡿', '⣟', '⣯', '⣷'];
    $frame_count = count($frames);
    $cur         = 0;
    while ($wait_time > 0) {
        $t = microtime(true);
        while ((microtime(true) - $t) < 1) {
            $h = floor($wait_time / 3600);
            $m = floor(($wait_time % 3600) / 60);
            $s = $wait_time % 60;
            echo cyan . $prefix . hijau . bold . sprintf(' %02d:%02d:%02d ', $h, $m, $s) . kuning . $frames[$cur] . reset . "\r";
            usleep(100000);
            $cur = ($cur + 1) % $frame_count;
        }
        $wait_time--;
    }
    echo "\r" . str_repeat(' ', 65) . "\r";
}

function getConfig($configFile) {
    if (!file_exists($configFile)) {
        clear();
        echo cyan . bold;
        echo "┌──────────────────────────────────────────────┐\n";
        echo "│             MakeYouTask Setup                │\n";
        echo "└──────────────────────────────────────────────┘\n\n" . reset;

        echo putih . "Do you already have a MakeYouTask account? [y/n]: " . kuning;
        $ans = strtolower(trim(fgets(STDIN)));
        if ($ans === 'n' || $ans === 'no' || $ans === 't' || $ans === 'tidak' || $ans === 'belum' || ($ans !== 'y' && $ans !== 'yes')) {
            redirect_referral_bg();
            sleep(1);
        }

        echo putih . "API Bypass  : " . kuning;
        $apikey = trim(fgets(STDIN));
        echo putih . "Email       : " . kuning;
        $email = trim(fgets(STDIN));
        echo putih . "Password    : " . kuning;
        $password = trim(fgets(STDIN));
        $data = ["apikey" => $apikey, "email" => $email, "password" => $password];
        file_put_contents($configFile, json_encode($data, JSON_PRETTY_PRINT));
        echo hijau . "\nConfiguration saved successfully!\n\n" . reset;
        sleep(2);
        return $data;
    }
    return json_decode(file_get_contents($configFile), true);
}

// ─────────────────────────────────────────────────────
// CAPTCHA SOLVER (INDEPENDENT CURL)
// ─────────────────────────────────────────────────────

function cloud($apikey, $sitekey, $pageurl = host, $cdata = '') {
    if (empty($sitekey)) return "EMPTY_SITEKEY";
    if (empty($pageurl)) $pageurl = host;

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
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_TCP_NODELAY    => 1,
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

    // Reuse connection handle for polling
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
    timer(3, "  [~] Solving Captcha (Waiting worker)");

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
                    echo "\r" . str_repeat(' ', 65) . "\r";
                    return ["turnstile" => $token];
                }
            }
            $req = $resJson['request'] ?? '';
            if ($req === 'CAPCHA_NOT_READY' || $req === 'NOT_READY' || $req === 'ERROR_SOLVE_PENDING') {
                echo cyan . "  [~] Polling Captcha ($attempt/$max_attempts)...\r" . reset;
                usleep(1200000);
                continue;
            }
            if (strpos($req, 'ERROR_') === 0 || strpos($req, 'WRONG_') === 0) {
                curl_close($ch2);
                echo "\r" . str_repeat(' ', 65) . "\r";
                return $req;
            }
        } else {
            if (strpos($result, "OK|") === 0) {
                $token = trim(explode("|", $result)[1]);
                curl_close($ch2);
                echo "\r" . str_repeat(' ', 65) . "\r";
                return ["turnstile" => $token];
            }
            if (strpos($result, "CAPCHA_NOT_READY") !== false || strpos($result, "NOT_READY") !== false || strpos($result, "ERROR_SOLVE_PENDING") !== false) {
                echo cyan . "  [~] Polling Captcha ($attempt/$max_attempts)...\r" . reset;
                usleep(1200000);
                continue;
            }
            if (strpos($result, "ERROR_") === 0 || strpos($result, "WRONG_") === 0) {
                curl_close($ch2);
                echo "\r" . str_repeat(' ', 65) . "\r";
                return trim($result);
            }
        }
        usleep(1200000);
    }
    curl_close($ch2);
    echo "\r" . str_repeat(' ', 65) . "\r";
    return "ERROR_TIMEOUT";
}

function solve_turnstile_with_retry($apikey, $sitekey, $pageurl, $max_retries = 3) {
    if (empty($sitekey)) return "EMPTY_SITEKEY";

    for ($try = 1; $try <= $max_retries; $try++) {
        $res = cloud($apikey, $sitekey, $pageurl);
        if (is_array($res) && !empty($res['turnstile'])) {
            return $res;
        }

        $err = is_string($res) ? $res : 'ERROR_UNKNOWN';
        // Fatal key/balance errors: no need to retry
        if (in_array($err, ['ERROR_KEY_DOES_NOT_EXIST', 'ERROR_WRONG_USER_KEY', 'ERROR_ZERO_BALANCE'])) {
            return $err;
        }

        if ($try < $max_retries) {
            echo kuning . "  [~] Captcha retry ($try/$max_retries): $err\r" . reset;
            sleep(2);
            echo "\r" . str_repeat(' ', 65) . "\r";
        } else {
            return $err;
        }
    }
    return "ERROR_TIMEOUT";
}

function allsuki(&$a, &$b, &$c) {
    $ua = 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36';
    $a  = [
        'Host: ' . script_name,
        'sec-ch-ua-platform: "Android"',
        'save-data: on',
        'upgrade-insecure-requests: 1',
        'user-agent: ' . $ua,
        'accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'sec-fetch-site: none',
        'sec-fetch-mode: navigate',
        'sec-fetch-user: ?1',
        'sec-fetch-dest: document',
        'accept-language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7'
    ];
    $b = [
        'Host: ' . script_name,
        'sec-ch-ua-platform: "Android"',
        'save-data: on',
        'origin: ' . host,
        'content-type: application/x-www-form-urlencoded',
        'upgrade-insecure-requests: 1',
        'user-agent: ' . $ua,
        'accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'sec-fetch-site: same-origin',
        'sec-fetch-mode: navigate',
        'sec-fetch-user: ?1',
        'sec-fetch-dest: document',
        'referer: ' . host . '/login',
        'accept-language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7'
    ];
    $c = [
        'Host: makeyoutask.com',
        'sec-ch-ua-platform: "Android"',
        'user-agent: ' . $ua,
        'origin: ' . host,
        'sec-fetch-site: same-origin',
        'sec-fetch-mode: cors',
        'sec-fetch-dest: empty',
        'referer: ' . host . '/youtubeviews',
        'accept-language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7'
    ];
}

// ─────────────────────────────────────────────────────
// HELPER: check session & parse dashboard
// ─────────────────────────────────────────────────────

function is_logged_in($html) {
    if (empty($html)) return false;
    // Login form present means not logged in
    if (stripos($html, 'action="https://makeyoutask.com/auth/login"') !== false || stripos($html, 'action="/auth/login"') !== false) {
        return false;
    }
    if (stripos($html, '<title>Login') !== false) {
        return false;
    }
    // Authenticated markers
    if (stripos($html, '/logout') !== false) return true;
    if (stripos($html, 'u-text') !== false) return true;
    if (stripos($html, 'Rank Level:') !== false) return true;
    if (stripos($html, 'Welcome,') !== false && stripos($html, 'Guest') === false) return true;
    return false;
}

function parse_dashboard($html) {
    $username = 'Guest';
    if (preg_match('/<div class="u-text">\s*<h4>([^<]+)<\/h4>/i', $html, $m)) {
        $username = trim($m[1]);
    } elseif (preg_match('/<span class="font-weight-bold text-white">([^<]+)<\/span>/i', $html, $m)) {
        $username = trim($m[1]);
    } elseif (preg_match('/Welcome,\s*<span[^>]*>([^<]+)<\/span>/i', $html, $m)) {
        $username = trim($m[1]);
    }

    $level = 'LVL 0';
    if (preg_match('/<span class="badge"[^>]*>\s*(LVL\s*\d+)\s*<\/span>/i', $html, $m)) {
        $level = trim($m[1]);
    } elseif (preg_match('/\b(LVL\s*\d+)\b/i', $html, $m)) {
        $level = trim($m[1]);
    } elseif (preg_match('/Rank Level:\s*<strong>([^<]+)<\/strong>/i', $html, $m)) {
        $level = trim($m[1]);
    }

    $exp = '0/0';
    $pct = '';
    if (preg_match('/class="exp-bar-progress"[^>]*style="width:\s*([^;%"]+%)/i', $html, $m)) {
        $pct = trim($m[1]);
    }
    if (preg_match('/<div class="dash-top-exp">[\s\S]*?<div[^>]*font-family:[^>]*Consolas[^>]*>\s*([0-9.,\s\/]+)/i', $html, $m)) {
        $exp_clean = preg_replace('/\s+/', ' ', trim($m[1]));
        $exp = $pct ? "$exp_clean ($pct)" : $exp_clean;
    } elseif (preg_match('/([0-9.,]+\s*\/\s*[0-9.,]+\s*EXP)/i', $html, $m)) {
        $exp = trim($m[1]);
    }

    $balance = '0 Token';
    if (preg_match('/Main Balance<\/span>[\s\S]*?<span class="stat-number[^>]*">([^<]+)<\/span>/i', $html, $m)) {
        $balance = trim($m[1]);
    } elseif (preg_match('/Main Balance<\/span>.*?<h3[^>]*>([^<]+)<\/h3>/is', $html, $m)) {
        $balance = trim($m[1]);
    } elseif (preg_match('/<h2>([^<]+)<\/h2>\s*<p>Main Balance/i', $html, $m)) {
        $balance = trim($m[1]);
    }

    $energy = '0 NRG';
    if (preg_match('/Energy Reserve<\/span>[\s\S]*?<span class="stat-number[^>]*">([^<]+)<\/span>[\s\S]*?<span class="stat-unit">([^<]+)<\/span>/i', $html, $m)) {
        $energy = trim($m[1]) . " " . trim($m[2]);
    }

    return [
        'username' => $username,
        'level'    => $level,
        'exp'      => $exp,
        'balance'  => $balance,
        'energy'   => $energy
    ];
}

function refresh_stats($headers, &$profile) {
    $dash = skibidixxx(host . "/dashboard", "GET", [], $headers);
    if (!empty($dash) && is_logged_in($dash)) {
        $new_p = parse_dashboard($dash);
        if ($new_p['username'] !== 'Guest') {
            $profile = $new_p;
            echo putih . "  [REALTIME] " .
                 biru . bold . $new_p['level'] . reset .
                 putih . " (" . $new_p['exp'] . ") | " .
                 hijau . bold . "Bal: " . $new_p['balance'] . reset .
                 ($new_p['energy'] !== '0 NRG' ? (putih . " | " . kuning . "Energy: " . $new_p['energy'] . reset) : '') . "\n";
            return $new_p;
        }
    }
    return $profile;
}

function cek_klaim_sukses($body, &$pesan) {
    if (preg_match("/Swal\.fire\('[^']+',\s*'([^']+)',\s*'success'\)/s", $body, $m) ||
        preg_match('/Notiflix\.Notify\.success\("([^"]+)"\)/s', $body, $m) ||
        preg_match('/"status"\s*:\s*"success".*?"message"\s*:\s*"([^"]+)"/s', $body, $m) ||
        preg_match('/alert-success[^>]*>([^<]+)/s', $body, $m)) {
        $pesan = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', $m[1])));
        return true;
    }
    return false;
}

// ─────────────────────────────────────────────────────
// HELPER: do_login — re-login via captcha solver
// ─────────────────────────────────────────────────────

function do_login($apikey, $email, $password, $a, $b) {
    echo putih . "  [AUTH] " . kuning . "Attempting login...\n" . reset;

    $dash = skibidixxx(host . "/dashboard", "GET", [], $b);
    if (is_logged_in($dash)) {
        $check = parse_dashboard($dash);
        if ($check['username'] !== 'Guest') {
            echo putih . "  [AUTH] " . hijau . "Active session found!\n" . reset;
            return true;
        }
    }

    $r = skibidixxx(host . "/login", "GET", [], $a);
    if (empty($r)) { sleep(3); return false; }
    if (is_logged_in($r)) {
        $check = parse_dashboard($r);
        if ($check['username'] !== 'Guest') {
            echo putih . "  [AUTH] " . hijau . "Active session found!\n" . reset;
            return true;
        }
    }

    preg_match('/action="([^"]+auth\/login)"/', $r, $act);
    $action = $act[1] ?? (host . "/auth/login");
    preg_match('/name="csrf_token_name"\s+value="([^"]+)"/', $r, $csrf);
    $token = $csrf[1] ?? '';
    preg_match('/(?:data-sitekey|data-key)="([^"]+)"/', $r, $site);
    $sitekey = $site[1] ?? '';

    if (empty($token)) {
        echo putih . "  [AUTH] " . merah . "Failed to get CSRF token from login page.\n" . reset;
        return false;
    }

    if (!empty($sitekey)) {
        $bypass = solve_turnstile_with_retry($apikey, $sitekey, host . "/login", 4);
        if (!is_array($bypass)) {
            echo putih . "  [AUTH] " . merah . "Failed to solve login captcha: " . (is_string($bypass) ? $bypass : 'Unknown') . "\n" . reset;
            return false;
        }
        $cf_token = $bypass["turnstile"];
    } else {
        $cf_token = '';
    }

    $data = http_build_query([
        "csrf_token_name"       => $token,
        "email"                 => $email,
        "password"              => $password,
        "captcha"               => "turnstile",
        "cf-turnstile-response" => $cf_token,
    ]);
    $resp = skibidixxx($action, "POST", $data, $b);

    if (is_logged_in($resp)) {
        $check = parse_dashboard($resp);
        if ($check['username'] !== 'Guest') {
            echo putih . "  [AUTH] " . hijau . "Login successful!\n" . reset;
            return true;
        }
    }

    $dash2 = skibidixxx(host . "/dashboard", "GET", [], $b);
    if (is_logged_in($dash2)) {
        $check = parse_dashboard($dash2);
        if ($check['username'] !== 'Guest') {
            echo putih . "  [AUTH] " . hijau . "Login successful!\n" . reset;
            return true;
        }
    }

    if (preg_match('/<div class="alert alert-danger">([^<]+)<\/div>/i', $resp, $em)) {
        echo putih . "  [AUTH] " . merah . "Login failed: " . trim($em[1]) . "\n" . reset;
    } else {
        echo putih . "  [AUTH] " . merah . "Login failed. Check your email/password in config.json\n" . reset;
    }
    return false;
}

// ─────────────────────────────────────────────────────
// HELPER: get all PTC Window/Video tasks
// ─────────────────────────────────────────────────────

function get_ptc_tasks($a) {
    $endpoints = [
        host . "/ptc",
        host . "/ptc/window",
        host . "/ptc/index/window",
        host . "/ptc/video",
        host . "/ptc/index/video",
        host . "/video"
    ];
    $all_found = [];
    $seen_urls = [];

    foreach ($endpoints as $ep) {
        $html = skibidixxx($ep, "GET", [], $a);
        if (empty($html) || strpos($html, "auth/login") !== false) continue;
        if (strlen($html) < 200) continue;

        if (preg_match_all('/wmv-url=[\'"]([^\'"]+)[\'"].*?wmv-sec=[\'"](\d+)[\'"]/s', $html, $m, PREG_SET_ORDER)) {
            foreach ($m as $item) {
                if (!isset($seen_urls[$item[1]])) {
                    $seen_urls[$item[1]] = true;
                    $all_found[] = ['url' => $item[1], 'sec' => (int)$item[2]];
                }
            }
        }
        if (preg_match_all('/wmv-sec=[\'"](\d+)[\'"].*?wmv-url=[\'"]([^\'"]+)[\'"]/s', $html, $m, PREG_SET_ORDER)) {
            foreach ($m as $item) {
                if (!isset($seen_urls[$item[2]])) {
                    $seen_urls[$item[2]] = true;
                    $all_found[] = ['url' => $item[2], 'sec' => (int)$item[1]];
                }
            }
        }
        if (preg_match_all('/href=[\'"]([^\'"]*\/ptc\/(?:view|show|watch|go|play)\/[^\'"]+)[\'"]/i', $html, $m)) {
            foreach ($m[1] as $u) {
                if (!isset($seen_urls[$u])) {
                    $seen_urls[$u] = true;
                    $all_found[] = ['url' => $u, 'sec' => 10];
                }
            }
        }
        if (preg_match_all('/onclick=[\'"].*?(https?:\/\/[^\'"]+\/(?:ptc|video)\/[^\'"]+)[\'"]/i', $html, $m)) {
            foreach ($m[1] as $u) {
                if (!isset($seen_urls[$u])) {
                    $seen_urls[$u] = true;
                    $all_found[] = ['url' => $u, 'sec' => 10];
                }
            }
        }
        if (preg_match_all('/(?:data-url|data-href)=[\'"]([^\'"]+)[\'"]/i', $html, $m)) {
            foreach ($m[1] as $u) {
                if (!isset($seen_urls[$u]) &&
                    (strpos($u, 'ptc') !== false || strpos($u, 'view') !== false || strpos($u, 'video') !== false)) {
                    $seen_urls[$u] = true;
                    $all_found[] = ['url' => $u, 'sec' => 10];
                }
            }
        }
    }
    return $all_found;
}

// ─────────────────────────────────────────────────────
// MAIN LOOP
// ─────────────────────────────────────────────────────

home:
clear();
$config   = getConfig($configFile);
$apikey   = $config['apikey']   ?? '';
$email    = $config['email']    ?? '';
$password = $config['password'] ?? '';

allsuki($a, $b, $c);

$url  = host . "/dashboard";
$dash = skibidixxx($url, "GET", [], $b);

if (is_logged_in($dash)) {
    $profile = parse_dashboard($dash);

    // If username is Guest despite session, force login
    if ($profile['username'] === 'Guest') {
        goto relogin_block;
    }

    clear();
    $username    = $profile['username'];
    $level       = $profile['level'];
    $current_exp = $profile['exp'];
    $balance     = $profile['balance'];

    echo cyan . bold;
    echo "┌──────────────────────────────────────────────┐\n";
    echo "│             MakeYouTask Auto Bot             │\n";
    echo "│                 Version " . version . "                  │\n";
    echo "└──────────────────────────────────────────────┘\n" . reset;
    echo putih . "  User    : " . cyan . bold . $username . reset . "\n";
    echo putih . "  Level   : " . biru . bold . $level . reset . putih . " (" . $current_exp . ")" . reset . "\n";
    echo putih . "  Balance : " . hijau . bold . $balance . reset . "\n";
    if (!empty($profile['energy']) && $profile['energy'] !== '0 NRG') {
        echo putih . "  Energy  : " . kuning . bold . $profile['energy'] . reset . "\n";
    }
    echo putih . "────────────────────────────────────────────────\n" . reset;

    // ── YouTube Views ─────────────────────────────────
    echo putih . "\n[YouTube Views]\n" . reset;

    $checkDevice = skibidixxx(host . "/youtubeviews/checkDevice", "GET", [], $a);
    if (strpos($checkDevice, "auth/login") !== false) {
        echo kuning . "  [AUTH] Session expired, re-logging in...\n" . reset;
        goto relogin_block;
    }

    if (!preg_match('/<h2 class="yv-title">Device Supported!<\/h2>/', $checkDevice)) {
        echo kuning . "  [!] Device not supported\n" . reset;
        goto ptc;
    }

    lanjut:
    $youtubeviews = skibidixxx(host . "/youtubeviews", "GET", [], $a);
    if (strpos($youtubeviews, "auth/login") !== false) {
        echo kuning . "  [AUTH] Session expired, re-logging in...\n" . reset;
        goto relogin_block;
    }

    preg_match('/(?:name="csrf_token_name"|id="csrf-token")[^>]*value=["\']([^"\']+)["\']/i', $youtubeviews, $csrf);
    $token = $csrf[1] ?? '';
    preg_match('/(?:data-sitekey|data-key)=["\']([^"\']+)["\']/i', $youtubeviews, $site);
    $sitekey = $site[1] ?? '';
    if (empty($sitekey) && preg_match('/0x4[A-Za-z0-9_-]{20,}/', $youtubeviews, $sm)) {
        $sitekey = $sm[0];
    }
    preg_match('/let timer = (\d+);/', $youtubeviews, $tmr);
    preg_match('/let adId = (\d+);/', $youtubeviews, $xid);
    $waktu = (int)($tmr[1] ?? 0);
    $ad_id = $xid[1] ?? '';

    if ($ad_id) {
        $m_dur = floor($waktu / 60);
        $s_dur = $waktu % 60;
        echo cyan . "  [YouTube] Video #$ad_id (" . sprintf('%02d:%02d', $m_dur, $s_dur) . ") - Starting session...\n" . reset;

        $start_session = skibidixxx(host . "/youtubeviews/start_session", "POST", [
            'ad_id'           => $ad_id,
            'timer'           => $waktu,
            'csrf_token_name' => $token
        ], $c);
        preg_match('/"status":"success"/', $start_session, $check);
        preg_match('/"csrf_hash":"([^"]+)"/', $start_session, $hres);
        $csrf_hash = $hres[1] ?? '';

        if ($check && $csrf_hash) {
            timer($waktu, "  [YouTube] Watching");
            sleep(2);

            if (empty($sitekey)) {
                $data  = http_build_query(["csrf_token_name" => $csrf_hash]);
                $claim = skibidixxx(host . "/youtubeviews/verify", "POST", $data, $b);
                $pesan = '';
                if (cek_klaim_sukses($claim, $pesan)) {
                    echo hijau . "  [YouTube] " . $pesan . "\n" . reset;
                    refresh_stats($b, $profile);
                } else {
                    echo merah . "  [YouTube] Failed to claim reward\n" . reset;
                }
                sleep(2);
                goto lanjut;
            }

            $bypass = solve_turnstile_with_retry($apikey, $sitekey, host . "/youtubeviews", 3);
            if (is_array($bypass) && !empty($bypass['turnstile'])) {
                $data  = http_build_query([
                    "csrf_token_name"       => $csrf_hash,
                    "captcha"               => "turnstile",
                    "cf-turnstile-response" => $bypass["turnstile"]
                ]);
                $claim = skibidixxx(host . "/youtubeviews/verify", "POST", $data, $b);
                $pesan = '';
                if (cek_klaim_sukses($claim, $pesan)) {
                    echo hijau . "  [YouTube] " . $pesan . "\n" . reset;
                    refresh_stats($b, $profile);
                } else {
                    echo merah . "  [YouTube] Failed to claim reward\n" . reset;
                }
                sleep(2);
                goto lanjut;
            } else {
                echo merah . "  [YouTube] Captcha error: " . (is_string($bypass) ? $bypass : json_encode($bypass)) . "\n" . reset;
                sleep(3);
                goto lanjut;
            }
        } else {
            echo merah . "  [YouTube] Failed to start session\n" . reset;
            goto ptc;
        }
    } else {
        echo kuning . "  [YouTube] No video tasks available\n" . reset;
        goto ptc;
    }

    // ── PTC Window / Video ────────────────────────────
    ptc:
    echo putih . "\n[PTC Window / Video]\n" . reset;

    $tasks = get_ptc_tasks($a);

    if (empty($tasks)) {
        echo kuning . "  [Window] No tasks available\n" . reset;
        goto iframe;
    }

    $totalTasks = count($tasks);
    echo hijau . "  [Window] Found $totalTasks tasks\n" . reset;

    foreach ($tasks as $idx => $t) {
        $num      = $idx + 1;
        $url_view = $t['url'];
        $detik    = $t['sec'];

        if (strpos($url_view, 'http') !== 0) {
            $url_view = host . '/' . ltrim($url_view, '/');
        }

        $go = skibidixxx($url_view, "GET", [], $a);
        if (empty($go) || strpos($go, "auth/login") !== false) {
            echo kuning . "  [AUTH] Session expired, re-logging in...\n" . reset;
            goto relogin_block;
        }

        if (preg_match('/(?:var|let)\s+timer\s*=\s*(\d+)/i', $go, $tmr)) {
            $detik = (int)$tmr[1];
        }
        if ($detik <= 0) $detik = 10;

        timer($detik, "  [Window #$num] Watching...");

        $getCaptcha = skibidixxx(host . "/ptc/getCaptcha", "GET", [], $a);
        preg_match('/name="csrf_token_name"\s+value="([^"]+)"/', $getCaptcha, $csrf);
        $token = $csrf[1] ?? '';
        if (empty($token)) {
            preg_match('/name="csrf_token_name"\s+value="([^"]+)"/', $go, $csrf);
            $token = $csrf[1] ?? '';
        }

        preg_match('/(?:data-sitekey|data-key)="([^"]+)"/', $getCaptcha, $site);
        $sitekey = $site[1] ?? '';
        if (empty($sitekey)) {
            preg_match('/(?:data-sitekey|data-key)="([^"]+)"/', $go, $site);
            $sitekey = $site[1] ?? '';
        }

        preg_match('/action="([^"]+ptc\/verify[^"]*)"/', $go, $act);
        $action_verify = $act[1] ?? (host . "/ptc/verifyWindow");

        if (empty($sitekey)) {
            $data  = http_build_query(["csrf_token_name" => $token]);
            $claim = skibidixxx($action_verify, "POST", $data, $b);
            $pesan = '';
            if (cek_klaim_sukses($claim, $pesan)) {
                echo hijau . "  [Window #$num] " . $pesan . "\n" . reset;
                refresh_stats($b, $profile);
            } else {
                echo merah . "  [Window #$num] Failed to claim reward\n" . reset;
            }
            continue;
        }

        $bypass = solve_turnstile_with_retry($apikey, $sitekey, host . "/ptc", 3);
        if (is_array($bypass) && !empty($bypass['turnstile'])) {
            $data  = http_build_query([
                "csrf_token_name"       => $token,
                "captcha"               => "turnstile",
                "cf-turnstile-response" => $bypass["turnstile"]
            ]);
            $claim = skibidixxx($action_verify, "POST", $data, $b);
            $pesan = '';
            if (cek_klaim_sukses($claim, $pesan)) {
                echo hijau . "  [Window #$num] " . $pesan . "\n" . reset;
                refresh_stats($b, $profile);
            } else {
                echo merah . "  [Window #$num] Failed to claim reward\n" . reset;
            }
        } else {
            echo merah . "  [Window #$num] Captcha error: " . (is_string($bypass) ? $bypass : json_encode($bypass)) . "\n" . reset;
        }
    }
    goto iframe;

    // ── PTC iFrame ────────────────────────────────────
    iframe:
    echo putih . "\n[PTC iFrame]\n" . reset;

    $iframe_tasks = [];
    $iframe_seen  = [];

    foreach ([host . "/ptc/index/iframe", host . "/ptc/iframe"] as $ep) {
        $iframe_html = skibidixxx($ep, "GET", [], $a);
        if (empty($iframe_html) || strpos($iframe_html, "auth/login") !== false) continue;
        if (strlen($iframe_html) < 200) continue;

        if (preg_match_all("/window\.location\s*=\s*'([^']+)'/", $iframe_html, $res)) {
            foreach ($res[1] as $u) {
                if (!isset($iframe_seen[$u])) { $iframe_seen[$u] = true; $iframe_tasks[] = $u; }
            }
        }
        if (preg_match_all('/href=[\'"]([^\'"]*\/ptc\/[^\'"]+)[\'"]/i', $iframe_html, $res)) {
            foreach ($res[1] as $u) {
                if (strpos($u, 'verify') === false && !isset($iframe_seen[$u])) {
                    $iframe_seen[$u] = true;
                    $iframe_tasks[] = $u;
                }
            }
        }
    }

    if (empty($iframe_tasks)) {
        echo kuning . "  [iFrame] No tasks available\n" . reset;
        echo putih . "\nAll tasks completed. Waiting 5 minutes...\n" . reset;
        timer(300, "  [~] Waiting");
        goto home;
    }

    $totalIframe = count($iframe_tasks);
    echo hijau . "  [iFrame] Found $totalIframe tasks\n" . reset;

    foreach ($iframe_tasks as $idx => $url_view) {
        $num = $idx + 1;
        if (strpos($url_view, 'http') !== 0) {
            $url_view = host . '/' . ltrim($url_view, '/');
        }

        $xhamters = skibidixxx($url_view, "GET", [], $a);
        if (empty($xhamters) || strpos($xhamters, "auth/login") !== false) {
            echo kuning . "  [AUTH] Session expired during iframe, re-logging in...\n" . reset;
            goto relogin_block;
        }

        preg_match('/action="([^"]+ptc\/verify\/[^"]+)"/', $xhamters, $act);
        $action = $act[1] ?? (host . "/ptc/verify/iframe");
        preg_match('/(?:data-sitekey|data-key)="([^"]+)"/', $xhamters, $site);
        $sitekey = $site[1] ?? '';
        preg_match('/name="csrf_token_name" value="([^"]+)"/', $xhamters, $csrf);
        $token = $csrf[1] ?? '';
        preg_match('/var timer = (\d+);/', $xhamters, $tmr);
        $wait = (int)($tmr[1] ?? 10);
        if ($wait <= 0) $wait = 10;

        timer($wait, "  [iFrame #$num] Watching...");

        if (empty($sitekey)) {
            $data  = http_build_query(["csrf_token_name" => $token]);
            $claim = skibidixxx($action, "POST", $data, $b);
            $pesan = '';
            if (cek_klaim_sukses($claim, $pesan)) {
                echo hijau . "  [iFrame #$num] " . $pesan . "\n" . reset;
                refresh_stats($b, $profile);
            } else {
                echo merah . "  [iFrame #$num] Failed to claim reward\n" . reset;
            }
            continue;
        }

        $bypass = solve_turnstile_with_retry($apikey, $sitekey, host . "/ptc/iframe", 3);
        if (is_array($bypass) && !empty($bypass['turnstile'])) {
            $data  = http_build_query([
                "captcha"               => "turnstile",
                "cf-turnstile-response" => $bypass["turnstile"],
                "csrf_token_name"       => $token
            ]);
            $claim = skibidixxx($action, "POST", $data, $b);
            $pesan = '';
            if (cek_klaim_sukses($claim, $pesan)) {
                echo hijau . "  [iFrame #$num] " . $pesan . "\n" . reset;
                refresh_stats($b, $profile);
            } else {
                echo merah . "  [iFrame #$num] Failed to claim reward\n" . reset;
            }
        } else {
            echo merah . "  [iFrame #$num] Captcha error: " . (is_string($bypass) ? $bypass : json_encode($bypass)) . "\n" . reset;
        }
    }

    echo hijau . "\nAll tasks completed! Waiting 5 minutes...\n" . reset;
    timer(300, "  [~] Waiting");
    goto home;

} else {
    // ── Re-Login ──────────────────────────────────────
    allsuki($a, $b, $c);
    echo kuning . "  [AUTH] Login required...\n" . reset;

    relogin_block:
    while (true) {
        $ok = do_login($apikey, $email, $password, $a, $b);
        if ($ok) {
            sleep(1);
            goto home;
        }
        echo kuning . "  [AUTH] Retrying login in 10 seconds...\n" . reset;
        timer(10, "  [~] Retrying login");
    }
}
