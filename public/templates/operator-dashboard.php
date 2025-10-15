<?php
if (!defined('ABSPATH')) { exit; }

// Get data passed from the shortcode
$customers = $dashboard_data['customers'] ?? [];
$stats = $dashboard_data['stats'] ?? [];
?>

<div class="idc-operator-dashboard">
  <!-- We don't need full HTML structure inside WordPress -->
  <!-- Tailwind Play CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            brand: {
              50:'#faf7ff',100:'#f3ecff',200:'#e9d8ff',300:'#d8b4fe',400:'#c084fc',
              500:'#a855f7',600:'#9333ea',700:'#7c3aed',800:'#6b21a8',900:'#581c87'
            }
          },
          boxShadow:{ 'elev':'0 10px 30px rgba(16,24,40,.06)' },
          borderRadius:{ 'xl2':'14px' }
        }
      }
    }
  </script>

  <!-- Inter font -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- React / ReactDOM -->
  <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
  <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
  
  <!-- QR Code Library -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

  <style>
    .idc-dashboard-container { 
      font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; 
      background:
        radial-gradient(900px 380px at -10% 10%, rgba(168,85,247,0.06), transparent 14%),
        linear-gradient(180deg,#fdfbff 0%, #fbf7ff 40%, #f9f5ff 100%);
      min-height: 100vh;
      color: #1e293b;
    }
    .dark .idc-dashboard-container { 
      background: radial-gradient(900px 380px at -10% 10%, rgba(168,85,247,0.12), transparent 14%), #1a0b2e; 
      color: #f1f5f9;
    }
    .table-wrap { overflow:auto; border-radius:12px; }
    canvas.spark { width:100% !important; height:44px !important; display:block; }
    
    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
  </style>

  <div id="idc-operator-root" class="idc-dashboard-container">
    <div style="padding: 20px; text-align: center; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;">
      <h2>IDC Operator Dashboard</h2>
      <p>Loading... If this message doesn't disappear, check browser console for errors.</p>
      <p><small>Shortcode: [idc_operator_dashboard] is working!</small></p>
    </div>
  </div>

<script type="text/javascript">
const { useState, useEffect, useRef, useMemo } = React;

/* Theme boot */
(function(){
  const stored = localStorage.getItem('idc_theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const mode = stored || (prefersDark ? 'dark' : 'light');
  if(mode === 'dark') document.documentElement.classList.add('dark');
})();

/* WordPress Integration - Data from PHP */
const INITIAL_DATA = <?php echo wp_json_encode($customers); ?>;
const INITIAL_STATS = <?php echo wp_json_encode($stats); ?>;
const REST_BASE = '<?php echo rest_url('idc/v1/'); ?>';
const NONCE = '<?php echo wp_create_nonce('wp_rest'); ?>';

/* Cross-Dashboard Notification Channel */
const notificationChannel = new BroadcastChannel('idc_notifications');

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
    options.body = JSON.stringify(data);
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

/* Data Management */
const loadData = () => INITIAL_DATA;
const saveData = (data) => {
  // In WordPress, we don't save to localStorage, we use the REST API
  console.log('Data would be saved via REST API:', data);
};

const addAudit = (action, id, meta) => {
  // Audit logging would be handled server-side
  console.log('Audit log:', { action, id, meta, at: new Date().toISOString() });
};

/* Icons */
const Icon = {
  menu:  (p)=>svg(p,'M4 6h16M4 12h16M4 18h16'),
  sun:   (p)=>React.createElement('svg',{...p,viewBox:'0 0 24 24',fill:'#fbbf24',stroke:'#fbbf24',strokeWidth:'2'},React.createElement('circle',{cx:'12',cy:'12',r:'5'}),React.createElement('path',{d:'M12 2v2M12 20v2M2 12h2M20 12h2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41'})),
  moon:  (p)=>svgPath(p,'M21 12.79A9 9 0 1 1 11.21 3A7 7 0 0 0 21 12.79z'),
  plus:  (p)=>svgPath(p,'M12 5v14M5 12h14'),
  users: (p)=>svgMulti(p,['M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2','M23 21v-2a4 4 0 0 0-3-3.87','M16 3.13a4 4 0 0 1 0 7.75'],[{type:'circle',cx:9,cy:7,r:4}]),
  check: (p)=>svgPath(p,'M20 6L9 17l-5-5'),
  alert: (p)=>svgMulti(p,['M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z','M12 9v4M12 17h.01']),
  printer:(p)=>svgMulti(p,['M6 9V2h12v7','M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2','M6 14h12v8H6z']),
  sort:  (p)=>svgMulti(p,['M11 11H3M7 15H3M3 7h14','M15 18l3 3 3-3M18 21V3']),
  logout:(p)=>svgMulti(p,['M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4','M16 17l5-5-5-5','M21 12H9']),
  eye:   (p)=>svgMulti(p,['M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z'],[{type:'circle',cx:12,cy:12,r:3}]),
  edit:  (p)=>svgPath(p,'M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z'),
  trash: (p)=>svgMulti(p,['M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14zM10 11v6M14 11v6'])
};
function svg(p, d){ return React.createElement('svg',{...p,viewBox:'0 0 24 24',fill:'none',stroke:'currentColor',strokeWidth:'2'},React.createElement('path',{d})); }
function svgPath(p, d){ return React.createElement('svg',{...p,viewBox:'0 0 24 24',fill:'none',stroke:'currentColor',strokeWidth:'2'},React.createElement('path',{d})); }
function svgMulti(p, paths=[], shapes=[]){
  return React.createElement('svg',{...p,viewBox:'0 0 24 24',fill:'none',stroke:'currentColor',strokeWidth:'2'},
    ...paths.map((d,i)=>React.createElement('path',{key:i,d})),
    ...shapes.map((s,i)=>s.type==='circle'?React.createElement('circle',{key:'c'+i,cx:s.cx,cy:s.cy,r:s.r}):null)
  );
}

/* Chart hook */
function useSparkline(color){
  const chartRef = useRef(null);
  const canvasRef = useRef(null);
  useEffect(()=>{
    if(canvasRef.current && !chartRef.current){
      const ctx = canvasRef.current.getContext('2d');
      chartRef.current = new Chart(ctx, {
        type:'line',
        data:{ labels:[0,1,2,3,4,5], datasets:[{ data:[0,0,0,0,0,0], borderColor: color, borderWidth:2, tension:0.35, pointRadius:0, fill:false }]},
        options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}, tooltip:{enabled:false}}, scales:{x:{display:false}, y:{display:false}}, animation:false }
      });
    }
  }, []);
  const update = (values)=>{
    if(!chartRef.current) return;
    chartRef.current.data.labels = values.map((_,i)=>i);
    chartRef.current.data.datasets[0].data = values;
    chartRef.current.update('none');
  };
  return { canvasRef, update };
}

/* User Profile Dropdown */
function UserProfile(){
  const [isOpen, setIsOpen] = useState(false);
  
  return React.createElement('div', { className:'relative' },
    React.createElement('button', { 
      className:'flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800',
      onClick: ()=> setIsOpen(!isOpen),
      onBlur: ()=> setTimeout(()=> setIsOpen(false), 150)
    },
      React.createElement('div', { className:'w-8 h-8 rounded-full bg-gradient-to-r from-brand-500 to-brand-700 flex items-center justify-center text-white text-sm font-semibold' },
        '<?php echo strtoupper(substr(wp_get_current_user()->display_name ?? "A", 0, 1)); ?>'
      ),
      React.createElement('span', { className:'hidden sm:block text-sm font-medium text-slate-700 dark:text-slate-300' }, 
        '<?php echo wp_get_current_user()->display_name ?? "Admin"; ?>'
      ),
      React.createElement('svg', { className:'w-4 h-4 text-slate-400 transition-transform ' + (isOpen ? 'rotate-180' : ''), fill:'none', stroke:'currentColor', viewBox:'0 0 24 24' },
        React.createElement('path', { strokeLinecap:'round', strokeLinejoin:'round', strokeWidth:'2', d:'M19 9l-7 7-7-7' })
      )
    ),
    isOpen && React.createElement('div', { className:'absolute right-0 top-full mt-1 w-48 bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-white/10 py-1', style: { zIndex: 10000 } },
      React.createElement('div', { className:'px-3 py-2 border-b border-slate-200 dark:border-white/10' },
        React.createElement('p', { className:'text-sm font-semibold text-slate-900 dark:text-white' }, '<?php echo wp_get_current_user()->display_name ?? "Admin"; ?>'),
        React.createElement('p', { className:'text-xs text-slate-500 dark:text-slate-400' }, '<?php echo wp_get_current_user()->user_email ?? "admin@example.com"; ?>')
      ),
      React.createElement('button', { 
        onClick: () => window.directLogout(),
        className:'flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 w-full text-left'
      },
        Icon.logout({ className:'w-4 h-4' }),
        'Logout'
      )
    )
  );
}

/* Modal */
function Modal({ open, title, children, onClose, primaryAction }){
  if(!open) return null;
  return (
    React.createElement('div', { className:'fixed inset-0 z-40' },
      React.createElement('div', { className:'absolute inset-0 bg-slate-900/40 backdrop-blur-sm', onClick: onClose, 'aria-hidden': true }),
      React.createElement('div', { className:'absolute inset-x-2 sm:inset-x-4 top-4 sm:top-8 md:top-16 mx-auto w-full max-w-2xl h-fit max-h-[90vh] overflow-y-auto rounded-xl2 shadow-elev bg-white dark:bg-slate-900 border border-white/60 dark:border-white/10' },
        React.createElement('div', { className:'px-4 sm:px-5 py-3 sm:py-4 border-b border-slate-200/70 dark:border-white/10 flex items-center justify-between sticky top-0 bg-white dark:bg-slate-900 z-10' },
          React.createElement('h3', { className:'font-semibold text-sm sm:text-base' }, title),
          React.createElement('button', { className:'px-2 py-1 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 text-lg', onClick:onClose, 'aria-label':'Close' }, '✕')
        ),
        React.createElement('div', { className:'p-4 sm:p-5' }, children),
        React.createElement('div', { className:'px-4 sm:px-5 pb-4 sm:pb-5 flex flex-col sm:flex-row justify-end gap-2 sticky bottom-0 bg-white dark:bg-slate-900 border-t border-slate-200/70 dark:border-white/10' },
          React.createElement('button', { className:'w-full sm:w-auto px-3 py-2 rounded-lg border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-slate-800 text-sm order-2 sm:order-1', onClick:onClose }, 'Cancel'),
          primaryAction && React.createElement('div', { className:'w-full sm:w-auto order-1 sm:order-2' }, primaryAction)
        )
      )
    )
  );
}

/* Direct logout function - Make it global */
window.directLogout = function() {
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
};

/* Date formatting function for display */
function formatDateForDisplay(isoDate) {
  if (!isoDate) return '';
  const d = new Date(isoDate);
  if (isNaN(d.valueOf())) return isoDate;
  const dd = String(d.getDate()).padStart(2,'0');
  const mm = String(d.getMonth()+1).padStart(2,'0');
  const yyyy = d.getFullYear();
  return `${dd}.${mm}.${yyyy}`;
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

/* Main App Component */
function IDCAdminApp(){
  const [rows, setRows] = useState(loadData());
  const [filters, setFilters] = useState({ q:'', name:'', country:'', nid:'', passport:'', tracking:'', from:'', to:'', status:'' });
  const [page, setPage] = useState(1);
  const [checked, setChecked] = useState(new Set());
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [themeDark, setThemeDark] = useState(document.documentElement.classList.contains('dark'));
  const [modal, setModal] = useState({ open:false, mode:'create', id:null });
  const [viewModal, setViewModal] = useState({ open:false, data:null });
  const [form, setForm] = useState({ full_name:'', national_id:'', passport:'', country:'', status:'active', photo:'', photo_media_id:null, dob:'', issued_on:'', job_title:'Student' });
  const [loading, setLoading] = useState(false);
  const [activeTab, setActiveTab] = useState(0); // 0=Dashboard, 1=Print Requests
  const [printRequests, setPrintRequests] = useState([]);
  const [toast, setToast] = useState({ show: false, message: '', type: 'success' });
  const [processings, setProcessings] = useState(new Set()); // Track which requests are being processed
  const [fadingOut, setFadingOut] = useState(new Set()); // Track records that are fading out

  // Sparklines
  const sTotal    = useSparkline('#0ea5e9');
  const sActive   = useSparkline('#10b981');
  const sInactive = useSparkline('#ef4444');
  const sQueue    = useSparkline('#f59e0b');

  useEffect(()=>{ updateAllSparklines(rows); }, [rows]);
  useEffect(()=>{ document.documentElement.classList.toggle('dark', themeDark); localStorage.setItem('idc_theme', themeDark?'dark':'light'); }, [themeDark]);

  // Listen for cross-dashboard notifications
  useEffect(() => {
    const handleBroadcastMessage = (event) => {
      const { message, type, action, userId, source } = event.data;
      
      console.log('Received broadcast message:', event.data);
      
      // Show notification
      if (message && type) {
        showToast(message, type);
      }
      
      // Sync data changes with fade-out animation
      if (action === 'approve' && userId) {
        setFadingOut(prev => new Set(prev).add(userId));
        setTimeout(() => {
          setPrintRequests(prev => prev.map(req => 
            req.user_id === userId ? { ...req, approved: true } : req
          ));
          setFadingOut(prev => {
            const newSet = new Set(prev);
            newSet.delete(userId);
            return newSet;
          });
        }, 300);
      } else if (action === 'reject' && userId) {
        setFadingOut(prev => new Set(prev).add(userId));
        setTimeout(() => {
          setPrintRequests(prev => prev.filter(req => req.user_id !== userId));
          setFadingOut(prev => {
            const newSet = new Set(prev);
            newSet.delete(userId);
            return newSet;
          });
        }, 300);
      }
    };

    console.log('Setting up broadcast listener for operator dashboard');
    notificationChannel.addEventListener('message', handleBroadcastMessage);
    return () => {
      console.log('Cleaning up broadcast listener for operator dashboard');
      notificationChannel.removeEventListener('message', handleBroadcastMessage);
    };
  }, []);

  function updateAllSparklines(data){
    const total    = data.length;
    const active   = data.filter(d=>d.status==='active').length;
    const inactive = data.filter(d=>d.status==='inactive').length;
    sTotal.update   ([Math.max(1,total-4), Math.max(1,total-2), total-1, total, total, total+1]);
    sActive.update  ([Math.max(0,active-3), Math.max(0,active-2), active-1, active, active, active+1]);
    sInactive.update([Math.max(0,inactive-2), inactive-1, inactive, inactive, inactive, inactive]);
    sQueue.update   ([0,0,0,0,0,0]);
  }

  /* Filter + paging */
  const filtered = useMemo(()=> rows.filter(r=>{
    const f = filters;
    if(f.q && ![r.full_name,r.national_id,r.passport,r.job_title,r.country].join(' ').toLowerCase().includes(f.q.toLowerCase())) return false;
    if(f.name && !r.full_name.toLowerCase().includes(f.name.toLowerCase())) return false;
    if(f.country && !r.country.toLowerCase().includes(f.country.toLowerCase())) return false;
    if(f.nid && !r.national_id.toLowerCase().includes(f.nid.toLowerCase())) return false;
    if(f.passport && !r.passport.toLowerCase().includes(f.passport.toLowerCase())) return false;
    if(f.tracking && !r.job_title.toLowerCase().includes(f.tracking.toLowerCase())) return false;
    if(f.status && r.status !== f.status) return false;
    if(f.from && new Date(r.created_at) < new Date(f.from)) return false;
    if(f.to && new Date(r.created_at) > new Date(f.to + 'T23:59:59')) return false;
    return true;
  }), [rows, filters]);

  const pageSize  = 8;
  const pageCount = Math.max(1, Math.ceil(filtered.length / pageSize));
  useEffect(()=> { if(page>pageCount) setPage(pageCount); }, [filtered.length]);
  const pageItems = filtered.slice((page-1)*pageSize, (page)*pageSize);

  /* Checkbox Selection Logic */
  const allOnPageChecked = pageItems.length > 0 && pageItems.every(item => checked.has(item.id));
  function toggleAllOnPage(){ 
    const n = new Set(checked); 
    (allOnPageChecked ? pageItems.forEach(x=> n.delete(x.id)) : pageItems.forEach(x=> n.add(x.id))); 
    setChecked(n); 
  }
  function toggleOne(id){ 
    const n=new Set(checked); 
    n.has(id) ? n.delete(id) : n.add(id); 
    setChecked(n); 
  }

  /* Toast Notification System */
  function showToast(message, type = 'success', broadcast = false) {
    setToast({ show: true, message, type });
    setTimeout(() => setToast({ show: false, message: '', type: 'success' }), 3000);
    
    // Broadcast notification to other dashboards if requested
    if (broadcast) {
      try {
        notificationChannel.postMessage({ message, type, timestamp: Date.now() });
      } catch (error) {
        console.warn('Failed to broadcast notification:', error);
      }
    }
  }

  /* Print Requests Management */
  async function loadPrintRequests() {
    try {
      const requests = await apiCall('print-requests');
      setPrintRequests(requests);
    } catch (error) {
      console.error('Failed to load print requests:', error);
    }
  }

  async function approvePrintRequest(userId) {
    setProcessings(prev => new Set(prev).add(userId)); // Add to processing set
    
    try {
      console.log('Starting approval for user:', userId);
      const response = await apiCall(`print-request/${userId}/approve`, 'POST');
      console.log('API call successful:', response);
      
      // Check if the API response indicates success
      if (response && response.success) {
        console.log('Approval successful, showing success notification');
        
        // Start fade-out animation safely
        try {
          setFadingOut(prev => new Set(prev).add(userId));
          
          // Update state after a small delay for animation
          setTimeout(() => {
            try {
              setPrintRequests(prev => prev.map(req => 
                req.user_id === userId ? { ...req, approved: true } : req
              ));
              setFadingOut(prev => {
                const newSet = new Set(prev);
                newSet.delete(userId);
                return newSet;
              });
            } catch (stateError) {
              console.error('Error updating state:', stateError);
            }
          }, 300);
        } catch (animationError) {
          console.error('Error with animation:', animationError);
        }
        
        showToast('Request Approved Successfully', 'success');
        
        // Broadcast action to other dashboards
        try {
          notificationChannel.postMessage({ 
            message: 'Request Approved Successfully', 
            type: 'success', 
            action: 'approve', 
            userId: userId, 
            timestamp: Date.now(),
            source: 'operator'
          });
          console.log('Broadcast message sent for approval');
        } catch (broadcastError) {
          console.warn('Failed to broadcast action:', broadcastError);
        }
        
      } else {
        console.error('API returned success=false:', response);
        showToast('Request Approval Failed', 'error');
      }
      
    } catch (error) {
      console.error('Failed to approve request:', error);
      showToast('Request Approval Failed', 'error');
    } finally {
      setProcessings(prev => {
        const newSet = new Set(prev);
        newSet.delete(userId);
        return newSet;
      });
    }
  }

  async function rejectPrintRequest(userId) {
    setProcessings(prev => new Set(prev).add(userId)); // Add to processing set
    
    try {
      console.log('Starting rejection for user:', userId);
      const response = await apiCall(`print-request/${userId}/reject`, 'POST');
      console.log('API call successful:', response);
      
      // Check if the API response indicates success
      if (response && response.success) {
        console.log('Rejection successful, showing success notification');
        
        // Start fade-out animation safely
        try {
          setFadingOut(prev => new Set(prev).add(userId));
          
          // Remove from list after animation
          setTimeout(() => {
            try {
              setPrintRequests(prev => prev.filter(req => req.user_id !== userId));
              setFadingOut(prev => {
                const newSet = new Set(prev);
                newSet.delete(userId);
                return newSet;
              });
            } catch (stateError) {
              console.error('Error updating state:', stateError);
            }
          }, 300);
        } catch (animationError) {
          console.error('Error with animation:', animationError);
        }
        
        showToast('Request Rejected Successfully', 'success');
        
        // Broadcast action to other dashboards
        try {
          notificationChannel.postMessage({ 
            message: 'Request Rejected Successfully', 
            type: 'success', 
            action: 'reject', 
            userId: userId, 
            timestamp: Date.now(),
            source: 'operator'
          });
          console.log('Broadcast message sent for rejection');
        } catch (broadcastError) {
          console.warn('Failed to broadcast action:', broadcastError);
        }
        
      } else {
        console.error('API returned success=false:', response);
        showToast('Request Rejection Failed', 'error');
      }
      
    } catch (error) {
      console.error('Failed to reject request:', error);
      showToast('Request Rejection Failed', 'error');
    } finally {
      setProcessings(prev => {
        const newSet = new Set(prev);
        newSet.delete(userId);
        return newSet;
      });
    }
  }

  // Load print requests when tab changes
  useEffect(() => {
    if (activeTab === 1) { // Print Requests tab
      loadPrintRequests();
    }
  }, [activeTab]);

  // Load pending requests count on mount for notification badge
  const [pendingCount, setPendingCount] = useState(0);
  useEffect(() => {
    async function loadPendingCount() {
      try {
        const requests = await apiCall('print-requests');
        const pending = requests.filter(r => !r.approved).length;
        setPendingCount(pending);
      } catch (error) {
        console.error('Failed to load pending count:', error);
      }
    }
    loadPendingCount();
    
    // Refresh count every 30 seconds
    const interval = setInterval(loadPendingCount, 30000);
    return () => clearInterval(interval);
  }, []);

  // Update pending count when print requests change
  useEffect(() => {
    const pending = printRequests.filter(r => !r.approved).length;
    setPendingCount(pending);
  }, [printRequests]);

  /* CRUD Operations with REST API */
  async function createRecord(data){
    setLoading(true);
    try {
      const payload = {
        full_name: data.full_name,
        national_id: data.national_id,
        passport_no: data.passport,
        country: data.country,
        status: data.status,
        photo_media_id: data.photo_media_id,
        dob: data.dob,
        issued_on: data.issued_on,
        job_title: data.job_title || 'Student',
      };
      
      const result = await apiCall('customer', 'POST', payload);
      
      // Refresh the data
      const customers = await apiCall('customers');
      const formatted = customers.map(c => ({
        id: 'c' + c.id,
        full_name: c.full_name || '',
        national_id: c.national_id || '',
        passport: c.passport_no || '',
        country: c.country || '',
        job_title: c.job_title || 'Student',
        created_at: c.created_at || '',
        status: c.status || 'active',
        photo: c.photo || ('https://placehold.co/300x300?text='+encodeURIComponent((c.full_name||'U')[0])),
        dob: c.dob || '',
        issued_on: c.issued_on || '',
      }));
      
      setRows(formatted);
      addAudit('create', result.id);
    } catch (error) {
      alert('Error creating record: ' + error.message);
    } finally {
      setLoading(false);
    }
  }

  async function updateRecord(id, patch){
    setLoading(true);
    try {
      const numericId = id.replace('c', '');
      await apiCall(`customer/${numericId}`, 'PUT', {
        full_name: patch.full_name,
        national_id: patch.national_id,
        passport_no: patch.passport,
        country: patch.country,
        status: patch.status,
        photo_media_id: patch.photo_media_id,
        dob: patch.dob,
        issued_on: patch.issued_on,
        job_title: patch.job_title,
      });
      
      // Update local state
      setRows(prev => prev.map(r=> r.id===id ? {...r, ...patch, updated_at:new Date().toISOString()} : r ));
      addAudit('update', id);
    } catch (error) {
      alert('Error updating record: ' + error.message);
    } finally {
      setLoading(false);
    }
  }



  /* Print */
  function printRecord(id){
    const it = rows.find(r=>r.id===id);
    if(!it) return alert('Customer not found');
    
    // Get plugin settings for card backgrounds
    fetch('<?php echo rest_url('idc/v1/card-settings'); ?>', {
      headers: {
        'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
      }
    })
      .then(response => response.json())
      .then(settings => {
        const frontImage = settings.front || '<?php echo IDC_CARD_URL; ?>assets/img/placeholder_front.png';
        const backImage = settings.back || '<?php echo IDC_CARD_URL; ?>assets/img/placeholder_back.png';
        
        // Prepare customer data for QR and display
        const customerData = {
          job_title: it.job_title || 'Student',
          name: it.full_name || '',
          national_id: it.national_id || '',
          date_of_birth: it.dob || '',
          country: it.country || '',
          issued: it.issued_on || '',
          passport_no: it.passport || '',
          generated_at: new Date().toISOString()
        };

        // Format date helper
        function fmtDate(val){
          if(!val) return '';
          const d = new Date(val);
          if (Number.isNaN(d.valueOf())) return val;
          const dd=String(d.getDate()).padStart(2,'0'), mm=String(d.getMonth()+1).padStart(2,'0'), yy=d.getFullYear();
          return `${dd}.${mm}.${yy}`;
        }

        // Strip empty values
        function stripEmpty(obj){
          const o={};
          Object.entries(obj).forEach(([k,v])=>{
            if(v!=='' && v!=null) o[k]=v;
          }); 
          return o;
        }

        // Generate QR code data
        const qrData = JSON.stringify(stripEmpty(customerData));

        // Generate QR code synchronously first
        const qrContainer = document.createElement('div');
        new QRCode(qrContainer, {
          text: qrData,
          width: 204,
          height: 204,
          correctLevel: QRCode.CorrectLevel.L
        });
        
        // Wait for QR to render, then create print content
        setTimeout(() => {
          const qrCanvas = qrContainer.querySelector('canvas');
          const qrImg = qrContainer.querySelector('img');
          const qrDataURL = qrCanvas ? qrCanvas.toDataURL() : (qrImg ? qrImg.src : '');
          
          const html = `<!doctype html><html><head><meta charset="utf-8"><title>Print ID Card — ${customerData.name}</title>
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
      <div class="photo"><img src="${it.photo || 'https://placehold.co/240x240?text=Photo'}" alt="Photo"></div>
      <div class="name ${getNameSizeClass(customerData.name)}">${customerData.name}</div>
      <div class="title">SSNYU: ${customerData.job_title || 'Student'}</div>
      <div class="qr"><img src="${qrDataURL}" alt="QR Code"></div>
      <div class="details">
        <div class="detail-row">
          <div class="label">NATIONAL ID</div><div class="colon">:</div>
          <div class="value">${customerData.national_id}</div>
        </div>
        <div class="detail-row">
          <div class="label">Date of birth</div><div class="colon">:</div>
          <div class="value">${fmtDate(customerData.date_of_birth)}</div>
        </div>
        <div class="detail-row">
          <div class="label">Country</div><div class="colon">:</div>
          <div class="value">${customerData.country}</div>
        </div>
        <div class="detail-row">
          <div class="label">Issued</div><div class="colon">:</div>
          <div class="value">${fmtDate(customerData.issued)}</div>
        </div>
        <div class="detail-row">
          <div class="label">Passport No</div><div class="colon">:</div>
          <div class="value">${customerData.passport_no}</div>
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
      })
      .catch(error => {
        console.error('Error fetching card settings:', error);
        alert('Error loading card templates. Please try again.');
      });
  }

  /* Sort + form */
  function sortBy(k,dir=1){ setRows([...rows].sort((a,b)=> (''+(a[k]??'')).localeCompare(''+(b[k]??''))*dir)); }
  function openCreate(){ 
    setForm({ 
      full_name:'', 
      national_id: genNationalID(), 
      passport:'', 
      country:'', 
      status:'active', 
      photo:'', 
      photo_media_id: null,
      dob:'', 
      issued_on: getCurrentDate(), 
      job_title: 'Student' 
    }); 
    setModal({ open:true, mode:'create', id:null }); 
  }
  function openEdit(id){ 
    const r = rows.find(x=>x.id===id); 
    if(!r) return; 
    setForm({ 
      full_name:r.full_name||'', 
      national_id:r.national_id||'', 
      passport:r.passport||'', 
      country:r.country||'', 
      status:r.status||'active', 
      photo:r.photo||'', 
      photo_media_id:r.photo_media_id||null,
      dob:r.dob||'', 
      issued_on:r.issued_on||'', 
      job_title:r.job_title||'Student' 
    }); 
    setModal({ open:true, mode:'edit', id }); 
  }
  
  async function submitForm(e){ 
    e.preventDefault(); 
    if(!form.full_name?.trim()) return alert('Name is required'); 
    
    if(modal.mode==='create') {
      await createRecord(form);
    } else {
      await updateRecord(modal.id, form);
    }
    
    setModal({ open:false, mode:'create', id:null }); 
  }

  async function handlePhotoUpload(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    // Validate file type
    if (!file.type.startsWith('image/')) {
      alert('Please select a valid image file');
      return;
    }
    
    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
      alert('Image size should be less than 5MB');
      return;
    }
    
    // Show preview immediately
    const reader = new FileReader();
    reader.onload = function(e) {
      setForm(prev => ({ ...prev, photo: e.target.result, photoFile: file }));
    };
    reader.readAsDataURL(file);
    
    // Upload to WordPress media library
    try {
      setLoading(true);
      const formData = new FormData();
      formData.append('file', file);
      
      const response = await fetch(REST_BASE + 'upload-photo', {
        method: 'POST',
        headers: {
          'X-WP-Nonce': NONCE,
        },
        body: formData
      });
      
      if (!response.ok) {
        throw new Error(`Upload failed: ${response.status}`);
      }
      
      const result = await response.json();
      
      // Store media ID for saving
      setForm(prev => ({ 
        ...prev, 
        photo: result.url,
        photo_media_id: result.id 
      }));
      
    } catch (error) {
      console.error('Photo upload error:', error);
      alert('Failed to upload photo: ' + error.message);
    } finally {
      setLoading(false);
    }
  }



  /* UI */
  return (
    React.createElement('div', { className: 'max-w-[1400px] mx-auto p-3 sm:p-4 md:p-6 space-y-4' },

      /* Top Bar */
      React.createElement('header', { className:'flex items-center gap-2 sm:gap-3 rounded-xl2 shadow-elev bg-white/90 dark:bg-slate-900/70 border border-white/60 dark:border-white/10 backdrop-blur px-2 sm:px-3 md:px-4 py-2' },
        React.createElement('button', { className:'p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800', onClick:()=> setSidebarOpen(!sidebarOpen), 'aria-label':'Toggle navigation' }, Icon.menu({ className:'w-5 h-5' })),
        React.createElement('div', { className:'font-extrabold tracking-tight text-slate-900 dark:text-white' }, 'IDC — Operator Dashboard'),
        React.createElement('div', { className:'hidden md:flex items-center gap-2 ml-3 px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200/70 dark:border-white/10 flex-1 max-w-lg' },
          React.createElement('input', { className:'bg-transparent outline-none w-full text-sm', placeholder:'Search name / NID / passport / job title…', value:filters.q, onChange:e=>{ setFilters({...filters, q:e.target.value}); setPage(1); }})
        ),
        React.createElement('div', { className:'ml-auto flex items-center gap-2' },
          React.createElement('nav', { className:'hidden md:flex items-center gap-2 mr-4' },
            React.createElement('a', { 
              href: '<?php echo home_url("/id-card/"); ?>', 
              className:'px-3 py-1.5 rounded-lg border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-slate-800 text-sm flex items-center gap-2',
              title: 'Go to ID Card Designer'
            }, 
              React.createElement('span', { className:'text-lg' }, '🎨'), 
              'Designer'
            ),
            React.createElement('span', { className:'text-slate-300 dark:text-slate-600' }, '|'),
            React.createElement('span', { className:'px-3 py-1.5 rounded-lg bg-brand-100 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300 text-sm font-medium' }, 'Operator Dashboard')
          ),
          React.createElement('button', { className:'px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800', onClick:()=> setThemeDark(!themeDark), title: themeDark?'Light mode':'Dark mode' },
            themeDark ? Icon.sun({ className:'w-5 h-5' }) : Icon.moon({ className:'w-5 h-5' })
          ),
          React.createElement('button', { className:'hidden sm:inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-brand-500 text-white hover:bg-brand-600', onClick: openCreate, disabled: loading }, 
            loading ? 'Loading...' : [Icon.plus({ className:'w-4 h-4' }), 'Add']
          ),
          React.createElement(UserProfile, null)
        )
      ),

      // Grid: responsive layout - expand main content when sidebar is closed
      React.createElement('div', { className: `grid gap-4 ${sidebarOpen ? 'grid-cols-1 lg:grid-cols-[260px_1fr]' : 'grid-cols-1'}` },

        // Sidebar
        sidebarOpen ? React.createElement('aside', { className:'rounded-xl2 shadow-elev bg-white/90 dark:bg-slate-900/70 border border-white/60 dark:border-white/10 p-4 lg:sticky lg:top-24 flex flex-col' },
          React.createElement('div', { className:'flex-1' },
            React.createElement('div', { className:'text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3' }, 'Navigation'),
            React.createElement('nav', { className:'space-y-1' },
              ['Dashboard','Print Requests'].map((item, i) =>
                React.createElement('button', { 
                  key:item, 
                  className:'w-full text-left px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 font-medium relative ' + (i===activeTab ? 'bg-gradient-to-r from-brand-500 to-brand-700 text-white' : ''), 
                  onClick: () => setActiveTab(i)
                }, 
                  item,
                  // Show notification badge for Print Requests
                  i === 1 && pendingCount > 0 && React.createElement('span', {
                    className: 'absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1'
                  }, pendingCount > 99 ? '99+' : pendingCount)
                )
              )
            ),
            React.createElement('div', { className:'mt-6' },
              React.createElement('div', { className:'text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2' }, 'Quick actions'),
              React.createElement('div', { className:'flex flex-col gap-2' },
                React.createElement('button', { className:'inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-brand-500 text-white hover:bg-brand-600', onClick: openCreate, disabled: loading }, Icon.plus({ className:'w-4 h-4' }), 'Create')
              )
            )
          )
        ) : React.createElement('div', null),

        /* Main */
        React.createElement('main', { className:'rounded-xl2 shadow-elev bg-white/90 dark:bg-slate-900/70 border border-white/60 dark:border-white/10 p-4 md:p-6' },

          // Dashboard Tab (activeTab === 0)
          activeTab === 0 && React.createElement('div', null,
          /* Metrics */
          React.createElement('div', { className:'grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 mb-6' },
            metricCard('Total', INITIAL_STATS.total || rows.length, sTotal, 'from-white to-slate-50', 'text-slate-500', Icon.users),
            metricCard('Active', INITIAL_STATS.active || rows.filter(r=>r.status==='active').length, sActive, 'from-emerald-50 to-white dark:from-emerald-900/20 dark:to-slate-900', 'text-emerald-700 dark:text-emerald-300', Icon.check),
            metricCard('Inactive', INITIAL_STATS.inactive || rows.filter(r=>r.status==='inactive').length, sInactive, 'from-rose-50 to-white dark:from-rose-900/20 dark:to-slate-900', 'text-rose-700 dark:text-rose-300', Icon.alert)
          ),

          /* Filters */
          React.createElement('div', { className:'flex flex-wrap items-center gap-2 md:gap-3 mb-4' },
            inputFilter('Name','name'), inputFilter('Country','country'), inputFilter('National ID','nid'),
            inputFilter('Passport','passport'), inputFilter('Job Title','tracking'),
            React.createElement('select', { className:'px-3 py-2 rounded-lg border border-slate-200 dark:border-white/10 bg-white/60 dark:bg-slate-800/60', value:filters.status, onChange:e=>{ setFilters({...filters, status:e.target.value}); setPage(1);} },
              React.createElement('option',{value:''},'Any status'),
              React.createElement('option',{value:'active'},'Active'),
              React.createElement('option',{value:'inactive'},'Inactive')
            ),
            React.createElement('div', { className:'ml-auto flex gap-2 w-full sm:w-auto' },
              React.createElement('button', { className:'flex-1 sm:flex-none px-3 py-2 rounded-lg border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-slate-800', onClick: ()=> { setFilters({ q:'', name:'', country:'', nid:'', passport:'', tracking:'', from:'', to:'', status:''}); setPage(1); } }, 'Reset'),
              React.createElement('button', { className:'flex-1 sm:flex-none px-3 py-2 rounded-lg bg-brand-500 text-white hover:bg-brand-600', onClick: ()=> setPage(1) }, 'Apply')
            )
          ),

          /* Bulk bar */
          React.createElement('div', { className:'flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2' },
            React.createElement('div', { className:'text-sm text-slate-500 dark:text-slate-400' },
              checked.size ? `${checked.size} selected` : `Showing ${(page-1)*pageSize + 1}–${Math.min(page*pageSize, filtered.length)} of ${filtered.length}`
            ),
            React.createElement('div', { className:'flex items-center gap-2' },
              React.createElement('button', { className:'px-3 py-1.5 rounded-lg border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-slate-800', onClick: ()=> sortBy('full_name', 1) }, React.createElement('span', { className:'inline-flex items-center gap-1' }, Icon.sort({ className:'w-4 h-4'}),'Sort A–Z'))
            )
          ),

          /* Table */
          React.createElement('div', { className:'table-wrap overflow-x-auto rounded-xl2 border border-slate-200 dark:border-white/10' },
            React.createElement('table', { className:'min-w-full text-left divide-y divide-slate-200/70 dark:divide-white/10' },
              React.createElement('thead', { className:'sticky top-0 bg-slate-50/70 dark:bg-slate-800/70 backdrop-blur' },
                React.createElement('tr', null,
                  React.createElement('th', { className:'px-4 py-3 text-xs font-semibold text-slate-500 w-10' }, 
                    React.createElement('input', { type:'checkbox', checked:allOnPageChecked, onChange:toggleAllOnPage })
                  ),
                  ['Name','Country','National ID','Passport','Job Title','Created','Status','Actions'].map(h =>
                    React.createElement('th', { key:h, className:'px-4 py-3 text-xs font-semibold text-slate-500 whitespace-nowrap' }, h)
                  )
                )
              ),
              React.createElement('tbody', { className:'divide-y divide-slate-100 dark:divide-white/5' },
                pageItems.length ? pageItems.map(item => 
                  React.createElement('tr', { key:item.id, className:'hover:bg-slate-50 dark:hover:bg-slate-800/40' },
                    React.createElement('td', { className:'px-4 py-3' }, 
                      React.createElement('input', { type:'checkbox', checked:checked.has(item.id), onChange:()=> toggleOne(item.id) })
                    ),
                    React.createElement('td', { className:'px-4 py-3' }, 
                      React.createElement('div',{className:'flex items-center gap-3'}, 
                        React.createElement('img',{src:item.photo,alt:'photo',className:'w-10 h-10 rounded-md object-cover ring-1 ring-slate-200 dark:ring-slate-700'}),
                        React.createElement('div', null, 
                          React.createElement('div',{className:'font-semibold'}, item.full_name),
                          React.createElement('div',{className:'text-xs text-slate-500'}, item.country)
                        )
                      )
                    ),
                    React.createElement('td', { className:'px-4 py-3' }, item.country),
                    React.createElement('td', { className:'px-4 py-3 font-mono text-sm' }, item.national_id),
                    React.createElement('td', { className:'px-4 py-3 font-mono text-sm' }, item.passport),
                    React.createElement('td', { className:'px-4 py-3 text-sm' }, item.job_title),
                    React.createElement('td', { className:'px-4 py-3 text-sm whitespace-nowrap' }, item.created_at ? new Date(item.created_at).toLocaleDateString() : ''),
                    React.createElement('td', { className:'px-4 py-3' }, 
                      React.createElement('span',{className:`px-2.5 py-1 rounded-full text-xs font-semibold ${item.status==='active'?'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300':'bg-rose-100 text-rose-700 dark:bg-rose-900/20 dark:text-rose-300'}`}, item.status)
                    ),
                    React.createElement('td', { className:'px-4 py-3' },
                      React.createElement('div',{className:'flex flex-wrap gap-1 justify-end'}, 
                        React.createElement('button',{className:'p-2 rounded-md border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-slate-800', onClick:()=> setViewModal({ open:true, data:item }), title:'View' }, Icon.eye({ className:'w-4 h-4' })),
                        React.createElement('button',{className:'p-2 rounded-md border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-slate-800', onClick:()=> openEdit(item.id), title:'Edit' }, Icon.edit({ className:'w-4 h-4' })),
                        React.createElement('button',{className:'p-2 rounded-md border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-slate-800', onClick:()=> printRecord(item.id), title:'Print' }, Icon.printer({ className:'w-4 h-4' }))
                      
                      )
                    )
                  )
                ) : React.createElement('tr', null,
                      React.createElement('td', { colSpan:9, className:'px-4 py-10 text-center text-slate-500' }, 'No records match the current filters.')
                )
              )
            )
          ),

          /* Pagination */
          React.createElement('div', { className:'flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mt-4' },
            React.createElement('div', { className:'text-sm text-slate-500 dark:text-slate-400' }, `Page ${page} of ${pageCount}`),
            React.createElement('div', { className:'flex gap-2' },
              React.createElement('button',{ className:'px-3 py-1.5 rounded-lg border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-slate-800', onClick: ()=> setPage(p => Math.max(1, p-1)) }, 'Prev'),
              React.createElement('button',{ className:'px-3 py-1.5 rounded-lg border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-slate-800', onClick: ()=> setPage(p => Math.min(pageCount, p+1)) }, 'Next')
            )
          )
          ), // End Dashboard Tab

          // Print Requests Tab (activeTab === 1)
          activeTab === 1 && React.createElement('div', null,
            React.createElement('h2', { className:'text-2xl font-bold text-slate-900 dark:text-white mb-6' }, 'Print Requests'),
            
            React.createElement('div', { className:'table-wrap overflow-x-auto rounded-xl2 border border-slate-200 dark:border-white/10' },
              React.createElement('table', { className:'min-w-full text-left divide-y divide-slate-200/70 dark:divide-white/10' },
                React.createElement('thead', { className:'sticky top-0 bg-slate-50/70 dark:bg-slate-800/70 backdrop-blur' },
                  React.createElement('tr', null,
                    ['Student','Email','Request Time','Card Info','Actions'].map(h =>
                      React.createElement('th', { key:h, className:'px-4 py-3 text-xs font-semibold text-slate-500 whitespace-nowrap' }, h)
                    )
                  )
                ),
                React.createElement('tbody', { className:'divide-y divide-slate-100 dark:divide-white/5' },
                  printRequests.length ? printRequests.map(request => 
                    React.createElement('tr', { 
                      key:request.user_id, 
                      className:`hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-all duration-300 ${
                        fadingOut.has(request.user_id) ? 'opacity-30 scale-95' : 'opacity-100 scale-100'
                      }` 
                    },
                      React.createElement('td', { className:'px-4 py-3' }, 
                        React.createElement('div',{className:'flex items-center gap-3'}, 
                          React.createElement('img',{src:request.customer_data?.photo || 'https://placehold.co/40x40?text=U',alt:'photo',className:'w-10 h-10 rounded-md object-cover ring-1 ring-slate-200 dark:ring-slate-700'}),
                          React.createElement('div', null, 
                            React.createElement('div',{className:'font-semibold'}, request.display_name),
                            React.createElement('div',{className:'text-xs text-slate-500'}, request.customer_data?.job_title || 'Student')
                          )
                        )
                      ),
                      React.createElement('td', { className:'px-4 py-3 text-sm' }, request.email),
                      React.createElement('td', { className:'px-4 py-3 text-sm' }, new Date(request.request_time).toLocaleString()),
                      React.createElement('td', { className:'px-4 py-3 text-sm' }, 
                        React.createElement('div', null,
                          React.createElement('div', null, request.customer_data?.full_name || 'No card data'),
                          React.createElement('div', { className:'text-xs text-slate-500' }, request.customer_data?.national_id || '')
                        )
                      ),
                      React.createElement('td', { className:'px-4 py-3' },
                        request.approved ? 
                          React.createElement('span', { className:'px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700' }, 'Approved') :
                          React.createElement('div',{className:'flex gap-2'}, 
                            React.createElement('button',{
                              className:`px-3 py-1 rounded-md bg-emerald-500 text-white hover:bg-emerald-600 text-sm ${processings.has(request.user_id) ? 'opacity-50 cursor-not-allowed' : ''}`, 
                              onClick:() => approvePrintRequest(request.user_id),
                              disabled: processings.has(request.user_id)
                            }, processings.has(request.user_id) ? 'Processing...' : 'Approve'),
                            React.createElement('button',{
                              className:`px-3 py-1 rounded-md bg-red-500 text-white hover:bg-red-600 text-sm ${processings.has(request.user_id) ? 'opacity-50 cursor-not-allowed' : ''}`, 
                              onClick:() => rejectPrintRequest(request.user_id),
                              disabled: processings.has(request.user_id)
                            }, processings.has(request.user_id) ? 'Processing...' : 'Reject')
                          )
                      )
                    )
                  ) : React.createElement('tr', null,
                        React.createElement('td', { colSpan:5, className:'px-4 py-10 text-center text-slate-500' }, 'No print requests found.')
                  )
                )
              )
            )
          ),

          // Other tabs can be added here...
        )
      ),

      /* Create/Edit Modal */
      React.createElement(Modal, { 
        open: modal.open, 
        title: modal.mode==='create' ? 'Create Customer' : 'Edit Customer',
        onClose: ()=> setModal({ open:false, mode:'create', id:null }),
        primaryAction: React.createElement('button', { className:'w-full sm:w-auto px-3 py-2 rounded-lg bg-brand-500 text-white hover:bg-brand-600 text-sm', onClick: submitForm, disabled: loading }, loading ? 'Saving...' : (modal.mode==='create' ? 'Create' : 'Save'))
      },
        React.createElement('form', { onSubmit: submitForm, className:'grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4' },
          React.createElement('div', { className:'sm:col-span-2 flex flex-col items-center' },
            React.createElement('label', { className:'text-sm text-slate-600 dark:text-slate-300 mb-2' }, 'Profile Photo'),
            React.createElement('div', { className:'flex flex-col items-center gap-3' },
              React.createElement('div', { className:'w-24 h-24 sm:w-32 sm:h-32 rounded-full overflow-hidden border-4 border-brand-300 shadow-md bg-gray-100' },
                React.createElement('img', { 
                  src: form.photo || 'https://placehold.co/128x128?text=Photo', 
                  className:'w-full h-full object-cover',
                  alt: 'Profile photo preview'
                })
              ),
              React.createElement('label', { className:'cursor-pointer px-4 py-2 rounded-lg border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-slate-800 text-sm' },
                'Click to upload profile photo',
                React.createElement('input', { 
                  type: 'file', 
                  accept: 'image/*',
                  className: 'hidden',
                  onChange: handlePhotoUpload
                })
              )
            )
          ),
          React.createElement('div', { className:'sm:col-span-2' },
            React.createElement('label', { className:'text-sm text-slate-600 dark:text-slate-300 mb-1' }, 'Full Name'),
            React.createElement('input', { 
              type: 'text', 
              value: form.full_name||'', 
              onChange: e=> setForm({...form, full_name: e.target.value}), 
              className:'px-3 py-2 rounded-lg border border-slate-200 dark:border-white/10 bg-white/60 dark:bg-slate-800/60 w-full', 
              placeholder: 'Irfan' 
            })
          ),
          inputReadonly('National ID','text','national_id'),
          inputL('Date of Birth','date','dob'),
          React.createElement('div', { className:'flex flex-col' },
            React.createElement('label', { className:'text-sm text-slate-600 dark:text-slate-300 mb-1' }, 'Country'),
            React.createElement('input', { 
              type: 'text', 
              value: form.country||'', 
              onChange: e=> setForm({...form, country: e.target.value}), 
              className:'px-3 py-2 rounded-lg border border-slate-200 dark:border-white/10 bg-white/60 dark:bg-slate-800/60', 
              placeholder: 'South Sudan' 
            })
          ),
          inputReadonly('Issued Date','date','issued_on'),
          React.createElement('div', { className:'flex flex-col' },
            React.createElement('label', { className:'text-sm text-slate-600 dark:text-slate-300 mb-1' }, 'Passport No.'),
            React.createElement('input', { 
              type: 'text', 
              value: form.passport||'', 
              onChange: e=> setForm({...form, passport: e.target.value}), 
              className:'px-3 py-2 rounded-lg border border-slate-200 dark:border-white/10 bg-white/60 dark:bg-slate-800/60', 
              placeholder: '8765433' 
            })
          ),
          React.createElement('div', { className:'flex flex-col' },
            React.createElement('label', { className:'text-sm text-slate-600 dark:text-slate-300 mb-1' }, 'Job Title'),
            React.createElement('input', { 
              type: 'text', 
              value: form.job_title||'', 
              onChange: e=> setForm({...form, job_title: e.target.value}), 
              className:'px-3 py-2 rounded-lg border border-slate-200 dark:border-white/10 bg-white/60 dark:bg-slate-800/60', 
              placeholder: 'Student' 
            })
          ),
          React.createElement('div', { className:'flex flex-col' },
            React.createElement('label', { className:'text-sm text-slate-600 dark:text-slate-300 mb-1' }, 'Status'),
            React.createElement('select', { className:'px-3 py-2 rounded-lg border border-slate-200 dark:border-white/10 bg-white/60 dark:bg-slate-800/60', value:form.status, onChange:e=> setForm({...form, status:e.target.value}) },
              React.createElement('option',{value:'active'},'Active'),
              React.createElement('option',{value:'inactive'},'Inactive')
            )
          )
        )
      ),

      /* View Customer Modal */
      React.createElement(Modal, { 
        open: viewModal.open, 
        title: 'Customer Details',
        onClose: ()=> setViewModal({ open:false, data:null }),
        primaryAction: viewModal.data ? React.createElement('button', { className:'w-full sm:w-auto px-3 py-2 rounded-lg bg-brand-500 text-white hover:bg-brand-600 text-sm', onClick: ()=> printRecord(viewModal.data.id) }, 'Print ID') : null
      },
        viewModal.data ? React.createElement('div', { className:'space-y-6' },
          React.createElement('div', { className:'text-center' },
            React.createElement('img', { src:viewModal.data.photo, alt:'photo', className:'w-32 h-32 rounded-full mx-auto object-cover mb-4 ring-4 ring-brand-100 dark:ring-brand-900/20' }),
            React.createElement('h2', { className:'text-2xl font-bold text-slate-900 dark:text-white' }, viewModal.data.full_name),
            React.createElement('div', { className:'mt-2' },
              React.createElement('span',{className:`px-3 py-1 rounded-full text-sm font-semibold ${viewModal.data.status==='active'?'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300':'bg-rose-100 text-rose-700 dark:bg-rose-900/20 dark:text-rose-300'}`}, viewModal.data.status)
            )
          ),
          React.createElement('div', { className:'grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4' },
            React.createElement('div', { className:'bg-slate-50 dark:bg-slate-800/50 rounded-lg p-4' },
              React.createElement('label', { className:'text-sm font-semibold text-slate-600 dark:text-slate-300' }, 'National ID'),
              React.createElement('div', { className:'mt-1 font-mono text-lg' }, viewModal.data.national_id || 'N/A')
            ),
            React.createElement('div', { className:'bg-slate-50 dark:bg-slate-800/50 rounded-lg p-4' },
              React.createElement('label', { className:'text-sm font-semibold text-slate-600 dark:text-slate-300' }, 'Passport'),
              React.createElement('div', { className:'mt-1 font-mono text-lg' }, viewModal.data.passport || 'N/A')
            ),
            React.createElement('div', { className:'bg-slate-50 dark:bg-slate-800/50 rounded-lg p-4' },
              React.createElement('label', { className:'text-sm font-semibold text-slate-600 dark:text-slate-300' }, 'Country'),
              React.createElement('div', { className:'mt-1 text-lg' }, viewModal.data.country || 'N/A')
            ),
            React.createElement('div', { className:'bg-slate-50 dark:bg-slate-800/50 rounded-lg p-4' },
              React.createElement('label', { className:'text-sm font-semibold text-slate-600 dark:text-slate-300' }, 'Job Title'),
              React.createElement('div', { className:'mt-1 text-lg' }, viewModal.data.job_title || 'Student')
            ),
            React.createElement('div', { className:'bg-slate-50 dark:bg-slate-800/50 rounded-lg p-4' },
              React.createElement('label', { className:'text-sm font-semibold text-slate-600 dark:text-slate-300' }, 'Date of Birth'),
              React.createElement('div', { className:'mt-1 text-lg' }, viewModal.data.dob ? formatDateForDisplay(viewModal.data.dob) : 'N/A')
            ),
            React.createElement('div', { className:'bg-slate-50 dark:bg-slate-800/50 rounded-lg p-4' },
              React.createElement('label', { className:'text-sm font-semibold text-slate-600 dark:text-slate-300' }, 'Issued On'),
              React.createElement('div', { className:'mt-1 text-lg' }, viewModal.data.issued_on ? formatDateForDisplay(viewModal.data.issued_on) : 'N/A')
            ),
            React.createElement('div', { className:'bg-slate-50 dark:bg-slate-800/50 rounded-lg p-4 sm:col-span-2' },
              React.createElement('label', { className:'text-sm font-semibold text-slate-600 dark:text-slate-300' }, 'Created'),
              React.createElement('div', { className:'mt-1 text-lg' }, viewModal.data.created_at ? new Date(viewModal.data.created_at).toLocaleString() : 'N/A')
            )
          )
        ) : null
      ),

      /* Toast Notification */
      toast.show && React.createElement('div', { 
        className: `fixed bottom-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg border ${
          toast.type === 'success' 
            ? 'bg-emerald-50 border-emerald-200 text-emerald-800' 
            : 'bg-red-50 border-red-200 text-red-800'
        } max-w-sm`,
        style: { animation: 'slideIn 0.3s ease-out' }
      }, 
        React.createElement('div', { className: 'flex items-center gap-2' },
          React.createElement('span', { className: toast.type === 'success' ? '✅' : '❌' }),
          React.createElement('span', { className: 'font-medium' }, toast.message)
        )
      )
    )
  );

  /* UI helpers */
  function metricCard(label, value, hook, grad, textCls, IconCmp){
    return React.createElement('div', { className:`rounded-xl2 border border-slate-200/70 dark:border-white/10 p-4 bg-gradient-to-br ${grad}` },
      React.createElement('div', { className:'flex items-center justify-between' },
        React.createElement('div', { className:`text-sm ${textCls}` }, label),
        IconCmp({ className:'w-5 h-5 text-brand-500' })
      ),
      React.createElement('div', { className:'text-2xl font-bold mt-1' }, value),
      React.createElement('div', null, React.createElement('canvas',{ className:'spark', ref: (el)=> hook.canvasRef.current = el }))
    );
  }
  function inputFilter(placeholder,key){
    return React.createElement('input', { placeholder, className:'px-3 py-2 rounded-lg border border-slate-200 dark:border-white/10 bg-white/60 dark:bg-slate-800/60', value:filters[key], onChange:e=>{ setFilters({...filters, [key]:e.target.value}); setPage(1);} });
  }
  function inputL(label, type, key){
    return React.createElement('div', { className:'flex flex-col' },
      React.createElement('label', { className:'text-sm text-slate-600 dark:text-slate-300 mb-1' }, label),
      React.createElement('input', { type, value: form[key]||'', onChange:e=> setForm({...form, [key]: e.target.value}), className:'px-3 py-2 rounded-lg border border-slate-200 dark:border-white/10 bg-white/60 dark:bg-slate-800/60', placeholder: label })
    );
  }
  function inputReadonly(label, type, key){
    return React.createElement('div', { className:'flex flex-col' },
      React.createElement('label', { className:'text-sm text-slate-600 dark:text-slate-300 mb-1' }, label),
      React.createElement('input', { 
        type, 
        value: form[key]||'', 
        readOnly: true,
        className:'px-3 py-2 rounded-lg border border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold cursor-not-allowed', 
        placeholder: '' 
      })
    );
  }
}

/* Helper Functions */
// genTrackingID removed - now using job_title instead
const genNationalID = () => 'SSNYU-' + Math.random().toString(36).substring(2, 8).toUpperCase().replace(/[^0-9A-Z]/g, '').substring(0, 6).padEnd(6, '0');
const getCurrentDate = () => new Date().toISOString().split('T')[0];

/* Mount the React app */
document.addEventListener('DOMContentLoaded', function() {
  console.log('DOM loaded, initializing IDC Dashboard...');
  
  const rootElement = document.getElementById('idc-operator-root');
  console.log('Root element found:', !!rootElement);
  console.log('React available:', typeof React !== 'undefined');
  console.log('ReactDOM available:', typeof ReactDOM !== 'undefined');
  console.log('Chart available:', typeof Chart !== 'undefined');
  console.log('QRCode available:', typeof QRCode !== 'undefined');
  console.log('QRCode available:', typeof QRCode !== 'undefined');
  
  if (rootElement && typeof ReactDOM !== 'undefined' && typeof React !== 'undefined') {
    try {
      const root = ReactDOM.createRoot(rootElement);
      root.render(React.createElement(IDCAdminApp));
      console.log('React app mounted successfully');
    } catch (error) {
      console.error('Error mounting React app:', error);
      showFallbackContent(rootElement);
    }
  } else {
    console.error('Dependencies not loaded or root element not found');
    showFallbackContent(rootElement);
  }
  
  function showFallbackContent(element) {
    if (!element) return;
    
    element.innerHTML = `
      <div style="padding: 40px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        <h1 style="color: #1f2937; margin-bottom: 20px;">IDC Operator Dashboard</h1>
        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
          <h3>Dashboard Status:</h3>
          <p>• Root element: ${!!element ? '✓' : '✗'}</p>
          <p>• React: ${typeof React !== 'undefined' ? '✓' : '✗'}</p>
          <p>• ReactDOM: ${typeof ReactDOM !== 'undefined' ? '✓' : '✗'}</p>
          <p>• Chart.js: ${typeof Chart !== 'undefined' ? '✓' : '✗'}</p>
          <p>• QRCode.js: ${typeof QRCode !== 'undefined' ? '✓' : '✗'}</p>
          <p>• Initial data: ${JSON.stringify(INITIAL_DATA).length} chars</p>
        </div>
        <div style="background: #fef3c7; padding: 15px; border-radius: 8px; border-left: 4px solid #f59e0b;">
          <strong>Fallback Mode:</strong> The React dashboard could not load. Check browser console for errors.
        </div>
        <div style="margin-top: 20px;">
          <h3>Quick Stats:</h3>
          <p>Total Customers: ${INITIAL_STATS.total || 0}</p>
          <p>Active: ${INITIAL_STATS.active || 0}</p>
          <p>Inactive: ${INITIAL_STATS.inactive || 0}</p>
        </div>
        <div style="margin-top: 20px;">
          <button onclick="location.reload()" style="background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
            Reload Page
          </button>
        </div>
      </div>
    `;
  }
});
</script>

</div>