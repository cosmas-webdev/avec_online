<?php
/**
 * Members Management
 * 
 * Professional CRUD operations for members.
 * 
 * @author Ir. Cosmas MUSAFIRI MUGONGO
 * @version 1.0.0
 */

require_once 'includes/auth.php';
requireLogin();

$page_title = __('members');
include 'includes/header.php';

$db = db();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

// ============================================
// CRUD ACTIONS
// ============================================

switch ($action) {
    
    // ============================================
    // CREATE - Add Member
    // ============================================
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $group_id = intval($_POST['group_id']);
            
            // Validation
            $errors = [];
            if (empty($first_name)) $errors[] = 'First name is required.';
            if (empty($last_name)) $errors[] = 'Last name is required.';
            if (empty($email)) $errors[] = 'Email is required.';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
            
            if (empty($errors)) {
                try {
                    $db->beginTransaction();
                    
                    // Check if email already exists
                    $check = $db->prepare("SELECT id FROM users WHERE email = :email");
                    $check->execute(['email' => $email]);
                    if ($check->fetch()) {
                        throw new Exception('Email already exists.');
                    }
                    
                    // Create user account
                    $stmt = $db->prepare("
                        INSERT INTO users (name, email, password, role_id, group_id) 
                        VALUES (:name, :email, :password, 4, :group_id)
                    ");
                    $stmt->execute([
                        'name' => $first_name . ' ' . $last_name,
                        'email' => $email,
                        'password' => 'password123',
                        'group_id' => $group_id
                    ]);
                    $user_id = $db->lastInsertId();
                    
                    // Create member
                    $stmt = $db->prepare("
                        INSERT INTO members (user_id, group_id, first_name, last_name, phone, address, joined_date) 
                        VALUES (:user_id, :group_id, :first_name, :last_name, :phone, :address, NOW())
                    ");
                    $stmt->execute([
                        'user_id' => $user_id,
                        'group_id' => $group_id,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'phone' => $phone,
                        'address' => $address
                    ]);
                    $member_id = $db->lastInsertId();
                    
                    // Create savings account
                    $db->prepare("
                        INSERT INTO savings_accounts (member_id, account_number, balance) 
                        VALUES (:member_id, :account_number, 0)
                    ")->execute([
                        'member_id' => $member_id,
                        'account_number' => generateReference('SAV')
                    ]);
                    
                    $db->commit();
                    logAction('CREATE', 'members', $member_id, "New member: $first_name $last_name");
                    redirect('members.php', 'Member added successfully!', 'success');
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = $e->getMessage();
                }
            } else {
                $error = implode('<br>', $errors);
            }
        }
        
        // Get groups for dropdown
        $groups = $db->query("SELECT * FROM groups ORDER BY name")->fetchAll();
        ?>
        <div class="container mx-auto px-4 py-6">
            <div class="max-w-3xl mx-auto">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-5 py-4 bg-gradient-to-r from-indigo-600 to-purple-600">
                        <h3 class="text-lg font-bold text-white">
                            <i class="fas fa-user-plus mr-2"></i>
                            <?php echo __('add_member'); ?>
                        </h3>
                        <p class="text-indigo-200 text-xs">Fill in the information below to register a new member</p>
                    </div>
                    <div class="p-5">
                        <?php if (isset($error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2.5 rounded-lg mb-4 text-sm"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">
                                        <i class="fas fa-user mr-2 text-gray-400"></i>
                                        <?php echo __('first_name'); ?> <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="first_name" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500" required>
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">
                                        <i class="fas fa-user mr-2 text-gray-400"></i>
                                        <?php echo __('last_name'); ?> <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="last_name" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500" required>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-envelope mr-2 text-gray-400"></i>
                                    <?php echo __('email'); ?> <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="email" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500" required>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-phone mr-2 text-gray-400"></i>
                                    <?php echo __('phone'); ?>
                                </label>
                                <input type="text" name="phone" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>
                                    <?php echo __('address'); ?>
                                </label>
                                <textarea name="address" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500" rows="2"></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-layer-group mr-2 text-gray-400"></i>
                                    <?php echo __('group'); ?> <span class="text-red-500">*</span>
                                </label>
                                <select name="group_id" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500" required>
                                    <option value="">Select a group</option>
                                    <?php foreach ($groups as $group): ?>
                                    <option value="<?php echo $group['id']; ?>"><?php echo escape($group['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="pt-3 border-t border-gray-200">
                                <button type="submit" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-5 py-2 rounded-lg hover:from-indigo-700 hover:to-purple-700">
                                    <i class="fas fa-save mr-2"></i>
                                    <?php echo __('save'); ?>
                                </button>
                                <a href="members.php" class="bg-gray-200 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-300">
                                    <i class="fas fa-times mr-2"></i>
                                    <?php echo __('cancel'); ?>
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
    // UPDATE - Edit Member
    // ============================================
    case 'edit':
        $member = $db->prepare("
            SELECT m.*, u.email, u.id as user_id 
            FROM members m 
            JOIN users u ON m.user_id = u.id 
            WHERE m.id = :id
        ")->execute(['id' => $id])->fetch();
        
        if (!$member) {
            redirect('members.php', 'Member not found', 'danger');
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $group_id = intval($_POST['group_id']);
            $status = $_POST['status'];
            
            try {
                $db->beginTransaction();
                $db->prepare("
                    UPDATE members SET 
                    first_name = :first_name, 
                    last_name = :last_name, 
                    phone = :phone, 
                    address = :address, 
                    group_id = :group_id, 
                    status = :status 
                    WHERE id = :id
                ")->execute([
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'phone' => $phone,
                    'address' => $address,
                    'group_id' => $group_id,
                    'status' => $status,
                    'id' => $id
                ]);
                $db->commit();
                logAction('UPDATE', 'members', $id, "Updated member: $first_name $last_name");
                redirect('members.php', 'Member updated successfully!', 'success');
            } catch (Exception $e) {
                $db->rollBack();
                $error = $e->getMessage();
            }
        }
        ?>
        <div class="container mx-auto px-4 py-6">
            <div class="max-w-3xl mx-auto">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-5 py-4 bg-gradient-to-r from-indigo-600 to-purple-600">
                        <h3 class="text-lg font-bold text-white">
                            <i class="fas fa-user-edit mr-2"></i>
                            <?php echo __('edit_member'); ?>
                        </h3>
                        <p class="text-indigo-200 text-xs">Update member information</p>
                    </div>
                    <div class="p-5">
                        <?php if (isset($error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2.5 rounded-lg mb-4 text-sm"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">
                                        <i class="fas fa-user mr-2 text-gray-400"></i>
                                        <?php echo __('first_name'); ?> <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="first_name" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500" value="<?php echo escape($member['first_name']); ?>" required>
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">
                                        <i class="fas fa-user mr-2 text-gray-400"></i>
                                        <?php echo __('last_name'); ?> <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="last_name" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500" value="<?php echo escape($member['last_name']); ?>" required>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-envelope mr-2 text-gray-400"></i>
                                    <?php echo __('email'); ?> (read-only)
                                </label>
                                <input type="email" class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100" value="<?php echo escape($member['email']); ?>" disabled>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-phone mr-2 text-gray-400"></i>
                                    <?php echo __('phone'); ?>
                                </label>
                                <input type="text" name="phone" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500" value="<?php echo escape($member['phone']); ?>">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">
                                    <i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>
                                    <?php echo __('address'); ?>
                                </label>
                                <textarea name="address" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500" rows="2"><?php echo escape($member['address']); ?></textarea>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">
                                        <i class="fas fa-layer-group mr-2 text-gray-400"></i>
                                        <?php echo __('group'); ?>
                                    </label>
                                    <select name="group_id" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500">
                                        <?php
                                        $groups = $db->query("SELECT * FROM groups ORDER BY name")->fetchAll();
                                        foreach ($groups as $group):
                                        ?>
                                        <option value="<?php echo $group['id']; ?>" <?php echo $group['id'] == $member['group_id'] ? 'selected' : ''; ?>>
                                            <?php echo escape($group['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">
                                        <i class="fas fa-info-circle mr-2 text-gray-400"></i>
                                        <?php echo __('status'); ?>
                                    </label>
                                    <select name="status" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-indigo-500">
                                        <option value="active" <?php echo $member['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $member['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="suspended" <?php echo $member['status'] == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                        <option value="left" <?php echo $member['status'] == 'left' ? 'selected' : ''; ?>>Left</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="pt-3 border-t border-gray-200">
                                <button type="submit" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-5 py-2 rounded-lg hover:from-indigo-700 hover:to-purple-700">
                                    <i class="fas fa-save mr-2"></i>
                                    <?php echo __('save'); ?>
                                </button>
                                <a href="members.php" class="bg-gray-200 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-300">
                                    <i class="fas fa-times mr-2"></i>
                                    <?php echo __('cancel'); ?>
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
    // DELETE - Delete Member
    // ============================================
    case 'delete':
        if ($id > 0) {
            try {
                $db->beginTransaction();
                
                // Get member details for logging
                $member = $db->prepare("SELECT * FROM members WHERE id = :id");
                $member->execute(['id' => $id]);
                $member_data = $member->fetch();
                
                if (!$member_data) {
                    throw new Exception('Member not found.');
                }
                
                // Delete member (cascade will delete user and savings account)
                $db->prepare("DELETE FROM members WHERE id = :id")->execute(['id' => $id]);
                
                $db->commit();
                logAction('DELETE', 'members', $id, "Deleted member: {$member_data['first_name']} {$member_data['last_name']}");
                redirect('members.php', 'Member deleted successfully!', 'success');
            } catch (Exception $e) {
                $db->rollBack();
                $error = $e->getMessage();
            }
        }
        break;
    
    // ============================================
    // READ - List Members
    // ============================================
    default:
        ?>
        <div class="container mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-users mr-2 text-indigo-500"></i>
                        <?php echo __('member_list'); ?>
                    </h2>
                    <p class="text-gray-500 text-sm">Manage all members of your organization</p>
                </div>
                <a href="members.php?action=add" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-4 py-2 rounded-lg hover:from-indigo-700 hover:to-purple-700">
                    <i class="fas fa-plus mr-2"></i>
                    <?php echo __('add_member'); ?>
                </a>
            </div>
            
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">N°</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo __('last_name'); ?></th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo __('first_name'); ?></th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo __('phone'); ?></th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo __('group'); ?></th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo __('status'); ?></th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $members = $db->query("
                                SELECT m.*, g.name as group_name 
                                FROM members m 
                                LEFT JOIN groups g ON m.group_id = g.id 
                                ORDER BY m.created_at DESC
                            ")->fetchAll();
                            
                            foreach ($members as $index => $member):
                            ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $index + 1; ?></td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm font-medium text-gray-800"><?php echo escape($member['last_name']); ?></td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo escape($member['first_name']); ?></td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <i class="fas fa-phone text-gray-400 mr-1"></i>
                                    <?php echo escape($member['phone'] ?? '—'); ?>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo escape($member['group_name'] ?? 'No group'); ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap"><?php echo getStatusBadge($member['status']); ?></td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a href="members.php?action=edit&id=<?php echo $member['id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="members.php?action=delete&id=<?php echo $member['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">
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
                        Total: <?php echo count($members); ?> members
                    </p>
                </div>
            </div>
        </div>
        <?php
        break;
}

include 'includes/footer.php';