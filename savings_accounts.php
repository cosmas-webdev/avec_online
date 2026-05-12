<?php
/**
 * Savings Accounts Management
 * 
 * Professional CRUD operations for savings accounts and transactions.
 * 
 * @author Ir. Cosmas MUSAFIRI MUGONGO
 * @version 1.0.0
 */

require_once 'includes/auth.php';
requireLogin();

$page_title = __('savings');
include 'includes/header.php';

$db = db();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

// ============================================
// CRUD ACTIONS
// ============================================

switch ($action) {
    
    // ============================================
    // CREATE - New Deposit
    // ============================================
    case 'deposit':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $member_id = intval($_POST['member_id']);
            $amount = floatval($_POST['amount']);
            $description = trim($_POST['description'] ?? 'Deposit');
            
            if ($amount <= 0) {
                $error = 'Amount must be greater than 0.';
            } else {
                try {
                    $db->beginTransaction();
                    
                    // Check current balance
                    $account = $db->prepare("SELECT * FROM savings_accounts WHERE member_id = :member_id");
                    $account->execute(['member_id' => $member_id]);
                    $account_data = $account->fetch();
                    
                    if (!$account_data) {
                        throw new Exception('Savings account not found.');
                    }
                    
                    // Update balance
                    $db->prepare("
                        UPDATE savings_accounts 
                        SET balance = balance + :amount 
                        WHERE member_id = :member_id
                    ")->execute(['amount' => $amount, 'member_id' => $member_id]);
                    
                    // Record transaction
                    $db->prepare("
                        INSERT INTO transactions (savings_account_id, member_id, transaction_type, amount, description, created_by)
                        VALUES (:savings_account_id, :member_id, 'deposit', :amount, :description, :created_by)
                    ")->execute([
                        'savings_account_id' => $account_data['id'],
                        'member_id' => $member_id,
                        'amount' => $amount,
                        'description' => $description,
                        'created_by' => session_get('user_id')
                    ]);
                    
                    $db->commit();
                    logAction('DEPOSIT', 'savings_accounts', $account_data['id'], "Deposit of " . formatMoney($amount));
                    redirect('savings_accounts.php', 'Deposit completed successfully!', 'success');
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = $e->getMessage();
                }
            }
        }
        
        // Get members list for dropdown
        $members = $db->query("
            SELECT m.id, m.first_name, m.last_name 
            FROM members m 
            WHERE m.status = 'active'
            ORDER BY m.last_name
        ")->fetchAll();
        ?>
        <div class="container mx-auto px-4 py-6">
            <div class="max-w-3xl mx-auto">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-5 py-4 bg-gradient-to-r from-green-400 to-green-600">
                        <h3 class="text-lg font-bold text-white">
                            <i class="fas fa-plus-circle mr-2"></i>
                            New Deposit
                        </h3>
                        <p class="text-green-100 text-xs">Add funds to a member's savings account</p>
                    </div>
                    <div class="p-5">
                        <?php if (isset($error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2.5 rounded-lg mb-4 text-sm"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" class="space-y-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-user mr-2 text-gray-400"></i>
                                    Member <span class="text-red-500">*</span>
                                </label>
                                <select name="member_id" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-green-500" required>
                                    <option value="">Select a member</option>
                                    <?php foreach ($members as $member): ?>
                                    <option value="<?php echo $member['id']; ?>">
                                        <?php echo escape($member['last_name'] . ' ' . $member['first_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-money-bill mr-2 text-gray-400"></i>
                                    Amount (CDF) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="amount" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-green-500" min="1" required placeholder="Ex: 50000">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-file-alt mr-2 text-gray-400"></i>
                                    Description
                                </label>
                                <textarea name="description" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-green-500" rows="2" placeholder="Deposit description (optional)"></textarea>
                            </div>
                            
                            <div class="pt-3 border-t border-gray-200">
                                <button type="submit" class="bg-gradient-to-r from-green-400 to-green-600 text-white px-5 py-2 rounded-lg hover:from-green-500 hover:to-green-700">
                                    <i class="fas fa-save mr-2"></i>
                                    Make Deposit
                                </button>
                                <a href="savings_accounts.php" class="bg-gray-200 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-300">
                                    <i class="fas fa-times mr-2"></i>
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
        break;
    
    // ============================================
    // CREATE - New Withdrawal
    // ============================================
    case 'withdraw':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $member_id = intval($_POST['member_id']);
            $amount = floatval($_POST['amount']);
            $description = trim($_POST['description'] ?? 'Withdrawal');
            
            if ($amount <= 0) {
                $error = 'Amount must be greater than 0.';
            } else {
                try {
                    $db->beginTransaction();
                    
                    // Check current balance
                    $account = $db->prepare("SELECT * FROM savings_accounts WHERE member_id = :member_id");
                    $account->execute(['member_id' => $member_id]);
                    $account_data = $account->fetch();
                    
                    if (!$account_data) {
                        throw new Exception('Savings account not found.');
                    }
                    
                    if ($account_data['balance'] < $amount) {
                        throw new Exception('Insufficient balance. Available balance: ' . formatMoney($account_data['balance']));
                    }
                    
                    // Update balance
                    $db->prepare("
                        UPDATE savings_accounts 
                        SET balance = balance - :amount 
                        WHERE member_id = :member_id
                    ")->execute(['amount' => $amount, 'member_id' => $member_id]);
                    
                    // Record transaction
                    $db->prepare("
                        INSERT INTO transactions (savings_account_id, member_id, transaction_type, amount, description, created_by)
                        VALUES (:savings_account_id, :member_id, 'withdrawal', :amount, :description, :created_by)
                    ")->execute([
                        'savings_account_id' => $account_data['id'],
                        'member_id' => $member_id,
                        'amount' => $amount,
                        'description' => $description,
                        'created_by' => session_get('user_id')
                    ]);
                    
                    $db->commit();
                    logAction('WITHDRAW', 'savings_accounts', $account_data['id'], "Withdrawal of " . formatMoney($amount));
                    redirect('savings_accounts.php', 'Withdrawal completed successfully!', 'success');
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = $e->getMessage();
                }
            }
        }
        
        $members = $db->query("
            SELECT m.id, m.first_name, m.last_name, s.balance
            FROM members m 
            JOIN savings_accounts s ON m.id = s.member_id
            WHERE m.status = 'active'
            ORDER BY m.last_name
        ")->fetchAll();
        ?>
        <div class="container mx-auto px-4 py-6">
            <div class="max-w-3xl mx-auto">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-5 py-4 bg-gradient-to-r from-red-400 to-red-600">
                        <h3 class="text-lg font-bold text-white">
                            <i class="fas fa-minus-circle mr-2"></i>
                            New Withdrawal
                        </h3>
                        <p class="text-red-100 text-xs">Withdraw funds from a member's savings account</p>
                    </div>
                    <div class="p-5">
                        <?php if (isset($error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2.5 rounded-lg mb-4 text-sm"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" class="space-y-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-user mr-2 text-gray-400"></i>
                                    Member <span class="text-red-500">*</span>
                                </label>
                                <select name="member_id" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-red-500" required>
                                    <option value="">Select a member</option>
                                    <?php foreach ($members as $member): ?>
                                    <option value="<?php echo $member['id']; ?>">
                                        <?php echo escape($member['last_name'] . ' ' . $member['first_name']); ?>
                                        (Balance: <?php echo formatMoney($member['balance']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-money-bill mr-2 text-gray-400"></i>
                                    Amount (CDF) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="amount" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-red-500" min="1" required placeholder="Ex: 50000">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-file-alt mr-2 text-gray-400"></i>
                                    Description
                                </label>
                                <textarea name="description" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-red-500" rows="2" placeholder="Withdrawal description (optional)"></textarea>
                            </div>
                            
                            <div class="pt-3 border-t border-gray-200">
                                <button type="submit" class="bg-gradient-to-r from-red-400 to-red-600 text-white px-5 py-2 rounded-lg hover:from-red-500 hover:to-red-700">
                                    <i class="fas fa-save mr-2"></i>
                                    Make Withdrawal
                                </button>
                                <a href="savings_accounts.php" class="bg-gray-200 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-300">
                                    <i class="fas fa-times mr-2"></i>
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
        break;
    
    // ============================================
    // UPDATE - Edit Savings Account
    // ============================================
    case 'edit':
        $account = $db->prepare("
            SELECT s.*, m.first_name, m.last_name 
            FROM savings_accounts s
            JOIN members m ON s.member_id = m.id
            WHERE s.id = :id
        ")->execute(['id' => $id])->fetch();
        
        if (!$account) {
            redirect('savings_accounts.php', 'Account not found', 'danger');
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $status = $_POST['status'];
            
            try {
                $db->prepare("UPDATE savings_accounts SET status = :status WHERE id = :id")
                   ->execute(['status' => $status, 'id' => $id]);
                logAction('UPDATE', 'savings_accounts', $id, "Updated status to $status");
                redirect('savings_accounts.php', 'Account updated successfully!', 'success');
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        ?>
        <div class="container mx-auto px-4 py-6">
            <div class="max-w-3xl mx-auto">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-5 py-4 bg-gradient-to-r from-yellow-400 to-yellow-600">
                        <h3 class="text-lg font-bold text-white">
                            <i class="fas fa-edit mr-2"></i>
                            Edit Savings Account
                        </h3>
                        <p class="text-yellow-100 text-xs">Update savings account status</p>
                    </div>
                    <div class="p-5">
                        <?php if (isset($error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2.5 rounded-lg mb-4 text-sm"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" class="space-y-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-info-circle mr-2 text-gray-400"></i>
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select name="status" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500" required>
                                    <option value="active" <?php echo $account['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="frozen" <?php echo $account['status'] == 'frozen' ? 'selected' : ''; ?>>Frozen</option>
                                    <option value="closed" <?php echo $account['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                            
                            <div class="pt-3 border-t border-gray-200">
                                <button type="submit" class="bg-gradient-to-r from-yellow-400 to-yellow-600 text-white px-5 py-2 rounded-lg hover:from-yellow-500 hover:to-yellow-700">
                                    <i class="fas fa-save mr-2"></i>
                                    Update
                                </button>
                                <a href="savings_accounts.php" class="bg-gray-200 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-300">
                                    <i class="fas fa-times mr-2"></i>
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
        break;
    
    // ============================================
    // DELETE - Delete Savings Account
    // ============================================
    case 'delete':
        if ($id > 0) {
            try {
                $db->beginTransaction();
                $db->prepare("DELETE FROM savings_accounts WHERE id = :id")->execute(['id' => $id]);
                $db->commit();
                logAction('DELETE', 'savings_accounts', $id, "Deleted savings account");
                redirect('savings_accounts.php', 'Account deleted successfully!', 'success');
            } catch (Exception $e) {
                $db->rollBack();
                $error = $e->getMessage();
            }
        }
        break;
    
    // ============================================
    // READ - List Savings Accounts
    // ============================================
    default:
        ?>
        <div class="container mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-piggy-bank mr-2 text-green-500"></i>
                        <?php echo __('savings'); ?>
                    </h2>
                    <p class="text-gray-500 text-sm">Manage savings accounts and transactions</p>
                </div>
                <div class="flex space-x-2">
                    <a href="savings_accounts.php?action=deposit" class="bg-gradient-to-r from-green-400 to-green-600 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-plus mr-2"></i>
                        Deposit
                    </a>
                    <a href="savings_accounts.php?action=withdraw" class="bg-gradient-to-r from-red-400 to-red-600 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-minus mr-2"></i>
                        Withdraw
                    </a>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">N°</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Member</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account Number</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $accounts = $db->query("
                                SELECT s.*, m.first_name, m.last_name 
                                FROM savings_accounts s
                                JOIN members m ON s.member_id = m.id
                                WHERE m.status = 'active'
                                ORDER BY s.created_at DESC
                            ")->fetchAll();
                            
                            foreach ($accounts as $index => $account):
                            ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $index + 1; ?></td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm font-medium text-gray-800">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600 text-xs font-bold">
                                            <?php echo substr($account['first_name'], 0, 1) . substr($account['last_name'], 0, 1); ?>
                                        </div>
                                        <span class="ml-2"><?php echo escape($account['last_name'] . ' ' . $account['first_name']); ?></span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="font-mono bg-gray-100 px-2 py-1 rounded text-xs"><?php echo escape($account['account_number']); ?></span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-800 font-semibold">
                                    <?php echo formatMoney($account['balance']); ?>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap"><?php echo getStatusBadge($account['status']); ?></td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a href="savings_accounts.php?action=edit&id=<?php echo $account['id']; ?>" class="text-yellow-600 hover:text-yellow-900">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="savings_accounts.php?action=delete&id=<?php echo $account['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-3 bg-gray-50 border-t border-gray-200 text-center">
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-database mr-2"></i>
                        Total: <?php echo count($accounts); ?> accounts
                    </p>
                </div>
            </div>
        </div>
        <?php
        break;
}

include 'includes/footer.php';