<?php
if (!defined('ABSPATH')) { exit; }

// Handle logout message
$logout_message = isset($_GET['loggedout']) ? 'You have been logged out successfully.' : '';
$error_message = '';

// Handle login form submission
if ($_POST && isset($_POST['log'], $_POST['pwd'])) {
    $creds = array(
        'user_login'    => sanitize_user($_POST['log']),
        'user_password' => $_POST['pwd'],
        'remember'      => isset($_POST['rememberme'])
    );
    
    $user = wp_signon($creds, false);
    
    if (is_wp_error($user)) {
        $error_message = $user->get_error_message();
    } else {
        // Check for redirect_to parameter
        $redirect_to = isset($_GET['redirect_to']) ? urldecode($_GET['redirect_to']) : '';
        
        // Validate redirect URL (must be from same domain)
        if ($redirect_to && strpos($redirect_to, home_url()) === 0) {
            wp_redirect($redirect_to);
        } elseif (user_can($user, 'idc_manage')) {
            // Admin users go to admin dashboard
            wp_redirect(home_url('/admin-dashboard/'));
        } elseif (user_can($user, 'idc_edit')) {
            // Operator users go to operator dashboard
            wp_redirect(home_url('/operator-dashboard/'));
        } elseif (user_can($user, 'idc_read') && !user_can($user, 'idc_edit')) {
            // Viewer/Student users go to student dashboard (has idc_read but not idc_edit)
            wp_redirect(home_url('/student-dashboard/'));
        } else {
            // Fallback for other users
            wp_redirect(home_url('/admin-dashboard/'));
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Login — IDC Card System</title>

<!-- Tailwind for quick spacing/layout -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- Inter & Poppins fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Inter:wght@600;700;800&display=swap" rel="stylesheet">

<style>
  :root{
    --brand:#16a34a;    /* emerald */
    --brand-2:#22c55e;  /* lighter emerald */
    --ink:#0b1220;
    --muted:#4b5563;
  }
  html,body{
    height:100%;
    background:linear-gradient(135deg,#d8f3dc 0%,#f6fff7 100%);
    font-family:Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
  }
  .page{
    min-height:100dvh;
    display:grid;
    place-items:center;
    padding:24px;
  }
  .glass{
    background:rgba(255,255,255,.55);
    box-shadow:0 8px 32px rgba(31,38,135,.15);
    backdrop-filter:blur(18px);
    border-radius:24px;
    border:1px solid rgba(255,255,255,.3);
  }
  .input-glass{
    background:rgba(255,255,255,.35);
    border-radius:14px;
    border:1px solid rgba(255,255,255,.45);
    padding:12px 14px;
    width:100%;
    color:#0f5132;
    font-weight:500;
    transition:.2s;
    backdrop-filter:blur(12px);
  }
  .input-glass::placeholder{ color:#56736a; }
  .input-glass:focus{
    outline:none; border-color:var(--brand);
    background:rgba(255,255,255,.6);
    box-shadow:0 0 0 4px rgba(22,163,74,.15);
  }
  .btn{
    display:inline-flex; align-items:center; justify-content:center;
    padding:12px 16px; border-radius:14px; font-weight:700;
    cursor: pointer; border: none;
  }
  .btn-primary{
    background:linear-gradient(90deg,var(--brand),var(--brand-2)); color:#fff;
    box-shadow:0 6px 18px rgba(22,163,74,.25);
  }
  .btn-primary:hover{ filter:brightness(1.05); }
  .field-label{ font-size:12px; color:#065f46; font-weight:700; letter-spacing:.2px; }
  .link{ color:#065f46; font-weight:700; text-decoration: none; }
  .link:hover{ text-decoration: underline; }
  .divider{
    display:grid; grid-template-columns:1fr auto 1fr; align-items:center; gap:10px; color:#065f46cc;
  }
  .divider::before,.divider::after{ content:""; height:1px; background:linear-gradient(90deg,#bfe9cf,transparent); }
  .divider::after{ background:linear-gradient(90deg,transparent,#bfe9cf); }
  .eye{
    position:absolute; right:12px; top:50%; transform:translateY(-50%);
    cursor:pointer; user-select:none; font-size:12px; color:#065f46cc; font-weight:700;
  }
  .alert{
    padding: 12px 16px; border-radius: 12px; margin-bottom: 16px; font-weight: 500;
  }
  .alert-error{
    background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2);
    color: #dc2626;
  }
  .alert-success{
    background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2);
    color: #16a34a;
  }
</style>
</head>
<body>
<main class="page">
  <section class="glass w-full max-w-md p-6 md:p-8">
    <header class="mb-6 text-center">
      <div class="mx-auto w-14 h-14 rounded-2xl grid place-items-center"
           style="background:linear-gradient(90deg,var(--brand),var(--brand-2)); color:#fff; font-weight:800;">
        IDC
      </div>
      <h1 class="mt-3 text-2xl md:text-3xl font-extrabold text-emerald-900">Welcome back</h1>
      <p class="text-sm text-emerald-900/70">Log in to IDC Card System</p>
    </header>

    <?php if ($error_message): ?>
      <div class="alert alert-error">
        <?php echo esc_html($error_message); ?>
      </div>
    <?php endif; ?>

    <?php if ($logout_message): ?>
      <div class="alert alert-success">
        <?php echo esc_html($logout_message); ?>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <?php wp_nonce_field('login_action', 'login_nonce'); ?>
      
      <div class="space-y-1">
        <label class="field-label" for="log">Username or Email</label>
        <input id="log" name="log" type="text" class="input-glass" placeholder="admin@example.com" required 
               value="<?php echo isset($_POST['log']) ? esc_attr($_POST['log']) : ''; ?>" />
      </div>

      <div class="space-y-1 relative">
        <label class="field-label" for="pwd">Password</label>
        <input id="pwd" name="pwd" type="password" class="input-glass pr-12" placeholder="••••••••" required />
        <span class="eye" onclick="togglePw('pwd', this)">SHOW</span>
      </div>

      <div class="flex items-center justify-between text-sm">
        <label class="inline-flex items-center gap-2">
          <input id="rememberme" name="rememberme" type="checkbox" class="accent-emerald-600" value="forever">
          <span class="text-emerald-900/80">Remember me</span>
        </label>
        <a href="<?php echo wp_lostpassword_url(); ?>" class="link">Forgot password?</a>
      </div>

      <button class="btn btn-primary w-full" type="submit">Log In</button>
    </form>

    <footer class="mt-6 text-center text-sm text-emerald-900/80">
      Don't have an account?
      <a class="link" href="<?php echo home_url('/idc-signup/'); ?>">Sign up</a>
    </footer>
  </section>
</main>

<script>
  function togglePw(id, el){
    const inp = document.getElementById(id);
    const isPw = inp.type === 'password';
    inp.type = isPw ? 'text' : 'password';
    el.textContent = isPw ? 'HIDE' : 'SHOW';
  }
</script>
</body>
</html>