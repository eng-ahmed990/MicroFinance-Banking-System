            <header>
                <div>
                    <h2 style="margin: 0; font-size: 1.75rem;"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h2>
                    <p style="margin: 4px 0 0; color: var(--text-muted);">Welcome back, <strong style="color: var(--primary-color);"><?php echo htmlspecialchars($user_name ?? $_SESSION['user_name'] ?? 'User'); ?></strong></p>
                </div>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <a href="notifications.php" id="notify-link" style="position: relative; color: var(--text-muted); font-size: 1.25rem;">
                        <i class="fas fa-bell"></i>
                        <span id="notify-badge" style="position: absolute; top: -5px; right: -5px; width: 18px; height: 18px; background: var(--danger); color: white; font-size: 0.7rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; <?php echo (isset($unread_count) && $unread_count > 0) ? '' : 'display: none !important;'; ?>">
                            <?php echo (isset($unread_count) && $unread_count > 0) ? $unread_count : ''; ?>
                        </span>
                    </a>
                    <a href="profile.php" style="display: flex; align-items: center; gap: 12px; background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%); padding: 8px 16px 8px 8px; border-radius: var(--radius-full);">
                        <?php if (isset($profile_pic) && $profile_pic): ?>
                            <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; border: 2px solid var(--primary-color);">
                                <img src="../assets/images/<?php echo $profile_pic; ?>" alt="User" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        <?php else: ?>
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-gradient); display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user" style="color: white; font-size: 1rem;"></i>
                            </div>
                        <?php endif; ?>
                        <span style="font-weight: 600; color: var(--text-color);"><?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?></span>
                    </a>
                </div>
            </header>
