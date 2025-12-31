<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

// Uninstall Trunk Balance - Modernized for FreePBX 17

$appdb = \FreePBX::Database();

try {
    // 1. Drop the table
    $sql = "DROP TABLE IF EXISTS `trunkbalance`";
    $appdb->query($sql);
    print "Dropped trunkbalance table.<br>";

    // 2. Remove balanced trunks from core trunks table
    // Using simple query is fine here as we are deleting specific named trunks
    $sql = "DELETE FROM `trunks` WHERE tech='custom' and name LIKE 'BAL_%' and channelid LIKE 'Balancedtrunk%'";
    $appdb->query($sql);
    print "Removed balanced trunks definitions.<br>";

} catch (\Exception $e) {
    print "Error uninstalling: " . $e->getMessage() . "<br>";
}

// Reload Asterisk logic
needreload();
?>