<?php
require_once 'config.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $konfirm  = $_POST['konfirmasi']    ?? '';

    // --- Validasi ---
    if (empty($nama) || empty($email) || empty($password) || empty($konfirm)) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Cek apakah email sudah terdaftar
        $cek = mysqli_prepare($mysqli, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($cek, 's', $email);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);

        if (mysqli_stmt_num_rows($cek) > 0) {
            $error = 'Email sudah terdaftar. Gunakan email lain.';
        } else {
            // Hash password sebelum disimpan
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $ins = mysqli_prepare($mysqli, "INSERT INTO users (nama, email, password) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($ins, 'sss', $nama, $email, $hashed);

            if (mysqli_stmt_execute($ins)) {
                $success = 'Akun berhasil dibuat! Silakan <a href="login.php">login di sini</a>.';
            } else {
                $error = 'Terjadi kesalahan. Coba lagi.';
            }
            mysqli_stmt_close($ins);
        }
        mysqli_stmt_close($cek);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — FTrans</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:       #0a0a0f;
      --card:     #12121a;
      --border:   #1e1e2e;
      --accent:   #e8c547;
      --accent2:  #f0a500;
      --text:     #e8e8f0;
      --muted:    #6b6b80;
      --input-bg: #1a1a26;
      --danger:   #ff5e5e;
      --success:  #5ecc8b;
    }

    body {
      min-height: 100vh;
      background: var(--bg);
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'DM Sans', sans-serif;
      color: var(--text);
      padding: 1.5rem;
      position: relative;
      overflow: hidden;
    }
    body::before {
      content: '';
      position: fixed;
      top: -20%; right: -10%;
      width: 600px; height: 600px;
      background: radial-gradient(circle, rgba(232,197,71,0.08) 0%, transparent 70%);
      pointer-events: none;
    }

    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 2.5rem 2rem;
      width: 100%;
      max-width: 440px;
      position: relative;
      animation: slideUp 0.5s cubic-bezier(.22,.68,0,1.2) both;
    }
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .card::before {
      content: '';
      position: absolute;
      top: 0; left: 10%; right: 10%;
      height: 2px;
      background: linear-gradient(90deg, transparent, var(--accent), transparent);
      border-radius: 999px;
    }

    .brand {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 1.8rem;
      letter-spacing: -0.03em;
      margin-bottom: 0.3rem;
    }
    .brand span { color: var(--accent); }

    .subtitle {
      color: var(--muted);
      font-size: 0.88rem;
      margin-bottom: 2rem;
    }

    .alert-error {
      background: rgba(255,94,94,0.1);
      border: 1px solid rgba(255,94,94,0.3);
      color: var(--danger);
      border-radius: 10px;
      padding: 0.75rem 1rem;
      font-size: 0.85rem;
      margin-bottom: 1.25rem;
    }
    .alert-success {
      background: rgba(94,204,139,0.1);
      border: 1px solid rgba(94,204,139,0.3);
      color: var(--success);
      border-radius: 10px;
      padding: 0.75rem 1rem;
      font-size: 0.85rem;
      margin-bottom: 1.25rem;
    }
    .alert-success a { color: var(--success); }

    .form-group { margin-bottom: 1rem; }
    label {
      display: block;
      font-size: 0.82rem;
      font-weight: 500;
      color: var(--muted);
      margin-bottom: 0.4rem;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }
    input {
      width: 100%;
      background: var(--input-bg);
      border: 1px solid var(--border);
      border-radius: 10px;
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.95rem;
      padding: 0.75rem 1rem;
      transition: border-color 0.2s, box-shadow 0.2s;
      outline: none;
    }
    input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(232,197,71,0.12);
    }

    .hint {
      font-size: 0.78rem;
      color: var(--muted);
      margin-top: 0.3rem;
    }

    .btn {
      display: block;
      width: 100%;
      margin-top: 1.5rem;
      padding: 0.85rem;
      background: var(--accent);
      color: #0a0a0f;
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 0.95rem;
      letter-spacing: 0.03em;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: background 0.2s, transform 0.15s;
    }
    .btn:hover  { background: var(--accent2); transform: translateY(-1px); }
    .btn:active { transform: translateY(0); }

    .footer-link {
      text-align: center;
      margin-top: 1.5rem;
      font-size: 0.85rem;
      color: var(--muted);
    }
    .footer-link a {
      color: var(--accent);
      text-decoration: none;
      font-weight: 500;
    }
    .footer-link a:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <div class="card">
    <div class="brand">F<span>Trans</span></div>
    <p class="subtitle">Buat akun baru</p>

    <?php if ($error): ?>
      <div class="alert-error">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert-success">✓ <?= $success ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" action="">
      <div class="form-group">
        <label for="nama">Nama Lengkap</label>
        <input type="text" id="nama" name="nama"
               placeholder="John Doe"
               value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
               required>
      </div>

      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email"
               placeholder="email@contoh.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               placeholder="••••••••" required>
        <p class="hint">Minimal 6 karakter</p>
      </div>

      <div class="form-group">
        <label for="konfirmasi">Konfirmasi Password</label>
        <input type="password" id="konfirmasi" name="konfirmasi"
               placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn">Daftar →</button>
    </form>
    <?php endif; ?>

    <p class="footer-link">
      Sudah punya akun? <a href="login.php">Login di sini</a>
    </p>
  </div>
</body>
</html>