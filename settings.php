<?php
/**
 * Settings
 * 
 * Manage application settings, user profile, and system configuration.
 * 
 * @author Ir. Cosmas MUSAFIRI MUGONGO
 * @version 1.0.0
 */

require_once 'includes/auth.php';
requireLogin();

$page_title = __('settings');
include 'includes/header.php';

$db = db();
$user = getCurrentUser();
$action = $_POST['action'] ?? '';

// Get member data for profile
$member_data = $db->prepare("SELECT phone, address FROM members WHERE user_id = :user_id");
$member_data->execute(['user_id' => $user['id']]);
$member_info = $member_data->fetch();

// ============================================
// HANDLE ACTIONS
// ============================================

// Update Profile
if ($action == 'update_profile' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required.';
    } else {
        try {
            $db->beginTransaction();
            
            // Update user
            $db->prepare("UPDATE users SET name = :name, email = :email WHERE id = :id")
               ->execute(['name' => $name, 'email' => $email, 'id' => $user['id']]);
            
            // Update member if exists
            $member = $db->prepare("SELECT id FROM members WHERE user_id = :user_id");
            $member->execute(['user_id' => $user['id']]);
            if ($member_data = $member->fetch()) {
                $db->prepare("UPDATE members SET phone = :phone, address = :address WHERE user_id = :user_id")
                   ->execute(['phone' => $phone, 'address' => $address, 'user_id' => $user['id']]);
            }
            
            $db->commit();
            logAction('UPDATE_PROFILE', 'users', $user['id'], "Profile updated");
            redirect('settings.php', 'Profile updated successfully!', 'success');
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}

// Change Password
if ($action == 'change_password' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        try {
            // Verify current password
            $user_data = $db->prepare("SELECT password FROM users WHERE id = :id");
            $user_data->execute(['id' => $user['id']]);
            $user_data = $user_data->fetch();
            
            if ($user_data['password'] !== $current_password) {
                $error = 'Current password is incorrect.';
            } else {
                $db->prepare("UPDATE users SET password = :password WHERE id = :id")
                   ->execute(['password' => $new_password, 'id' => $user['id']]);
                
                logAction('CHANGE_PASSWORD', 'users', $user['id'], "Password changed");
                redirect('settings.php', 'Password changed successfully!', 'success');
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Update Language
if ($action == 'update_language' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $language = $_POST['language'] ?? 'en';
    
    set_language($language);
    logAction('CHANGE_LANGUAGE', 'settings', 0, "Language changed to $language");
    redirect('settings.php', 'Language updated successfully!', 'success');
}

// Update System Settings (admin only)
if ($action == 'update_system' && $_SERVER['REQUEST_METHOD'] == 'POST' && hasRole('admin')) {
    $app_name = trim($_POST['app_name']);
    $default_lang = $_POST['default_lang'] ?? 'en';
    $session_lifetime = intval($_POST['session_lifetime']);
    
    if (empty($app_name)) {
        $error = 'Application name is required.';
    } elseif ($session_lifetime < 60) {
        $error = 'Session lifetime must be at least 60 seconds.';
    } else {
        try {
            // Update config file
            $config_file = dirname(__DIR__) . '/config/app.php';
            $config_content = file_get_contents($config_file);
            
            $config_content = preg_replace("/define\('APP_NAME', '[^']*'\)/", "define('APP_NAME', '$app_name')", $config_content);
            $config_content = preg_replace("/define\('DEFAULT_LANG', '[^']*'\)/", "define('DEFAULT_LANG', '$default_lang')", $config_content);
            $config_content = preg_replace("/define\('SESSION_LIFETIME', \d+\)/", "define('SESSION_LIFETIME', $session_lifetime)", $config_content);
            
            file_put_contents($config_file, $config_content);
            
            logAction('UPDATE_SYSTEM', 'settings', 0, "System settings updated");
            redirect('settings.php', 'System settings updated successfully!', 'success');
        } catch (Exception $e) {
            $error = 'Failed to update system settings: ' . $e->getMessage();
        }
    }
}
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-cog mr-2 text-indigo-500"></i>
                <?php echo __('settings'); ?>
            </h2>
            <p class="text-gray-500 text-sm">Manage your account and application settings</p>
        </div>
    </div>
    
    <!-- Settings Navigation Tabs -->
    <div class="mb-4">
        <div class="border-b border-gray-200">
            <nav class="flex flex-wrap space-x-4 text-sm">
                <button class="py-2 px-4 font-medium text-indigo-600 border-b-2 border-indigo-600" data-tab="profile">
                    <i class="fas fa-user mr-2"></i> Profile
                </button>
                <button class="py-2 px-4 font-medium text-gray-500 hover:text-gray-700" data-tab="password">
                    <i class="fas fa-lock mr-2"></i> Password
                </button>
                <button class="py-2 px-4 font-medium text-gray-500 hover:text-gray-700" data-tab="language">
                    <i class="fas fa-globe mr-2"></i> Language
                </button>
                <?php if (hasRole('admin')): ?>
                <button class="py-2 px-4 font-medium text-gray-500 hover:text-gray-700" data-tab="system">
                    <i class="fas fa-server mr-2"></i> System
                </button>
                <?php endif; ?>
            </nav>
        </div>
    </div>
    
    <!-- Settings Content -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Profile Tab -->
        <div id="profile-tab" class="p-5">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Profile Settings</h3>
            <p class="text-gray-500 text-sm mb-4">Update your personal information</p>
            
            <?php if (isset($error) && $action == 'update_profile'): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2.5 rounded-lg mb-4 text-sm"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post" class="space-y-4 max-w-2xl">
                <input type="hidden" name="action" value="update_profile">
                
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-1">
                        <i class="fas fa-user mr-2 text-gray-400"></i>
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500" value="<?php echo escape($user['name']); ?>" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-1">
                        <i class="fas fa-envelope mr-2 text-gray-400"></i>
                        Email Address <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="email" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500" value="<?php echo escape($user['email']); ?>" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-1">
                        <i class="fas fa-phone mr-2 text-gray-400"></i>
                        Phone Number
                    </label>
                    <input type="text" name="phone" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500" value="<?php echo escape($member_info['phone'] ?? ''); ?>">
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-1">
                        <i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>
                        Address
                    </label>
                    <textarea name="address" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500" rows="2"><?php echo escape($member_info['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="pt-3 border-t border-gray-200">
                    <button type="submit" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-5 py-2 rounded-lg hover:from-indigo-700 hover:to-purple-700">
                        <i class="fas fa-save mr-2"></i>
                        Update Profile
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Password Tab -->
        <div id="password-tab" class="p-5 hidden">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Change Password</h3>
            <p class="text-gray-500 text-sm mb-4">Update your password for better security</p>
            
            <?php if (isset($error) && $action == 'change_password'): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2.5 rounded-lg mb-4 text-sm"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post" class="space-y-4 max-w-2xl">
                <input type="hidden" name="action" value="change_password">
                
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-1">
                        <i class="fas fa-lock mr-2 text-gray-400"></i>
                        Current Password <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="current_password" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-1">
                        <i class="fas fa-key mr-2 text-gray-400"></i>
                        New Password <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="new_password" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500" required>
                    <p class="text-xs text-gray-500 mt-1">Must be at least 6 characters</p>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-1">
                        <i class="fas fa-check-circle mr-2 text-gray-400"></i>
                        Confirm New Password <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="confirm_password" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500" required>
                </div>
                
                <div class="pt-3 border-t border-gray-200">
                    <button type="submit" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-5 py-2 rounded-lg hover:from-indigo-700 hover:to-purple-700">
                        <i class="fas fa-save mr-2"></i>
                        Change Password
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Language Tab -->
        <div id="language-tab" class="p-5 hidden">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Language Settings</h3>
            <p class="text-gray-500 text-sm mb-4">Choose your preferred language</p>
            
            <form method="post" class="space-y-4 max-w-2xl">
                <input type="hidden" name="action" value="update_language">
                
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-1">
                        <i class="fas fa-language mr-2 text-gray-400"></i>
                        Language
                    </label>
                    <select name="language" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500">
                        <option value="en" <?php echo get_language() == 'en' ? 'selected' : ''; ?>>English</option>
                        <option value="fr" <?php echo get_language() == 'fr' ? 'selected' : ''; ?>>Français</option>
                        <option value="es" <?php echo get_language() == 'es' ? 'selected' : ''; ?>>Español</option>
                        <option value="sw" <?php echo get_language() == 'sw' ? 'selected' : ''; ?>>Swahili</option>
                    </select>
                </div>
                
                <div class="pt-3 border-t border-gray-200">
                    <button type="submit" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-5 py-2 rounded-lg hover:from-indigo-700 hover:to-purple-700">
                        <i class="fas fa-save mr-2"></i>
                        Update Language
                    </button>
                </div>
            </form>
        </div>
        
        <?php if (hasRole('admin')): ?>
        <!-- System Tab -->
        <div id="system-tab" class="p-5 hidden">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">System Settings</h3>
            <p class="text-gray-500 text-sm mb-4">Configure global application settings</p>
            
            <?php if (isset($error) && $action == 'update_system'): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2.5 rounded-lg mb-4 text-sm"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post" class="space-y-4 max-w-2xl">
                <input type="hidden" name="action" value="update_system">
                
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-1">
                        <i class="fas fa-cube mr-2 text-gray-400"></i>
                        Application Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="app_name" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500" value="<?php echo APP_NAME; ?>" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-1">
                        <i class="fas fa-globe mr-2 text-gray-400"></i>
                        Default Language
                    </label>
                    <select name="default_lang" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500">
                        <option value="en" <?php echo DEFAULT_LANG == 'en' ? 'selected' : ''; ?>>English</option>
                        <option value="fr" <?php echo DEFAULT_LANG == 'fr' ? 'selected' : ''; ?>>Français</option>
                        <option value="es" <?php echo DEFAULT_LANG == 'es' ? 'selected' : ''; ?>>Español</option>
                        <option value="sw" <?php echo DEFAULT_LANG == 'sw' ? 'selected' : ''; ?>>Swahili</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-1">
                        <i class="fas fa-clock mr-2 text-gray-400"></i>
                        Session Lifetime (seconds) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="session_lifetime" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500" value="<?php echo SESSION_LIFETIME; ?>" min="60" required>
                </div>
                
                <div class="pt-3 border-t border-gray-200">
                    <button type="submit" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-5 py-2 rounded-lg hover:from-indigo-700 hover:to-purple-700">
                        <i class="fas fa-save mr-2"></i>
                        Update System Settings
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Account Information -->
    <div class="mt-4 bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-info-circle mr-2 text-indigo-500"></i>
                Account Information
            </h3>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <p class="text-sm text-gray-500">Role</p>
                    <p class="text-base font-medium text-gray-800"><?php echo escape($user['role_name']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Member Since</p>
                    <p class="text-base font-medium text-gray-800"><?php echo formatDate(date('Y-m-d')); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Group</p>
                    <p class="text-base font-medium text-gray-800"><?php echo escape($user['group_id'] ? 'Group #' . $user['group_id'] : 'N/A'); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Email</p>
                    <p class="text-base font-medium text-gray-800"><?php echo escape($user['email']); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Tab switching functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('[data-tab]');
    const tabContents = {
        'profile': document.getElementById('profile-tab'),
        'password': document.getElementById('password-tab'),
        'language': document.getElementById('language-tab'),
        'system': document.getElementById('system-tab')
    };
    
    // Activate first tab by default
    if (tabs.length > 0) {
        tabs[0].click();
    }
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            tabs.forEach(t => {
                t.classList.remove('text-indigo-600', 'border-indigo-600');
                t.classList.add('text-gray-500', 'border-transparent');
            });
            
            // Add active class to clicked tab
            this.classList.remove('text-gray-500', 'border-transparent');
            this.classList.add('text-indigo-600', 'border-indigo-600');
            
            // Hide all tab contents
            Object.values(tabContents).forEach(content => {
                if (content) content.classList.add('hidden');
            });
            
            // Show selected tab content
            const tabName = this.dataset.tab;
            if (tabContents[tabName]) {
                tabContents[tabName].classList.remove('hidden');
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>