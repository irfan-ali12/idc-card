<?php
if (!defined('ABSPATH')) { exit; }

// Check if user is logged in - redirect to custom login if not
if (!is_user_logged_in()) {
    // Try WordPress redirect first
    if (function_exists('wp_redirect') && function_exists('home_url')) {
        wp_redirect(home_url('/idc-login/?redirect_to=' . urlencode($_SERVER['REQUEST_URI'])));
        exit;
    } else {
        // Fallback to PHP redirect if WordPress functions aren't available
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $redirect_url = $protocol . $host . '/idc-login/?redirect_to=' . urlencode($_SERVER['REQUEST_URI']);
        header('Location: ' . $redirect_url);
        exit;
    }
}

// Check if user has permission to access designer
if (!current_user_can('idc_read')) {
    if (function_exists('wp_die')) {
        wp_die('You do not have permission to access the ID Card Designer.');
    } else {
        // Fallback error display
        echo '<h1>Access Denied</h1><p>You do not have permission to access the ID Card Designer.</p>';
        exit;
    }
}

// Get plugin settings for background images
$settings = get_option('idc_settings', []);
$frontURL = $settings['front_png_id'] ? wp_get_attachment_url((int)$settings['front_png_id']) : IDC_CARD_URL.'assets/img/placeholder_front.png';
$backURL  = $settings['back_png_id']  ? wp_get_attachment_url((int)$settings['back_png_id'])  : IDC_CARD_URL.'assets/img/placeholder_back.png';

// Default photo
$defaultPhoto = 'https://placehold.co/240x240?text=Photo';
?>

<!-- Ensure proper mobile viewport -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

<!-- Authentication fallback check -->
<script>
// Additional authentication check via AJAX as fallback
(function() {
    // Check if we're logged in via REST API
    fetch('<?php echo rest_url('wp/v2/users/me'); ?>', {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
        }
    }).then(response => {
        if (response.status === 401) {
            // User not authenticated - redirect to login
            const currentUrl = encodeURIComponent(window.location.href);
            const loginUrl = '<?php echo home_url('/idc-login/'); ?>?redirect_to=' + currentUrl;
            window.location.href = loginUrl;
        }
    }).catch(error => {
        console.log('Auth check completed');
    });
})();
</script>

<!-- Tailwind for the left-side form only -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- QR lib -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<!-- Poppins for details -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Inter:wght@600;700;800&display=swap" rel="stylesheet">

<!-- put this in <head>, after your other scripts -->
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>


<style>
  :root{
    --brand:#16a34a; --brand-2:#22c55e; --ink:#111827; --muted:#4b5563;
    --cardW:340px; --cardH:540px;
  }

  /* Prevent horizontal scroll on the entire page */
  html, body {
    overflow-x: hidden !important;
    max-width: 100% !important;
  }

  * {
    box-sizing: border-box !important;
  }
  
  /* Override WordPress theme styles for full width */
  .idc-designer-container {
    width: 100% !important;
    max-width: none !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow-x: hidden !important;
  }
  
  .idc-designer-body {
    background: linear-gradient(135deg, #d8f3dc 0%, #f6fff7 100%) !important;
    min-height: 100vh;
    font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial !important;
    display: flex;
    flex-direction: column;
    /* align-items: center; */
    /* justify-content: center; */
    padding: 12px;
    margin: 0;
    box-sizing: border-box;
  }
  
  .glass{ 
    background:rgba(255,255,255,.55); 
    box-shadow:0 8px 32px rgba(31,38,135,.15); 
    backdrop-filter:blur(18px); 
    border-radius:24px; 
    border:1px solid rgba(255,255,255,.3);
    overflow: hidden;
    box-sizing: border-box;
  }
  
  .input-glass{ 
    background:rgba(255,255,255,.35); 
    border-radius:14px; 
    border:1px solid rgba(255,255,255,.45); 
    padding:10px 14px; 
    width:100%; 
    color:#0f5132; 
    font-weight:500; 
    transition:.2s; 
    backdrop-filter:blur(12px);
    box-sizing: border-box;
    min-width: 0; /* Allow inputs to shrink */
  }
  
  .input-glass::placeholder{ color:#56736a; }
  .input-glass:focus{ outline:none; border-color:var(--brand); background:rgba(255,255,255,.6) }

  /* Card */
  .card-preview{ 
    width:var(--cardW); 
    height:var(--cardH); 
    position:relative; 
    border-radius:18px; 
    overflow:hidden; 
    box-shadow:0 10px 20px rgba(0,0,0,.08); 
  }
  
  .card-bg{ position:absolute; inset:0; width:100%; height:100%; object-fit:fill; }

  /* FRONT CONTENT */
  .front-content{ position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; }
  .front-content .photo{
    width:180px; height:180px; border-radius:999px; overflow:hidden;
    border:3px solid var(--brand); background:#fff; box-shadow:0 6px 18px rgba(0,0,0,.08);
    margin-top:20px;
  }
  .front-content .photo img{ width:100%; height:100%; object-fit:cover; }

  .front-content .name{
    font-weight:700; font-size:28px; margin-top:12px; color:#111827; text-align:center;
  }
  .front-content .title{
    font-size:13px; color:#4b5563; margin-bottom:10px; text-align:center;
  }

  .front-content .qr{
    width:54px; height:54px; display:flex; align-items:center; justify-content:center;
    background:#fff; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,.08);
  }
  .front-content .qr img,
  .front-content .qr canvas { width:54px; height:54px; }

  /* Details (screen) */
  .details {
    width: 85%;
    margin-top: 8px;
    padding-bottom: 8px;
    font-family: 'Poppins', sans-serif;
    font-size: 13px;
    line-height: 1.7;
    display: grid;
    row-gap: 4px;
    margin-left: auto;
    margin-right: auto;
  }
  .detail-row {
    display: grid;
    grid-template-columns: 1fr 16px 1fr;
    align-items: center;
    column-gap: 6px;
  }
  .detail-row .label {
    font-weight: 400;
    color: #0b0f14;
    text-align: right;
    white-space: nowrap;
  }
  .detail-row .colon {
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 400;
    color: #0b0f14;
  }
  .detail-row .value {
    text-align: left;
    font-weight: 400;
    color: #0b0f14;
    font-style: italic;
    white-space: nowrap;
  }

  .toggle{ 
    padding:6px 16px; 
    border-radius:12px; 
    border:1px solid rgba(255,255,255,.5); 
    font-weight:600; 
    background:rgba(255,255,255,.35); 
    backdrop-filter:blur(8px); 
    cursor:pointer; 
  }
  .toggle.active{ 
    background:linear-gradient(90deg,var(--brand),var(--brand-2)); 
    color:#fff; 
    box-shadow:0 3px 12px rgba(22,163,74,.25); 
  }

  /* Responsive Design */
  .layout {
    box-sizing: border-box;
  }

  /* Form sections */
  .form-section {
    min-width: 0;
    flex: 1;
  }

  .preview-section {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  /* Grid responsiveness */
  .grid {
    display: grid;
    gap: 0.75rem;
  }

  .grid-cols-2 {
    grid-template-columns: repeat(2, 1fr);
  }

  @media (max-width: 1200px) {
    .idc-designer-body {
      padding: 8px;
    }
  }

  @media (max-width: 1024px) {
    .layout { 
      flex-direction: column !important; 
      gap: 1.5rem !important;
      max-width: 100% !important;
      padding: 1rem !important;
    }
    .card-preview { 
      width: min(90vw, 340px); 
      height: auto; 
      aspect-ratio: 340/540; 
      align-self: center;
    }
  }

  @media (max-width: 768px) {
    .idc-designer-body {
      padding: 4px;
      min-height: auto;
    }
    .layout {
      padding: 0.75rem !important;
      gap: 1rem !important;
      border-radius: 16px !important;
    }
    .card-preview {
      width: min(95vw, 280px);
    }
    .grid-cols-2 {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 480px) {
    .layout {
      padding: 0.5rem !important;
      gap: 0.75rem !important;
      border-radius: 12px !important;
    }
    .card-preview {
      width: min(98vw, 260px);
    }
    .input-glass {
      padding: 8px 12px;
      font-size: 14px;
    }
  }

  @media print{
    @page{ size:53.98mm 85.6mm; margin:0; }
    html,body{ width:53.98mm; height:85.6mm; margin:0; padding:0; }
    *{-webkit-print-color-adjust:exact; print-color-adjust:exact}
    .screen-only{ display:none !important; }
  }

  /* Tiny toast */
  .toast {
    position: fixed; inset-inline: 0; bottom: 18px; margin-inline: auto;
    width: fit-content; max-width: 90vw;
    background: #065f46; color: #fff; padding: 10px 14px; border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,.18); font-weight: 600; display: none;
    z-index: 9999;
  }
</style>

<div class="idc-designer-container">
  <div class="idc-designer-body">

    <div class="layout glass w-full max-w-7xl p-4 sm:p-6 lg:p-8 flex flex-col gap-4 sm:gap-6 screen-only">
      
      <!-- Header inside main layout -->
      <header class="w-full -m-4 sm:-m-6 lg:-m-8 mb-0 sm:mb-0 lg:mb-0">
        <div class="relative flex items-center px-4 sm:px-6 lg:px-8 py-3 bg-white/30 backdrop-blur border-b border-white/20">
          <div class="flex items-center gap-4">
            <button class="p-2 rounded-lg hover:bg-white/20 lg:hidden" onclick="toggleMobileNav()" aria-label="Toggle navigation">
              <svg class="w-5 h-5 text-emerald-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
              </svg>
            </button>
            <div class="font-extrabold tracking-tight text-emerald-900 text-lg sm:text-xl">IDC — Designer</div>
          </div>
          
          <nav class="hidden md:flex items-center gap-3 absolute left-1/2 transform -translate-x-1/2">
            <span class="px-3 py-1.5 rounded-lg bg-emerald-100 text-emerald-700 text-sm font-medium">Designer</span>
            <span class="text-emerald-600/50">|</span>
            <?php
            // Check user role and show appropriate dashboard link
            $current_user = wp_get_current_user();
            $is_operator = in_array('idc_operator', (array) $current_user->roles);
            
            if ($is_operator) {
                // Show Operator Dashboard button with purple styling
                ?>
                <a href="<?php echo home_url('/operator-dashboard/'); ?>" 
                   class="px-3 py-1.5 rounded-lg border border-purple-200 hover:bg-purple-50 text-purple-700 text-sm flex items-center gap-2"
                   title="Go to Operator Dashboard">
                  <span class="text-lg">📊</span>
                  Operator Dashboard
                </a>
                <?php
            } else {
                // Show Admin Dashboard button (default)
                ?>
                <a href="<?php echo home_url('/admin-dashboard/'); ?>" 
                   class="px-3 py-1.5 rounded-lg border border-emerald-200 hover:bg-emerald-50 text-emerald-700 text-sm flex items-center gap-2"
                   title="Go to Admin Dashboard">
                  <span class="text-lg">📊</span>
                  Admin Dashboard
                </a>
                <?php
            }
            ?>
          </nav>
          
          <div class="flex items-center gap-2 ml-auto">
            <button class="p-2 rounded-lg hover:bg-white/20" onclick="toggleTheme()" title="Toggle theme">
              <svg id="theme-icon-sun" class="w-5 h-5 text-emerald-900 hidden" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
              </svg>
              <svg id="theme-icon-moon" class="w-5 h-5 text-emerald-900" fill="currentColor" viewBox="0 0 20 20">
                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
              </svg>
            </button>
            
            <div class="relative" id="user-profile">
              <button class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-white/20" onclick="toggleUserMenu()">
                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 flex items-center justify-center text-white text-sm font-semibold">
                  <?php echo strtoupper(substr(wp_get_current_user()->display_name ?? "A", 0, 1)); ?>
                </div>
                <span class="hidden sm:block text-sm font-medium text-emerald-900">
                  <?php echo wp_get_current_user()->display_name ?? "Admin"; ?>
                </span>
                <svg class="w-4 h-4 text-emerald-700 transition-transform" id="user-menu-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </button>
              
              <div id="user-menu" class="absolute right-0 top-full mt-1 w-48 bg-white rounded-lg shadow-lg border border-emerald-200 py-1 hidden" style="z-index: 99999;">
                <div class="px-3 py-2 border-b border-emerald-100">
                  <p class="text-sm font-semibold text-emerald-900"><?php echo wp_get_current_user()->display_name ?? "Admin"; ?></p>
                  <p class="text-xs text-emerald-600"><?php echo wp_get_current_user()->user_email ?? "admin@example.com"; ?></p>
                </div>
                <button onclick="directLogout()" 
                        class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 w-full text-left">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3 3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                  </svg>
                  Logout
                </button>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Main content area -->
      <div class="flex flex-col lg:flex-row gap-4 sm:gap-6 lg:gap-8">
        <!-- LEFT: Form -->
        <section class="form-section space-y-4">
          <div class="flex items-center justify-between flex-wrap gap-2">
            <h2 class="text-xl sm:text-2xl font-extrabold text-emerald-900">ID Card Generator</h2>
            <span class="text-sm text-emerald-900/70">Saved: <strong id="saveCount">0</strong></span>
          </div>

        <div class="flex flex-col items-center">
          <label class="cursor-pointer">
            <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-emerald-300 shadow-md">
              <img id="photoPreview" src="<?php echo esc_url($defaultPhoto); ?>" class="w-full h-full object-cover" />
            </div>
            <input type="file" id="photoInput" accept="image/*" class="hidden" />
          </label>
          <p class="text-sm text-emerald-700 mt-2">Click to upload profile photo</p>
        </div>

        <div>
          <label class="text-sm text-emerald-900 font-medium">Full Name</label>
          <input id="inpName" type="text" class="input-glass mt-1" placeholder="Irfan" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="text-sm text-emerald-900 font-medium">National ID (Auto-generated)</label>
            <input id="inpID" type="text" readonly class="input-glass mt-1 text-emerald-800 font-semibold" placeholder="Auto-generated SSNYU-XXXXXX" />
          </div>
          <div>
            <label class="text-sm text-emerald-900 font-medium">Date of Birth</label>
            <input id="inpDOB" type="date" class="input-glass mt-1" />
          </div>
          <div>
            <label class="text-sm text-emerald-900 font-medium">Country</label>
            <input id="inpCountry" type="text" class="input-glass mt-1" placeholder="South Sudan" />
          </div>
          <div>
            <label class="text-sm text-emerald-900 font-medium">Issued Date (Auto-set)</label>
            <input id="inpIssued" type="date" readonly class="input-glass mt-1 text-emerald-800 font-semibold" />
          </div>
          <div>
            <label class="text-sm text-emerald-900 font-medium">Passport No.</label>
            <input id="inpPassport" type="text" class="input-glass mt-1" placeholder="8765433" />
          </div>
          <div>
            <label class="text-sm text-emerald-900 font-medium">Job Title</label>
            <input id="jobTitle" type="text" class="input-glass mt-1" placeholder="Student" />
          </div>
        </div>

        <div class="mt-2">
          <label class="text-sm text-emerald-900 font-medium">QR Code (payload)</label>
          <div id="qrcode" class="p-3 bg-white rounded-md w-fit shadow mt-1"></div>
        </div>

        <div class="flex flex-wrap gap-3 pt-2">
          <button id="btnSave"  class="border border-emerald-200 hover:bg-emerald-50 text-emerald-900 px-4 py-2 rounded-md">💾 Save Card</button>
          <button id="btnPrint" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-md">🖨️ Print Card</button>
          <button id="btnRandom" class="border border-emerald-200 hover:bg-emerald-50 text-emerald-900 px-4 py-2 rounded-md">Randomize Tracking</button>
        </div>
      </section>

      <!-- RIGHT: Preview -->
      <section class="preview-section">
        <div class="flex gap-3 mb-4">
          <button id="btnFront" class="toggle active">Front</button>
          <button id="btnBack"  class="toggle">Back</button>
        </div>

        <!-- FRONT -->
        <div id="cardFront" class="card-preview">
          <img id="frontBg" class="card-bg" src="<?php echo esc_url($frontURL); ?>" alt="Front background" />
          <div class="front-content">
            <div class="photo"><img decoding="async" id="w_photo" src="<?php echo esc_url($defaultPhoto); ?>" alt="User photo"></div>
            <div class="name"  id="w_name">alihamd</div>
            <div class="title" id="w_title">SSNYU: Student</div>

            <div id="w_qr" class="qr" title=""></div>

            <div class="details">
              <div class="detail-row">
                <div class="label">NATIONAL ID</div><div class="colon">:</div>
                <div class="value"><span id="w_nid"></span></div>
              </div>
              <div class="detail-row">
                <div class="label">Date of birth</div><div class="colon">:</div>
                <div class="value"><span id="w_dob"></span></div>
              </div>
              <div class="detail-row">
                <div class="label">Country</div><div class="colon">:</div>
                <div class="value"><span id="w_country"></span></div>
              </div>
              <div class="detail-row">
                <div class="label">Issued</div><div class="colon">:</div>
                <div class="value"><span id="w_issued"></span></div>
              </div>
              <div class="detail-row">
                <div class="label">Passport No</div><div class="colon">:</div>
                <div class="value"><span id="w_passport"></span></div>
              </div>
            </div>
          </div>
        </div>

        <!-- BACK -->
        <div id="cardBack" class="card-preview hidden">
          <img id="backBg" class="card-bg" src="<?php echo esc_url($backURL); ?>" alt="Back background" />
        </div>
        </section>
      </div>

      <!-- PRINT SHELL -->
      <div id="printRoot"></div>
      <div id="toast" class="toast">Saved ✅</div>
    </div>
  </div>
</div>

<!-- Ensure JavaScript configuration is available -->
<script type="text/javascript">
// Fallback configuration in case wp_localize_script doesn't work
if (typeof IDC_CONFIG === 'undefined') {
  window.IDC_CONFIG = {
    rest: {
      url: '<?php echo esc_url_raw(rest_url('idc/v1/')); ?>',
      nonce: '<?php echo wp_create_nonce('wp_rest'); ?>'
    },
    assets: {
      front: '<?php echo esc_url($frontURL); ?>',
      back: '<?php echo esc_url($backURL); ?>'
    },
    card: {
      width_mm: 53.98,
      height_mm: 85.6
    }
  };
}
console.log('IDC_CONFIG loaded:', window.IDC_CONFIG);

// Header functionality
function toggleUserMenu() {
  const menu = document.getElementById('user-menu');
  const arrow = document.getElementById('user-menu-arrow');
  const isHidden = menu.classList.contains('hidden');
  
  if (isHidden) {
    menu.classList.remove('hidden');
    arrow.style.transform = 'rotate(180deg)';
  } else {
    menu.classList.add('hidden');
    arrow.style.transform = 'rotate(0deg)';
  }
}

function toggleTheme() {
  const sunIcon = document.getElementById('theme-icon-sun');
  const moonIcon = document.getElementById('theme-icon-moon');
  const body = document.body;
  
  if (body.classList.contains('dark-theme')) {
    body.classList.remove('dark-theme');
    sunIcon.classList.add('hidden');
    moonIcon.classList.remove('hidden');
    localStorage.setItem('idc-theme', 'light');
  } else {
    body.classList.add('dark-theme');
    sunIcon.classList.remove('hidden');
    moonIcon.classList.add('hidden');
    localStorage.setItem('idc-theme', 'dark');
  }
}

function toggleMobileNav() {
  // For future mobile navigation if needed
  console.log('Mobile nav toggle');
}

// Close user menu when clicking outside
document.addEventListener('click', function(event) {
  const userProfile = document.getElementById('user-profile');
  const userMenu = document.getElementById('user-menu');
  
  if (!userProfile.contains(event.target) && !userMenu.classList.contains('hidden')) {
    userMenu.classList.add('hidden');
    document.getElementById('user-menu-arrow').style.transform = 'rotate(0deg)';
  }
});

// Direct logout function
function directLogout() {
  // Create a form to submit logout request
  const logoutForm = document.createElement('form');
  logoutForm.method = 'POST';
  logoutForm.action = '<?php echo wp_logout_url(); ?>';
  logoutForm.style.display = 'none';
  
  // Add logout nonce for security
  const nonceField = document.createElement('input');
  nonceField.type = 'hidden';
  nonceField.name = '_wpnonce';
  nonceField.value = '<?php echo wp_create_nonce("log-out"); ?>';
  logoutForm.appendChild(nonceField);
  
  // Add redirect URL to custom login page
  const redirectField = document.createElement('input');
  redirectField.type = 'hidden';
  redirectField.name = 'redirect_to';
  redirectField.value = '<?php echo home_url("/idc-login/?loggedout=true"); ?>';
  logoutForm.appendChild(redirectField);
  
  // Submit form
  document.body.appendChild(logoutForm);
  logoutForm.submit();
}

// Initialize theme
document.addEventListener('DOMContentLoaded', function() {
  const savedTheme = localStorage.getItem('idc-theme');
  if (savedTheme === 'dark') {
    toggleTheme();
  }
});
</script>
