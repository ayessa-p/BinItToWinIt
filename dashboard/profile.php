<?php
require_once '../config/config.php';
require_login();

$page_title = 'My Profile';
$user_id    = get_user_id();
$db         = Database::getInstance()->getConnection();

$message      = '';
$message_type = '';

if (isset($_GET['updated']) && $_GET['updated'] == '1') {
    $message      = 'Profile updated successfully!';
    $message_type = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token. Please try again.'; $message_type = 'error';
    } else {
        $full_name     = sanitize_input($_POST['full_name']     ?? '');
        $email         = sanitize_input($_POST['email']         ?? '');
        $course        = sanitize_input($_POST['course']        ?? '');
        $year_level    = sanitize_input($_POST['year_level']    ?? '');
        $barcode_value = sanitize_input($_POST['barcode_value'] ?? '');

        if (empty($full_name) || empty($email)) {
            $message = 'Full name and email are required.'; $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.'; $message_type = 'error';
        } else {
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $message = 'Email address is already in use by another account.'; $message_type = 'error';
            } else {
                $profile_image_path = null;
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $avatar_dir = __DIR__ . '/../uploads/avatars/';
                    if (!is_dir($avatar_dir)) mkdir($avatar_dir, 0755, true);
                    $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg','jpeg','png'], true)) {
                        $safe_name = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $avatar_dir . $safe_name))
                            $profile_image_path = SITE_URL . '/uploads/avatars/' . $safe_name;
                    }
                }
                if ($profile_image_path !== null) {
                    $stmt   = $db->prepare("UPDATE users SET full_name=?,email=?,course=?,year_level=?,barcode_value=?,profile_image=? WHERE id=?");
                    $params = [$full_name,$email,$course,$year_level,$barcode_value?:null,$profile_image_path,$user_id];
                } else {
                    $stmt   = $db->prepare("UPDATE users SET full_name=?,email=?,course=?,year_level=?,barcode_value=? WHERE id=?");
                    $params = [$full_name,$email,$course,$year_level,$barcode_value?:null,$user_id];
                }
                if ($stmt->execute($params)) {
                    $_SESSION['full_name'] = $full_name;
                    if ($profile_image_path !== null) $_SESSION['profile_image'] = $profile_image_path;
                    header('Location: profile.php?updated=1'); exit();
                } else {
                    $message = 'Failed to update profile. Please try again.'; $message_type = 'error';
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.'; $message_type = 'error';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password     = $_POST['new_password']     ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        if (empty($current_password)||empty($new_password)||empty($confirm_password)) {
            $message = 'Please fill in all password fields.'; $message_type = 'error';
        } elseif (strlen($new_password) < 8) {
            $message = 'New password must be at least 8 characters long.'; $message_type = 'error';
        } elseif ($new_password !== $confirm_password) {
            $message = 'New passwords do not match.'; $message_type = 'error';
        } else {
            $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user_id]); $row = $stmt->fetch();
            if ($row && password_verify($current_password, $row['password_hash'])) {
                $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                if ($stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $user_id])) {
                    $message = 'Password changed successfully!'; $message_type = 'success';
                } else { $message = 'Failed to change password.'; $message_type = 'error'; }
            } else { $message = 'Current password is incorrect.'; $message_type = 'error'; }
        }
    }
}

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

include '../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.19.1/umd/index.min.js"></script>

<style>
    #scannerModal{display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.78);align-items:center;justify-content:center;}
    #scannerModal.active{display:flex;}
    #scannerBox{background:#fff;border-radius:16px;padding:1.5rem;width:min(480px,95vw);box-shadow:0 24px 60px rgba(0,0,0,.45);display:flex;flex-direction:column;gap:1rem;max-height:92vh;overflow-y:auto;}
    #scannerBox h3{margin:0;font-size:1.1rem;color:#1f2937;display:flex;align-items:center;gap:.5rem;}
    .scan-tabs{display:flex;gap:0;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;}
    .scan-tab{flex:1;padding:.5rem .75rem;font-size:.85rem;font-weight:600;cursor:pointer;background:#f9fafb;color:#6b7280;border:none;transition:background .15s,color .15s;}
    .scan-tab.active{background:#1976d2;color:#fff;}
    .scan-tab-panel{display:none;} .scan-tab-panel.active{display:block;}
    #scannerVideo{width:100%;border-radius:10px;background:#111;aspect-ratio:4/3;object-fit:cover;display:block;}
    #scanLineWrapper{position:relative;border-radius:10px;overflow:hidden;}
    #scanLine{position:absolute;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,#3b82f6,transparent);animation:sweep 2s linear infinite;}
    @keyframes sweep{0%,100%{top:8%;}50%{top:88%;}}
    .img-drop-zone{border:2px dashed #d1d5db;border-radius:10px;padding:1.5rem;text-align:center;cursor:pointer;transition:border-color .2s,background .2s;background:#fafafa;}
    .img-drop-zone:hover,.img-drop-zone.drag-over{border-color:#1976d2;background:#eff6ff;}
    .img-drop-zone input[type=file]{display:none;}
    .img-drop-zone .drop-icon{font-size:2rem;margin-bottom:.4rem;}
    .img-drop-zone p{margin:0;font-size:.875rem;color:#6b7280;}
    .img-drop-zone strong{color:#1976d2;}
    #imgPreviewWrap{display:none;position:relative;}
    #imgPreview{width:100%;border-radius:8px;display:block;max-height:280px;object-fit:contain;background:#111;}
    #imgScanOverlay{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.45);border-radius:8px;font-size:.9rem;font-weight:600;color:#fff;}
    #scannerStatus{font-size:.85rem;color:#6b7280;text-align:center;min-height:1.3em;}
    #scannerStatus.ok{color:#16a34a;font-weight:600;} #scannerStatus.err{color:#dc3545;}
    .scan-btn{display:inline-flex;align-items:center;gap:.4rem;background:#1976d2;color:#fff;border:none;border-radius:8px;padding:.5rem 1rem;font-size:.875rem;font-weight:600;cursor:pointer;transition:background .15s;white-space:nowrap;}
    .scan-btn:hover{background:#1565c0;} .scan-btn.scanned{background:#16a34a;}
    .modal-close-btn{background:#f3f4f6;color:#374151;border:none;border-radius:8px;padding:.5rem 1rem;font-size:.875rem;font-weight:600;cursor:pointer;}
    .modal-close-btn:hover{background:#e5e7eb;}
    .barcode-pill{display:none;align-items:center;gap:.5rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:.45rem .75rem;font-size:.82rem;color:#166534;font-weight:600;margin-top:.4rem;}
    .barcode-pill.show{display:flex;} .barcode-pill code{font-family:monospace;word-break:break-all;}
    .barcode-pill-clear{margin-left:auto;background:none;border:none;color:#6b7280;cursor:pointer;font-size:1rem;padding:0 2px;line-height:1;}
    .barcode-pill-clear:hover{color:#dc3545;}
</style>

<section class="section">
    <div class="container">
        <h1 class="section-title">My Profile</h1>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" style="max-width:800px;margin:0 auto 2rem;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-2" style="max-width:1000px;margin:0 auto;gap:2rem;">

            <div class="card">
                <div class="card-header"><h2 class="card-title">Profile Information</h2></div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data" data-validate>
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                        <div class="form-group">
                            <label class="form-label">Profile Picture</label>
                            <div style="display:flex;align-items:center;gap:1rem;">
                                <?php $avatar = $user['profile_image'] ?? null; ?>
                                <div style="width:64px;height:64px;border-radius:50%;overflow:hidden;background:#e0e7ff;display:flex;align-items:center;justify-content:center;font-weight:600;color:#4f46e5;flex-shrink:0;">
                                    <?php if ($avatar): ?><img src="<?php echo htmlspecialchars($avatar); ?>" alt="" style="width:100%;height:100%;object-fit:cover;"><?php else: ?><span><?php echo strtoupper(substr($user['full_name'],0,1)); ?></span><?php endif; ?>
                                </div>
                                <input type="file" name="profile_image" accept=".jpg,.jpeg,.png">
                            </div>
                            <small style="color:var(--medium-gray);font-size:.875rem;">Optional. JPG or PNG, up to 5MB.</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Student ID</label>
                            <input type="text" class="form-input" value="<?php echo htmlspecialchars($user['student_id']); ?>" disabled>
                            <small style="color:var(--medium-gray);font-size:.875rem;">Student ID cannot be changed.</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                ID Card Barcode
                                <?php if (!empty($user['barcode_value'])): ?>
                                    <span style="background:#dcfce7;color:#166534;font-size:.75rem;font-weight:600;padding:2px 8px;border-radius:10px;margin-left:.4rem;">✓ Registered</span>
                                <?php else: ?>
                                    <span style="background:#f3f4f6;color:#6b7280;font-size:.75rem;font-weight:600;padding:2px 8px;border-radius:10px;margin-left:.4rem;">Not set</span>
                                <?php endif; ?>
                            </label>

                            <input type="hidden" id="barcode_value" name="barcode_value"
                                   value="<?php echo htmlspecialchars($user['barcode_value'] ?? ''); ?>">

                            <button type="button" class="scan-btn" id="scanBtn" onclick="openScanner()">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="7" width="3" height="10"/><rect x="7" y="7" width="1" height="10"/><rect x="9" y="7" width="2" height="10"/><rect x="12" y="7" width="1" height="10"/><rect x="14" y="7" width="3" height="10"/><rect x="18" y="7" width="1" height="10"/></svg>
                                <span id="scanBtnLabel"><?php echo !empty($user['barcode_value']) ? 'Re-scan / Re-upload' : 'Scan or Upload ID Barcode'; ?></span>
                            </button>

                            <div class="barcode-pill <?php echo !empty($user['barcode_value']) ? 'show' : ''; ?>" id="barcodePill">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                Saved:&nbsp;<code id="barcodeDisplay"><?php echo htmlspecialchars($user['barcode_value'] ?? ''); ?></code>
                                &nbsp;<span id="barcodeFormat" style="font-size:.75rem;color:#6b7280;font-weight:normal;"></span>
                                <button type="button" class="barcode-pill-clear" onclick="clearBarcode()" title="Remove">✕</button>
                            </div>

                            <small style="color:var(--medium-gray);font-size:.875rem;display:block;margin-top:.4rem;">
                                Use the <strong>camera</strong> or <strong>upload a photo</strong> of your ID card's barcode.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" class="form-input" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label for="course" class="form-label">Course</label>
                                <input type="text" id="course" name="course" class="form-input" value="<?php echo htmlspecialchars($user['course'] ?? ''); ?>" placeholder="e.g., Computer Science">
                            </div>
                            <div class="form-group">
                                <label for="year_level" class="form-label">Year Level</label>
                                <select id="year_level" name="year_level" class="form-select">
                                    <option value="">Select year level</option>
                                    <option value="1st Year" <?php echo ($user['year_level']==='1st Year')?'selected':''; ?>>1st Year</option>
                                    <option value="2nd Year" <?php echo ($user['year_level']==='2nd Year')?'selected':''; ?>>2nd Year</option>
                                    <option value="3rd Year" <?php echo ($user['year_level']==='3rd Year')?'selected':''; ?>>3rd Year</option>
                                    <option value="4th Year" <?php echo ($user['year_level']==='4th Year')?'selected':''; ?>>4th Year</option>
                                    <option value="Graduate" <?php echo ($user['year_level']==='Graduate') ?'selected':''; ?>>Graduate</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group" style="margin-top:2rem;">
                            <button type="submit" name="update_profile" class="btn btn-primary" style="width:100%;">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">Change Password</h2></div>
                <div class="card-body">
                    <form method="POST" action="" data-validate>
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <div class="form-group">
                            <label for="current_password" class="form-label">Current Password *</label>
                            <input type="password" id="current_password" name="current_password" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password" class="form-label">New Password *</label>
                            <input type="password" id="new_password" name="new_password" class="form-input" placeholder="Minimum 8 characters" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Re-enter new password" required>
                        </div>
                        <div class="form-group" style="margin-top:2rem;">
                            <button type="submit" name="change_password" class="btn btn-primary" style="width:100%;">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div style="text-align:center;margin-top:3rem;">
            <a href="index.php" class="btn btn-secondary btn-large">Back to Dashboard</a>
        </div>
    </div>
</section>

<!-- ══ Scanner Modal ══ -->
<div id="scannerModal" role="dialog" aria-modal="true">
    <div id="scannerBox">
        <h3>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1976d2" stroke-width="2"><rect x="3" y="7" width="3" height="10"/><rect x="7" y="7" width="1" height="10"/><rect x="9" y="7" width="2" height="10"/><rect x="12" y="7" width="1" height="10"/><rect x="14" y="7" width="3" height="10"/></svg>
            Scan ID Card Barcode
        </h3>

        <div class="scan-tabs">
            <button class="scan-tab active" onclick="switchTab('camera')">📷 Camera</button>
            <button class="scan-tab"        onclick="switchTab('upload')">🖼 Upload Image</button>
        </div>

        <!-- Camera tab -->
        <div class="scan-tab-panel active" id="tab-camera">
            <p style="margin:0 0 .5rem;font-size:.82rem;color:#6b7280;">Hold the barcode in front of the camera — first read is accepted instantly.</p>
            <div id="scanLineWrapper">
                <video id="scannerVideo" playsinline muted></video>
                <div id="scanLine"></div>
            </div>
        </div>

        <!-- Upload tab -->
        <div class="scan-tab-panel" id="tab-upload">
            <p style="margin:0 0 .5rem;font-size:.82rem;color:#6b7280;">Take a clear photo of your ID card's barcode and upload it here.</p>
            <div class="img-drop-zone" id="dropZone" onclick="document.getElementById('barcodeImageInput').click()">
                <input type="file" id="barcodeImageInput" accept="image/*" onchange="handleImageUpload(this.files[0])">
                <div class="drop-icon">📷</div>
                <p><strong>Click to choose a photo</strong> or drag &amp; drop here</p>
                <p style="font-size:.78rem;margin-top:.3rem;">JPG, PNG — close-up of just the barcode works best</p>
            </div>
            <div id="imgPreviewWrap">
                <img id="imgPreview" alt="Preview">
                <div id="imgScanOverlay" style="display:none;"><span>🔍 Scanning…</span></div>
            </div>
        </div>

        <div id="scannerStatus">Select a method above to get started.</div>

        <div style="display:flex;gap:.75rem;justify-content:flex-end;">
            <button type="button" class="modal-close-btn" onclick="closeScanner()">Cancel</button>
        </div>
    </div>
</div>

<script>
(function () {
    // CONFIRM_NEEDED = 1 — first clean read is accepted immediately
    const CONFIRM_NEEDED = 1;

    let reader=null, scanning=false, activeTab='camera';

    function setStatus(msg, cls) {
        const el = document.getElementById('scannerStatus');
        el.innerHTML = msg; el.className = cls || '';
    }

    function getHints() {
        const h = new Map();
        h.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [
            ZXing.BarcodeFormat.CODE_128,
            ZXing.BarcodeFormat.CODE_39,
        ]);
        h.set(ZXing.DecodeHintType.TRY_HARDER, true);
        return h;
    }

    /* ── Tabs ── */
    window.switchTab = function(tab) {
        activeTab = tab;
        document.querySelectorAll('.scan-tab').forEach((t,i) =>
            t.classList.toggle('active', (i===0&&tab==='camera')||(i===1&&tab==='upload'))
        );
        document.getElementById('tab-camera').classList.toggle('active', tab==='camera');
        document.getElementById('tab-upload').classList.toggle('active', tab==='upload');
        if (tab === 'camera') {
            setStatus('Hold the barcode in front of the camera…');
            startCamera();
        } else {
            stopCamera();
            setStatus('Choose or drag a photo of your ID barcode.');
        }
    };

    /* ── Open / Close ── */
    window.openScanner = function () {
        document.getElementById('scannerModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        document.getElementById('dropZone').style.display = '';
        document.getElementById('imgPreviewWrap').style.display = 'none';
        setStatus('Select a method above to get started.');
        switchTab('camera');
    };

    window.closeScanner = function () {
        stopCamera();
        document.getElementById('scannerModal').classList.remove('active');
        document.body.style.overflow = '';
    };

    window.clearBarcode = function () {
        document.getElementById('barcode_value').value        = '';
        document.getElementById('barcodeDisplay').textContent = '';
        document.getElementById('barcodeFormat').textContent  = '';
        document.getElementById('barcodePill').classList.remove('show');
        document.getElementById('scanBtn').classList.remove('scanned');
        document.getElementById('scanBtnLabel').textContent   = 'Scan or Upload ID Barcode';
    };

    /* ── Camera ── */
    async function getRearCameraId() {
        try {
            const p = await navigator.mediaDevices.getUserMedia({ video: true });
            p.getTracks().forEach(t => t.stop());
            const all  = await navigator.mediaDevices.enumerateDevices();
            const cams = all.filter(d => d.kind === 'videoinput');
            if (cams.length <= 1) return undefined;
            const r = cams.find(d => /back|rear|environment/i.test(d.label));
            return r ? r.deviceId : undefined;
        } catch (_) { return undefined; }
    }

    function stopCamera() {
        scanning = false;
        if (reader) { try { reader.reset(); } catch (_) {} reader = null; }
        const v = document.getElementById('scannerVideo');
        if (v && v.srcObject) { v.srcObject.getTracks().forEach(t => t.stop()); v.srcObject = null; }
    }

    async function startCamera() {
        stopCamera();
        if (typeof ZXing === 'undefined') { setStatus('Scanner library not loaded. Refresh the page.', 'err'); return; }
        setStatus('Starting camera…');
        try {
            const deviceId = await getRearCameraId();
            reader   = new ZXing.BrowserMultiFormatReader(getHints());
            scanning = true;
            setStatus('Hold the ID barcode in front of the camera…');
            await reader.decodeFromVideoDevice(deviceId, 'scannerVideo', (result) => {
                if (!scanning || activeTab !== 'camera') return;
                if (result) capture(result.getText(), result.getBarcodeFormat());
            });
        } catch (err) {
            if (err.name === 'NotAllowedError')    setStatus('Camera access denied. Use the Upload tab instead.', 'err');
            else if (err.name === 'NotFoundError') setStatus('No camera found. Use the Upload tab instead.', 'err');
            else                                   setStatus('Camera error. Try the Upload tab.', 'err');
        }
    }

    /* ── Image upload decode ── */
    window.handleImageUpload = async function(file) {
        if (!file) return;
        if (typeof ZXing === 'undefined') { setStatus('Scanner library not loaded. Refresh the page.', 'err'); return; }

        const url     = URL.createObjectURL(file);
        const preview = document.getElementById('imgPreview');
        preview.src   = url;
        document.getElementById('dropZone').style.display        = 'none';
        document.getElementById('imgPreviewWrap').style.display  = '';
        document.getElementById('imgScanOverlay').style.display  = 'flex';
        setStatus('Scanning image for barcode…');

        try {
            const r      = new ZXing.BrowserMultiFormatReader(getHints());
            const result = await r.decodeFromImageUrl(url);
            document.getElementById('imgScanOverlay').style.display = 'none';
            URL.revokeObjectURL(url);
            if (result) capture(result.getText(), result.getBarcodeFormat());
        } catch (err) {
            document.getElementById('imgScanOverlay').style.display = 'none';
            URL.revokeObjectURL(url);
            if (err && err.name === 'NotFoundException')
                setStatus('❌ No barcode found. Try a closer, clearer photo of just the barcode.', 'err');
            else
                setStatus('❌ Could not read image: ' + (err.message || err), 'err');
            document.getElementById('dropZone').style.display       = '';
            document.getElementById('imgPreviewWrap').style.display = 'none';
            document.getElementById('barcodeImageInput').value      = '';
        }
    };

    // Drag and drop
    const dz = document.getElementById('dropZone');
    dz.addEventListener('dragover',  e => { e.preventDefault(); dz.classList.add('drag-over'); });
    dz.addEventListener('dragleave', ()  => dz.classList.remove('drag-over'));
    dz.addEventListener('drop', e => {
        e.preventDefault(); dz.classList.remove('drag-over');
        const f = e.dataTransfer.files[0];
        if (f && f.type.startsWith('image/')) handleImageUpload(f);
    });

    /* ── Capture ── */
    function capture(value, format) {
        scanning = false; // stop processing further camera frames
        value    = (value || '').trim();
        if (!value) return;

        const fmtName = format !== undefined ? String(format) : '';

        document.getElementById('barcode_value').value        = value;
        document.getElementById('barcodeDisplay').textContent = value;
        document.getElementById('barcodeFormat').textContent  = fmtName ? '(' + fmtName + ')' : '';
        document.getElementById('barcodePill').classList.add('show');
        document.getElementById('scanBtn').classList.add('scanned');
        document.getElementById('scanBtnLabel').textContent   = 'Re-scan / Re-upload';

        setStatus('✓ Barcode captured! Click "Update Profile" to save.', 'ok');
        setTimeout(closeScanner, 1000);
    }

    document.getElementById('scannerModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeScanner(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeScanner(); });
})();
</script>

<?php include '../includes/footer.php'; ?>