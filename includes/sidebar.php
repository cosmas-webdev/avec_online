<?php
// Si on est en export PDF, ne rien afficher (pour éviter l'erreur FPDF)
if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
    return;
}

$current_page = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
$user_name = $user['name'] ?? 'User';
$user_role = $user['role_name'] ?? 'Member';
$user_initial = substr($user_name, 0, 1);
?>
<aside class="sidebar">
    <div class="flex flex-col items-center justify-center h-20 bg-gray-900 border-b border-gray-700">
        <div class="text-2xl font-bold tracking-wider text-white">
            <i class="fas fa-hand-holding-heart text-indigo-400 mr-2"></i>
            <?php echo APP_NAME; ?>
        </div>
        <div class="text-xs text-gray-400 mt-1">Version 1.0.0</div>
    </div>
    
    <div class="px-6 py-4 border-b border-gray-700">
        <div class="flex items-center">
            <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center">
                <span class="text-lg font-bold"><?php echo $user_initial; ?></span>
            </div>
            <div class="ml-3">
                <div class="font-medium"><?php echo escape($user_name); ?></div>
                <div class="text-xs text-gray-400"><?php echo escape($user_role); ?></div>
            </div>
        </div>
    </div>
    
    <nav class="px-4 py-4 space-y-1">
        <a href="dashboard.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo $current_page == 'dashboard.php' ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
            <i class="fas fa-tachometer-alt w-6 text-center"></i>
            <span class="ml-3"><?php echo __('dashboard'); ?></span>
        </a>
        <a href="members.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo $current_page == 'members.php' ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
            <i class="fas fa-users w-6 text-center"></i>
            <span class="ml-3"><?php echo __('members'); ?></span>
        </a>
        <a href="savings_accounts.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo $current_page == 'savings_accounts.php' ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
            <i class="fas fa-piggy-bank w-6 text-center"></i>
            <span class="ml-3"><?php echo __('savings'); ?></span>
        </a>
        <a href="loans.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo $current_page == 'loans.php' ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
            <i class="fas fa-hand-holding-usd w-6 text-center"></i>
            <span class="ml-3"><?php echo __('loans'); ?></span>
        </a>
        <a href="repayments.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo $current_page == 'repayments.php' ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
            <i class="fas fa-undo-alt w-6 text-center"></i>
            <span class="ml-3"><?php echo __('repayments'); ?></span>
        </a>
        <a href="reports.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo $current_page == 'reports.php' ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
            <i class="fas fa-chart-bar w-6 text-center"></i>
            <span class="ml-3"><?php echo __('reports'); ?></span>
        </a>
        <a href="settings.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo $current_page == 'settings.php' ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
            <i class="fas fa-cog w-6 text-center"></i>
            <span class="ml-3"><?php echo __('settings'); ?></span>
        </a>
        <div class="border-t border-gray-700 my-4"></div>
        <a href="logout.php" class="flex items-center px-4 py-3 rounded-lg transition-colors text-red-400 hover:bg-red-900/20 hover:text-red-300">
            <i class="fas fa-sign-out-alt w-6 text-center"></i>
            <span class="ml-3"><?php echo __('logout'); ?></span>
        </a>
    </nav>
</aside>