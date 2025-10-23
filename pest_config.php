<?php
/**
 * Pest Configuration Management
 * Dynamic pest database management system
 */

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/security.php';

// Check admin access
if (!hasRole('admin')) {
    $_SESSION['error_message'] = 'Access denied. Admin privileges required.';
    header('Location: dashboard.php');
    exit();
}

$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'role' => $_SESSION['role']
];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        $pdo = getDatabaseConnection();
        $action = $_POST['action'];
        
        switch ($action) {
            case 'add_pest':
                $stmt = $pdo->prepare("
                    INSERT INTO pest_config 
                    (pest_name, common_name, pest_type, description, severity, economic_threshold, suggested_actions, remarks, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $result = $stmt->execute([
                    $_POST['pest_name'],
                    $_POST['common_name'],
                    $_POST['pest_type'],
                    $_POST['description'],
                    $_POST['severity'],
                    $_POST['economic_threshold'],
                    $_POST['suggested_actions'],
                    $_POST['remarks'],
                    $currentUser['id']
                ]);
                
                echo json_encode(['success' => $result, 'message' => 'Pest added successfully']);
                break;
                
            case 'update_pest':
                $stmt = $pdo->prepare("
                    UPDATE pest_config 
                    SET pest_name = ?, common_name = ?, pest_type = ?, description = ?, severity = ?, 
                        economic_threshold = ?, suggested_actions = ?, remarks = ?, updated_by = ?
                    WHERE id = ?
                ");
                $result = $stmt->execute([
                    $_POST['pest_name'],
                    $_POST['common_name'],
                    $_POST['pest_type'],
                    $_POST['description'],
                    $_POST['severity'],
                    $_POST['economic_threshold'],
                    $_POST['suggested_actions'],
                    $_POST['remarks'],
                    $currentUser['id'],
                    $_POST['id']
                ]);
                
                echo json_encode(['success' => $result, 'message' => 'Pest updated successfully']);
                break;
                
            case 'delete_pest':
                $stmt = $pdo->prepare("DELETE FROM pest_config WHERE id = ?");
                $result = $stmt->execute([$_POST['id']]);
                
                echo json_encode(['success' => $result, 'message' => 'Pest deleted successfully']);
                break;
                
            case 'toggle_active':
                $stmt = $pdo->prepare("UPDATE pest_config SET is_active = NOT is_active WHERE id = ?");
                $result = $stmt->execute([$_POST['id']]);
                
                echo json_encode(['success' => $result, 'message' => 'Status updated']);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get all pests
$search = $_GET['search'] ?? '';
$severity_filter = $_GET['severity'] ?? '';
$type_filter = $_GET['type'] ?? '';

$pdo = getDatabaseConnection();
$query = "SELECT * FROM pest_config WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (pest_name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($severity_filter) {
    $query .= " AND severity = ?";
    $params[] = $severity_filter;
}

if ($type_filter) {
    $query .= " AND pest_type = ?";
    $params[] = $type_filter;
}

$query .= " ORDER BY severity DESC, pest_name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pests = $stmt->fetchAll();

// Get statistics
$stats_stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
        SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high,
        SUM(CASE WHEN severity = 'medium' THEN 1 ELSE 0 END) as medium,
        SUM(CASE WHEN severity = 'low' THEN 1 ELSE 0 END) as low,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
    FROM pest_config
");
$stats = $stats_stmt->fetch();

// Get unique pest types
$types_stmt = $pdo->query("SELECT DISTINCT pest_type FROM pest_config ORDER BY pest_type");
$pest_types = $types_stmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Pest Configuration - IoT Farm Monitoring System';
include 'includes/header.php';
include 'includes/navigation.php';
?>

<div class="p-4 max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-bug text-yellow-600 mr-3"></i>
            Pest Configuration Database
        </h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Manage pest information, severity levels, and treatment recommendations
        </p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-6">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
            <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Pests</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['total']; ?></div>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
            <div class="text-xs text-red-600 dark:text-red-400 mb-1">Critical</div>
            <div class="text-2xl font-bold text-red-600 dark:text-red-400"><?php echo $stats['critical']; ?></div>
        </div>
        <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-3">
            <div class="text-xs text-orange-600 dark:text-orange-400 mb-1">High</div>
            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400"><?php echo $stats['high']; ?></div>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
            <div class="text-xs text-yellow-600 dark:text-yellow-400 mb-1">Medium</div>
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400"><?php echo $stats['medium']; ?></div>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3">
            <div class="text-xs text-green-600 dark:text-green-400 mb-1">Low</div>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400"><?php echo $stats['low']; ?></div>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
            <div class="text-xs text-blue-600 dark:text-blue-400 mb-1">Active</div>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?php echo $stats['active']; ?></div>
        </div>
    </div>

    <!-- Filters and Actions -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text" id="searchInput" placeholder="Search pests..." value="<?php echo htmlspecialchars($search); ?>"
                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <select id="severityFilter" class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                <option value="">All Severities</option>
                <option value="critical" <?php echo $severity_filter === 'critical' ? 'selected' : ''; ?>>Critical</option>
                <option value="high" <?php echo $severity_filter === 'high' ? 'selected' : ''; ?>>High</option>
                <option value="medium" <?php echo $severity_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                <option value="low" <?php echo $severity_filter === 'low' ? 'selected' : ''; ?>>Low</option>
            </select>
            <select id="typeFilter" class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                <option value="">All Types</option>
                <?php foreach ($pest_types as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $type_filter === $type ? 'selected' : ''; ?>>
                        <?php echo ucfirst($type); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button onclick="clearFilters()" class="px-4 py-2 bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700">
                <i class="fas fa-times mr-2"></i>Clear
            </button>
            <button onclick="openAddModal()" class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                <i class="fas fa-plus mr-2"></i>Add Pest
            </button>
        </div>
    </div>

    <!-- Pests Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Pest Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Type</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Severity</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Economic Threshold</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-700 dark:text-gray-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($pests)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>No pests found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pests as $pest): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($pest['common_name'] ?: $pest['pest_name']); ?>
                                    </div>
                                    <?php if ($pest['common_name']): ?>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 italic"><?php echo htmlspecialchars($pest['pest_name']); ?></div>
                                    <?php endif; ?>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs mt-1"><?php echo htmlspecialchars(substr($pest['description'], 0, 80)); ?>...</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs rounded">
                                        <?php echo ucfirst($pest['pest_type']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <?php
                                    $severity_colors = [
                                        'critical' => 'bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-300',
                                        'high' => 'bg-orange-100 dark:bg-orange-900/20 text-orange-800 dark:text-orange-300',
                                        'medium' => 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300',
                                        'low' => 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300'
                                    ];
                                    ?>
                                    <span class="px-2 py-1 <?php echo $severity_colors[$pest['severity']]; ?> text-xs font-medium rounded uppercase">
                                        <?php echo $pest['severity']; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    <?php echo htmlspecialchars($pest['economic_threshold'] ?: 'Not specified'); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 <?php echo $pest['is_active'] ? 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'; ?> text-xs rounded">
                                        <?php echo $pest['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <button onclick='viewPest(<?php echo json_encode($pest); ?>)' class="text-blue-600 hover:text-blue-800 dark:text-blue-400" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick='editPest(<?php echo json_encode($pest); ?>)' class="text-green-600 hover:text-green-800 dark:text-green-400" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="toggleActive(<?php echo $pest['id']; ?>)" class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400" title="Toggle Status">
                                            <i class="fas fa-toggle-on"></i>
                                        </button>
                                        <button onclick="deletePest(<?php echo $pest['id']; ?>, '<?php echo htmlspecialchars($pest['pest_name']); ?>')" class="text-red-600 hover:text-red-800 dark:text-red-400" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="pestModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white" id="modalTitle">Add Pest</h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="pestForm" class="space-y-4">
                <input type="hidden" id="pestId" name="id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Common Name (Filipino/English) *
                        <span class="text-xs text-gray-500 font-normal">- Easy to understand name</span>
                    </label>
                    <input type="text" id="pestCommonName" name="common_name" required
                        placeholder="e.g., Uod ng Mais (Corn Borer)"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Scientific Name *
                            <span class="text-xs text-gray-500 font-normal">- Must match YOLO model</span>
                        </label>
                        <input type="text" id="pestName" name="pest_name" required
                            placeholder="e.g., corn borer"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pest Type *</label>
                        <select id="pestType" name="pest_type" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="insect">Insect</option>
                            <option value="mite">Mite</option>
                            <option value="beetle">Beetle</option>
                            <option value="moth">Moth</option>
                            <option value="fly">Fly</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                    <textarea id="pestDescription" name="description" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Severity *</label>
                        <select id="pestSeverity" name="severity" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Economic Threshold</label>
                        <input type="text" id="pestThreshold" name="economic_threshold"
                            placeholder="e.g., 10 per plant"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Suggested Actions *</label>
                    <textarea id="pestActions" name="suggested_actions" rows="4" required
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Remarks</label>
                    <textarea id="pestRemarks" name="remarks" rows="2"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-save mr-2"></i>Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white" id="viewPestName"></h3>
                <button onclick="closeViewModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Pest Type</div>
                        <div class="font-medium text-gray-900 dark:text-white" id="viewPestType"></div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Severity</div>
                        <div id="viewPestSeverity"></div>
                    </div>
                </div>
                
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Description</div>
                    <div class="text-gray-900 dark:text-white" id="viewPestDescription"></div>
                </div>
                
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Economic Threshold</div>
                    <div class="text-gray-900 dark:text-white" id="viewPestThreshold"></div>
                </div>
                
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Suggested Actions</div>
                    <div class="text-gray-900 dark:text-white whitespace-pre-wrap" id="viewPestActions"></div>
                </div>
                
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Remarks</div>
                    <div class="text-gray-900 dark:text-white" id="viewPestRemarks"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const severity = document.getElementById('severityFilter').value;
    const type = document.getElementById('typeFilter').value;
    
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (severity) params.append('severity', severity);
    if (type) params.append('type', type);
    
    window.location.href = 'pest_config.php?' + params.toString();
}

function clearFilters() {
    window.location.href = 'pest_config.php';
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New Pest';
    document.getElementById('pestForm').reset();
    document.getElementById('pestId').value = '';
    document.getElementById('pestModal').classList.remove('hidden');
}

function editPest(pest) {
    document.getElementById('modalTitle').textContent = 'Edit Pest';
    document.getElementById('pestId').value = pest.id;
    document.getElementById('pestCommonName').value = pest.common_name || '';
    document.getElementById('pestName').value = pest.pest_name;
    document.getElementById('pestType').value = pest.pest_type;
    document.getElementById('pestDescription').value = pest.description || '';
    document.getElementById('pestSeverity').value = pest.severity;
    document.getElementById('pestThreshold').value = pest.economic_threshold || '';
    document.getElementById('pestActions').value = pest.suggested_actions;
    document.getElementById('pestRemarks').value = pest.remarks || '';
    document.getElementById('pestModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('pestModal').classList.add('hidden');
}

function viewPest(pest) {
    const severityColors = {
        'critical': 'px-2 py-1 bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-xs font-medium rounded uppercase',
        'high': 'px-2 py-1 bg-orange-100 dark:bg-orange-900/20 text-orange-800 dark:text-orange-300 text-xs font-medium rounded uppercase',
        'medium': 'px-2 py-1 bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300 text-xs font-medium rounded uppercase',
        'low': 'px-2 py-1 bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-xs font-medium rounded uppercase'
    };
    
    // Show common name prominently, scientific name as subtitle
    const displayName = pest.common_name || pest.pest_name;
    const subtitle = pest.common_name ? `<div class="text-sm text-gray-500 dark:text-gray-400 italic mt-1">Scientific: ${pest.pest_name}</div>` : '';
    
    document.getElementById('viewPestName').innerHTML = displayName + subtitle;
    document.getElementById('viewPestType').textContent = pest.pest_type.charAt(0).toUpperCase() + pest.pest_type.slice(1);
    document.getElementById('viewPestSeverity').innerHTML = `<span class="${severityColors[pest.severity]}">${pest.severity}</span>`;
    document.getElementById('viewPestDescription').textContent = pest.description || 'No description provided';
    document.getElementById('viewPestThreshold').textContent = pest.economic_threshold || 'Not specified';
    document.getElementById('viewPestActions').textContent = pest.suggested_actions;
    document.getElementById('viewPestRemarks').textContent = pest.remarks || 'No remarks';
    document.getElementById('viewModal').classList.remove('hidden');
}

function closeViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
}

document.getElementById('pestForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const pestId = document.getElementById('pestId').value;
    formData.append('action', pestId ? 'update_pest' : 'add_pest');
    
    try {
        const response = await fetch('pest_config.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            window.location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
});

async function toggleActive(id) {
    if (!confirm('Toggle pest status?')) return;
    
    const formData = new FormData();
    formData.append('action', 'toggle_active');
    formData.append('id', id);
    
    try {
        const response = await fetch('pest_config.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function deletePest(id, name) {
    if (!confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_pest');
    formData.append('id', id);
    
    try {
        const response = await fetch('pest_config.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            window.location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

// Debounce function
let searchTimeout;
function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500); // 500ms delay
}

// Auto-search with debounce on input
document.getElementById('searchInput').addEventListener('input', debounceSearch);

// Auto-apply filters with debounce on dropdown change
document.getElementById('severityFilter').addEventListener('change', debounceSearch);
document.getElementById('typeFilter').addEventListener('change', debounceSearch);

// Enter key to search immediately
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        clearTimeout(searchTimeout);
        applyFilters();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
