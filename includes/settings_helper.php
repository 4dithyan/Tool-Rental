<?php
/**
 * Settings Helper Functions
 * Provides functions to get and manage site settings
 */

/**
 * Get a setting value by name
 * 
 * @param string $setting_name The name of the setting to retrieve
 * @param mixed $default The default value to return if setting is not found
 * @return mixed The setting value or default value
 */
function get_setting($setting_name, $default = null) {
    global $conn;
    
    $query = "SELECT setting_value FROM settings WHERE setting_name = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $setting_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['setting_value'];
    }
    
    return $default;
}

/**
 * Get the late fee per day setting
 * 
 * @return float The late fee per day in INR
 */
function get_late_fee_per_day() {
    return floatval(get_setting('late_fee_per_day', 50));
}

/**
 * Get the damage fee percentage setting
 * 
 * @return float The damage fee percentage
 */
function get_damage_fee_percentage() {
    return floatval(get_setting('damage_fee_percentage', 20));
}

/**
 * Calculate late fee based on days overdue
 * 
 * @param int $days_overdue Number of days the rental is overdue
 * @return float The calculated late fee
 */
function calculate_late_fee($days_overdue) {
    $late_fee_per_day = get_late_fee_per_day();
    return $days_overdue * $late_fee_per_day;
}

/**
 * Calculate damage fee based on tool's actual price
 * 
 * @param float $actual_price The actual price of the tool
 * @return float The calculated damage fee
 */
function calculate_damage_fee($actual_price) {
    $damage_fee_percentage = get_damage_fee_percentage();
    return $actual_price * ($damage_fee_percentage / 100);
}