<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validasi input tidak kosong
    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        // Query pakai prepared statement agar aman dari SQL injection
        $stmt = mysqli_prepare($mysqli, "SELECT id, nama, password FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            // Login berhasil — simpan data ke session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — FTrans</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:        #0a0a0f;
      --card:      #12121a;
      --border:    #1e1e2e;
      --accent:    #e8c547;
      --accent2:   #f0a500;
      --text:      #e8e8f0;
      --muted:     #6b6b80;
      --input-bg:  #1a1a26;
      --danger:    #ff5e5e;
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

    /* Dekoratif background */
    body::before {
      content: '';
      position: fixed;
      top: -20%;
      right: -10%;
      width: 600px;
      height: 600px;
      background: radial-gradient(circle, rgba(232,197,71,0.08) 0%, transparent 70%);
      pointer-events: none;
    }
    body::after {
      content: '';
      position: fixed;
      bottom: -20%;
      left: -10%;
      width: 500px;
      height: 500px;
      background: radial-gradient(circle, rgba(232,197,71,0.05) 0%, transparent 70%);
      pointer-events: none;
    }

    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 2.5rem 2rem;
      width: 100%;
      max-width: 420px;
      position: relative;
      animation: slideUp 0.5s cubic-bezier(.22,.68,0,1.2) both;
    }
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* Garis aksen atas */
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

    .form-group {
      margin-bottom: 1.1rem;
    }
    label {
      display: block;
      font-size: 0.82rem;
      font-weight: 500;
      color: var(--muted);
      margin-bottom: 0.45rem;
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
    <p class="subtitle">Sistem Manajemen Kendaraan</p>

    <?php if ($error): ?>
      <div class="alert-error">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
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
      </div>

      <button type="submit" class="btn">Masuk →</button>
    </form>

    <p class="footer-link">
      Belum punya akun? <a href="register.php">Daftar sekarang</a>
    </p>
  </div>
</body>
</html>