<?php
require_once '../config/config.php';
require_admin();

$db = Database::getInstance()->getConnection();
$event_id = (int)($_GET['event_id'] ?? 0);
if ($event_id <= 0) { header('Location: events.php'); exit; }

$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();
if (!$event) { header('Location: events.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scan_checkin'])) {
    header('Content-Type: application/json');
    $raw = trim($_POST['barcode'] ?? '');
    if ($raw === '') { echo json_encode(['status'=>'error','message'=>'Empty barcode.']); exit; }

    $stmt = $db->prepare("SELECT id,full_name,student_id,course,year_level,profile_image FROM users WHERE barcode_value=? AND is_active=1");
    $stmt->execute([$raw]);
    $user = $stmt->fetch();

    if (!$user) { echo json_encode(['status'=>'not_found','barcode'=>$raw]); exit; }

    $stmt = $db->prepare("SELECT id,attendance_status FROM event_attendance WHERE event_id=? AND user_id=?");
    $stmt->execute([$event_id,$user['id']]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ($existing['attendance_status'] === 'approved') { echo json_encode(['status'=>'already','student'=>$user]); exit; }
        $stmt = $db->prepare("UPDATE event_attendance SET attendance_status='approved',tokens_awarded=10,admin_notes='Checked in via barcode scanner',reviewed_at=NOW(),reviewed_by=? WHERE id=?");
        $stmt->execute([$_SESSION['user_id']??1,$existing['id']]);
    } else {
        $stmt = $db->prepare("INSERT INTO event_attendance (event_id,user_id,attendance_status,tokens_awarded,admin_notes,submitted_at,reviewed_at,reviewed_by) VALUES (?,?,'approved',10,'Checked in via barcode scanner',NOW(),NOW(),?)");
        $stmt->execute([$event_id,$user['id'],$_SESSION['user_id']??1]);
        $stmt = $db->prepare("UPDATE users SET eco_tokens=eco_tokens+10 WHERE id=?");
        $stmt->execute([$user['id']]);
        try {
            $stmt = $db->prepare("INSERT INTO transactions (user_id,transaction_type,amount,description,created_at) VALUES (?,'earned',10,?,NOW())");
            $stmt->execute([$user['id'],'Event attendance: '.$event['title']]);
        } catch (Exception $e) {}
    }
    echo json_encode(['status'=>'success','student'=>$user]);
    exit;
}

$page_title = 'Live Check-in';
include '../includes/admin_header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.19.1/umd/index.min.js"></script>

<style>
    .checkin-wrap{display:grid;grid-template-columns:1fr 380px;gap:1.5rem;max-width:1100px;margin:0 auto;padding:0 1.5rem 2rem;}
    @media(max-width:780px){.checkin-wrap{grid-template-columns:1fr;}}
    .cam-panel{background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.08);overflow:hidden;display:flex;flex-direction:column;}
    .cam-header{background:#1976d2;color:#fff;padding:1rem 1.25rem;}
    .cam-header h2{margin:0 0 .15rem;font-size:1.05rem;}
    .cam-header p{margin:0;font-size:.8rem;opacity:.85;}
    .cam-body{position:relative;background:#111;}
    #scanVideo{width:100%;display:block;aspect-ratio:4/3;object-fit:cover;}
    #scanLine{display:none;position:absolute;left:0;right:0;height:3px;background:linear-gradient(90deg,transparent,#22d3ee,transparent);animation:sweep 2.2s linear infinite;pointer-events:none;}
    @keyframes sweep{0%,100%{top:8%;}50%{top:88%;}}
    #camConfirmBar{height:6px;background:#1e40af;overflow:hidden;display:none;}
    #camConfirmFill{height:100%;background:#22d3ee;transition:width .12s ease;width:0%;}
    .cam-status{padding:.7rem 1rem;font-size:.875rem;color:#6b7280;text-align:center;border-top:1px solid #f0f0f0;min-height:2.6em;display:flex;align-items:center;justify-content:center;}
    .cam-status.scanning{color:#1976d2;font-weight:600;} .cam-status.ok{color:#16a34a;font-weight:600;} .cam-status.warn{color:#d97706;font-weight:600;} .cam-status.err{color:#dc3545;font-weight:600;} .cam-status.confirming{color:#f59e0b;font-weight:600;}
    .side-panel{display:flex;flex-direction:column;gap:1rem;}
    .info-card{background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.08);padding:1.25rem;}
    .info-card h3{margin:0 0 .4rem;font-size:.95rem;color:#1976d2;} .info-card p{margin:0 0 .2rem;font-size:.85rem;color:#374151;}
    .stat-row{display:flex;gap:.75rem;margin-top:.8rem;}
    .stat-box{flex:1;background:#f0f9ff;border-radius:8px;padding:.6rem;text-align:center;}
    .stat-box .num{font-size:1.6rem;font-weight:700;color:#1976d2;} .stat-box .num.orange{color:#ea580c;} .stat-box .lbl{font-size:.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;}
    .result-card{background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.08);padding:1.25rem;border:2px solid transparent;transition:border-color .2s;}
    .result-card h3{margin:0 0 .75rem;font-size:.8rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;}
    .result-card.ok{border-color:#16a34a;} .result-card.already{border-color:#f59e0b;} .result-card.notfound{border-color:#dc3545;}
    .student-row{display:flex;align-items:center;gap:.75rem;margin-bottom:.6rem;}
    .s-avatar{width:52px;height:52px;border-radius:50%;flex-shrink:0;overflow:hidden;background:#e0e7ff;display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:700;color:#4f46e5;}
    .s-avatar img{width:100%;height:100%;object-fit:cover;} .s-name{font-size:1rem;font-weight:700;color:#1f2937;} .s-sub{font-size:.8rem;color:#6b7280;}
    .badge-result{display:inline-flex;align-items:center;gap:.3rem;padding:.3rem .8rem;border-radius:20px;font-size:.8rem;font-weight:700;}
    .badge-result.ok{background:#dcfce7;color:#166534;} .badge-result.already{background:#fef3c7;color:#92400e;} .badge-result.notfound{background:#fee2e2;color:#991b1b;}
    .log-card{background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.08);padding:1.25rem;flex:1;}
    .log-card h3{margin:0 0 .75rem;font-size:.8rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;}
    .log-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.35rem;max-height:240px;overflow-y:auto;}
    .log-item{display:flex;align-items:center;gap:.5rem;font-size:.8rem;padding:.35rem .5rem;border-radius:6px;background:#f9fafb;}
    .dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
    .dot.ok{background:#16a34a;} .dot.already{background:#f59e0b;} .dot.notfound{background:#dc3545;}
    .log-time{margin-left:auto;color:#9ca3af;font-size:.73rem;white-space:nowrap;}
    .btn-ctrl{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem 1.1rem;border-radius:8px;font-size:.875rem;font-weight:600;cursor:pointer;border:none;transition:opacity .15s;text-decoration:none;}
    .btn-ctrl:hover{opacity:.85;} .btn-start{background:#16a34a;color:#fff;} .btn-stop{background:#dc3545;color:#fff;display:none;} .btn-back{background:#f3f4f6;color:#374151;}
</style>

<div style="padding:1.25rem 1.5rem .75rem;">
    <div class="admin-page-header" style="margin-bottom:.75rem;">
        <h1 class="admin-page-title">🔍 Live Check-in Scanner</h1>
        <p class="admin-page-subtitle"><?php echo htmlspecialchars($event['title']); ?> &nbsp;·&nbsp; <?php echo date('M j, Y g:i A',strtotime($event['event_date'])); ?></p>
    </div>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
        <button class="btn-ctrl btn-start" id="startBtn" onclick="startScanner()">▶ Start Scanner</button>
        <button class="btn-ctrl btn-stop"  id="stopBtn"  onclick="stopScanner()">■ Stop Scanner</button>
        <a href="events.php" class="btn-ctrl btn-back">← Back to Events</a>
    </div>
</div>

<div class="checkin-wrap">
    <div class="cam-panel">
        <div class="cam-header">
            <h2>ID Barcode Camera</h2>
            <p>Code 128 / Code 39 · same value reads 3× to confirm · 3-second cooldown between students</p>
        </div>
        <div class="cam-body">
            <video id="scanVideo" playsinline muted></video>
            <div id="scanLine"></div>
        </div>
        <div id="camConfirmBar"><div id="camConfirmFill"></div></div>
        <div class="cam-status" id="camStatus">Scanner idle — click <strong>&nbsp;▶ Start Scanner&nbsp;</strong> to begin.</div>
    </div>

    <div class="side-panel">
        <div class="info-card">
            <h3><?php echo htmlspecialchars($event['title']); ?></h3>
            <p>📅 <?php echo date('M j, Y g:i A',strtotime($event['event_date'])); ?></p>
            <p>📍 <?php echo htmlspecialchars($event['location']?:'N/A'); ?></p>
            <div class="stat-row">
                <div class="stat-box"><div class="num" id="statTotal">–</div><div class="lbl">Total Present</div></div>
                <div class="stat-box" style="background:#fff7ed;"><div class="num orange" id="statSession">0</div><div class="lbl">This Session</div></div>
            </div>
        </div>

        <div class="result-card" id="resultCard">
            <h3>Last Scan Result</h3>
            <div id="resultBody" style="color:#9ca3af;font-size:.875rem;text-align:center;padding:.5rem 0;">Waiting for first scan…</div>
        </div>

        <div class="log-card">
            <h3>Session Log</h3>
            <ul class="log-list" id="logList"><li style="color:#9ca3af;font-size:.8rem;padding:.3rem;">No scans yet.</li></ul>
        </div>
    </div>
</div>

<script>
(function () {
    const CONFIRM_NEEDED = 3;
    const COOLDOWN_MS    = 3000;   // ms before same barcode can fire again
    const EVENT_ID       = <?php echo $event_id; ?>;
    const CHECKIN_URL    = '?event_id=' + EVENT_ID;

    let reader=null, scanning=false;
    let lastValue=null, lastFormat=null, confirmCount=0;
    let cooldown=false, sessionCount=0;

    function setStatus(msg,cls){const el=document.getElementById('camStatus');el.innerHTML=msg;el.className='cam-status '+(cls||'');}
    function setProgress(n){document.getElementById('camConfirmBar').style.display=n>0?'':'none';document.getElementById('camConfirmFill').style.width=(n/CONFIRM_NEEDED*100)+'%';}
    function resetConfirm(){lastValue=null;lastFormat=null;confirmCount=0;setProgress(0);}

    async function getRearCameraId(){try{const p=await navigator.mediaDevices.getUserMedia({video:true});p.getTracks().forEach(t=>t.stop());const all=await navigator.mediaDevices.enumerateDevices();const cams=all.filter(d=>d.kind==='videoinput');if(cams.length<=1)return undefined;const r=cams.find(d=>/back|rear|environment/i.test(d.label));return r?r.deviceId:undefined;}catch(_){return undefined;}}

    window.startScanner=async function(){
        if(typeof ZXing==='undefined'){setStatus('Scanner library not loaded — please refresh.','err');return;}
        document.getElementById('startBtn').style.display='none';
        document.getElementById('stopBtn').style.display='';
        document.getElementById('scanLine').style.display='';
        resetConfirm();
        setStatus('Starting camera…','scanning');
        try{
            // Lock to CODE_128 and CODE_39 only — eliminates cross-format misreads
            const hints=new Map();
            hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS,[
                ZXing.BarcodeFormat.CODE_39,
            ]);
            hints.set(ZXing.DecodeHintType.TRY_HARDER,true);

            const deviceId=await getRearCameraId();
            reader=new ZXing.BrowserMultiFormatReader(hints);
            scanning=true;
            setStatus('🟢 Ready — hold ID barcode up to camera…','scanning');
            await reader.decodeFromVideoDevice(deviceId,'scanVideo',(result)=>{
                if(!scanning||cooldown)return;
                if(result)handleRead(result.getText(),result.getBarcodeFormat());
            });
        }catch(err){
            if(err.name==='NotAllowedError')setStatus('Camera access denied. Allow camera access then try again.','err');
            else if(err.name==='NotFoundError')setStatus('No camera found.','err');
            else setStatus('Camera error: '+err.message,'err');
        }
    };

    window.stopScanner=function(){
        scanning=false;
        if(reader){try{reader.reset();}catch(_){}reader=null;}
        const v=document.getElementById('scanVideo');
        if(v.srcObject){v.srcObject.getTracks().forEach(t=>t.stop());v.srcObject=null;}
        document.getElementById('scanLine').style.display='none';
        document.getElementById('startBtn').style.display='';
        document.getElementById('stopBtn').style.display='none';
        resetConfirm();
        setStatus('Scanner stopped.','');
    };

    function handleRead(raw,format){
        const value=(raw||'').trim();
        if(!value)return;
        if(value===lastValue){confirmCount++;}
        else{lastValue=value;lastFormat=format;confirmCount=1;}
        setProgress(confirmCount);
        if(confirmCount<CONFIRM_NEEDED){setStatus(`Confirming… (${confirmCount}/${CONFIRM_NEEDED})`,'confirming');return;}
        // Confirmed
        const confirmed=lastValue;
        resetConfirm();
        cooldown=true;
        setTimeout(()=>{cooldown=false;if(scanning)setStatus('🟢 Ready — hold ID barcode up to camera…','scanning');},COOLDOWN_MS);
        submitCheckin(confirmed);
    }

    async function submitCheckin(barcode){
        setStatus('⏳ Processing…','scanning');
        try{
            const fd=new FormData();
            fd.append('scan_checkin','1');
            fd.append('barcode',barcode);
            const res=await fetch(CHECKIN_URL,{method:'POST',body:fd});
            const data=await res.json();
            renderResult(data);addLog(data);loadStats();
        }catch(e){setStatus('Network error — check connection.','err');}
    }

    function renderResult(data){
        const card=document.getElementById('resultCard');
        const body=document.getElementById('resultBody');
        card.className='result-card';
        if(data.status==='success'){
            card.classList.add('ok');sessionCount++;
            document.getElementById('statSession').textContent=sessionCount;
            setStatus('✅ Checked in: '+data.student.full_name,'ok');
            body.innerHTML=buildStudentHTML(data.student,'ok','✅ Checked In &nbsp;+10 tokens');
        }else if(data.status==='already'){
            card.classList.add('already');
            setStatus('⚠ Already present: '+data.student.full_name,'warn');
            body.innerHTML=buildStudentHTML(data.student,'already','⚠ Already Present');
        }else{
            card.classList.add('notfound');
            const bc=data.barcode||'—';
            setStatus('❌ Barcode not registered','err');
            body.innerHTML=`<div style="text-align:center;padding:.5rem 0;"><div style="font-size:2.2rem;">🚫</div><div style="font-weight:700;color:#dc3545;margin:.3rem 0;">Not Found</div><div style="font-size:.8rem;color:#6b7280;">Barcode <code style="background:#f3f4f6;padding:1px 5px;border-radius:4px;">${esc(bc)}</code> is not linked to any registered member.</div></div>`;
        }
    }

    function buildStudentHTML(s,type,label){
        const init=(s.full_name||'?').charAt(0).toUpperCase();
        const avatar=s.profile_image?`<img src="${esc(s.profile_image)}" alt="">`:init;
        return `<div class="student-row"><div class="s-avatar">${avatar}</div><div><div class="s-name">${esc(s.full_name)}</div><div class="s-sub">${esc(s.student_id)}</div><div class="s-sub">${esc((s.course||'')+(s.year_level?' · '+s.year_level:''))}</div></div></div><span class="badge-result ${type}">${label}</span>`;
    }

    function addLog(data){
        const list=document.getElementById('logList');
        const ph=list.querySelector('li[style]');if(ph)ph.remove();
        const now=new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit',second:'2-digit'});
        const li=document.createElement('li');li.className='log-item';
        let dot='notfound',name='Unknown barcode';
        if(data.status==='success'){dot='ok';name=data.student.full_name;}
        if(data.status==='already'){dot='already';name=data.student.full_name+' (already present)';}
        li.innerHTML=`<span class="dot ${dot}"></span><span>${esc(name)}</span><span class="log-time">${now}</span>`;
        list.prepend(li);
    }

    async function loadStats(){try{const res=await fetch('checkin_stats.php?event_id='+EVENT_ID);const data=await res.json();document.getElementById('statTotal').textContent=data.approved??'–';}catch(_){}}

    function esc(str){return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

    loadStats();
})();
</script>

<?php include '../includes/admin_footer.php'; ?>