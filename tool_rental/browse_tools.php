<?php
require_once 'includes/db_connect.php';
require_once 'includes/settings_helper.php';

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name';

// Build WHERE clause
$where_conditions = ["t.status = 'active'"];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(t.name LIKE ? OR t.description LIKE ? OR t.brand LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

if (!empty($category_filter)) {
    $where_conditions[] = "t.category_id = ?";
    $params[] = $category_filter;
    $param_types .= 'i';
}

$where_clause = implode(' AND ', $where_conditions);

// Build ORDER BY clause
$order_options = [
    'name' => 't.name ASC',
    'price_low' => 't.daily_rate ASC',
    'price_high' => 't.daily_rate DESC',
    'newest' => 't.created_at DESC'
];
$order_by = isset($order_options[$sort_by]) ? $order_options[$sort_by] : $order_options['name'];

// Get tools with average ratings - FIXED QUERY
$tools_query = "SELECT t.tool_id, t.name, t.description, t.category_id, t.brand, t.model, 
                t.daily_rate, t.actual_price, t.image_url, t.quantity_available, t.status, t.created_at,
                c.category_name,
                COALESCE(AVG(r.rating), 0) as avg_rating,
                COUNT(r.review_id) as review_count
                FROM tools t 
                LEFT JOIN categories c ON t.category_id = c.category_id 
                LEFT JOIN reviews r ON t.tool_id = r.tool_id
                WHERE $where_clause
                GROUP BY t.tool_id, t.name, t.description, t.category_id, t.brand, t.model, 
                         t.daily_rate, t.actual_price, t.image_url, t.quantity_available, t.status, t.created_at,
                         c.category_name
                ORDER BY $order_by";

$stmt = $conn->prepare($tools_query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$tools_result = $stmt->get_result();

// Get all categories for filter dropdown
$categories_query = "SELECT * FROM categories ORDER BY category_name";
$categories_result = $conn->query($categories_query);

include 'includes/header.php';
?>

<div class="container">
    <div class="main-content">
        <h1><i class="fas fa-search"></i> Browse Tools</h1>
        <p class="mb-3">Find the perfect tool for your project</p>
        
        <!-- Search and Filter Bar -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="grid grid-2" style="align-items: end;">
                    <div class="form-group mb-0">
                        <label class="form-label" for="search">Search Tools</label>
                        <input type="text" id="search_input" name="search" class="form-control" 
                               placeholder="Search by name, description, or brand..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                        <div class="form-group mb-0">
                            <label class="form-label" for="category">Category</label>
                            <select id="category_filter" name="category" class="form-control form-select">
                                <option value="">All Categories</option>
                                <?php while ($category = $categories_result->fetch_assoc()): ?>
                                    <option value="<?php echo $category['category_id']; ?>" 
                                            <?php echo ($category_filter == $category['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group mb-0">
                            <label class="form-label" for="sort">Sort By</label>
                            <select id="sort" name="sort" class="form-control form-select">
                                <option value="name" <?php echo ($sort_by == 'name') ? 'selected' : ''; ?>>Name A-Z</option>
                                <option value="price_low" <?php echo ($sort_by == 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo ($sort_by == 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="newest" <?php echo ($sort_by == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                            </select>
                        </div>
                        
                        <div class="d-flex gap-1">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="browse_tools.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Results Count -->
        <div class="mb-3">
            <p><strong><?php echo $tools_result->num_rows; ?></strong> tools found
            <?php if (!empty($search)): ?>
                for "<strong><?php echo htmlspecialchars($search); ?></strong>"
            <?php endif; ?>
            <?php if (!empty($category_filter)): ?>
                in category
            <?php endif; ?>
            </p>
        </div>
        
        <!-- Tools Grid -->
        <?php if ($tools_result->num_rows > 0): ?>
            <div class="grid grid-3">
                <?php while ($tool = $tools_result->fetch_assoc()): ?>
                    <div class="tool-card" data-category="<?php echo $tool['category_id']; ?>">
                        <img src="<?php echo $tool['image_url'] ?: 'https://images.unsplash.com/photo-1572981779307-38b8cabb2407?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=400&q=80'; ?>" 
                             alt="<?php echo htmlspecialchars($tool['name']); ?>" class="tool-image"
                             onerror="this.src='https://images.unsplash.com/photo-1572981779307-38b8cabb2407?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=400&q=80'">
                        <div class="tool-info">
                            <div class="tool-name"><?php echo htmlspecialchars($tool['name']); ?></div>
                            
                            <?php if ($tool['brand']): ?>
                                <div style="color: #666; font-size: 0.9rem; margin-bottom: 5px;">
                                    <?php echo htmlspecialchars($tool['brand'] . ($tool['model'] ? ' ' . $tool['model'] : '')); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="tool-description">
                                <?php echo htmlspecialchars(substr($tool['description'], 0, 100)); ?>
                                <?php echo strlen($tool['description']) > 100 ? '...' : ''; ?>
                            </div>
                            
                            <div style="margin-bottom: 10px;">
                                <span class="badge badge-active"><?php echo htmlspecialchars($tool['category_name'] ?: 'Uncategorized'); ?></span>
                            </div>
                            
                            <!-- Rating Display -->
                            <?php if ($tool['avg_rating']): ?>
                                <div style="margin-bottom: 10px;">
                                    <div class="star-rating" data-rating="<?php echo round($tool['avg_rating']); ?>">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star"><?php echo ($i <= round($tool['avg_rating'])) ? '★' : '☆'; ?></span>
                                        <?php endfor; ?>
                                    </div>
                                    <small style="color: #666;">(<?php echo $tool['review_count']; ?> reviews)</small>
                                </div>
                            <?php endif; ?>
                            
                            <div class="tool-price">₹<?php echo number_format($tool['daily_rate'], 2); ?>/day</div>
                            
                            <div style="margin-bottom: 15px;">
                                <?php if ($tool['quantity_available'] > 0): ?>
                                    <small style="color: #28A745;">
                                        <i class="fas fa-check-circle"></i> 
                                        <?php echo $tool['quantity_available']; ?> available
                                    </small>
                                <?php else: ?>
                                    <small style="color: #DC3545;">
                                        <i class="fas fa-times-circle"></i> 
                                        Out of stock
                                    </small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex gap-1" style="flex-wrap: wrap;">
                                <a href="tool_details.php?id=<?php echo $tool['tool_id']; ?>" class="btn btn-secondary" style="flex: 1; min-width: 70px;">
                                    <i class="fas fa-info-circle"></i> Details
                                </a>
                                <?php if ($tool['quantity_available'] > 0): ?>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <a href="tool_details.php?id=<?php echo $tool['tool_id']; ?>" class="btn btn-outline-primary" style="flex: 1; min-width: 70px;">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </a>
                                        <button onclick="console.log('Rent Now button clicked'); openRentNowModal(<?php echo $tool['tool_id']; ?>, '<?php echo addslashes($tool['name']); ?>', <?php echo $tool['daily_rate']; ?>)" class="btn btn-primary" style="flex: 1; min-width: 70px;">
                                            <i class="fas fa-bolt"></i> Rent Now
                                        </button>
                                    <?php else: ?>
                                        <a href="tool_details.php?id=<?php echo $tool['tool_id']; ?>" class="btn btn-outline-primary" style="flex: 1; min-width: 70px;">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </a>
                                        <a href="login.php" class="btn btn-primary" style="flex: 1; min-width: 70px;">
                                            <i class="fas fa-sign-in-alt"></i> Login to Rent
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled style="flex: 1; min-width: 70px;">
                                        <i class="fas fa-times-circle"></i> Out of Stock
                                    </button>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-search" style="font-size: 3rem; color: var(--accent-yellow); margin-bottom: 20px;"></i>
                    <h3>No Tools Found</h3>
                    <p>Try adjusting your search criteria or browse all categories.</p>
                    <a href="browse_tools.php" class="btn btn-primary">View All Tools</a>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Category Quick Links -->
        <?php if (empty($category_filter)): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h3>Browse by Category</h3>
                </div>
                <div class="card-body">
                    <?php
                    // Reset categories result
                    $categories_result = $conn->query($categories_query);
                    ?>
                    <div class="grid grid-3">
                        <?php while ($category = $categories_result->fetch_assoc()): ?>
                            <a href="browse_tools.php?category=<?php echo $category['category_id']; ?>" class="card text-center" style="text-decoration: none; color: inherit; transition: var(--transition);">
                                <div class="card-body">
                                    <i class="fas fa-tools" style="font-size: 2rem; color: var(--accent-yellow); margin-bottom: 10px;"></i>
                                    <h4><?php echo htmlspecialchars($category['category_name']); ?></h4>
                                    <?php if ($category['description']): ?>
                                        <p style="font-size: 0.9rem; color: #666;"><?php echo htmlspecialchars($category['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Rent Now Modal -->
<div id="rentNowModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalToolName">Rent Tool</h3>
            <span class="close" onclick="closeRentNowModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div id="stockInfo" style="margin-bottom: 15px; padding: 10px; background-color: #f8f9fa; border-radius: 4px; border-left: 4px solid #28a745;">
                <i class="fas fa-info-circle"></i> 
                <span id="stockCountText">Checking availability...</span>
            </div>
            
            <form id="rentNowForm">
                <input type="hidden" id="modalToolId">
                <input type="hidden" id="modalDailyRate">
                <input type="hidden" id="modalStockCount">
                
                <div class="form-group">
                    <label class="form-label" for="modalQuantity">Quantity</label>
                    <select id="modalQuantity" name="quantity" class="form-control form-select" onchange="updateQuantity()">
                        <!-- Options will be populated by JavaScript -->
                    </select>
                </div>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label" for="modalStartDate">Start Date</label>
                        <input type="date" id="modalStartDate" name="rental_start_date" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="modalEndDate">End Date</label>
                        <input type="date" id="modalEndDate" name="rental_end_date" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <div id="modalRentalTotal" style="font-size: 1.1rem; font-weight: bold; color: var(--primary-gray); text-align: center; padding: 15px; background: var(--light-gray); border-radius: var(--border-radius);">
                        Select dates to see total price
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="button" onclick="closeRentNowModal()" class="btn btn-secondary" style="flex: 1;">
                        Cancel
                    </button>
                    <button type="button" onclick="showConfirmationModal()" class="btn btn-primary" style="flex: 1;" id="rentNowButton" disabled>
                        <i class="fas fa-bolt"></i> Rent Now
                    </button>
                </div>
            </form>
            
            <!-- Notify me when available button (shown when tool is out of stock) -->
            <div id="notifyMeSection" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-gray);">
                <button type="button" onclick="notifyMeWhenAvailable()" class="btn btn-outline-primary" style="width: 100%;">
                    <i class="fas fa-bell"></i> Notify me when available
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Star rating styles */
.star-rating {
    display: inline-block;
    margin-right: 10px;
}

.star-rating .star {
    color: #FFC107;
    font-size: 1rem;
    margin-right: 2px;
}

/* Hover effect for category cards */
.card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
}

/* Modal Styles */
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(3px);
}

.modal-content {
    background-color: #fff;
    margin: 5% auto;
    border-radius: var(--border-radius);
    width: 90%;
    max-width: 800px;
    box-shadow: var(--shadow-heavy);
    animation: modalSlideIn 0.3s ease-out;
}

.modal-header {
    padding: 15px;
    border-bottom: 1px solid var(--border-gray);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 15px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .modal-content {
        max-width: 95%;
    }
}
</style>

<script>
let currentToolId = null;
let currentDailyRate = 0;
let currentToolName = '';
let currentStockCount = 0;

// Preview proof image when selected
document.addEventListener('DOMContentLoaded', function() {
    const proofImageInput = document.getElementById('proofImage');
    const proofPreview = document.getElementById('proofPreview');
    const previewImage = document.getElementById('previewImage');
    
    if (proofImageInput) {
        proofImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    proofPreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                proofPreview.style.display = 'none';
            }
        });
    }
});

function updateQuantity() {
    // Update the tool name to reflect the selected quantity
    const selectedQuantity = parseInt(document.getElementById('modalQuantity').value) || 1;
    document.getElementById('modalToolName').innerHTML = `Rent: ${currentToolName} &times; ${selectedQuantity} unit${selectedQuantity > 1 ? 's' : ''} selected`;
    
    // Recalculate total when quantity changes
    const startDateInput = document.getElementById('modalStartDate');
    const endDateInput = document.getElementById('modalEndDate');
    
    if (startDateInput && endDateInput) {
        // Get current values
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        
        // Only trigger calculation if both dates are selected
        if (startDate && endDate) {
            // Directly call calculateTotal instead of dispatching events
            calculateTotal();
        }
    }
}

// Calculate rental total when dates change
function calculateTotal() {
    const startDateInput = document.getElementById('modalStartDate');
    const endDateInput = document.getElementById('modalEndDate');
    const totalDisplay = document.getElementById('modalRentalTotal');
    
    if (!startDateInput || !endDateInput || !totalDisplay) {
        console.log('Required elements not found');
        return;
    }
    
    const startDate = startDateInput.value;
    const endDate = endDateInput.value;
    const quantity = parseInt(document.getElementById('modalQuantity').value) || 1;
    
    console.log('Calculating total with:', { startDate, endDate, quantity, currentDailyRate });
    
    // Update the tool name to reflect the selected quantity
    document.getElementById('modalToolName').innerHTML = `Rent: ${currentToolName} &times; ${quantity} unit${quantity > 1 ? 's' : ''} selected`;
    
    if (startDate && endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        
        if (end > start) {
            const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
            const total = days * currentDailyRate * quantity;
            totalDisplay.innerHTML = `Total: <strong>₹${total.toLocaleString()}</strong> for ${days} day${days > 1 ? 's' : ''} (${quantity} unit${quantity > 1 ? 's' : ''})`;
            totalDisplay.style.background = '#e8f5e9';
            totalDisplay.style.color = '#2e7d32';
            
            // Enable the rent button
            document.getElementById('rentNowButton').disabled = false;
            console.log('Rent button enabled');
        } else {
            totalDisplay.innerHTML = '<i class="fas fa-exclamation-circle"></i> End date must be after start date';
            totalDisplay.style.background = '#ffebee';
            totalDisplay.style.color = '#c62828';
            document.getElementById('rentNowButton').disabled = true;
            console.log('Rent button disabled - invalid dates');
        }
    } else {
        totalDisplay.innerHTML = '<i class="fas fa-info-circle"></i> Select dates to see total price';
        totalDisplay.style.background = '#f5f5f5';
        totalDisplay.style.color = '#666';
        document.getElementById('rentNowButton').disabled = true;
        console.log('Rent button disabled - dates not selected');
    }
}

function openRentNowModal(toolId, toolName, dailyRate) {
    console.log('Opening Rent Now Modal for:', { toolId, toolName, dailyRate });
    
    // First check if tool is still available (without dates initially)
    fetch(`modules/check_tool_availability.php?tool_id=${toolId}`)
    .then(response => response.json())
    .then(data => {
        console.log('Tool availability check result:', data);
        
        if (!data.available) {
            // Tool is not available, show notify me button
            document.getElementById('stockCountText').textContent = 'This tool is currently out of stock';
            document.getElementById('stockInfo').style.borderLeftColor = '#dc3545';
            document.getElementById('notifyMeSection').style.display = 'block';
            
            // Hide rental form elements
            document.getElementById('modalQuantity').closest('.form-group').style.display = 'none';
            document.querySelector('.grid.grid-2').style.display = 'none';
            document.getElementById('modalRentalTotal').style.display = 'none';
            document.getElementById('rentNowButton').style.display = 'none';
            
            // Set tool info for notification
            currentToolId = toolId;
            currentToolName = toolName;
            document.getElementById('modalToolId').value = toolId;
            document.getElementById('modalToolName').innerHTML = `Rent: ${toolName}`;
            
            document.getElementById('rentNowModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
            return;
        }
        
        // Tool is available, proceed with normal rental modal
        currentToolId = toolId;
        currentDailyRate = dailyRate;
        currentToolName = toolName;
        currentStockCount = data.quantity_available || 0;
        
        document.getElementById('modalToolId').value = toolId;
        document.getElementById('modalToolName').innerHTML = `Rent: ${toolName} &times; ${currentStockCount} unit${currentStockCount > 1 ? 's' : ''} available`;
        document.getElementById('modalDailyRate').value = dailyRate;
        document.getElementById('modalStockCount').value = currentStockCount;
        document.getElementById('stockCountText').textContent = `${currentStockCount} unit${currentStockCount > 1 ? 's' : ''} available for rental`;
        document.getElementById('stockInfo').style.borderLeftColor = '#28a745';
        
        // Show rental form elements
        document.getElementById('modalQuantity').closest('.form-group').style.display = 'block';
        document.querySelector('.grid.grid-2').style.display = 'grid';
        document.getElementById('modalRentalTotal').style.display = 'block';
        document.getElementById('rentNowButton').style.display = 'block';
        document.getElementById('notifyMeSection').style.display = 'none';
        
        // Populate quantity dropdown
        const quantitySelect = document.getElementById('modalQuantity');
        quantitySelect.innerHTML = '';
        for (let i = 1; i <= Math.min(currentStockCount, 10); i++) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = i;
            quantitySelect.appendChild(option);
        }
        
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('modalStartDate').min = today;
        document.getElementById('modalEndDate').min = today;
        
        // Clear previous values
        document.getElementById('modalStartDate').value = '';
        document.getElementById('modalEndDate').value = '';
        document.getElementById('modalRentalTotal').innerHTML = '<i class="fas fa-info-circle"></i> Select dates to see total price';
        document.getElementById('modalQuantity').value = 1;
        
        // Enable the rent button by default (it will be disabled again if dates are not selected)
        document.getElementById('rentNowButton').disabled = false;
        
        // Add event listeners to date inputs and quantity select
        const modalStartDateInput = document.getElementById('modalStartDate');
        const modalEndDateInput = document.getElementById('modalEndDate');
        const modalQuantitySelect = document.getElementById('modalQuantity');
        
        if (modalStartDateInput && modalEndDateInput) {
            console.log('Adding event listeners to date inputs');
            // Remove any existing listeners to prevent duplicates
            modalStartDateInput.removeEventListener('change', calculateTotal);
            modalEndDateInput.removeEventListener('change', calculateTotal);
            modalStartDateInput.addEventListener('change', calculateTotal);
            modalEndDateInput.addEventListener('change', calculateTotal);
        }
        
        if (modalQuantitySelect) {
            console.log('Adding event listener to quantity select');
            // Remove any existing listeners to prevent duplicates
            modalQuantitySelect.removeEventListener('change', calculateTotal);
            modalQuantitySelect.addEventListener('change', calculateTotal);
        }
        
        document.getElementById('rentNowModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        console.log('Modal opened successfully');
    })
    .catch(error => {
        console.error('Error checking availability:', error);
        showAlert('error', 'Unable to check tool availability. Please try again.');
    });
}

function closeRentNowModal() {
    console.log('Closing Rent Now Modal');
    document.getElementById('rentNowModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    
    // Reset modal header
    if (currentToolName) {
        document.getElementById('modalToolName').innerHTML = `Rent: ${currentToolName}`;
    }
}

// Show confirmation modal with rental details (landscape version)
function showConfirmationModal() {
    console.log('Show confirmation modal called');
    
    const startDate = document.getElementById('modalStartDate').value;
    const endDate = document.getElementById('modalEndDate').value;
    const toolName = document.getElementById('modalToolName').textContent.replace('Rent: ', '');
    const toolId = document.getElementById('modalToolId').value;
    const dailyRate = currentDailyRate;
    const quantity = parseInt(document.getElementById('modalQuantity').value) || 1;
    
    console.log('Form data:', { startDate, endDate, toolName, toolId, dailyRate, quantity });
    
    // Remove quantity information from tool name if present
    const cleanToolName = toolName.split(' ×')[0];
    
    if (!startDate || !endDate) {
        showAlert('error', 'Please select both start and end dates');
        return;
    }
    
    const start = new Date(startDate);
    const end = new Date(endDate);
    
    if (end <= start) {
        showAlert('error', 'End date must be after start date');
        return;
    }
    
    const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
    const total = days * dailyRate * quantity;
    let currentTotalAmount = total;
    let currentRentalDays = days;
    
    // Show loading state on button
    const rentButton = document.getElementById('rentNowButton');
    const originalButtonText = rentButton.innerHTML;
    rentButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    rentButton.disabled = true;
    
    // Add to cart via AJAX
    const formData = new FormData();
    formData.append('tool_id', toolId);
    formData.append('rental_start_date', startDate);
    formData.append('rental_end_date', endDate);
    formData.append('quantity', quantity);
    
    console.log('Sending request to add_to_cart.php with data:', {
        tool_id: toolId,
        rental_start_date: startDate,
        rental_end_date: endDate,
        quantity: quantity
    });
    
    fetch('modules/add_to_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Response from add_to_cart.php:', data);
        
        if (data.success) {
            showAlert('success', data.message);
            // Redirect to checkout page with delay
            setTimeout(() => {
                window.location.href = 'checkout.php';
            }, 2000); // 2 second delay
        } else {
            showAlert('error', data.message || 'Failed to add item to cart');
            // Restore button state
            rentButton.innerHTML = originalButtonText;
            rentButton.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while adding to cart. Please try again.');
        // Restore button state
        rentButton.innerHTML = originalButtonText;
        rentButton.disabled = false;
    });
}

// Function to handle "Notify me when available" button
function notifyMeWhenAvailable() {
    if (!currentToolId) {
        showAlert('error', 'Tool information not available.');
        return;
    }
    
    // Send request to create notification
    fetch('modules/create_notification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            tool_id: currentToolId,
            notification_type: 'availability'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'You will be notified when this tool becomes available.');
            setTimeout(() => {
                closeRentNowModal();
            }, 2000);
        } else {
            showAlert('error', data.message || 'Failed to set notification.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred. Please try again.');
    });
}

// Show alert message
function showAlert(type, message) {
    // Remove any existing alerts
    const existingAlert = document.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // Create alert element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <span>${message}</span>
        <span class="close-alert" onclick="this.parentElement.remove()">&times;</span>
    `;
    
    // Add to body
    document.body.appendChild(alert);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alert.parentElement) {
            alert.remove();
        }
    }, 5000);
}

// Close modals when clicking outside
window.onclick = function(event) {
    const rentModal = document.getElementById('rentNowModal');
    
    if (event.target == rentModal) {
        closeRentNowModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
