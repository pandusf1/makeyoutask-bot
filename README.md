# MakeYouTask Automation Bot

Script otomasi tugas MakeYouTask berbasis PHP dengan integrasi solver Turnstile Captcha [Vernuable API](https://vernuable.my.id/).

## Fitur
- **Auto Claim & Task:** Menyelesaikan tugas otomatis (PTC, Faucet, dsb).
- **Watch & Earn:** Otomasi modul Watch & Earn (`watchearn.php`).
- **Turnstile Captcha Solver:** Menggunakan Vernuable API yang cepat dan handal dengan retry mechanism cerdas.
- **Real-time Live Stats:** Menampilkan Level (LVL), Progress EXP, Token Balance, dan Energy secara berkala.

## Persyaratan
- PHP 8.0 atau lebih baru (dengan ekstensi `curl`, `json`, `mbstring`).
- Akun MakeYouTask.
- API Key Vernuable Captcha Solver ([vernuable.my.id](https://vernuable.my.id/)).

## Cara Penggunaan
1. Salin `config.example.json` menjadi `config.json`:
   ```bash
   cp config.example.json config.json
   ```
2. Isi `config.json` dengan kredensial Anda:
   ```json
   {
       "apikey": "MASUKKAN_VERNUABLE_API_KEY_ANDA",
       "email": "email_makeyoutask_anda@example.com",
       "password": "password_makeyoutask_anda"
   }
   ```
3. Jalankan script utama:
   ```bash
   php bot.php
   ```
   Atau untuk modul Watch & Earn:
   ```bash
   php watchearn.php
   ```

## Catatan Keamanan
Pastikan file `config.json` dan file session `*.txt` tidak diunggah ke publik. File tersebut telah didaftarkan dalam `.gitignore`.
