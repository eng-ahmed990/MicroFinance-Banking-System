<?php
require('includes/db_connect.php');

// Handle Actions (Clear Inbox)
if (isset($_POST['action']) && $_POST['action'] === 'clear') {
    $conn->query("TRUNCATE TABLE email_outbox");
    header("Location: local_inbox.php");
    exit();
}

// Build Query with Filters
$where_clauses = [];
if (!empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $where_clauses[] = "(to_email LIKE '%$search%' OR subject LIKE '%$search%')";
}

// Filter by Type (Derived from Subject)
if (!empty($_GET['type'])) {
    $type = $_GET['type'];
    if ($type === 'otp') $where_clauses[] = "subject LIKE '%OTP%'";
    if ($type === 'welcome') $where_clauses[] = "subject LIKE '%Welcome%'";
    if ($type === 'kyc') $where_clauses[] = "subject LIKE '%KYC%'";
    if ($type === 'approval') $where_clauses[] = "subject LIKE '%Approved%'";
}

$sql = "SELECT * FROM email_outbox";
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}
$sql .= " ORDER BY sent_at DESC LIMIT 50";

$result = $conn->query($sql);
$emails = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Determine Type Badge
        $badge = 'Info';
        $badgeClass = 'secondary';
        if (stripos($row['subject'], 'OTP') !== false) { $badge = 'OTP'; $badgeClass = 'warning'; }
        elseif (stripos($row['subject'], 'Welcome') !== false) { $badge = 'Welcome'; $badgeClass = 'success'; }
        elseif (stripos($row['subject'], 'KYC') !== false) { $badge = 'KYC Pending'; $badgeClass = 'info'; }
        elseif (stripos($row['subject'], 'Approved') !== false) { $badge = 'Approved'; $badgeClass = 'success'; }
        
        $row['badge'] = $badge;
        $row['badgeClass'] = $badgeClass;
        
        // Extract OTP if present (simple regex)
        if (preg_match('/\b\d{6}\b/', $row['body'], $matches)) {
            $row['otp_code'] = $matches[0];
        } else {
            $row['otp_code'] = null;
        }
        
        $emails[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Local Inbox - MicroFinance Dev</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f1f5f9; height: 100vh; overflow: hidden; display: flex; flex-direction: column; }
        .inbox-header { background: white; padding: 15px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 2px rgba(0,0,0,0.05); z-index: 10; }
        .inbox-container { display: flex; flex: 1; overflow: hidden; }
        
        /* Left Panel: List */
        .email-list-panel { width: 400px; background: white; border-right: 1px solid #e2e8f0; overflow-y: auto; display: flex; flex-direction: column; }
        .email-item { padding: 16px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.1s; position: relative; }
        .email-item:hover { background-color: #f8fafc; }
        .email-item.active { background-color: #eef2ff; border-left: 4px solid var(--primary-color); }
        .email-subject { font-weight: 600; color: #1e293b; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .email-meta { display: flex; justify-content: space-between; font-size: 12px; color: #64748b; margin-bottom: 6px; }
        .badge { padding: 2px 8px; border-radius: 99px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .badge-warning { background: #fffbeb; color: #d97706; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-info { background: #e0f2fe; color: #0369a1; }
        .badge-secondary { background: #f1f5f9; color: #475569; }

        /* Right Panel: Content */
        .email-content-panel { flex: 1; background: #f8fafc; padding: 24px; overflow-y: auto; display: flex; justify-content: center; }
        .email-viewer { background: white; width: 100%; max-width: 800px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); overflow: hidden; display: flex; flex-direction: column; min-height: 200px; }
        .viewer-header { padding: 20px; border-bottom: 1px solid #e2e8f0; background: #fff; }
        .viewer-body { flex: 1; padding: 0; position: relative; }
        iframe { width: 100%; height: 100%; min-height: 500px; border: none; display: block; }
        
        .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #94a3b8; }
        .empty-state i { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }

        /* Controls */
        .search-bar { position: relative; width: 100%; max-width: 300px; }
        .search-bar input { padding-left: 36px; height: 40px; font-size: 14px; }
        .search-bar i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        
        .refresh-toggle label { font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 6px; cursor: pointer; }
        
        /* OTP Highlight */
        .otp-quick-view { margin-top: 8px; background: #fffbeb; border: 1px dashed #d97706; padding: 8px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #b45309; }
        .btn-copy { background: white; border: 1px solid #d97706; color: #d97706; padding: 2px 8px; font-size: 11px; border-radius: 4px; cursor: pointer; }
        .btn-copy:hover { background: #d97706; color: white; }

        /* Scrollbars */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="inbox-header">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div class="logo" style="font-size: 1.2rem;">MicroFinance <span style="font-weight: 300; font-size: 1rem;">Local Inbox</span></div>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search subject or email..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            <select id="typeFilter" style="height: 40px; padding: 0 12px; border-radius: 8px; border: 2px solid #e2e8f0; color: #475569;">
                <option value="">All Types</option>
                <option value="otp" <?php if(($_GET['type']??'')=='otp') echo 'selected'; ?>>OTP Codes</option>
                <option value="welcome" <?php if(($_GET['type']??'')=='welcome') echo 'selected'; ?>>Welcome</option>
                <option value="kyc" <?php if(($_GET['type']??'')=='kyc') echo 'selected'; ?>>KYC Pending</option>
                <option value="approval" <?php if(($_GET['type']??'')=='approval') echo 'selected'; ?>>Approvals</option>
            </select>
        </div>
        
        <div style="display: flex; align-items: center; gap: 16px;">
            <div class="refresh-toggle">
                <label>
                    <input type="checkbox" id="autoRefresh" checked> Auto-refresh (5s)
                </label>
            </div>
            <a href="local_inbox.php" class="btn btn-white btn-small"><i class="fas fa-sync-alt"></i></a>
            <form method="POST" onsubmit="return confirm('Clear all emails?');" style="margin:0;">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="btn btn-primary btn-small" style="background: #ef4444; border: none; color: white;"><i class="fas fa-trash"></i> Clear</button>
            </form>
        </div>
    </header>

    <!-- Main Layout -->
    <div class="inbox-container">
        
        <!-- Left: List -->
        <div class="email-list-panel">
            <?php if (empty($emails)): ?>
                <div style="padding: 40px; text-align: center; color: #94a3b8;">
                    <i class="fas fa-inbox" style="font-size: 32px; margin-bottom: 10px;"></i><br>
                    Inbox is empty
                </div>
            <?php else: ?>
                <?php foreach ($emails as $email): ?>
                    <div class="email-item" onclick="viewEmail(<?php echo $email['id']; ?>, this)">
                        <div class="email-meta">
                            <span><?php echo htmlspecialchars($email['to_email']); ?></span>
                            <span><?php echo date('H:i', strtotime($email['sent_at'])); ?></span>
                        </div>
                        <div class="email-subject">
                            <?php echo htmlspecialchars($email['subject']); ?>
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center; margin-top: 6px;">
                            <span class="badge badge-<?php echo $email['badgeClass']; ?>"><?php echo $email['badge']; ?></span>
                        </div>
                        
                        <?php if ($email['otp_code']): ?>
                        <div class="otp-quick-view" onclick="event.stopPropagation()">
                            <span>OTP: <strong><?php echo $email['otp_code']; ?></strong></span>
                            <button class="btn-copy" onclick="copyToClipboard('<?php echo $email['otp_code']; ?>')">Copy</button>
                        </div>
                        <?php endif; ?>

                        <!-- Hidden Data for JS -->
                        <div id="data-subject-<?php echo $email['id']; ?>" style="display:none;"><?php echo htmlspecialchars($email['subject']); ?></div>
                        <div id="data-meta-<?php echo $email['id']; ?>" style="display:none;">To: <?php echo htmlspecialchars($email['to_email']); ?> &bull; <?php echo $email['sent_at']; ?></div>
                        <div id="data-body-<?php echo $email['id']; ?>" style="display:none;"><?php echo htmlspecialchars($email['body']); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Right: Content -->
        <div class="email-content-panel">
            <div id="emptyState" class="empty-state">
                <i class="far fa-envelope-open"></i>
                <h3>Select an email to read</h3>
                <p>Emails sent from the system will appear here instantly.</p>
            </div>
            
            <div id="emailViewer" class="email-viewer" style="display: none;">
                <div class="viewer-header">
                    <h2 id="viewSubject" style="font-size: 1.25rem; margin-bottom: 8px;">Subject</h2>
                    <div id="viewMeta" style="color: #64748b; font-size: 13px;">Meta info</div>
                </div>
                <div class="viewer-body">
                    <iframe id="viewFrame"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Copy OTP
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('OTP Copied: ' + text);
            });
        }

        // View Email
        function viewEmail(id, el) {
            // Highlight active item
            document.querySelectorAll('.email-item').forEach(i => i.classList.remove('active'));
            el.classList.add('active');

            // Hide empty state, show viewer
            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('emailViewer').style.display = 'flex';

            // Populate Content
            document.getElementById('viewSubject').innerText = document.getElementById('data-subject-' + id).innerText;
            document.getElementById('viewMeta').innerText = document.getElementById('data-meta-' + id).innerText;
            
            // Render HTML Body in Iframe
            var bodyContent = document.getElementById('data-body-' + id).innerText;
            // Decode entities if needed (htmlspecialchars encoded them)
            var textarea = document.createElement('textarea');
            textarea.innerHTML = bodyContent;
            var decodedBody = textarea.value;
            
            // Force all links to open in new tab (Fix for embedded view)
            if (decodedBody.indexOf('<head>') !== -1) {
                decodedBody = decodedBody.replace('<head>', '<head><base target="_blank">');
            } else {
                decodedBody = '<base target="_blank">' + decodedBody;
            }

            document.getElementById('viewFrame').srcdoc = decodedBody;
        }

        // Search & Filter (Simple Redirect)
        const searchInput = document.getElementById('searchInput');
        const typeFilter = document.getElementById('typeFilter');

        function applyFilters() {
            const search = searchInput.value;
            const type = typeFilter.value;
            window.location.href = `local_inbox.php?search=${encodeURIComponent(search)}&type=${encodeURIComponent(type)}`;
        }

        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') applyFilters();
        });
        typeFilter.addEventListener('change', applyFilters);

        // Auto Refresh
        setInterval(() => {
            if (document.getElementById('autoRefresh').checked) {
                // Ideally use AJAX to fetch new list, but simple reload works for list view updates
                // BUT we don't want to lose the currently open email.
                // For this requirements ("Simple"), let's just reload IF user hasn't selected an email or just warn them.
                // Actually, let's keep it simple: strict reload might be annoying if reading.
                // We'll skip reload if an email is selected (active class exists) to prevent disruption,
                // OR we accept the refresh. 
                // Let's implement full page reload only if NO email is selected.
                if (!document.querySelector('.email-item.active')) {
                     window.location.reload();
                }
            }
        }, 5000);

    </script>
</body>
</html>
