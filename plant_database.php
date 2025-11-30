<?php
/**
 * Plant Database Management
 * Dynamic plant monitoring configuration system
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
            case 'add_plant':
                $stmt = $pdo->prepare("
                    INSERT INTO plants 
                    (PlantName, LocalName, MinSoilMoisture, MaxSoilMoisture, MinTemperature, MaxTemperature, 
                     MinHumidity, MaxHumidity, WarningTrigger, SuggestedAction)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $result = $stmt->execute([
                    $_POST['plant_name'],
                    $_POST['local_name'],
                    $_POST['min_soil'],
                    $_POST['max_soil'],
                    $_POST['min_temp'],
                    $_POST['max_temp'],
                    $_POST['min_humidity'],
                    $_POST['max_humidity'],
                    $_POST['warning_trigger'] ?? 5,
                    $_POST['suggested_action']
                ]);
                
                echo json_encode(['success' => $result, 'message' => 'Plant added successfully']);
                break;
                
            case 'update_plant':
                $stmt = $pdo->prepare("
                    UPDATE plants 
                    SET PlantName = ?, LocalName = ?, MinSoilMoisture = ?, MaxSoilMoisture = ?,
                        MinTemperature = ?, MaxTemperature = ?, MinHumidity = ?, MaxHumidity = ?,
                        WarningTrigger = ?, SuggestedAction = ?
                    WHERE PlantID = ?
                ");
                $result = $stmt->execute([
                    $_POST['plant_name'],
                    $_POST['local_name'],
                    $_POST['min_soil'],
                    $_POST['max_soil'],
                    $_POST['min_temp'],
                    $_POST['max_temp'],
                    $_POST['min_humidity'],
                    $_POST['max_humidity'],
                    $_POST['warning_trigger'] ?? 5,
                    $_POST['suggested_action'],
                    $_POST['id']
                ]);
                
                echo json_encode(['success' => $result, 'message' => 'Plant updated successfully']);
                break;
                
            case 'delete_plant':
                $stmt = $pdo->prepare("DELETE FROM plants WHERE PlantID = ?");
                $result = $stmt->execute([$_POST['id']]);
                
                echo json_encode(['success' => $result, 'message' => 'Plant deleted successfully']);
                break;
                
            case 'set_active':
                // Update active plant
                $pdo->exec("DELETE FROM activeplant");
                $stmt = $pdo->prepare("INSERT INTO activeplant (SelectedPlantID) VALUES (?)");
                $result = $stmt->execute([$_POST['id']]);
                
                echo json_encode(['success' => $result, 'message' => 'Active plant updated successfully']);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get all plants
$search = $_GET['search'] ?? '';

$pdo = getDatabaseConnection();
$query = "SELECT * FROM plants WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (PlantName LIKE ? OR LocalName LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY PlantName ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$plants = $stmt->fetchAll();

// Get active plant
$stmt = $pdo->query("SELECT SelectedPlantID FROM activeplant LIMIT 1");
$activePlant = $stmt->fetch();
$activePlantID = $activePlant ? $activePlant['SelectedPlantID'] : null;

// Get statistics
$stats_stmt = $pdo->query("SELECT COUNT(*) as total FROM plants");
$stats = $stats_stmt->fetch();

$pageTitle = 'Plant Database - IoT Farm Monitoring System';
include 'includes/header.php';
include 'includes/navigation.php';
?>

<div class="p-4 max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-seedling text-green-600 mr-3"></i>
            Plant Database Management
        </h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Manage plant profiles with sensor thresholds and recommended actions
        </p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
            <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Plants</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['total']; ?></div>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3">
            <div class="text-xs text-green-600 dark:text-green-400 mb-1">Active Plant</div>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400"><?php echo $activePlantID ? '1' : '0'; ?></div>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
            <div class="text-xs text-blue-600 dark:text-blue-400 mb-1">Monitoring</div>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-3">
            <div class="text-xs text-purple-600 dark:text-purple-400 mb-1">Sensors</div>
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">3</div>
        </div>
    </div>

    <!-- Filters and Actions -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text" id="searchInput" placeholder="Search plants..." value="<?php echo htmlspecialchars($search); ?>"
                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            <button onclick="clearFilters()" class="px-4 py-2 bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700">
                <i class="fas fa-times mr-2"></i>Clear
            </button>
            <button onclick="openAddModal()" class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                <i class="fas fa-plus mr-2"></i>Add Plant
            </button>
        </div>
    </div>

    <!-- Plants Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Plant Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Soil Moisture (%)</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Temperature (°C)</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Humidity (%)</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-700 dark:text-gray-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($plants)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>No plants found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($plants as $plant): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($plant['PlantName']); ?>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 italic">
                                        <?php echo htmlspecialchars($plant['LocalName']); ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    <?php echo $plant['MinSoilMoisture']; ?>-<?php echo $plant['MaxSoilMoisture']; ?>%
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    <?php echo $plant['MinTemperature']; ?>-<?php echo $plant['MaxTemperature']; ?>°C
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    <?php echo $plant['MinHumidity']; ?>-<?php echo $plant['MaxHumidity']; ?>%
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($activePlantID == $plant['PlantID']): ?>
                                        <span class="px-2 py-1 bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-xs font-medium rounded uppercase">
                                            <i class="fas fa-check-circle mr-1"></i>ACTIVE
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs rounded">
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <button onclick='viewPlant(<?php echo json_encode($plant); ?>)' class="text-blue-600 hover:text-blue-800 dark:text-blue-400" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick='editPlant(<?php echo json_encode($plant); ?>)' class="text-green-600 hover:text-green-800 dark:text-green-400" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="setActive(<?php echo $plant['PlantID']; ?>, '<?php echo htmlspecialchars($plant['PlantName']); ?>')" 
                                                class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400" title="Set as Active">
                                            <i class="fas fa-toggle-on"></i>
                                        </button>
                                        <button onclick="deletePlant(<?php echo $plant['PlantID']; ?>, '<?php echo htmlspecialchars($plant['PlantName']); ?>')" 
                                                class="text-red-600 hover:text-red-800 dark:text-red-400" title="Delete">
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
<div id="plantModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white" id="modalTitle">Add Plant</h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="plantForm" class="space-y-4">
                <input type="hidden" id="plantId" name="id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Plant Name *
                        </label>
                        <input type="text" id="plantName" name="plant_name" required
                            placeholder="e.g., Tomato"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Local Name (Filipino) *
                        </label>
                        <input type="text" id="localName" name="local_name" required
                            placeholder="e.g., Kamatis"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
                
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-tint text-blue-500 mr-2"></i>Soil Moisture Thresholds (%)
                    </h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Minimum *</label>
                            <input type="number" id="minSoil" name="min_soil" min="0" max="100" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Maximum *</label>
                            <input type="number" id="maxSoil" name="max_soil" min="0" max="100" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-thermometer-half text-red-500 mr-2"></i>Temperature Thresholds (°C)
                    </h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Minimum *</label>
                            <input type="number" id="minTemp" name="min_temp" step="0.1" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Maximum *</label>
                            <input type="number" id="maxTemp" name="max_temp" step="0.1" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-cloud text-cyan-500 mr-2"></i>Humidity Thresholds (%)
                    </h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Minimum *</label>
                            <input type="number" id="minHumidity" name="min_humidity" min="0" max="100" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Maximum *</label>
                            <input type="number" id="maxHumidity" name="max_humidity" min="0" max="100" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Warning Trigger *
                        <span class="text-xs text-gray-500 font-normal">- Violations before alert</span>
                    </label>
                    <input type="number" id="warningTrigger" name="warning_trigger" value="5" min="1" required
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Suggested Action *</label>
                    <textarea id="suggestedAction" name="suggested_action" rows="4" required
                        placeholder="Enter recommended actions when thresholds are violated..."
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500"></textarea>
                </div>
                
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
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
                <h3 class="text-xl font-bold text-gray-900 dark:text-white" id="viewPlantName"></h3>
                <button onclick="closeViewModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                        <div class="text-sm text-blue-600 dark:text-blue-400 mb-2">
                            <i class="fas fa-tint mr-1"></i>Soil Moisture
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white" id="viewSoilRange"></div>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                        <div class="text-sm text-red-600 dark:text-red-400 mb-2">
                            <i class="fas fa-thermometer-half mr-1"></i>Temperature
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white" id="viewTempRange"></div>
                    </div>
                    <div class="bg-cyan-50 dark:bg-cyan-900/20 p-4 rounded-lg">
                        <div class="text-sm text-cyan-600 dark:text-cyan-400 mb-2">
                            <i class="fas fa-cloud mr-1"></i>Humidity
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white" id="viewHumidityRange"></div>
                    </div>
                </div>
                
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Warning Trigger</div>
                    <div class="text-gray-900 dark:text-white" id="viewWarningTrigger"></div>
                </div>
                
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Suggested Action</div>
                    <div class="text-gray-900 dark:text-white whitespace-pre-wrap bg-gray-50 dark:bg-gray-700 p-3 rounded-lg" id="viewSuggestedAction"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    window.location.href = 'plant_database.php?' + params.toString();
}

function clearFilters() {
    window.location.href = 'plant_database.php';
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New Plant';
    document.getElementById('plantForm').reset();
    document.getElementById('plantId').value = '';
    document.getElementById('warningTrigger').value = '5';
    document.getElementById('plantModal').classList.remove('hidden');
}

function editPlant(plant) {
    document.getElementById('modalTitle').textContent = 'Edit Plant';
    document.getElementById('plantId').value = plant.PlantID;
    document.getElementById('plantName').value = plant.PlantName;
    document.getElementById('localName').value = plant.LocalName;
    document.getElementById('minSoil').value = plant.MinSoilMoisture;
    document.getElementById('maxSoil').value = plant.MaxSoilMoisture;
    document.getElementById('minTemp').value = plant.MinTemperature;
    document.getElementById('maxTemp').value = plant.MaxTemperature;
    document.getElementById('minHumidity').value = plant.MinHumidity;
    document.getElementById('maxHumidity').value = plant.MaxHumidity;
    document.getElementById('warningTrigger').value = plant.WarningTrigger;
    document.getElementById('suggestedAction').value = plant.SuggestedAction;
    document.getElementById('plantModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('plantModal').classList.add('hidden');
}

function viewPlant(plant) {
    const displayName = plant.PlantName;
    const subtitle = `<div class="text-sm text-gray-500 dark:text-gray-400 italic mt-1">${plant.LocalName}</div>`;
    
    document.getElementById('viewPlantName').innerHTML = displayName + subtitle;
    document.getElementById('viewSoilRange').textContent = `${plant.MinSoilMoisture}-${plant.MaxSoilMoisture}%`;
    document.getElementById('viewTempRange').textContent = `${plant.MinTemperature}-${plant.MaxTemperature}°C`;
    document.getElementById('viewHumidityRange').textContent = `${plant.MinHumidity}-${plant.MaxHumidity}%`;
    document.getElementById('viewWarningTrigger').textContent = `${plant.WarningTrigger} violations`;
    document.getElementById('viewSuggestedAction').textContent = plant.SuggestedAction;
    document.getElementById('viewModal').classList.remove('hidden');
}

function closeViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
}

document.getElementById('plantForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const plantId = document.getElementById('plantId').value;
    formData.append('action', plantId ? 'update_plant' : 'add_plant');
    
    try {
        const response = await fetch('plant_database.php', {
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

async function setActive(id, name) {
    if (!confirm(`Set "${name}" as the active plant for monitoring?`)) return;
    
    const formData = new FormData();
    formData.append('action', 'set_active');
    formData.append('id', id);
    
    try {
        const response = await fetch('plant_database.php', {
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

async function deletePlant(id, name) {
    if (!confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_plant');
    formData.append('id', id);
    
    try {
        const response = await fetch('plant_database.php', {
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
    }, 500);
}

// Auto-search with debounce on input
document.getElementById('searchInput').addEventListener('input', debounceSearch);

// Enter key to search immediately
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        clearTimeout(searchTimeout);
        applyFilters();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
