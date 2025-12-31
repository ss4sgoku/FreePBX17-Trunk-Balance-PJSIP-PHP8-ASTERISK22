<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

// Updated for FreePBX 17 (PDO Support) - Variable rename to prevent collision
print 'Installing Trunk Balance<br>';

// USAMOS UN NOMBRE DE VARIABLE DISTINTO PARA NO ROMPER EL INSTALADOR
$appdb = \FreePBX::Database(); 

// Column definitions
$tablename = "trunkbalance";
$cols = [
    'desttrunk_id' => "INTEGER default '0'",
    'disabled' => "varchar(50) default NULL",
    'description' => "varchar(50) default NULL",
    'dialpattern' => "varchar(255) default NULL",
    'dp_andor' => "varchar(50) default NULL",
    'notdialpattern' => "varchar(255) default NULL",
    'notdp_andor' => "varchar(50) default NULL",
    'billing_cycle' => "varchar(50) default NULL",
    'billingtime' => "time default NULL",
    'billing_day' => "varchar(50) default NULL",
    'billingdate' => "SMALLINT default '0'",
    'billingperiod' => "INT default '0'",
    'endingdate' => "datetime default NULL",
    'count_inbound' => "varchar(50) default NULL",
    'count_unanswered' => "varchar(50) default NULL",
    'loadratio' => "INTEGER default '1'",
    'maxtime' => "INTEGER default '-1'",
    'maxnumber' => "INTEGER default '-1'",
    'maxidentical' => "INTEGER default '-1'",
    'timegroup_id' => "INTEGER default '-1'",
    'url' => "varchar(250) default NULL",
    'url_timeout' => "INTEGER default '10'",
    'regex' => "varchar(250) default NULL"
];

try {
    // 1. Create Table
    $sql = "CREATE TABLE IF NOT EXISTS `$tablename` (
        trunkbalance_id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT
    )";
    $appdb->query($sql);

    // 2. Check and Create Columns
    // Get current columns
    $sql = "DESC `$tablename`";
    $stmt = $appdb->query($sql);
    $current_cols = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($cols as $key => $val) {
        if (!in_array($key, $current_cols)) {
            $sql = "ALTER TABLE `$tablename` ADD `$key` $val";
            $appdb->query($sql);
            print "Added column $key to $tablename table.<br>";
        } else {
            // Optional: Modify column to ensure definition matches
            $sql = "ALTER TABLE `$tablename` MODIFY `$key` $val";
            $appdb->query($sql);
        }
    }

} catch (\Exception $e) {
    print "Error installing table: " . $e->getMessage() . "<br>";
    // No usamos die() para dejar que FreePBX maneje el error
}
?>