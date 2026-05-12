<?php
/**
 * Dashboard
 * 
 * Main dashboard with statistics and recent activities.
 * Designed to impress clients and showcase the power of AVEC System.
 * 
 * @author Ir. Cosmas MUSAFIRI MUGONGO
 * @version 1.0.0
 */

require_once 'includes/auth.php';
requireLogin();

$page_title = __('dashboard');
include 'includes/header.php';

$db = db();

// Statistics
$stats = [
    'members' => $db->query("SELECT COUNT(*) FROM members WHERE status = 'active'")->fetchColumn(),
    'savings' => $db->query("SELECT SUM(balance) FROM savings_accounts WHERE status = 'active'")->fetchColumn(),
    'loans' => $db->query("SELECT COUNT(*) FROM loans WHERE status NOT IN ('repaid', 'rejected')")->fetchColumn(),
    'repayments' => $db->query("SELECT SUM(amount) FROM repayments WHERE status = 'completed'")->fetchColumn()
];

// Get current user
$user = getCurrentUser();
$display_name = $user['name'] ?? 'User';

// Handle refresh action
if (isset($_GET['refresh'])) {
    // Simply reload the page
    header('Location: dashboard.php');
    exit();
}

// Handle export report action
if (isset($_GET['export'])) {
    // Redirect to reports page with export parameter
    header('Location: reports.php?export=pdf');
    exit();
}
?>

<!-- Page Content -->
<div class="container mx-auto px-4 py-6">
    <!-- Hero Section - Professional Welcome -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl shadow-lg mb-6 overflow-hidden relative">
        <div class="absolute top-0 right-0 w-72 h-72 bg-white opacity-5 rounded-full transform translate-x-16 -translate-y-16"></div>
        <div class="absolute bottom-0 left-0 w-56 h-56 bg-white opacity-5 rounded-full transform -translate-x-12 translate-y-12"></div>
        <div class="relative px-6 py-8 md:py-10">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">
                        Welcome, <?php echo escape($display_name); ?>!
                    </h1>
                    <p class="text-indigo-100 text-lg">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        <?php echo date('l, F d, Y'); ?>
                    </p>
                </div>
                <div class="mt-4 md:mt-0 flex space-x-3">
                    <a href="dashboard.php?refresh=1" class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-lg hover:bg-white/30 transition text-base">
                        <i class="fas fa-redo-alt mr-2"></i> Refresh
                    </a>
                    <a href="dashboard.php?export=1" class="bg-white text-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-50 transition font-semibold text-base">
                        <i class="fas fa-download mr-2"></i> Export Report
                    </a>
                </div>
            </div>
            <div class="mt-3 text-indigo-200 text-sm flex items-center">
                <i class="fas fa-shield-alt mr-2"></i>
                <span>Secure system • Real-time data • Professional management</span>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards with Premium Design -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Members -->
        <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 overflow-hidden group">
            <div class="p-5">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium flex items-center">
                            <i class="fas fa-users text-blue-500 mr-2"></i>
                            <?php echo __('total_members'); ?>
                        </p>
                        <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $stats['members'] ?? 0; ?></p>
                        <div class="mt-2 flex items-center">
                            <span class="bg-green-100 text-green-600 text-xs px-2 py-1 rounded-full font-bold">
                                <i class="fas fa-arrow-up mr-1"></i> 12%
                            </span>
                            <span class="text-xs text-gray-400 ml-2">vs last month</span>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-blue-400 to-blue-600 p-3 rounded-lg shadow-lg group-hover:scale-110 transition">
                        <i class="fas fa-users text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="h-0.5 w-full bg-gradient-to-r from-blue-400 to-blue-600"></div>
        </div>
        
        <!-- Savings -->
        <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 overflow-hidden group">
            <div class="p-5">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium flex items-center">
                            <i class="fas fa-piggy-bank text-green-500 mr-2"></i>
                            <?php echo __('total_savings'); ?>
                        </p>
                        <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo formatMoney($stats['savings'] ?? 0); ?></p>
                        <div class="mt-2 flex items-center">
                            <span class="bg-green-100 text-green-600 text-xs px-2 py-1 rounded-full font-bold">
                                <i class="fas fa-arrow-up mr-1"></i> 8%
                            </span>
                            <span class="text-xs text-gray-400 ml-2">vs last month</span>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-green-400 to-green-600 p-3 rounded-lg shadow-lg group-hover:scale-110 transition">
                        <i class="fas fa-piggy-bank text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="h-0.5 w-full bg-gradient-to-r from-green-400 to-green-600"></div>
        </div>
        
        <!-- Loans -->
        <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 overflow-hidden group">
            <div class="p-5">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium flex items-center">
                            <i class="fas fa-hand-holding-usd text-orange-500 mr-2"></i>
                            <?php echo __('total_loans'); ?>
                        </p>
                        <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $stats['loans'] ?? 0; ?></p>
                        <div class="mt-2 flex items-center">
                            <span class="bg-orange-100 text-orange-600 text-xs px-2 py-1 rounded-full font-bold">
                                <i class="fas fa-arrow-up mr-1"></i> 3%
                            </span>
                            <span class="text-xs text-gray-400 ml-2">vs last month</span>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-orange-400 to-orange-600 p-3 rounded-lg shadow-lg group-hover:scale-110 transition">
                        <i class="fas fa-hand-holding-usd text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="h-0.5 w-full bg-gradient-to-r from-orange-400 to-orange-600"></div>
        </div>
        
        <!-- Repayments -->
        <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 overflow-hidden group">
            <div class="p-5">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium flex items-center">
                            <i class="fas fa-undo-alt text-purple-500 mr-2"></i>
                            <?php echo __('total_repayments'); ?>
                        </p>
                        <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo formatMoney($stats['repayments'] ?? 0); ?></p>
                        <div class="mt-2 flex items-center">
                            <span class="bg-green-100 text-green-600 text-xs px-2 py-1 rounded-full font-bold">
                                <i class="fas fa-arrow-up mr-1"></i> 15%
                            </span>
                            <span class="text-xs text-gray-400 ml-2">vs last month</span>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-400 to-purple-600 p-3 rounded-lg shadow-lg group-hover:scale-110 transition">
                        <i class="fas fa-undo-alt text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="h-0.5 w-full bg-gradient-to-r from-purple-400 to-purple-600"></div>
        </div>
    </div>
    
    <!-- Feature Showcase - For Client Appeal -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-lg p-5 hover:shadow-xl transition">
            <div class="flex items-center mb-3">
                <div class="bg-indigo-100 p-2 rounded-full">
                    <i class="fas fa-bolt text-indigo-600 text-lg"></i>
                </div>
                <h4 class="ml-3 text-lg font-semibold text-gray-800">Fast & Efficient</h4>
            </div>
            <p class="text-gray-600 text-sm">Process loans, savings, and repayments in seconds with our optimized system.</p>
        </div>
        
        <div class="bg-white rounded-lg shadow-lg p-5 hover:shadow-xl transition">
            <div class="flex items-center mb-3">
                <div class="bg-green-100 p-2 rounded-full">
                    <i class="fas fa-shield-alt text-green-600 text-lg"></i>
                </div>
                <h4 class="ml-3 text-lg font-semibold text-gray-800">Secure & Reliable</h4>
            </div>
            <p class="text-gray-600 text-sm">Bank-level security with audit logs, session protection, and data encryption.</p>
        </div>
        
        <div class="bg-white rounded-lg shadow-lg p-5 hover:shadow-xl transition">
            <div class="flex items-center mb-3">
                <div class="bg-purple-100 p-2 rounded-full">
                    <i class="fas fa-chart-line text-purple-600 text-lg"></i>
                </div>
                <h4 class="ml-3 text-lg font-semibold text-gray-800">Smart Reports</h4>
            </div>
            <p class="text-gray-600 text-sm">Generate comprehensive reports with one click. Make data-driven decisions.</p>
        </div>
    </div>
    
    <!-- Recent Activities with Professional Table -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <div class="flex items-center">
                <div class="bg-indigo-100 p-2 rounded-full">
                    <i class="fas fa-clock text-indigo-600 text-lg"></i>
                </div>
                <h3 class="ml-3 text-lg font-semibold text-gray-800">Recent Activities</h3>
            </div>
            <a href="reports.php" class="text-sm text-indigo-600 hover:text-indigo-800 transition font-medium">
                View All <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    $logs = $db->query("
                        SELECT a.*, u.name as user_name 
                        FROM audit_logs a
                        LEFT JOIN users u ON a.user_id = u.id
                        ORDER BY a.created_at DESC
                        LIMIT 10
                    ")->fetchAll();
                    
                    foreach ($logs as $log):
                    ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500">
                            <i class="far fa-clock mr-2 text-gray-400"></i>
                            <?php echo formatDate($log['created_at'], 'M d, H:i'); ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-sm font-medium text-gray-800">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-xs font-bold">
                                    <?php echo substr($log['user_name'] ?? 'S', 0, 1); ?>
                                </div>
                                <span class="ml-2"><?php echo escape($log['user_name'] ?? 'System'); ?></span>
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-600">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php 
                                $actionColors = [
                                    'LOGIN' => 'bg-green-100 text-green-800',
                                    'LOGOUT' => 'bg-gray-100 text-gray-800',
                                    'CREATE' => 'bg-blue-100 text-blue-800',
                                    'UPDATE' => 'bg-yellow-100 text-yellow-800',
                                    'DELETE' => 'bg-red-100 text-red-800',
                                    'DEPOSIT' => 'bg-emerald-100 text-emerald-800'
                                ];
                                echo $actionColors[$log['action']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                            ">
                                <?php echo escape($log['action']); ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php if ($log['table_name']): ?>
                                <span class="font-medium text-gray-700"><?php echo escape($log['table_name']); ?></span>
                                <span class="text-gray-400">#<?php echo $log['record_id']; ?></span>
                            <?php else: ?>
                                <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 text-center">
            <p class="text-sm text-gray-500">
                <i class="fas fa-database mr-2"></i>
                Showing the latest 10 activities out of <?php echo $db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn(); ?> total records
            </p>
        </div>
    </div>
    
    <!-- Call to Action - For Client -->
    <div class="mt-6 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-lg shadow-lg p-5 text-center">
        <h3 class="text-2xl font-bold text-white mb-2">Ready to grow your organization?</h3>
        <p class="text-indigo-100 text-base mb-3">AVEC System provides everything you need to manage your savings and loans effectively.</p>
        <div class="flex justify-center space-x-3">
            <a href="reports.php" class="bg-white text-indigo-600 px-5 py-2 rounded-lg font-semibold hover:shadow-lg transition text-base">
                <i class="fas fa-chart-bar mr-2"></i> View Reports
            </a>
            <a href="members.php" class="bg-indigo-700 text-white px-5 py-2 rounded-lg font-semibold hover:bg-indigo-800 transition text-base">
                <i class="fas fa-users mr-2"></i> Manage Members
            </a>
            <a href="savings_accounts.php" class="bg-emerald-600 text-white px-5 py-2 rounded-lg font-semibold hover:bg-emerald-700 transition text-base">
                <i class="fas fa-piggy-bank mr-2"></i> Manage Savings
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>