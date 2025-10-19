<?php
if (!defined('ABSPATH')) { exit; }

// Get data passed from the shortcode
$user_data = $dashboard_data['user_data'] ?? null;
$settings = $dashboard_data['settings'] ?? [];
$user_id = $dashboard_data['user_id'] ?? 0;

$frontURL = $settings['front_png_id'] ? wp_get_attachment_url((int)$settings['front_png_id']) : IDC_CARD_URL.'assets/img/placeholder_front.png';
$backURL  = $settings['back_png_id']  ? wp_get_attachment_url((int)$settings['back_png_id'])  : IDC_CARD_URL.'assets/img/placeholder_back.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>SSNYU — Student Dashboard</title>

<!-- Tailwind -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- QR -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Inter:wght@600;700;800&display=swap" rel="stylesheet">

<style>
  :root{
    --brand:#facc15; --brand-2:#fbbf24; --ink:#111827; --muted:#4b5563; --glass: rgba(255,255,255,.55);
    --cardW:340px; --cardH:540px;
  }
  body{
    background:radial-gradient(900px 380px at 0% 0%, rgba(250,204,21,.18), transparent 60%),
               linear-gradient(135deg,#fffbea 0%,#fffbeb 40%,#fff7d6 100%);
    min-height:100vh; padding:24px;
    font-family:Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
    color:var(--ink); display:flex; align-items:center; justify-content:center;
  }
  .glass{ background:var(--glass); border:1px solid rgba(0,0,0,.06); border-radius:24px; backdrop-filter:blur(18px); box-shadow:0 10px 32px rgba(180,83,9,.15); }
  .input-glass{ background:rgba(255,255,255,.8); border:1px solid rgba(0,0,0,.08); border-radius:14px; padding:10px 14px; width:100%; color:#1f2937; font-weight:500; }
  .input-glass[readonly]{ background:rgba(255,255,255,.6); color:#6b7280; cursor:not-allowed; }
  .tabs{ display:flex; gap:8px; padding:6px; border-radius:14px; background:rgba(255,255,255,.6); border:1px solid rgba(0,0,0,.05); }
  .tab{ padding:8px 14px; border-radius:10px; font-weight:700; cursor:pointer; color:#92400e; border:1px solid transparent; }
  .tab.active{ color:#111827; background:linear-gradient(90deg,var(--brand),var(--brand-2)); box-shadow:0 6px 18px rgba(180,83,9,.25); }
  .btn-primary{ background:linear-gradient(90deg,var(--brand),var(--brand-2)); color:#111827; font-weight:800; padding:10px 14px; border-radius:12px; box-shadow:0 10px 24px rgba(180,83,9,.18); }
  .btn-secondary{ background:#fff; color:#7c2d12; border:1px solid rgba(0,0,0,.08); padding:9px 12px; border-radius:12px; font-weight:700; }
  .pill{ display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:999px; font-weight:700; font-size:14px; }
  .pill-red{ background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
  .pill-green{ background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }

  .card-preview{ width:var(--cardW); height:var(--cardH); position:relative; border-radius:18px; overflow:hidden; box-shadow:0 10px 20px rgba(0,0,0,.08); background:#fff; }
  .card-bg{ position:absolute; inset:0; width:100%; height:100%; object-fit:fill; }
  .front-content, .back-content{ position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; }

  .front-content .photo{ width:180px; height:180px; border-radius:999px; overflow:hidden; border:3px solid #16a34a; background:#fff; box-shadow:0 6px 18px rgba(0,0,0,.08); margin-top:40px; }
  .front-content .photo img{ width:100%; height:100%; object-fit:cover; }

  .front-content .name{ font-weight:700; font-size:28px; margin-top:12px; color:#111827; text-align:center; }
  .front-content .title{ font-size:13px; color:#4b5563; margin-bottom:10px; text-align:center; }

  .front-content .qr{ width:54px; height:54px; display:flex; align-items:center; justify-content:center; background:#fff; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,.08); }
  .front-content .qr img,.front-content .qr canvas{ width:54px; height:54px; }

  .details{ width:85%; margin-top:8px; padding-bottom:8px; font-family:'Poppins',sans-serif; font-size:13px; line-height:1.7; display:grid; row-gap:4px; margin-inline:auto; }
  .detail-row{ display:grid; grid-template-columns:1fr 16px 1fr; align-items:center; column-gap:6px; }
  .detail-row .label{ font-weight:400; color:#0b0f14; text-align:right; white-space:nowrap; }
  .detail-row .colon{ display:flex; align-items:center; justify-content:center; color:#0b0f14; }
  .detail-row .value{ text-align:left; font-weight:400; color:#0b0f14; font-style:italic; white-space:nowrap; }

  @media (max-width:1024px){
    .layout{ flex-direction:column; }
    .card-preview{ width:min(92vw, 340px); height:auto; aspect-ratio:340/540; }
  }

  @media print{ .screen-only{ display:none !important; } }
</style>
</head>
<body>

<div class="layout glass w-full max-w-6xl p-6 md:p-8 flex flex-col gap-6 screen-only">
  <!-- Header -->
  <div class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-extrabold text-amber-800">Student Dashboard</h1>
      <p class="text-sm text-amber-900/70">Your SSNYU ID card & account</p>
    </div>
    <div class="tabs">
      <button id="tabCard" class="tab active">ID Card</button>
      <button id="tabAccount" class="tab">Account Settings</button>
      <button onclick="logout()" class="tab" style="color: #dc2626;">Logout</button>
    </div>
  </div>

  <!-- Status & Buttons -->
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
      <span id="printStatusPill" class="pill pill-red"><span>🔒</span><span id="printStatusText">Printing Locked</span></span>
      <span class="text-sm text-amber-900/70">Admin approval required to print.</span>
    </div>
    <div class="flex gap-2">
      <button id="btnSaveCard" class="btn-secondary">💾 Save Card</button>
      <button id="btnRequestPrint" class="btn-primary">Request Print</button>
    </div>
  </div>

  <!-- Card View -->
  <div id="viewCard" class="flex flex-col lg:flex-row gap-8">
    <section class="w-full lg:w-1/2 space-y-4">
      <div class="flex flex-col items-center">
        <label class="cursor-pointer">
          <div class="w-32 h-32 rounded-full overflow-hidden border-4 shadow-md" style="border-color: #16a34a;">
            <img id="photoPreview" src="https://placehold.co/240x240?text=Photo" class="w-full h-full object-cover" />
          </div>
          <input type="file" id="photoInput" accept="image/*" class="hidden" />
        </label>
        <p class="text-sm text-amber-900 mt-2">Click to upload profile photo</p>
      </div>

      <div>
        <label class="text-sm text-amber-900 font-medium">Full Name</label>
        <input id="inpName" type="text" class="input-glass mt-1" placeholder="Your name" />
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="text-sm text-amber-900 font-medium">National ID (auto)</label>
          <input id="inpID" type="text" class="input-glass mt-1" readonly />
        </div>
        <div>
          <label class="text-sm text-amber-900 font-medium">Date of Birth</label>
          <input id="inpDOB" type="date" class="input-glass mt-1" />
        </div>
        <div>
          <label class="text-sm text-amber-900 font-medium">Country</label>
          <input id="inpCountry" type="text" class="input-glass mt-1" placeholder="Country" />
        </div>
        <div>
          <label class="text-sm text-amber-900 font-medium">Issued</label>
          <input id="inpIssued" type="text" class="input-glass mt-1" readonly />
        </div>
        <div>
          <label class="text-sm text-amber-900 font-medium">Passport No.</label>
          <input id="inpPassport" type="text" class="input-glass mt-1" placeholder="Passport" />
        </div>
        <div>
          <label class="text-sm text-amber-900 font-medium">Job Title</label>
          <input id="jobTitle" type="text" class="input-glass mt-1" value="Student" />
        </div>
      </div>

      <div class="mt-2">
        <label class="text-sm text-amber-900 font-medium">QR Code (payload)</label>
        <div id="qrcode" class="p-3 bg-white rounded-md w-fit shadow mt-1"></div>
      </div>
    </section>

    <!-- Card Preview -->
    <section class="w-full lg:w-1/2 flex flex-col items-center">
      <div class="flex gap-2 mb-3">
        <button id="btnFront" class="tab active">Front</button>
        <button id="btnBack" class="tab">Back</button>
      </div>

      <div id="cardFront" class="card-preview">
        <img id="frontBg" class="card-bg" src="<?php echo esc_url($frontURL); ?>" alt="Front">
        <div class="front-content">
          <div class="photo"><img id="w_photo" src="https://placehold.co/150x150" alt=""></div>
          <div class="name" id="w_name">Student Name</div>
          <div class="title" id="w_title">SSNYU: Student</div>
          <div id="w_qr" class="qr" title=""></div>
          <div class="details">
            <div class="detail-row"><div class="label">NATIONAL ID</div><div class="colon">:</div><div class="value"><span id="w_nid"></span></div></div>
            <div class="detail-row"><div class="label">Date of birth</div><div class="colon">:</div><div class="value"><span id="w_dob"></span></div></div>
            <div class="detail-row"><div class="label">Country</div><div class="colon">:</div><div class="value"><span id="w_country"></span></div></div>
            <div class="detail-row"><div class="label">Issued</div><div class="colon">:</div><div class="value"><span id="w_issued"></span></div></div>
            <div class="detail-row"><div class="label">Passport No</div><div class="colon">:</div><div class="value"><span id="w_passport"></span></div></div>
          </div>
        </div>
      </div>

      <div id="cardBack" class="card-preview hidden">
        <img id="backBg" class="card-bg" src="<?php echo esc_url($backURL); ?>" alt="Back">
      </div>
    </section>
  </div>

  <!-- Account Settings -->
  <div id="viewAccount" class="hidden">
    <div class="glass p-6">
      <h3 class="text-xl font-extrabold text-amber-800 mb-4">Account Settings</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div><label class="text-sm text-amber-900 font-medium">Old Password</label><input id="oldPassword" type="password" class="input-glass mt-1" placeholder="••••••••" /></div>
        <div><label class="text-sm text-amber-900 font-medium">New Password</label><input id="newPassword" type="password" class="input-glass mt-1" placeholder="New password" /></div>
        <div><label class="text-sm text-amber-900 font-medium">Confirm New Password</label><input id="confirmPassword" type="password" class="input-glass mt-1" placeholder="Confirm new password" /></div>
      </div>
      <div class="mt-4"><button id="btnSavePassword" class="btn-secondary">Save Password</button></div>
    </div>
  </div>
</div>

<!-- Toast -->
<div id="toast" style="position:fixed;bottom:18px;left:50%;transform:translateX(-50%);background:#111827;color:#fff;padding:10px 14px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.18);font-weight:700;display:none;">Saved ✅</div>

<script>
/* WordPress Integration */
const REST_BASE = '<?php echo rest_url('idc/v1/'); ?>';
const NONCE = '<?php echo wp_create_nonce('wp_rest'); ?>';
const USER_ID = <?php echo $user_id; ?>;
const EXISTING_DATA = <?php echo wp_json_encode($user_data); ?>;

/* API Helper Functions */
const apiCall = async (endpoint, method = 'GET', data = null) => {
  const url = REST_BASE + endpoint;
  const options = {
    method,
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': NONCE,
    },
  };
  
  if (data) {
    if (data instanceof FormData) {
      delete options.headers['Content-Type'];
      options.body = data;
    } else {
      options.body = JSON.stringify(data);
    }
  }
  
  try {
    const response = await fetch(url, options);
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    return await response.json();
  } catch (error) {
    console.error('API call failed:', error);
    throw error;
  }
};

/* ---------- helpers ---------- */
const $ = id => document.getElementById(id);

const genAlnum = n => Array.from({length:n},()=> "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"[Math.floor(Math.random()*36)]).join('');
const genNationalID = () => `SSNYU-${genAlnum(8)}`;
const fmtDateDMY = val => {
  if(!val) return '';
  const d = new Date(val);
  if (Number.isNaN(d.valueOf())) return val;
  return `${String(d.getDate()).padStart(2,'0')}.${String(d.getMonth()+1).padStart(2,'0')}.${d.getFullYear()}`;
};
const todayDMY = () => {
  const d = new Date();
  return `${String(d.getDate()).padStart(2,'0')}.${String(d.getMonth()+1).padStart(2,'0')}.${d.getFullYear()}`;
};
function toast(m){const t=$('toast');t.textContent=m;t.style.display='block';setTimeout(()=>t.style.display='none',1500);}

/* ---------- logout ---------- */
function logout() {
  // Create a form to submit logout request (same as admin dashboard)
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

/* ---------- dynamic font sizing ---------- */
function adjustNameFontSize(nameElement) {
  if (!nameElement) return;
  
  const nameText = nameElement.textContent || '';
  const nameLength = nameText.length;
  
  // Default font size
  let fontSize = '28px';
  
  // Apply dynamic sizing based on character count
  if (nameLength > 30) {
    fontSize = '14px';
  } else if (nameLength > 26) {
    fontSize = '16px';
  } else if (nameLength > 18) {
    fontSize = '20px';
  }
  
  nameElement.style.fontSize = fontSize;
}

// Get CSS class for name font size in print mode
function getNameSizeClass(nameText) {
  const nameLength = (nameText || '').length;
  
  if (nameLength > 30) {
    return 'tiny';
  } else if (nameLength > 26) {
    return 'small';
  } else if (nameLength > 18) {
    return 'medium';
  }
  return 'default';
}

/* ---------- payload + preview ---------- */
function buildPayload(){
  return {
    user_id: USER_ID,
    name: $('inpName').value || 'Student Name',
    national_id: $('inpID').value,
    date_of_birth: $('inpDOB').value || '',
    dob_display: fmtDateDMY($('inpDOB').value),
    country: $('inpCountry').value || '',
    issued: $('inpIssued').value,
    passport_no: $('inpPassport').value || '',
    job_title: $('jobTitle').value || 'Student'
  };
}

function renderPreview(){
  const p = buildPayload();

  $('w_title').textContent = `SSNYU: ${p.job_title}`;
  $('w_name').textContent     = p.name;
  // Apply dynamic font sizing to name
  adjustNameFontSize($('w_name'));
  $('w_nid').textContent      = p.national_id;
  $('w_dob').textContent      = p.dob_display;
  $('w_country').textContent  = p.country;
  $('w_issued').textContent   = p.issued;
  $('w_passport').textContent = p.passport_no;

  // Side QR
  const side=$('qrcode'); side.innerHTML='';
  new QRCode(side,{ text: JSON.stringify(p), width:160, height:160, correctLevel:QRCode.CorrectLevel.M });

  // Card QR
  const host=$('w_qr'); host.innerHTML='';
  new QRCode(host,{ text: JSON.stringify(p), width:216, height:216, correctLevel:QRCode.CorrectLevel.L });
}

/* ---------- print permission ---------- */
function setPrintUI(allowed, hasRequested = false){
  const pill=$('printStatusPill'), txt=$('printStatusText'), btn=$('btnRequestPrint');
  if(allowed){
    pill.classList.remove('pill-red'); pill.classList.add('pill-green');
    pill.firstElementChild.textContent='✅';
    txt.textContent='Printing Allowed';
    btn.disabled=false; btn.textContent='Print Card'; btn.classList.remove('opacity-75','cursor-not-allowed');
    btn.onclick = printCard;
  } else if (hasRequested) {
    pill.classList.add('pill-red'); pill.classList.remove('pill-green');
    pill.firstElementChild.textContent='⏳';
    txt.textContent='Approval Pending';
    btn.disabled=true; btn.textContent='Request Sent'; btn.classList.add('opacity-75','cursor-not-allowed');
  } else {
    pill.classList.add('pill-red'); pill.classList.remove('pill-green');
    pill.firstElementChild.textContent='🔒';
    txt.textContent='Printing Locked';
    btn.disabled=false; btn.textContent='Request Print'; btn.classList.remove('opacity-75','cursor-not-allowed');
    btn.onclick = requestPrint;
  }
}

async function fetchPrintPermission(){
  try {
    const result = await apiCall('student/print-status');
    return result;
  } catch (error) {
    console.error('Failed to fetch print permission:', error);
    return { can_print: false, has_requested: false };
  }
}

async function requestPrint(){
  try {
    // Save card silently (without showing notification)
    await saveCardSilently();
    
    console.log('Sending print request...');
    const result = await apiCall('student/request-print', 'POST');
    console.log('Print request API response:', result);
    console.log('Response type:', typeof result, 'Response keys:', Object.keys(result));
    
    if (result.success) {
      toast('Print request sent successfully ✅');
      refreshPermission();
    } else {
      // Handle API response that indicates failure
      console.log('API returned success=false:', result);
      toast('Request failed ❌');
    }
  } catch (error) {
    // Handle network or other errors
    console.error('Print request failed with error:', error);
    toast('Request failed ❌');
  }
}

async function printCard(){
  try {
    await saveCard(); // Save card first
    
    const p = buildPayload();
    const photo = $('w_photo').src;
    const frontImage = $('frontBg').src;
    const backImage = $('backBg').src;

    // Generate QR code synchronously first
    const qrContainer = document.createElement('div');
    new QRCode(qrContainer, {
      text: JSON.stringify(p),
      width: 204,
      height: 204,
      correctLevel: QRCode.CorrectLevel.L
    });
    
    // Wait a moment for QR to render, then get the data URL
    setTimeout(() => {
      const qrCanvas = qrContainer.querySelector('canvas');
      const qrImg = qrContainer.querySelector('img');
      const qrDataURL = qrCanvas ? qrCanvas.toDataURL() : (qrImg ? qrImg.src : '');

      const html = `<!doctype html><html><head><meta charset="utf-8"><title>Print — ${p.name}</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  @page { size: 53.98mm 85.6mm; margin: 0; }
  html, body { width: 53.98mm; height: 85.6mm; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
  * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  @media print { image-resolution: 300dpi; -webkit-image-resolution: 300dpi; }
  
  .page { 
    position: relative; width: 53.98mm; height: 85.6mm; overflow: hidden; 
    /* minimal background-only bleed to cover PDF/printer edges */
    background-size: calc(53.98mm + 1mm) calc(85.6mm + 1mm); 
    background-position: -0.5mm -0.5mm; 
    background-repeat: no-repeat; 
    page-break-after: always; border-radius: 0px !important;
  }
  .front { background-image: url('${frontImage}'); }
  .back { background-image: url('${backImage}'); }
  
  .content { 
    position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; 
    padding: 2mm 3mm 4mm; text-align: center; box-sizing: border-box;
  }
  
  .photo { 
    width: 25.4mm; height: 25.4mm; border-radius: 50%; overflow: hidden; 
    border: 0.6mm solid #22c55e; margin-top: 1.7mm; box-sizing: border-box; 
  }
  .photo img { width: 100%; height: 100%; object-fit: cover; }
  
  .name { font-weight: 700; margin-top: 2.5mm; color: #111827; line-height: 1.1; }
  .name.default { font-size: 4.2mm; }
  .name.medium { font-size: 3.5mm; }
  .name.small { font-size: 2.8mm; }
  .name.tiny { font-size: 2.4mm; }
  .title { font-size: 2.2mm; color: #4b5563; margin-bottom: 2mm; font-weight: 700; }
  
  .qr { 
    margin: 0.2mm 0; width: 12mm; height: 12mm; 
    display: flex; align-items: center; justify-content: center;
    background: #fff; border-radius: 2mm; box-shadow: 0 1mm 3mm rgba(0,0,0,.08);
  }
  .qr img { width: 12mm; height: 12mm; object-fit: contain; }
  
  .details { 
    width: 85%; margin-top: 2mm; padding-bottom: 2mm; font-size: 2.3mm; line-height: 1.6; 
    display: flex; flex-direction: column; gap: 1mm; 
  }
  .detail-row { 
    display: grid; grid-template-columns: 1.3fr 2mm 1.6fr; align-items: center; gap: 1.2mm; 
  }
  .detail-row .label { text-align: right; color: #374151; white-space: nowrap; font-size: 2.3mm; }
  .detail-row .colon { text-align: center; color: #374151; font-size: 2.3mm; }
  .detail-row .value { text-align: left; color: #374151; font-style: oblique; letter-spacing: .02em; font-size: 2.3mm; }
</style></head><body>
  <div class="page front">
    <div class="content">
      <div class="photo"><img src="${photo}" alt="Photo"></div>
      <div class="name ${getNameSizeClass(p.name)}">${p.name}</div>
      <div class="title">SSNYU: ${p.job_title || 'Student'}</div>
      <div class="qr"><img src="${qrDataURL}" alt="QR Code"></div>
      <div class="details">
        <div class="detail-row">
          <div class="label">NATIONAL ID</div><div class="colon">:</div>
          <div class="value">${p.national_id}</div>
        </div>
        <div class="detail-row">
          <div class="label">Date of birth</div><div class="colon">:</div>
          <div class="value">${p.dob_display}</div>
        </div>
        <div class="detail-row">
          <div class="label">Country</div><div class="colon">:</div>
          <div class="value">${p.country}</div>
        </div>
        <div class="detail-row">
          <div class="label">Issued</div><div class="colon">:</div>
          <div class="value">${p.issued}</div>
        </div>
        <div class="detail-row">
          <div class="label">Passport No</div><div class="colon">:</div>
          <div class="value">${p.passport_no}</div>
        </div>
      </div>
    </div>
  </div>
  <div class="page back"></div>
  <script>setTimeout(() => window.print(), 500);<\/script>
</body></html>`;

      const printWindow = window.open('', '_blank');
      printWindow.document.write(html);
      printWindow.document.close();
      
      toast('Card sent to printer ✅');
    }, 100);
    
  } catch (error) {
    toast('Print failed ❌');
    console.error('Print error:', error);
  }
}

async function refreshPermission(){
  try {
    const status = await fetchPrintPermission();
    setPrintUI(status.can_print, status.has_requested);
  } catch(e) {
    console.warn('Permission check failed:', e);
  }
}

/* ---------- save card ---------- */
async function saveCard(){
  try {
    const result = await saveCardInternal();
    toast('Card saved ✅');
  } catch (error) {
    toast('Save failed ❌');
    console.error('Save failed:', error);
  }
}

async function saveCardSilently(){
  return await saveCardInternal();
}

async function saveCardInternal(){
  const payload = {
    full_name: $('inpName').value,
    national_id: $('inpID').value,
    passport_no: $('inpPassport').value,
    country: $('inpCountry').value,
    photo_media_id: window.currentPhotoMediaId || null,
    dob: $('inpDOB').value,
    issued_on: $('inpIssued').value,
    job_title: $('jobTitle').value || 'Student'
  };
  
  let result;
  if (window.hasExistingCard) {
    // Update existing using student endpoint
    result = await apiCall('student/my-card', 'PUT', payload);
  } else {
    // Create new using student endpoint
    result = await apiCall('student/my-card', 'POST', payload);
    if (result.success) {
      window.hasExistingCard = true;
    }
  }
  
  // Update existing data reference
  if (!EXISTING_DATA) {
    window.location.reload(); // Refresh to get the ID
  }
  
  return result;
}

async function handlePhotoUpload(e) {
  const file = e.target.files[0];
  if (!file) return;
  
  if (!file.type.startsWith('image/')) {
    alert('Please select a valid image file');
    return;
  }
  
  if (file.size > 5 * 1024 * 1024) {
    alert('Image size should be less than 5MB');
    return;
  }
  
  // Show preview immediately
  const reader = new FileReader();
  reader.onload = function(e) {
    $('photoPreview').src = e.target.result;
    $('w_photo').src = e.target.result;
  };
  reader.readAsDataURL(file);
  
  // Upload to WordPress media library
  try {
    const formData = new FormData();
    formData.append('file', file);
    
    const result = await apiCall('upload-photo', 'POST', formData);
    window.currentPhotoMediaId = result.id;
    
  } catch (error) {
    console.error('Photo upload error:', error);
    alert('Failed to upload photo: ' + error.message);
  }
}

/* ---------- tabs & events ---------- */
function switchTab(which){
  if(which==='card'){
    $('viewCard').classList.remove('hidden');
    $('viewAccount').classList.add('hidden');
    $('tabCard').classList.add('active'); $('tabAccount').classList.remove('active');
  }else{
    $('viewAccount').classList.remove('hidden');
    $('viewCard').classList.add('hidden');
    $('tabAccount').classList.add('active'); $('tabCard').classList.remove('active');
  }
}

$('tabCard').onclick    = () => switchTab('card');
$('tabAccount').onclick = () => switchTab('account');

['inpName','inpDOB','inpCountry','inpPassport','jobTitle'].forEach(id=>{
  $(id).addEventListener('input', renderPreview);
});

$('photoInput').addEventListener('change', handlePhotoUpload);

$('btnFront').onclick=()=>{ $('cardFront').classList.remove('hidden'); $('cardBack').classList.add('hidden'); $('btnFront').classList.add('active'); $('btnBack').classList.remove('active'); };
$('btnBack' ).onclick=()=>{ $('cardBack' ).classList.remove('hidden'); $('cardFront').classList.add('hidden'); $('btnBack' ).classList.add('active'); $('btnFront' ).classList.remove('active'); };

$('btnSavePassword').onclick = async () => {
  const oldP=$('oldPassword').value.trim(), n=$('newPassword').value.trim(), c=$('confirmPassword').value.trim();
  if(!oldP||!n||!c) return toast('Fill all fields');
  if(n!==c) return toast('New passwords do not match');
  
  try {
    const result = await apiCall('student/change-password', 'POST', {
      old_password: oldP,
      new_password: n,
      confirm_password: c
    });
    
    if (result.success) {
      toast('Password updated ✅');
      $('oldPassword').value=$('newPassword').value=$('confirmPassword').value='';
    }
  } catch (error) {
    const message = error.message || 'Password change failed';
    toast(message + ' ❌');
    console.error('Password change error:', error);
  }
};

$('btnSaveCard').onclick = saveCard;

/* ---------- init ---------- */
async function loadStudentCard() {
  try {
    const result = await apiCall('student/my-card');
    if (result.exists && result.data) {
      const data = result.data;
      window.hasExistingCard = true;
      
      $('inpName').value = data.full_name || '';
      $('inpID').value = data.national_id || '';
      $('inpDOB').value = data.dob || '';
      $('inpCountry').value = data.country || '';
      $('inpIssued').value = data.issued_on || todayDMY();
      $('inpPassport').value = data.passport_no || '';
      $('jobTitle').value = data.job_title || 'Student';
      
      if (data.photo) {
        $('photoPreview').src = data.photo;
        $('w_photo').src = data.photo;
      }
      
      window.currentPhotoMediaId = data.photo_media_id;
    } else {
      // New user defaults
      window.hasExistingCard = false;
      $('inpID').value = genNationalID();
      $('inpIssued').value = todayDMY();
      $('inpName').value = '<?php echo esc_js(wp_get_current_user()->display_name); ?>';
      $('jobTitle').value = 'Student';
    }
  } catch (error) {
    console.error('Failed to load student card:', error);
    // Set defaults on error
    window.hasExistingCard = false;
    $('inpID').value = genNationalID();
    $('inpIssued').value = todayDMY();
    $('inpName').value = '<?php echo esc_js(wp_get_current_user()->display_name); ?>';
    $('jobTitle').value = 'Student';
  }
  
  renderPreview();
  refreshPermission();
}

(function init(){
  // Load existing data from server
  loadStudentCard();
  setInterval(refreshPermission, 30000); // Check every 30 seconds
})();
</script>
</body>
</html>