<?php
if (!defined('ABSPATH')) { exit; }

// Start session to get any errors/data
if (!session_id()) {
    session_start();
}

$errors = $_SESSION['idc_signup_errors'] ?? [];
$form_data = $_SESSION['idc_signup_data'] ?? [];

// Clear session data after retrieving
unset($_SESSION['idc_signup_errors']);
unset($_SESSION['idc_signup_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Sign Up — SSNYU</title>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Inter:wght@600;700;800&display=swap" rel="stylesheet">

<style>
  :root{
    --brand:#16a34a;
    --brand-2:#22c55e;
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
    display:grid; place-items:center; padding:24px;
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
  .btn{ display:inline-flex; align-items:center; justify-content:center; padding:12px 16px; border-radius:14px; font-weight:700; }
  .btn-primary{
    background:linear-gradient(90deg,var(--brand),var(--brand-2)); color:#fff;
    box-shadow:0 6px 18px rgba(22,163,74,.25);
  }
  .btn-primary:hover{ filter:brightness(1.05); }
  .field-label{ font-size:12px; color:#065f46; font-weight:700; letter-spacing:.2px; }
  .link{ color:#065f46; font-weight:700; }
  .divider{
    display:grid; grid-template-columns:1fr auto 1fr; align-items:center; gap:10px; color:#065f46cc;
  }
  .divider::before,.divider::after{ content:""; height:1px; background:linear-gradient(90deg,#bfe9cf,transparent); }
  .divider::after{ background:linear-gradient(90deg,transparent,#bfe9cf); }
  .eye{
    position:absolute; right:12px; top:50%; transform:translateY(-50%);
    cursor:pointer; user-select:none; font-size:12px; color:#065f46cc; font-weight:700;
  }
  .hint{ font-size:12px; color:#0f5132; }
  .error{ font-size:12px; color:#b3261e; }
  .error-box{ 
    background:rgba(179,38,30,.1); 
    border:1px solid rgba(179,38,30,.2); 
    border-radius:14px; 
    padding:12px; 
    margin-bottom:16px;
  }
</style>
</head>
<body>
<main class="page">
  <section class="glass w-full max-w-md p-6 md:p-8">
    <header class="mb-6 text-center">
      <div class="mx-auto w-14 h-14 rounded-2xl grid place-items-center"
           style="background:linear-gradient(90deg,var(--brand),var(--brand-2)); color:#fff; font-weight:800;">
        SS
      </div>
      <h1 class="mt-3 text-2xl md:text-3xl font-extrabold text-emerald-900">Create your account</h1>
      <p class="text-sm text-emerald-900/70">Join SSNYU to continue</p>
    </header>

    <?php if (!empty($errors)): ?>
    <div class="error-box">
      <?php foreach ($errors as $error): ?>
        <div class="error"><?php echo esc_html($error); ?></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4" onsubmit="return handleSignup(event)">
      <?php wp_nonce_field('idc_signup', 'idc_signup_nonce'); ?>
      <?php if (isset($_GET['redirect_to'])): ?>
        <input type="hidden" name="redirect_to" value="<?php echo esc_attr($_GET['redirect_to']); ?>">
      <?php endif; ?>
      
      <div class="space-y-1">
        <label class="field-label" for="full_name">Full Name</label>
        <input id="full_name" name="full_name" type="text" class="input-glass" 
               placeholder="John Doe" 
               value="<?php echo esc_attr($form_data['full_name'] ?? ''); ?>"
               required />
      </div>

      <div class="space-y-1">
        <label class="field-label" for="email">Email</label>
        <input id="email" name="email" type="email" class="input-glass" 
               placeholder="you@example.com" 
               value="<?php echo esc_attr($form_data['email'] ?? ''); ?>"
               required />
      </div>

      <div class="space-y-1">
        <label class="field-label" for="password">Password</label>
        <div class="relative">
          <input id="password" name="password" type="password" class="input-glass pr-12" 
                 placeholder="••••••••" minlength="6" required />
          <span class="eye" onclick="togglePw('password', this)">SHOW</span>
        </div>
        <p class="hint">At least 6 characters.</p>
      </div>

      <div class="space-y-1">
        <label class="field-label" for="confirm_password">Confirm Password</label>
        <div class="relative">
          <input id="confirm_password" name="confirm_password" type="password" class="input-glass pr-12" 
                 placeholder="••••••••" minlength="6" required />
          <span class="eye" onclick="togglePw('confirm_password', this)">SHOW</span>
        </div>
        <p id="pwError" class="error" style="display:none;">Passwords don't match.</p>
      </div>

      <label class="inline-flex items-center gap-2 text-sm">
        <input id="terms" type="checkbox" class="accent-emerald-600" required>
        <span class="text-emerald-900/80">I agree to the Terms & Privacy Policy</span>
      </label>

      <button class="btn btn-primary w-full" type="submit">Create Account</button>
    </form>

    <footer class="mt-6 text-center text-sm text-emerald-900/80">
      Already have an account?
      <a class="link" href="<?php echo home_url('/idc-login/'); ?>">Log in</a>
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

  function handleSignup(e){
    const pw = document.getElementById('password').value;
    const cf = document.getElementById('confirm_password').value;
    const err = document.getElementById('pwError');
    
    if(pw !== cf){
      err.style.display = 'block';
      e.preventDefault();
      return false;
    }
    
    err.style.display = 'none';
    return true; // Allow form submission
  }
</script>
</body>
</html>