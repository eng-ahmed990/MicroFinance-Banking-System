            <header>
                <div>
                    <h2 style="margin: 0; font-size: 1.75rem;"><?php echo isset($page_title) ? $page_title : 'Admin Dashboard'; ?></h2>
                    <p style="margin: 4px 0 0; color: var(--text-muted);">Welcome back, <strong style="color: var(--primary-color);"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator'); ?></strong></p>
                </div>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <a href="profile.php" style="display: flex; align-items: center; gap: 12px; background: linear-gradient(135deg, rgba(251, 191, 36, 0.15) 0%, rgba(245, 158, 11, 0.1) 100%); padding: 10px 20px; border-radius: var(--radius-full); transition: transform 0.2s;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user-shield" style="color: white; font-size: 1rem;"></i>
                        </div>
                        <span style="font-weight: 700; color: #d97706;">Administrator</span>
                    </a>
                </div>
            </header>
