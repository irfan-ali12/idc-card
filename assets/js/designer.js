const $ = id => document.getElementById(id);
// No longer needed - using job title instead of tracking ID
const LS_KEY = 'ssnyu_cards';

// Global configuration - moved outside DOMContentLoaded for access by all functions
const cfg = (typeof IDC_CONFIG !== 'undefined') ? IDC_CONFIG : { rest:{}, assets:{}, card:{} };

// Dynamic font sizing function for name field
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

// After DOM loads
document.addEventListener('DOMContentLoaded', () => {

// Debug: Check if configuration is loaded
console.log('IDC Designer Config:', cfg);
console.log('REST URL:', cfg.rest?.url);
console.log('REST Nonce:', cfg.rest?.nonce);
console.log('Current user logged in:', document.body.classList.contains('logged-in'));
if (!cfg.rest?.url || !cfg.rest?.nonce) {
  console.error('IDC Designer: REST configuration missing!', cfg);
  toast('⚠️ Configuration error - please reload the page');
}
  // Generate National ID with SSNYU format
  const genNationalID = () => 'SSNYU-' + Math.random().toString(36).substring(2, 8).toUpperCase();
  
  // Set default job title
  $('jobTitle').value = 'Student';
  $('w_title').textContent = 'SSNYU: Student';
  
  // Auto-generate National ID
  $('inpID').value = genNationalID();
  
  // Set default values
  $('w_name').textContent = 'alihamd';
  $('inpName').value = 'alihamd';
  
  // Apply dynamic font sizing to name
  adjustNameFontSize($('w_name'));
  
  // Auto-set only issued date (not DOB)
  const today = new Date().toISOString().split('T')[0];
  $('inpIssued').value = today;
  
  // Initialize
  updateSaveCount();
  renderPreview();
  bindEvents();
  
  // Setup photo upload
  setupPhotoUpload();
  
  // Test REST API on page load
  testRESTAPI();
});

// Test function to verify REST API is working
async function testRESTAPI() {
  try {
    console.log('Testing REST API connection...');
    const response = await fetch(cfg.rest.url + 'customers', {
      method: 'GET',
      headers: { 'X-WP-Nonce': cfg.rest.nonce }
    });
    console.log('REST API test - Status:', response.status);
    if (response.ok) {
      console.log('✅ REST API is accessible');
    } else {
      console.error('❌ REST API error:', response.status, response.statusText);
      const errorText = await response.text();
      console.error('Error details:', errorText);
    }
  } catch (error) {
    console.error('❌ REST API connection failed:', error);
  }
}

function fmtDate(val){
  if(!val) return '';
  const d = new Date(val);
  if (Number.isNaN(d.valueOf())) return val;
  const dd=String(d.getDate()).padStart(2,'0'), mm=String(d.getMonth()+1).padStart(2,'0'), yy=d.getFullYear();
  return `${dd}.${mm}.${yy}`;
}



function buildPayloadFull(){
  return {
    job_title: $('jobTitle').value || 'Student',
    name: $('inpName').value || 'alihamd',
    national_id: $('inpID').value || '',
    date_of_birth: $('inpDOB').value || '',
    country: $('inpCountry').value || '',
    issued: $('inpIssued').value || '',
    passport_no: $('inpPassport').value || '',
    generated_at: new Date().toISOString()
  };
}

function stripEmpty(obj){
  const o={};
  Object.entries(obj).forEach(([k,v])=>{
    if(v!=='' && v!=null) o[k]=v;
  }); 
  return o;
}

function compactForQR(full){
  return JSON.stringify(stripEmpty(full));
}

function renderPreview(){
  const pFull = buildPayloadFull();

  $('w_name').textContent      = pFull.name;
  // Apply dynamic font sizing to name
  adjustNameFontSize($('w_name'));
  $('w_title').textContent     = 'SSNYU: ' + (pFull.job_title || 'Student');
  $('w_nid').textContent       = pFull.national_id;
  $('w_dob').textContent       = fmtDate(pFull.date_of_birth);
  $('w_country').textContent   = pFull.country;
  $('w_issued').textContent    = fmtDate(pFull.issued);
  $('w_passport').textContent  = pFull.passport_no;

  // Left side QR code
  const side = $('qrcode'); 
  side.innerHTML = '';
  if (typeof QRCode !== 'undefined') {
    new QRCode(side, {
      text: JSON.stringify(stripEmpty(pFull)),
      width: 160, 
      height: 160,
      correctLevel: QRCode.CorrectLevel.M
    });
  } else {
    side.innerHTML = '<p style="color: red;">QRCode library not loaded</p>';
  }

  // Card QR code
  const host = $('w_qr'); 
  host.innerHTML = '';
  const qrText = compactForQR(pFull);
  host.title = JSON.stringify(stripEmpty(pFull));
  if (typeof QRCode !== 'undefined') {
    const INTERNAL_QR_SIZE = 216;  // 54  4x
    new QRCode(host, {
      text: qrText,
      width: INTERNAL_QR_SIZE,
      height: INTERNAL_QR_SIZE,
      correctLevel: QRCode.CorrectLevel.L
    });
  } else {
    host.innerHTML = '<div style="width: 54px; height: 54px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-size: 8px;">QR</div>';
  }
}

// ---- Save / Load helpers ----
function getSaved(){
  try { 
    return JSON.parse(localStorage.getItem(LS_KEY) || '[]'); 
  } catch(e){ 
    return []; 
  }
}

function setSaved(arr){
  localStorage.setItem(LS_KEY, JSON.stringify(arr));
  updateSaveCount();
}

function updateSaveCount(){
  $('saveCount').textContent = getSaved().length;
}

function toast(msg){
  const t = $('toast'); 
  t.textContent = msg; 
  t.style.display='block';
  setTimeout(()=>{ 
    t.style.display='none'; 
  }, 1400);
}

// WordPress Database Save Function
async function saveCard(){
  console.log('=== SAVE CARD STARTED ===');
  const payload = stripEmpty(buildPayloadFull());
  console.log('Full payload:', payload);

  // Basic required field validation for backend contract
  const fullName = (payload.name || '').trim();
  const nationalId = payload.national_id || ''; // Auto-generated, should always exist
  const dob = (payload.date_of_birth || '').trim();
  console.log('Validation - Name:', fullName, 'NID:', nationalId, 'DOB:', dob);
  
  if (!fullName || !dob) {
    toast('Please fill Name and DOB (National ID is auto-generated)');
    console.error('Validation failed - missing required fields');
    return;
  }

  try {
    // Check if configuration is available
    console.log('Checking configuration:', cfg);
    if (!cfg.rest.url || !cfg.rest.nonce) {
      toast('Configuration error - please reload page');
      console.error('IDC Designer: Missing REST configuration', cfg);
      return;
    }
    console.log('Configuration OK - REST URL:', cfg.rest.url);

    // Test REST endpoint availability
    console.log('Testing REST endpoint availability...');
    try {
      const testRes = await fetch(cfg.rest.url + 'customers', {
        method: 'GET',
        headers: { 'X-WP-Nonce': cfg.rest.nonce },
      });
      console.log('REST test response:', testRes.status, testRes.statusText);
      if (!testRes.ok) {
        const testText = await testRes.text();
        console.log('REST test error:', testText);
      }
    } catch (testError) {
      console.error('REST endpoint test failed:', testError);
    }

    // Get photo media ID if uploaded
    const photoInput = $('photoInput');
    const photoMediaId = photoInput && photoInput.dataset.mediaId ? parseInt(photoInput.dataset.mediaId) : null;
    
    // 1) Create or update customer
    const customer = {
      full_name: fullName,
      national_id: nationalId,
      dob: dob,
      country: payload.country || null,
      issued_on: payload.issued || null, // Map 'issued' to 'issued_on'
      passport_no: payload.passport_no || null,
      job_title: payload.job_title || 'Student',
      photo_media_id: photoMediaId,
      status: 'active'
    };
    
    console.log('Customer payload being sent:', customer);

    console.log('Saving customer:', customer);
    const cRes = await fetch(cfg.rest.url + 'customer', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.rest.nonce },
      body: JSON.stringify(customer)
    });

    if (!cRes.ok) {
      const text = await cRes.text();
      console.error('Customer save failed:', cRes.status, cRes.statusText, text);
      console.error('Request payload:', customer);
      toast(`Save failed (customer): ${cRes.status} ${cRes.statusText}`);
      return;
    }

    const cJson = await cRes.json();
    const customerId = cJson.id;

    // 2) Create card record with QR payload
    const card = {
      customer_id: customerId,
      qr_payload: payload, // full payload in QR
      front_image_uri: $('frontBg').src || '',
      back_image_uri: $('backBg').src || '',
      note: 'SSNYU: ' + (payload.job_title || 'Student')
    };

    const cardRes = await fetch(cfg.rest.url + 'card', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.rest.nonce },
      body: JSON.stringify(card)
    });

    if (!cardRes.ok) {
      const text = await cardRes.text();
      console.error('Card save failed:', cardRes.status, cardRes.statusText, text);
      console.error('Card request payload:', card);
      toast(`Save failed (card): ${cardRes.status} ${cardRes.statusText}`);
      return;
    }

    // Optional local echo for quick access/history
    saveToLocalStorage();
    console.log('=== SAVE COMPLETED SUCCESSFULLY ===');
    toast('✅ Student card saved to database successfully!');
  } catch (err) {
    console.error('=== SAVE FAILED ===', err);
    toast(`❌ Save error: ${err.message || 'Unknown error'}`);
  }
}

function saveToLocalStorage(){
  const payload = stripEmpty(buildPayloadFull());
  const record = {
    id: crypto.randomUUID ? crypto.randomUUID() : ('id-' + Math.random().toString(36).slice(2)),
    saved_at: new Date().toISOString(),
    front_image: $('frontBg').src,
    back_image: $('backBg').src,
    photo: $('w_photo').src,
    payload: payload,
    display: {
      name: payload.name || '',
      job_title: payload.job_title || 'Student',
      national_id: payload.national_id || '',
      date_of_birth: payload.date_of_birth || '',
      country: payload.country || '',
      issued: payload.issued || '',
      passport_no: payload.passport_no || ''
    }
  };
  const arr = getSaved();
  arr.unshift(record);
  setSaved(arr);
  toast('Card saved locally ');
}

// Event Binding Function
function bindEvents() {
  // Inputs live preview
  ['inpName','inpID','inpDOB','inpCountry','inpIssued','inpPassport','jobTitle'].forEach(id=>{
    const element = $(id);
    if (element) {
      element.addEventListener('input', ()=>{
        if(id==='jobTitle') $('w_title').textContent = 'SSNYU: ' + ($('jobTitle').value || 'Student');
        renderPreview();
      });
    }
  });

  // Photo sync is now handled by setupPhotoUpload() function

  // Toggle front/back panes
  const btnFront = $('btnFront');
  const btnBack = $('btnBack');
  if (btnFront && btnBack) {
    btnFront.addEventListener('click', ()=>{ 
      $('cardFront').classList.remove('hidden'); 
      $('cardBack').classList.add('hidden');  
      btnFront.classList.add('active'); 
      btnBack.classList.remove('active'); 
    });
    
    btnBack.addEventListener('click', ()=>{ 
      $('cardBack').classList.remove('hidden'); 
      $('cardFront').classList.add('hidden');  
      btnBack.classList.add('active'); 
      btnFront.classList.remove('active'); 
    });
  }

  // Randomize button - now only regenerates National ID
  const btnRandom = $('btnRandom');
  if (btnRandom) {
    btnRandom.addEventListener('click', ()=>{ 
      // Only regenerate National ID
      const genNationalID = () => 'SSNYU-' + Math.random().toString(36).substring(2, 8).toUpperCase();
      $('inpID').value = genNationalID();
      
      // Update preview
      renderPreview(); 
    });
  }

  // Save + Print
  const btnSave = $('btnSave');
  const btnPrint = $('btnPrint');
  if (btnSave) btnSave.addEventListener('click', saveCard);
  if (btnPrint) btnPrint.addEventListener('click', printCard);
}

function printCard(){
  const p = buildPayloadFull();
  const photo = $('w_photo').src;
  const frontImage = $('frontBg').src;
  const backImage = $('backBg').src;

  // Generate QR code synchronously first
  const qrContainer = document.createElement('div');
  new QRCode(qrContainer, {
    text: compactForQR(p),
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
  
  .page { 
    position: relative; width: 53.98mm; height: 85.6mm; overflow: hidden; 
    background-size: 100% 100%; background-repeat: no-repeat; background-position: center; 
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
    border: 0.4mm solid #22c55e; margin-top: 1.7mm; box-sizing: border-box; 
  }
  .photo img { width: 100%; height: 100%; object-fit: cover; }
  
  .name { font-weight: 700; margin-top: 2.5mm; color: #111827; line-height: 1.1; }
  .name.default { font-size: 4.2mm; }
  .name.medium { font-size: 3.5mm; }
  .name.small { font-size: 2.8mm; }
  .name.tiny { font-size: 2.4mm; }
  .title { font-size: 2.2mm; color: #4b5563; margin-bottom: 2mm; }
  
  .qr { 
    margin: 0.4mm 0; width: 12mm; height: 12mm; 
    display: flex; align-items: center; justify-content: center;
    background: #fff; border-radius: 2mm; box-shadow: 0 1mm 3mm rgba(0,0,0,.08);
  }
  .qr img { width: 12mm; height: 12mm; object-fit: contain; }
  
  .details { 
    width: 85%; margin-top: 2mm; padding-bottom: 2mm; font-size: 2.1mm; line-height: 1.6; 
    display: flex; flex-direction: column; gap: 1mm; 
  }
  .detail-row { 
    display: grid; grid-template-columns: 1.3fr 2mm 1.6fr; align-items: center; gap: 1.2mm; 
  }
  .detail-row .label { text-align: right; color: #374151; white-space: nowrap; font-size: 2.1mm; }
  .detail-row .colon { text-align: center; color: #374151; font-size: 2.1mm; }
  .detail-row .value { text-align: left; color: #374151; font-style: oblique; letter-spacing: .02em; font-size: 2.1mm; }
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
          <div class="value">${fmtDate(p.date_of_birth)}</div>
        </div>
        <div class="detail-row">
          <div class="label">Country</div><div class="colon">:</div>
          <div class="value">${p.country}</div>
        </div>
        <div class="detail-row">
          <div class="label">Issued</div><div class="colon">:</div>
          <div class="value">${fmtDate(p.issued)}</div>
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
  }, 100);
}

// Photo Upload Functions
function setupPhotoUpload() {
  const photoPreview = $('photoPreview');
  const photoInput = $('photoInput');
  const w_photo = $('w_photo');
  
  if (!photoPreview || !photoInput || !w_photo) return;
  
  // Note: No need for click handler on photoPreview since the HTML <label> element
  // already handles clicking on the image to trigger the file input
  
  // Handle file selection
  photoInput.addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    
    // Validate file type
    if (!file.type.startsWith('image/')) {
      toast('Please select an image file');
      return;
    }
    
    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
      toast('Image size must be less than 5MB');
      return;
    }
    
    // Preview the image locally
    const reader = new FileReader();
    reader.onload = (e) => {
      const imageSrc = e.target.result;
      photoPreview.src = imageSrc;
      w_photo.src = imageSrc;
    };
    reader.readAsDataURL(file);
    
    // Upload to WordPress Media Library
    try {
      toast('Uploading photo...');
      const mediaId = await uploadImageToWordPress(file);
      // Store the media ID for later use in save
      photoInput.dataset.mediaId = mediaId;
      console.log('Photo upload completed, media ID stored:', mediaId);
      toast('✅ Photo uploaded successfully');
    } catch (error) {
      console.error('Photo upload failed:', error);
      toast(`❌ Photo upload failed: ${error.message}`);
      // Reset the file input and preview on error
      photoInput.value = '';
      const defaultPhotoUrl = 'https://placehold.co/240x240?text=Photo';
      photoPreview.src = defaultPhotoUrl;
      w_photo.src = defaultPhotoUrl;
    }
  });
}

async function uploadImageToWordPress(file) {
  console.log('Starting image upload...', file.name, file.size);
  
  if (!cfg.rest.url || !cfg.rest.nonce) {
    console.error('Configuration missing for image upload');
    throw new Error('Configuration missing');
  }
  
  // Create FormData for multipart upload
  const formData = new FormData();
  formData.append('file', file);
  
  // Use our custom upload endpoint
  const uploadEndpoint = cfg.rest.url + 'upload-photo';
  console.log('Uploading to custom endpoint:', uploadEndpoint);
  
  try {
    const response = await fetch(uploadEndpoint, {
      method: 'POST',
      headers: {
        'X-WP-Nonce': cfg.rest.nonce
      },
      body: formData
    });
    
    console.log('Upload response status:', response.status);
    
    if (!response.ok) {
      const text = await response.text();
      console.error('Media upload failed:', response.status, response.statusText, text);
      
      // Try to parse error message from JSON
      try {
        const errorData = JSON.parse(text);
        throw new Error(errorData.message || `Upload failed: ${response.status}`);
      } catch (parseError) {
        throw new Error(`Upload failed: ${response.status} ${response.statusText}`);
      }
    }
    
    const result = await response.json();
    console.log('Upload successful, media ID:', result.id, 'URL:', result.url);
    return result.id;
  } catch (error) {
    console.error('Image upload error:', error);
    throw error;
  }
}
