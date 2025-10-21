    <?php
/**
 * Learning Resources Page
 * Educational content for students about pest management and smart farming
 */

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'email' => $_SESSION['email'] ?? '',
    'role' => $_SESSION['role'] ?? 'student'
];

$pageTitle = 'Learning Resources - IoT Farm Monitoring System';
include 'includes/header.php';
include 'includes/navigation.php';
?>

<div class="p-4 max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white mb-2 flex items-center">
                    <i class="fas fa-graduation-cap text-green-600 mr-3"></i>
                    Learning Resources
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Comprehensive educational materials about IoT agriculture, AI pest detection, and smart farming technologies</p>
            </div>
        </div>
        
        <!-- Breadcrumb -->
        <nav class="flex text-xs text-gray-500 dark:text-gray-400">
            <a href="dashboard.php" class="hover:text-green-600 dark:hover:text-green-400">Dashboard</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900 dark:text-white">Learning Resources</span>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Left Column - Main Content -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Pest Identification Guide -->
            <div id="pest-guide" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                    <i class="fas fa-bug text-red-600 mr-2 text-sm"></i>
                    Common Pest Identification Guide
                </h2>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Learn about common agricultural pests detected by our AI system. Understanding these pests helps in early identification and effective management.
                </p>

                <div class="space-y-3">
                    <!-- Aphids -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 hover:shadow-md transition-shadow">
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-bug text-red-600 dark:text-red-400 text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Aphids (Aphis spp.)</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    Small, soft-bodied insects (1-3mm) that feed on plant sap. Usually found on new growth and undersides of leaves. Can be green, black, brown, or pink.
                                </p>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded p-2 mb-2">
                                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">📊 Key Facts:</p>
                                    <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                        <li>• Reproduce rapidly - one female can produce 80 offspring in a week</li>
                                        <li>• Transmit plant viruses while feeding</li>
                                        <li>• Excrete honeydew that attracts ants and causes sooty mold</li>
                                        <li>• Detected by YOLO model with 92% accuracy</li>
                                    </ul>
                                </div>
                                <div class="flex flex-wrap gap-2 text-xs">
                                    <span class="px-2 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded">Critical Risk</span>
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded">Sap Feeder</span>
                                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded">AI Detectable</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Whiteflies -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 hover:shadow-md transition-shadow">
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-bug text-orange-600 dark:text-orange-400 text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Whiteflies (Bemisia tabaci)</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    Tiny white flying insects (1-2mm) that cluster on leaf undersides. Major agricultural pest that can transmit over 100 plant viruses.
                                </p>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded p-2 mb-2">
                                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">📊 Key Facts:</p>
                                    <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                        <li>• Life cycle: 18-30 days depending on temperature</li>
                                        <li>• Vectors of geminiviruses and criniviruses</li>
                                        <li>• Cause yellowing, stunting, and leaf curling</li>
                                        <li>• Detected by YOLO model with 89% accuracy</li>
                                    </ul>
                                </div>
                                <div class="flex flex-wrap gap-2 text-xs">
                                    <span class="px-2 py-1 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded">High Risk</span>
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded">Disease Vector</span>
                                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded">AI Detectable</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Caterpillars -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 hover:shadow-md transition-shadow">
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-bug text-yellow-600 dark:text-yellow-400 text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Caterpillars (Lepidoptera larvae)</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    Larvae of moths and butterflies. Chew leaves and can cause significant defoliation. Size varies from 5mm to 50mm depending on species.
                                </p>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded p-2 mb-2">
                                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">📊 Key Facts:</p>
                                    <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                        <li>• Larval stage lasts 2-5 weeks</li>
                                        <li>• Can consume 200-300 times their body weight</li>
                                        <li>• Active feeders during night and early morning</li>
                                        <li>• Detected by YOLO model with 94% accuracy</li>
                                    </ul>
                                </div>
                                <div class="flex flex-wrap gap-2 text-xs">
                                    <span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded">Medium Risk</span>
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded">Leaf Chewer</span>
                                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded">AI Detectable</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Thrips -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 hover:shadow-md transition-shadow">
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-bug text-purple-600 dark:text-purple-400 text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Thrips (Thysanoptera)</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    Slender insects (1-2mm) with fringed wings. Feed by puncturing plant cells and sucking contents, causing silvery streaks on leaves.
                                </p>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded p-2 mb-2">
                                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">📊 Key Facts:</p>
                                    <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                        <li>• Complete life cycle in 2-3 weeks</li>
                                        <li>• Transmit tospoviruses (e.g., TSWV)</li>
                                        <li>• Cause leaf distortion and flower damage</li>
                                        <li>• Detected by YOLO model with 87% accuracy</li>
                                    </ul>
                                </div>
                                <div class="flex flex-wrap gap-2 text-xs">
                                    <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded">High Risk</span>
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded">Cell Feeder</span>
                                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded">AI Detectable</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- IPM Principles -->
            <div id="ipm" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-leaf text-green-600 mr-2"></i>
                    Integrated Pest Management (IPM)
                </h2>
                
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    IPM is an ecosystem-based strategy that focuses on long-term prevention of pests through a combination of biological, cultural, physical, and chemical techniques. This approach minimizes economic, health, and environmental risks while maintaining effective pest control.
                </p>

                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-6">
                    <h4 class="font-semibold text-green-900 dark:text-green-200 mb-2 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i>Why IPM Matters
                    </h4>
                    <ul class="text-sm text-green-800 dark:text-green-300 space-y-1 list-disc list-inside">
                        <li>Reduces pesticide use by 50-70% compared to conventional methods</li>
                        <li>Protects beneficial insects and pollinators</li>
                        <li>Prevents pest resistance to chemical treatments</li>
                        <li>Lowers production costs by $200-500 per hectare annually</li>
                        <li>Improves crop quality and marketability</li>
                    </ul>
                </div>

                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">The Four Pillars of IPM</h3>
                <div class="space-y-4 mb-6">
                    <div class="border-l-4 border-green-500 bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-green-600 dark:text-green-400 font-bold text-lg">1</span>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Prevention (Cultural Control)</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Create conditions unfavorable for pests:</p>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 list-disc list-inside ml-4 space-y-1">
                                    <li>Select pest-resistant crop varieties (e.g., BT corn, resistant tomatoes)</li>
                                    <li>Maintain healthy soil with proper pH (6.0-7.0) and organic matter</li>
                                    <li>Practice crop rotation (3-4 year cycles)</li>
                                    <li>Proper spacing for air circulation (reduces humidity-related diseases)</li>
                                    <li>Remove crop residues and weeds that harbor pests</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="border-l-4 border-blue-500 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-blue-600 dark:text-blue-400 font-bold text-lg">2</span>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Monitoring & Identification</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Early detection through systematic observation:</p>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 list-disc list-inside ml-4 space-y-1">
                                    <li>IoT sensors monitor temperature, humidity, soil moisture (every 5 minutes)</li>
                                    <li>AI-powered cameras detect pests with 91% accuracy</li>
                                    <li>Weekly visual inspections of plants (check undersides of leaves)</li>
                                    <li>Sticky traps and pheromone traps for monitoring populations</li>
                                    <li>Economic threshold: Act when pest population reaches damage level</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="border-l-4 border-purple-500 bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-purple-600 dark:text-purple-400 font-bold text-lg">3</span>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Biological Control</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Use natural enemies to control pests:</p>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 list-disc list-inside ml-4 space-y-1">
                                    <li><strong>Predators:</strong> Ladybugs (eat 50 aphids/day), lacewings, praying mantis</li>
                                    <li><strong>Parasitoids:</strong> Trichogramma wasps, Encarsia formosa for whiteflies</li>
                                    <li><strong>Pathogens:</strong> Bt (Bacillus thuringiensis) for caterpillars</li>
                                    <li><strong>Nematodes:</strong> Steinernema for soil-dwelling pests</li>
                                    <li>Establish habitat for beneficial insects (flowering plants, hedgerows)</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="border-l-4 border-orange-500 bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-orange-600 dark:text-orange-400 font-bold text-lg">4</span>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Chemical Control (Last Resort)</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Use pesticides only when necessary:</p>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 list-disc list-inside ml-4 space-y-1">
                                    <li><strong>Selective pesticides:</strong> Target specific pests, spare beneficials</li>
                                    <li><strong>Organic options:</strong> Neem oil, insecticidal soap, horticultural oil</li>
                                    <li><strong>Proper timing:</strong> Apply when pests are most vulnerable (early morning)</li>
                                    <li><strong>Rotation:</strong> Alternate pesticide classes to prevent resistance</li>
                                    <li><strong>Safety:</strong> Follow label instructions, use PPE, observe re-entry intervals</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">IPM Decision-Making Process</h4>
                    <div class="flex items-center justify-between text-sm">
                        <div class="text-center flex-1">
                            <div class="w-12 h-12 bg-green-500 text-white rounded-full flex items-center justify-center mx-auto mb-2 font-bold">1</div>
                            <p class="text-gray-700 dark:text-gray-300">Monitor</p>
                        </div>
                        <i class="fas fa-arrow-right text-gray-400"></i>
                        <div class="text-center flex-1">
                            <div class="w-12 h-12 bg-blue-500 text-white rounded-full flex items-center justify-center mx-auto mb-2 font-bold">2</div>
                            <p class="text-gray-700 dark:text-gray-300">Identify</p>
                        </div>
                        <i class="fas fa-arrow-right text-gray-400"></i>
                        <div class="text-center flex-1">
                            <div class="w-12 h-12 bg-purple-500 text-white rounded-full flex items-center justify-center mx-auto mb-2 font-bold">3</div>
                            <p class="text-gray-700 dark:text-gray-300">Assess</p>
                        </div>
                        <i class="fas fa-arrow-right text-gray-400"></i>
                        <div class="text-center flex-1">
                            <div class="w-12 h-12 bg-orange-500 text-white rounded-full flex items-center justify-center mx-auto mb-2 font-bold">4</div>
                            <p class="text-gray-700 dark:text-gray-300">Act</p>
                        </div>
                        <i class="fas fa-arrow-right text-gray-400"></i>
                        <div class="text-center flex-1">
                            <div class="w-12 h-12 bg-red-500 text-white rounded-full flex items-center justify-center mx-auto mb-2 font-bold">5</div>
                            <p class="text-gray-700 dark:text-gray-300">Evaluate</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sensor Understanding -->
            <div id="sensors" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-thermometer-half text-blue-600 mr-2"></i>
                    Understanding IoT Sensor Technology
                </h2>

                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Our system uses 9 IoT sensors deployed across Field A, Field B, and Greenhouse A to continuously monitor environmental conditions. Each sensor transmits data every 5 minutes to the central database via wireless network. Learn the science behind each sensor and how to interpret the data.
                </p>

                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                    <h4 class="font-semibold text-blue-900 dark:text-blue-200 mb-2 flex items-center">
                        <i class="fas fa-network-wired mr-2"></i>Sensor Network Architecture
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm text-blue-800 dark:text-blue-300">
                        <div><strong>Total Sensors:</strong> 9 active nodes</div>
                        <div><strong>Communication:</strong> WiFi (2.4GHz)</div>
                        <div><strong>Protocol:</strong> MQTT/HTTP</div>
                        <div><strong>Update Rate:</strong> Every 5 minutes</div>
                        <div><strong>Power:</strong> 5V DC (USB/Solar)</div>
                        <div><strong>Range:</strong> Up to 100m outdoor</div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20 p-3 rounded">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                            <i class="fas fa-thermometer-half text-red-600 mr-2"></i>
                            Temperature Sensor (DHT22)
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            <strong>How it works:</strong> Uses a thermistor to measure temperature. Resistance changes with temperature, converted to digital signal.
                        </p>
                        <div class="bg-white dark:bg-gray-800 rounded p-2 mb-2">
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">🔧 Technical Specs:</p>
                            <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                <li>• Range: -40°C to 80°C</li>
                                <li>• Accuracy: ±0.5°C</li>
                                <li>• Reading interval: Every 5 minutes</li>
                                <li>• Power: 3.3-5V DC</li>
                            </ul>
                        </div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Optimal Range: 20-28°C</p>
                        <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                            <li>• <strong>Too high (>30°C):</strong> Heat stress, reduced photosynthesis, increased pest activity</li>
                            <li>• <strong>Too low (<15°C):</strong> Slow growth, potential frost damage, reduced nutrient uptake</li>
                            <li>• <strong>Impact:</strong> Every 1°C above optimal reduces yield by 3-5%</li>
                        </ul>
                    </div>

                    <div class="border-l-4 border-blue-500 bg-blue-50 dark:bg-blue-900/20 p-3 rounded">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                            <i class="fas fa-tint text-blue-600 mr-2"></i>
                            Humidity Sensor (DHT22)
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            <strong>How it works:</strong> Capacitive sensor measures moisture in air. Humidity changes capacitance, converted to percentage.
                        </p>
                        <div class="bg-white dark:bg-gray-800 rounded p-2 mb-2">
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">🔧 Technical Specs:</p>
                            <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                <li>• Range: 0-100% RH</li>
                                <li>• Accuracy: ±2-5% RH</li>
                                <li>• Response time: 6-20 seconds</li>
                                <li>• Sampling rate: 0.5 Hz (once per 2 seconds)</li>
                            </ul>
                        </div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Optimal Range: 60-80% RH</p>
                        <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                            <li>• <strong>Too high (>85%):</strong> Fungal diseases, powdery mildew, botrytis, mold growth</li>
                            <li>• <strong>Too low (<50%):</strong> Water stress, wilting, increased transpiration, spider mites</li>
                            <li>• <strong>Impact:</strong> High humidity increases disease risk by 40-60%</li>
                        </ul>
                    </div>

                    <div class="border-l-4 border-green-500 bg-green-50 dark:bg-green-900/20 p-3 rounded">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                            <i class="fas fa-seedling text-green-600 mr-2"></i>
                            Soil Moisture Sensor (Capacitive)
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            <strong>How it works:</strong> Measures dielectric constant of soil. Water content affects capacitance between two plates.
                        </p>
                        <div class="bg-white dark:bg-gray-800 rounded p-2 mb-2">
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">🔧 Technical Specs:</p>
                            <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                <li>• Range: 0-100% volumetric water content</li>
                                <li>• Accuracy: ±3%</li>
                                <li>• Depth: 10cm insertion</li>
                                <li>• Corrosion resistant (capacitive vs resistive)</li>
                            </ul>
                        </div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Optimal Range: 40-60%</p>
                        <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                            <li>• <strong>Too high (>70%):</strong> Root rot, oxygen deficiency, anaerobic conditions, pythium</li>
                            <li>• <strong>Too low (<30%):</strong> Drought stress, nutrient deficiency, wilting, reduced yield</li>
                            <li>• <strong>Impact:</strong> Proper moisture increases nutrient uptake by 25-30%</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- AI Pest Detection Model -->
            <div id="ai-model" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                    <i class="fas fa-brain text-purple-600 mr-2 text-sm"></i>
                    YOLO AI Pest Detection Model
                </h2>

                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Our system uses YOLOv8 (You Only Look Once) for real-time pest detection. Learn how this cutting-edge AI technology works.
                </p>

                <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-3 mb-3">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">🤖 What is YOLO?</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                        YOLO is a state-of-the-art, real-time object detection system. Unlike traditional methods that scan images multiple times, YOLO looks at the entire image once and predicts bounding boxes and class probabilities simultaneously.
                    </p>
                </div>

                <div class="space-y-3">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded p-3">
                        <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">📊 Model Performance</h5>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="bg-white dark:bg-gray-800 rounded p-2">
                                <div class="text-gray-600 dark:text-gray-400">Overall Accuracy</div>
                                <div class="text-lg font-bold text-green-600">91.2%</div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded p-2">
                                <div class="text-gray-600 dark:text-gray-400">Detection Speed</div>
                                <div class="text-lg font-bold text-blue-600">45 FPS</div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded p-2">
                                <div class="text-gray-600 dark:text-gray-400">Training Images</div>
                                <div class="text-lg font-bold text-purple-600">2,500+</div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded p-2">
                                <div class="text-gray-600 dark:text-gray-400">Pest Classes</div>
                                <div class="text-lg font-bold text-orange-600">10+</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 rounded p-3">
                        <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">🎯 Detectable Pests</h5>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check-circle text-green-600"></i>
                                <span class="text-gray-700 dark:text-gray-300">Aphids (92%)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check-circle text-green-600"></i>
                                <span class="text-gray-700 dark:text-gray-300">Whiteflies (89%)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check-circle text-green-600"></i>
                                <span class="text-gray-700 dark:text-gray-300">Caterpillars (94%)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check-circle text-green-600"></i>
                                <span class="text-gray-700 dark:text-gray-300">Thrips (87%)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check-circle text-green-600"></i>
                                <span class="text-gray-700 dark:text-gray-300">Leaf Miners (88%)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check-circle text-green-600"></i>
                                <span class="text-gray-700 dark:text-gray-300">Spider Mites (85%)</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded p-3">
                        <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">⚙️ How Detection Works</h5>
                        <ol class="text-xs text-gray-600 dark:text-gray-400 space-y-2">
                            <li class="flex items-start gap-2">
                                <span class="font-bold text-blue-600">1.</span>
                                <span><strong>Image Capture:</strong> Camera captures crop images every 5 seconds</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="font-bold text-blue-600">2.</span>
                                <span><strong>Preprocessing:</strong> Image resized to 640x640 pixels and normalized</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="font-bold text-blue-600">3.</span>
                                <span><strong>Detection:</strong> YOLO model analyzes image and identifies pests</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="font-bold text-blue-600">4.</span>
                                <span><strong>Classification:</strong> Pest type and confidence score determined</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="font-bold text-blue-600">5.</span>
                                <span><strong>Alert:</strong> If confidence >60%, system logs detection and sends alert</span>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Sidebar -->
        <div class="space-y-4">
            <!-- Smart Farming Concepts -->
            <div id="smart-farming" class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl p-4">
                <h3 class="text-base font-semibold mb-3 flex items-center">
                    <i class="fas fa-brain mr-2 text-sm"></i>
                    Smart Farming
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3">
                        <h4 class="font-semibold mb-1">IoT Technology</h4>
                        <p class="text-white/80 text-xs">Connected sensors collect real-time environmental data</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3">
                        <h4 class="font-semibold mb-1">AI Detection</h4>
                        <p class="text-white/80 text-xs">Machine learning identifies pests with high accuracy</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3">
                        <h4 class="font-semibold mb-1">Data Analytics</h4>
                        <p class="text-white/80 text-xs">Historical data helps predict and prevent issues</p>
                    </div>
                </div>
            </div>

            <!-- Quick Tips -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                    <i class="fas fa-lightbulb text-yellow-500 mr-2 text-sm"></i>
                    Quick Tips
                </h3>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-600 mt-1"></i>
                        <span>Check sensor data daily for anomalies</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-600 mt-1"></i>
                        <span>Respond to pest alerts within 24 hours</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-600 mt-1"></i>
                        <span>Use IPM principles before chemicals</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-600 mt-1"></i>
                        <span>Document all observations and actions</span>
                    </li>
                </ul>
            </div>

            <!-- Video Tutorials -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                    <i class="fas fa-video text-red-600 mr-2 text-sm"></i>
                    Video Tutorials
                </h3>
                <div class="space-y-2">
                    <a href="https://www.youtube.com/results?search_query=IoT+agriculture+sensors" target="_blank" class="block p-2 bg-gray-50 dark:bg-gray-700 rounded hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-play-circle text-red-600 mt-1"></i>
                            <div>
                                <div class="text-xs font-semibold text-gray-900 dark:text-white">IoT Sensors in Agriculture</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Learn sensor basics</div>
                            </div>
                        </div>
                    </a>
                    <a href="https://www.youtube.com/results?search_query=YOLO+object+detection+tutorial" target="_blank" class="block p-2 bg-gray-50 dark:bg-gray-700 rounded hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-play-circle text-red-600 mt-1"></i>
                            <div>
                                <div class="text-xs font-semibold text-gray-900 dark:text-white">YOLO Object Detection</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">AI model training</div>
                            </div>
                        </div>
                    </a>
                    <a href="https://www.youtube.com/results?search_query=Arduino+DHT22+sensor" target="_blank" class="block p-2 bg-gray-50 dark:bg-gray-700 rounded hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-play-circle text-red-600 mt-1"></i>
                            <div>
                                <div class="text-xs font-semibold text-gray-900 dark:text-white">Arduino DHT22 Setup</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Hands-on tutorial</div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- External Resources -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                    <i class="fas fa-external-link-alt text-blue-600 mr-2 text-sm"></i>
                    External Resources
                </h3>
                <div class="space-y-2">
                    <a href="https://www.fao.org/home/en" target="_blank" class="block text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        <i class="fas fa-globe mr-1"></i> FAO - Food & Agriculture
                    </a>
                    <a href="https://plantvillage.psu.edu/" target="_blank" class="block text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        <i class="fas fa-globe mr-1"></i> PlantVillage
                    </a>
                    <a href="https://www.da.gov.ph/" target="_blank" class="block text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        <i class="fas fa-globe mr-1"></i> DA Philippines
                    </a>
                    <a href="https://www.arduino.cc/en/Guide" target="_blank" class="block text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        <i class="fas fa-globe mr-1"></i> Arduino Official Guide
                    </a>
                    <a href="https://docs.ultralytics.com/" target="_blank" class="block text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        <i class="fas fa-globe mr-1"></i> YOLOv8 Documentation
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive Quiz Section -->
    <div class="mt-6 bg-gradient-to-br from-indigo-500 to-purple-600 text-white rounded-xl p-6">
        <h2 class="text-xl font-bold mb-4 flex items-center">
            <i class="fas fa-question-circle mr-2"></i>
            Test Your Knowledge
        </h2>
        <p class="text-sm mb-4 opacity-90">Take this quick quiz to test what you've learned about IoT farming and pest detection!</p>
        
        <div id="quiz-container" class="space-y-4">
            <!-- Question 1 -->
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                <p class="font-semibold mb-3">1. What does IoT stand for?</p>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-white/10 p-2 rounded">
                        <input type="radio" name="q1" value="a" class="form-radio">
                        <span class="text-sm">Internet of Things</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-white/10 p-2 rounded">
                        <input type="radio" name="q1" value="b" class="form-radio">
                        <span class="text-sm">Integration of Technology</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-white/10 p-2 rounded">
                        <input type="radio" name="q1" value="c" class="form-radio">
                        <span class="text-sm">Internet of Terminals</span>
                    </label>
                </div>
            </div>

            <!-- Question 2 -->
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                <p class="font-semibold mb-3">2. What is the optimal temperature range for most crops?</p>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-white/10 p-2 rounded">
                        <input type="radio" name="q2" value="a" class="form-radio">
                        <span class="text-sm">10-15°C</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-white/10 p-2 rounded">
                        <input type="radio" name="q2" value="b" class="form-radio">
                        <span class="text-sm">20-28°C</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-white/10 p-2 rounded">
                        <input type="radio" name="q2" value="c" class="form-radio">
                        <span class="text-sm">35-40°C</span>
                    </label>
                </div>
            </div>

            <!-- Question 3 -->
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                <p class="font-semibold mb-3">3. What does YOLO stand for in AI detection?</p>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-white/10 p-2 rounded">
                        <input type="radio" name="q3" value="a" class="form-radio">
                        <span class="text-sm">You Only Look Once</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-white/10 p-2 rounded">
                        <input type="radio" name="q3" value="b" class="form-radio">
                        <span class="text-sm">Your Online Learning Object</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-white/10 p-2 rounded">
                        <input type="radio" name="q3" value="c" class="form-radio">
                        <span class="text-sm">Yield Optimization Learning Output</span>
                    </label>
                </div>
            </div>

            <!-- Question 4 -->
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                <p class="font-semibold mb-3">4. Which sensor measures soil water content?</p>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-white/10 p-2 rounded">
                        <input type="radio" name="q4" value="a" class="form-radio">
                        <span class="text-sm">DHT22</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-white/10 p-2 rounded">
                        <input type="radio" name="q4" value="b" class="form-radio">
                        <span class="text-sm">Capacitive Soil Moisture Sensor</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-white/10 p-2 rounded">
                        <input type="radio" name="q4" value="c" class="form-radio">
                        <span class="text-sm">LDR Sensor</span>
                    </label>
                </div>
            </div>

            <!-- Question 5 -->
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                <p class="font-semibold mb-3">5. What does IPM stand for?</p>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-white/10 p-2 rounded">
                        <input type="radio" name="q5" value="a" class="form-radio">
                        <span class="text-sm">Integrated Pest Management</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-white/10 p-2 rounded">
                        <input type="radio" name="q5" value="b" class="form-radio">
                        <span class="text-sm">Internet Protocol Management</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-white/10 p-2 rounded">
                        <input type="radio" name="q5" value="c" class="form-radio">
                        <span class="text-sm">Insect Prevention Method</span>
                    </label>
                </div>
            </div>

            <button onclick="checkQuiz()" class="w-full bg-white text-indigo-600 font-semibold py-3 rounded-lg hover:bg-gray-100 transition-colors">
                <i class="fas fa-check-circle mr-2"></i>Submit Quiz
            </button>
            
            <div id="quiz-result" class="hidden bg-white/20 backdrop-blur-sm rounded-lg p-4 text-center">
                <p class="text-xl font-bold mb-2" id="quiz-score"></p>
                <p class="text-sm" id="quiz-message"></p>
            </div>
        </div>
    </div>

    <!-- Case Studies Section -->
    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                <i class="fas fa-book-open text-green-600 mr-2"></i>
                Case Studies
            </h3>
            
            <!-- Case Study 1 -->
            <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">PlantVillage Nuru (FAO & Penn State)</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    Mobile AI app for diagnosing plant diseases in cassava, maize, and potato. Designed for smallholder farmers in remote areas.
                </p>
                <div class="flex flex-wrap gap-2 text-xs mb-2">
                    <span class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded">AI Detection</span>
                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded">Mobile App</span>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    <strong>Key Learning:</strong> Offline AI capabilities for areas with limited connectivity
                </p>
            </div>

            <!-- Case Study 2 -->
            <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">DLSU Greenhouse IoT System (2019)</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    IoT-based greenhouse automation using temperature and humidity sensors for automated irrigation in rural Philippines.
                </p>
                <div class="flex flex-wrap gap-2 text-xs mb-2">
                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded">IoT Sensors</span>
                    <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded">Automation</span>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    <strong>Key Learning:</strong> Local implementation reduces manual labor and water waste
                </p>
            </div>

            <!-- Case Study 3 -->
            <div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Libelium SmartFarm (Spain)</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    Commercial IoT system collecting real-time data on soil moisture, pH, temperature, and humidity for precision agriculture.
                </p>
                <div class="flex flex-wrap gap-2 text-xs mb-2">
                    <span class="px-2 py-1 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded">Commercial</span>
                    <span class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded">Multi-Sensor</span>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    <strong>Key Learning:</strong> Modular design allows scalability for different farm sizes
                </p>
            </div>
        </div>

        <!-- Glossary Section -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                <i class="fas fa-book text-purple-600 mr-2"></i>
                Technical Glossary
            </h3>
            
            <div class="space-y-3 max-h-96 overflow-y-auto">
                <div class="pb-2 border-b border-gray-200 dark:border-gray-700">
                    <h5 class="font-semibold text-sm text-gray-900 dark:text-white">AI (Artificial Intelligence)</h5>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Computer systems that can perform tasks requiring human intelligence, such as visual perception and decision-making.</p>
                </div>

                <div class="pb-2 border-b border-gray-200 dark:border-gray-700">
                    <h5 class="font-semibold text-sm text-gray-900 dark:text-white">Capacitive Sensor</h5>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Sensor that measures changes in capacitance to detect moisture levels without direct contact with water.</p>
                </div>

                <div class="pb-2 border-b border-gray-200 dark:border-gray-700">
                    <h5 class="font-semibold text-sm text-gray-900 dark:text-white">CNN (Convolutional Neural Network)</h5>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Deep learning algorithm designed for processing structured grid data like images, used in pest detection.</p>
                </div>

                <div class="pb-2 border-b border-gray-200 dark:border-gray-700">
                    <h5 class="font-semibold text-sm text-gray-900 dark:text-white">DHT22</h5>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Digital sensor that measures both temperature and humidity with high accuracy.</p>
                </div>

                <div class="pb-2 border-b border-gray-200 dark:border-gray-700">
                    <h5 class="font-semibold text-sm text-gray-900 dark:text-white">IoT (Internet of Things)</h5>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Network of physical devices embedded with sensors and software that connect and exchange data over the internet.</p>
                </div>

                <div class="pb-2 border-b border-gray-200 dark:border-gray-700">
                    <h5 class="font-semibold text-sm text-gray-900 dark:text-white">IPM (Integrated Pest Management)</h5>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Ecosystem-based strategy focusing on long-term pest prevention through biological, cultural, and chemical methods.</p>
                </div>

                <div class="pb-2 border-b border-gray-200 dark:border-gray-700">
                    <h5 class="font-semibold text-sm text-gray-900 dark:text-white">Machine Learning</h5>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Subset of AI that enables systems to learn and improve from experience without being explicitly programmed.</p>
                </div>

                <div class="pb-2 border-b border-gray-200 dark:border-gray-700">
                    <h5 class="font-semibold text-sm text-gray-900 dark:text-white">Precision Agriculture</h5>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Farming management concept using technology to observe, measure, and respond to crop variability.</p>
                </div>

                <div class="pb-2 border-b border-gray-200 dark:border-gray-700">
                    <h5 class="font-semibold text-sm text-gray-900 dark:text-white">Real-Time Monitoring</h5>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Continuous data collection and analysis that provides immediate feedback on system status.</p>
                </div>

                <div class="pb-2 border-b border-gray-200 dark:border-gray-700">
                    <h5 class="font-semibold text-sm text-gray-900 dark:text-white">Sensor Node</h5>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Individual device in an IoT network that collects and transmits environmental data.</p>
                </div>

                <div class="pb-2 border-b border-gray-200 dark:border-gray-700">
                    <h5 class="font-semibold text-sm text-gray-900 dark:text-white">Smart Farming</h5>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Modern farming approach using IoT, AI, and data analytics to increase crop quantity and quality.</p>
                </div>

                <div class="pb-2">
                    <h5 class="font-semibold text-sm text-gray-900 dark:text-white">YOLO (You Only Look Once)</h5>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Real-time object detection algorithm that processes entire images in a single pass for fast pest identification.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Quiz functionality
function checkQuiz() {
    const answers = {
        q1: 'a', // Internet of Things
        q2: 'b', // 20-28°C
        q3: 'a', // You Only Look Once
        q4: 'b', // Capacitive Soil Moisture Sensor
        q5: 'a'  // Integrated Pest Management
    };
    
    let score = 0;
    let total = Object.keys(answers).length;
    
    for (let question in answers) {
        const selected = document.querySelector(`input[name="${question}"]:checked`);
        if (selected && selected.value === answers[question]) {
            score++;
        }
    }
    
    const percentage = (score / total) * 100;
    const resultDiv = document.getElementById('quiz-result');
    const scoreText = document.getElementById('quiz-score');
    const messageText = document.getElementById('quiz-message');
    
    scoreText.textContent = `You scored ${score} out of ${total} (${percentage}%)`;
    
    if (percentage === 100) {
        messageText.textContent = '🎉 Perfect! You have excellent knowledge of IoT farming!';
    } else if (percentage >= 80) {
        messageText.textContent = '👏 Great job! You have a strong understanding of the concepts!';
    } else if (percentage >= 60) {
        messageText.textContent = '👍 Good effort! Review the materials to improve your score.';
    } else {
        messageText.textContent = '📚 Keep learning! Review the learning resources above.';
    }
    
    resultDiv.classList.remove('hidden');
    resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
</script>

<?php include 'includes/footer.php'; ?>
