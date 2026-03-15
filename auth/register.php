<?php
require_once '../config/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $student_id       = sanitize_input($_POST['student_id'] ?? '');
        $barcode_value    = sanitize_input($_POST['barcode_value'] ?? '');
        $email            = sanitize_input($_POST['email'] ?? '');
        $password         = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $full_name        = sanitize_input($_POST['full_name'] ?? '');
        $course           = sanitize_input($_POST['course'] ?? '');
        $year_level       = sanitize_input($_POST['year_level'] ?? '');

        if (empty($student_id)||empty($email)||empty($password)||empty($full_name)) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            try {
                $db = Database::getInstance()->getConnection();
                $check = $db->prepare("SELECT id FROM users WHERE student_id = ? OR email = ?");
                $check->execute([$student_id, $email]);
                if ($check->fetch()) {
                    $error = 'Student ID or email already registered.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $ins  = $db->prepare("INSERT INTO users (student_id,barcode_value,email,password_hash,full_name,course,year_level,eco_tokens) VALUES (?,?,?,?,?,?,?,0)");
                    if ($ins->execute([$student_id,$barcode_value?:null,$email,$hash,$full_name,$course,$year_level])) {
                        $success    = 'Registration successful! You can now login.';
                        $student_id = $barcode_value = $email = $full_name = $course = $year_level = '';
                    } else { $error = 'Registration failed. Please try again.'; }
                }
            } catch (PDOException $e) {
                $error = 'Database error. Please ensure the database tables are properly installed.';
            }
        }
    }
}

$page_title = 'Register';
include '../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.19.1/umd/index.min.js"></script>

<style>
    #scannerModal{display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.78);align-items:center;justify-content:center;}
    #scannerModal.active{display:flex;}
    #scannerBox{background:#fff;border-radius:16px;padding:1.5rem;width:min(460px,95vw);box-shadow:0 24px 60px rgba(0,0,0,.45);display:flex;flex-direction:column;gap:1rem;}
    #scannerBox h3{margin:0;font-size:1.1rem;color:#1f2937;display:flex;align-items:center;gap:.5rem;}
    #scannerVideo{width:100%;border-radius:10px;background:#111;aspect-ratio:4/3;object-fit:cover;display:block;}
    #scannerStatus{font-size:.85rem;color:#6b7280;text-align:center;min-height:1.3em;}
    #scannerStatus.ok{color:#16a34a;font-weight:600;}
    #scannerStatus.err{color:#dc3545;}
    #scannerStatus.confirming{color:#f59e0b;font-weight:600;}
    #scanLineWrapper{position:relative;border-radius:10px;overflow:hidden;}
    #scanLine{position:absolute;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,#3b82f6,transparent);animation:sweep 2s linear infinite;}
    @keyframes sweep{0%,100%{top:8%;}50%{top:88%;}}
    #confirmBar{height:5px;background:#e5e7eb;border-radius:3px;overflow:hidden;display:none;}
    #confirmFill{height:100%;background:#f59e0b;border-radius:3px;transition:width .12s ease;width:0%;}
    .scan-btn{display:inline-flex;align-items:center;gap:.4rem;background:#1976d2;color:#fff;border:none;border-radius:8px;padding:.5rem 1rem;font-size:.875rem;font-weight:600;cursor:pointer;transition:background .15s;white-space:nowrap;}
    .scan-btn:hover{background:#1565c0;}.scan-btn.scanned{background:#16a34a;}
    .modal-close-btn{background:#f3f4f6;color:#374151;border:none;border-radius:8px;padding:.5rem 1rem;font-size:.875rem;font-weight:600;cursor:pointer;}
    .modal-close-btn:hover{background:#e5e7eb;}
    .barcode-pill{display:none;align-items:center;gap:.5rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:.45rem .75rem;font-size:.82rem;color:#166534;font-weight:600;margin-top:.4rem;}
    .barcode-pill.show{display:flex;}
    .barcode-pill code{font-family:monospace;word-break:break-all;}
    .barcode-pill-clear{margin-left:auto;background:none;border:none;color:#6b7280;cursor:pointer;font-size:1rem;padding:0 2px;line-height:1;}
    .barcode-pill-clear:hover{color:#dc3545;}
</style>

<section class="section">
    <div class="container">
        <div style="max-width:600px;margin:0 auto;">
            <div class="card">
                <div class="card-header" style="text-align:center;">
                    <h1 class="card-title">Create Your Account</h1>
                    <p style="color:var(--medium-gray);">Join Bin It to Win It and start earning Eco-Tokens!</p>
                </div>
                <div class="card-body">
                    <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?><br><br>
                            <a href="<?php echo SITE_URL; ?>/auth/login.php" class="btn btn-primary">Go to Login</a>
                        </div>
                    <?php else: ?>

                    <form method="POST" action="" data-validate>
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                        <div class="form-group">
                            <label for="student_id" class="form-label">Student ID *</label>
                            <input type="text" id="student_id" name="student_id" class="form-input"
                                   value="<?php echo isset($student_id)?htmlspecialchars($student_id):''; ?>"
                                   placeholder="e.g. TUPT-24-0428" required autofocus>
                            <small style="color:var(--medium-gray);font-size:.875rem;">Type your Student ID exactly as printed on your ID card.</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                ID Card Barcode
                                <span style="font-weight:normal;color:var(--medium-gray);font-size:.82rem;">— optional, can be added later</span>
                            </label>
                            <input type="hidden" id="barcode_value" name="barcode_value"
                                   value="<?php echo isset($barcode_value)?htmlspecialchars($barcode_value):''; ?>">
                            <button type="button" class="scan-btn" id="scanBtn" onclick="openScanner()">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="7" width="3" height="10"/><rect x="7" y="7" width="1" height="10"/><rect x="9" y="7" width="2" height="10"/><rect x="12" y="7" width="1" height="10"/><rect x="14" y="7" width="3" height="10"/><rect x="18" y="7" width="1" height="10"/></svg>
                                <span id="scanBtnLabel">Scan ID Barcode</span>
                            </button>
                            <div class="barcode-pill" id="barcodePill">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                Captured:&nbsp;<code id="barcodeDisplay"></code>&nbsp;<span id="barcodeFormat" style="font-size:.75rem;color:#6b7280;font-weight:normal;"></span>
                                <button type="button" class="barcode-pill-clear" onclick="clearBarcode()" title="Remove">✕</button>
                            </div>
                            <small style="color:var(--medium-gray);font-size:.875rem;display:block;margin-top:.4rem;">
                                Scans Code 128 / Code 39 only (school ID formats). Same value must read 3× to confirm.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" id="email" name="email" class="form-input" value="<?php echo isset($email)?htmlspecialchars($email):''; ?>" placeholder="Enter your email" required>
                        </div>
                        <div class="form-group">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" class="form-input" value="<?php echo isset($full_name)?htmlspecialchars($full_name):''; ?>" placeholder="Enter your full name" required>
                        </div>
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label for="course" class="form-label">Course</label>
                                <input type="text" id="course" name="course" class="form-input" value="<?php echo isset($course)?htmlspecialchars($course):''; ?>" placeholder="e.g., Computer Science">
                            </div>
                            <div class="form-group">
                                <label for="year_level" class="form-label">Year Level</label>
                                <select id="year_level" name="year_level" class="form-select">
                                    <option value="">Select year level</option>
                                    <option value="1st Year"  <?php echo (isset($year_level)&&$year_level==='1st Year') ?'selected':'';?>>1st Year</option>
                                    <option value="2nd Year"  <?php echo (isset($year_level)&&$year_level==='2nd Year') ?'selected':'';?>>2nd Year</option>
                                    <option value="3rd Year"  <?php echo (isset($year_level)&&$year_level==='3rd Year') ?'selected':'';?>>3rd Year</option>
                                    <option value="4th Year"  <?php echo (isset($year_level)&&$year_level==='4th Year') ?'selected':'';?>>4th Year</option>
                                    <option value="Graduate"  <?php echo (isset($year_level)&&$year_level==='Graduate') ?'selected':'';?>>Graduate</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" id="password" name="password" class="form-input" placeholder="Minimum 8 characters" required>
                            <small style="color:var(--medium-gray);font-size:.875rem;">Must be at least 8 characters long.</small>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Re-enter your password" required>
                        </div>
                        <div class="form-group" style="text-align:center;margin-top:2rem;">
                            <button type="submit" name="register" class="btn btn-primary" style="width:100%;">Create Account</button>
                        </div>
                    </form>
                    <?php endif; ?>

                    <div style="text-align:center;margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--azure-blue);">
                        <p style="color:var(--medium-gray);">Already have an account? <a href="<?php echo SITE_URL; ?>/auth/login.php" style="color:var(--light-blue);text-decoration:none;">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div id="scannerModal" role="dialog" aria-modal="true">
    <div id="scannerBox">
        <h3>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1976d2" stroke-width="2"><rect x="3" y="7" width="3" height="10"/><rect x="7" y="7" width="1" height="10"/><rect x="9" y="7" width="2" height="10"/><rect x="12" y="7" width="1" height="10"/><rect x="14" y="7" width="3" height="10"/></svg>
            Scan ID Card Barcode
        </h3>
        <p style="margin:0;font-size:.85rem;color:#6b7280;">Hold the barcode steady. Reads <strong>Code 128 / Code 39 only</strong> — same value must appear 3× to confirm.</p>
        <div id="scanLineWrapper">
            <video id="scannerVideo" playsinline muted></video>
            <div id="scanLine"></div>
        </div>
        <div id="confirmBar"><div id="confirmFill"></div></div>
        <div id="scannerStatus">Starting…</div>
        <div style="display:flex;gap:.75rem;justify-content:flex-end;">
            <button type="button" class="modal-close-btn" onclick="closeScanner()">Cancel</button>
        </div>
    </div>
</div>

<script>
(function () {
    const CONFIRM_NEEDED = 3;
    let reader=null,scanning=false,lastValue=null,lastFormat=null,confirmCount=0;

    function setStatus(msg,cls){const el=document.getElementById('scannerStatus');el.innerHTML=msg;el.className=cls||'';}
    function setProgress(n){document.getElementById('confirmBar').style.display=n>0?'':'none';document.getElementById('confirmFill').style.width=(n/CONFIRM_NEEDED*100)+'%';}
    function resetConfirm(){lastValue=null;lastFormat=null;confirmCount=0;setProgress(0);}

    window.openScanner=function(){document.getElementById('scannerModal').classList.add('active');document.body.style.overflow='hidden';resetConfirm();startScan();};
    window.closeScanner=function(){stopScan();document.getElementById('scannerModal').classList.remove('active');document.body.style.overflow='';setProgress(0);};
    window.clearBarcode=function(){
        document.getElementById('barcode_value').value='';
        document.getElementById('barcodeDisplay').textContent='';
        document.getElementById('barcodeFormat').textContent='';
        document.getElementById('barcodePill').classList.remove('show');
        document.getElementById('scanBtn').classList.remove('scanned');
        document.getElementById('scanBtnLabel').textContent='Scan ID Barcode';
    };

    function stopScan(){scanning=false;if(reader){try{reader.reset();}catch(_){}reader=null;}const v=document.getElementById('scannerVideo');if(v&&v.srcObject){v.srcObject.getTracks().forEach(t=>t.stop());v.srcObject=null;}}

    async function getRearCameraId(){try{const prime=await navigator.mediaDevices.getUserMedia({video:true});prime.getTracks().forEach(t=>t.stop());const all=await navigator.mediaDevices.enumerateDevices();const cams=all.filter(d=>d.kind==='videoinput');if(cams.length<=1)return undefined;const rear=cams.find(d=>/back|rear|environment/i.test(d.label));return rear?rear.deviceId:undefined;}catch(_){return undefined;}}

    async function startScan(){
        stopScan();
        setStatus('Starting camera…');
        if(typeof ZXing==='undefined'){setStatus('Scanner library not loaded. Refresh the page.','err');return;}
        try{
            // Lock to CODE_128 and CODE_39 — prevents cross-format misreads
            const hints=new Map();
            hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS,[
                ZXing.BarcodeFormat.CODE_39,
            ]);
            hints.set(ZXing.DecodeHintType.TRY_HARDER,true);

            const deviceId=await getRearCameraId();
            reader=new ZXing.BrowserMultiFormatReader(hints);
            scanning=true;
            setStatus('Hold the ID barcode in front of the camera…');
            await reader.decodeFromVideoDevice(deviceId,'scannerVideo',(result)=>{
                if(!scanning)return;
                if(result)handleRead(result.getText(),result.getBarcodeFormat());
            });
        }catch(err){
            if(err.name==='NotAllowedError')setStatus('Camera access denied.','err');
            else if(err.name==='NotFoundError')setStatus('No camera found.','err');
            else setStatus('Camera error: '+err.message,'err');
        }
    }

    function handleRead(raw,format){
        const value=(raw||'').trim();
        if(!value)return;
        if(value===lastValue){confirmCount++;}else{lastValue=value;lastFormat=format;confirmCount=1;}
        setProgress(confirmCount);
        if(confirmCount<CONFIRM_NEEDED){setStatus(`Confirming… (${confirmCount}/${CONFIRM_NEEDED} consistent reads)`,'confirming');return;}
        capture(value,lastFormat);
    }

    function capture(value,format){
        scanning=false;
        const fmtName=format!==undefined?String(format):'';
        document.getElementById('barcode_value').value=value;
        document.getElementById('barcodeDisplay').textContent=value;
        document.getElementById('barcodeFormat').textContent=fmtName?'('+fmtName+')':'';
        document.getElementById('barcodePill').classList.add('show');
        document.getElementById('scanBtn').classList.add('scanned');
        document.getElementById('scanBtnLabel').textContent='Re-scan';
        setStatus('✓ Barcode confirmed!','ok');
        setProgress(CONFIRM_NEEDED);
        setTimeout(closeScanner,900);
    }

    document.getElementById('scannerModal').addEventListener('click',e=>{if(e.target===e.currentTarget)closeScanner();});
    document.addEventListener('keydown',e=>{if(e.key==='Escape')closeScanner();});
})();
</script>

<?php include '../includes/footer.php'; ?>