<?php
if (!defined('FREEPBX_IS_AUTH')) {
    die('No direct script access allowed');
}

// PBX Open Source Software Alliance
// Fixed for FreePBX 17 / PHP 8.2 Strict Typing - Robust Data Types & Debugging

function trunkbalance_list() {
    $db = \FreePBX::Database(); 
    $allowed = [['trunkbalance_id' => 0, 'description' => _("None")]];
    
    $sql = "SELECT * FROM trunkbalance";
    try {
        $stmt = $db->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (is_array($results)) {
            foreach ($results as $result) {
                $allowed[] = $result;
            }
        }
    } catch (\Exception $e) {
        // Tabla vacía
    }
    return $allowed ?? null;
}

function trunkbalance_listtrunk() {
    $db = \FreePBX::Database();
    $allowed = [['trunkid' => 0, 'name' => _("None"), 'tech' => _("None")]];
    // Ajuste para mostrar troncales PJSIP correctamente
    $sql = "SELECT * FROM `trunks` WHERE (name NOT LIKE 'BAL_%') ORDER BY tech, name";
    
    try {
        $stmt = $db->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (is_array($results)) {
            foreach ($results as $result) {
                $allowed[] = $result;
            }
        }
    } catch (\Exception $e) {
        // Error silencioso
    }
    return $allowed ?? null;
}

function trunkbalance_listtimegroup() {
    $db = \FreePBX::Database();
    $allowed = [['id' => 0, 'description' => _("None")]];
    $sql = "SELECT * FROM `timegroups_groups`";
    
    try {
        $stmt = $db->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (is_array($results)) {
            foreach ($results as $result) {
                $allowed[] = $result;
            }
        }
    } catch (\Exception $e) {
        // Error silencioso
    }
    return $allowed ?? null;
}

function trunkbalance_get($id) {
    $db = \FreePBX::Database();
    $sql = "SELECT * FROM trunkbalance WHERE trunkbalance_id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

function trunkbalance_trunkid($trunkname) {
    $db = \FreePBX::Database();
    $sql = "SELECT `trunkid` FROM `trunks` WHERE name = :name";
    $stmt = $db->prepare($sql);
    $stmt->execute([':name' => "BAL_$trunkname"]);
    $result = $stmt->fetchColumn();
    return $result ?: null;
}

function trunkbalance_del($id) {
    $db = \FreePBX::Database();
    
    try {
        // Get trunk name first
        $sql = "SELECT `description` FROM `trunkbalance` WHERE trunkbalance_id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $trunkname = $stmt->fetchColumn();
        
        if ($trunkname) {
            $trunknum = trunkbalance_trunkid($trunkname);
            if ($trunknum) {
                core_trunks_del($trunknum, '');
            }
        }
        
        $sql = "DELETE FROM `trunkbalance` WHERE trunkbalance_id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
    } catch (\Exception $e) {
        die("Error deleting trunk: " . $e->getMessage());
    }
}

// --- FUNCIONES AUXILIARES DE LIMPIEZA ---

// Limpia enteros (convierte vacíos en 0 o default)
function _tb_clean_int($val, $default = 0) {
    if ($val === '' || $val === null) {
        return $default;
    }
    return (int)$val;
}

// Limpia Strings/Time/Date (convierte vacíos en NULL para que SQL no falle)
function _tb_clean_null($val) {
    if ($val === '' || $val === null) {
        return null;
    }
    return trim($val);
}

function trunkbalance_add($post) {
    $db = \FreePBX::Database();
    
    try {
        $sql = "
            INSERT INTO trunkbalance
                (desttrunk_id, disabled, description, dialpattern, dp_andor, 
                 notdialpattern, notdp_andor, billing_cycle, billingtime, 
                 billing_day, billingdate, billingperiod, endingdate, 
                 count_inbound, count_unanswered, loadratio, maxtime, 
                 maxnumber, maxidentical, timegroup_id, url, url_timeout, regex)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        // AQUI ESTABA EL ERROR: Usamos _tb_clean_null para fechas y horas
        $params = [
            _tb_clean_int($post['desttrunk_id'] ?? 0),
            $post['disabled'] ?? '',
            $post['description'] ?? '',
            $post['dialpattern'] ?? '',
            $post['dp_andor'] ?? '',
            $post['notdialpattern'] ?? '',
            $post['notdp_andor'] ?? '',
            $post['billing_cycle'] ?? '',
            _tb_clean_null($post['billingtime'] ?? null), // Fix para campo TIME
            $post['billing_day'] ?? '',
            _tb_clean_int($post['billingdate'] ?? 0),
            _tb_clean_int($post['billingperiod'] ?? 0),
            _tb_clean_null($post['endingdate'] ?? null),  // Fix para campo DATETIME
            $post['count_inbound'] ?? '',
            $post['count_unanswered'] ?? '',
            _tb_clean_int($post['loadratio'] ?? 1, 1),
            _tb_clean_int($post['maxtime'] ?? -1, -1),
            _tb_clean_int($post['maxnumber'] ?? -1, -1),
            _tb_clean_int($post['maxidentical'] ?? -1, -1),
            _tb_clean_int($post['timegroup_id'] ?? -1, -1),
            $post['url'] ?? '',
            _tb_clean_int($post['url_timeout'] ?? 10, 10),
            $post['regex'] ?? ''
        ];
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        if (!empty($post['description'])) {
            core_trunks_add('custom', 'Balancedtrunk/'.$post['description'], '', '', '', '', 'notneeded', '', '', 'off', '', 'off', 'BAL_'.$post['description'], '');
        }
        
        return true;
        
    } catch (\Exception $e) {
        // MODO DEBUG: ESTO MOSTRARÁ EL ERROR EN PANTALLA
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 20px; border: 2px solid red; margin: 20px;'>";
        echo "<h3>ERROR AL GUARDAR TRONCAL:</h3>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "<pre>";
        print_r($params); // Muestra qué datos intentamos enviar
        echo "</pre>";
        echo "</div>";
        die(); // Detiene la ejecución para que veas el error
    }
}

function trunkbalance_edit($id, $post) {
    $db = \FreePBX::Database();
    
    try {
        $sql = "SELECT `description` FROM `trunkbalance` WHERE trunkbalance_id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $olddescription = $stmt->fetchColumn();
        
        $sql = "
            UPDATE trunkbalance 
            SET 
                desttrunk_id = ?, disabled = ?, description = ?, dialpattern = ?, 
                dp_andor = ?, notdialpattern = ?, notdp_andor = ?, billing_cycle = ?, 
                billingtime = ?, billing_day = ?, billingdate = ?, billingperiod = ?,
                endingdate = ?, count_inbound = ?, count_unanswered = ?, loadratio = ?, 
                maxtime = ?, maxnumber = ?, maxidentical = ?, timegroup_id = ?, 
                url = ?, url_timeout = ?, regex = ?
            WHERE trunkbalance_id = ?
        ";
        
        $params = [
            _tb_clean_int($post['desttrunk_id'] ?? 0),
            $post['disabled'] ?? '',
            $post['description'] ?? '',
            $post['dialpattern'] ?? '',
            $post['dp_andor'] ?? '',
            $post['notdialpattern'] ?? '',
            $post['notdp_andor'] ?? '',
            $post['billing_cycle'] ?? '',
            _tb_clean_null($post['billingtime'] ?? null),
            $post['billing_day'] ?? '',
            _tb_clean_int($post['billingdate'] ?? 0),
            _tb_clean_int($post['billingperiod'] ?? 0),
            _tb_clean_null($post['endingdate'] ?? null),
            $post['count_inbound'] ?? '',
            $post['count_unanswered'] ?? '',
            _tb_clean_int($post['loadratio'] ?? 1, 1),
            _tb_clean_int($post['maxtime'] ?? -1, -1),
            _tb_clean_int($post['maxnumber'] ?? -1, -1),
            _tb_clean_int($post['maxidentical'] ?? -1, -1),
            _tb_clean_int($post['timegroup_id'] ?? -1, -1),
            $post['url'] ?? '',
            _tb_clean_int($post['url_timeout'] ?? 10, 10),
            $post['regex'] ?? '',
            $id
        ];
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        if ($olddescription !== ($post['description'] ?? '')) {
            $trunknum = trunkbalance_trunkid($olddescription);
            if ($trunknum) {
                core_trunks_edit($trunknum, 'Balancedtrunk/'.$post['description'], '', '', '', '', 'notneeded', '', '', 'off', '', 'off', 'BAL_'.$post['description'], '');
            }
        }
        
        return true;
        
    } catch (\Exception $e) {
        die("Error updating trunk: " . $e->getMessage());
    }
}

function trunkbalance_hookGet_config($engine) {
    global $ext;
    
    if ($engine === "asterisk") {
        $ext->splice('macro-dialout-trunk', 's', 1, new ext_agi('trunkbalance.php,${ARG1},${ARG2}'));
    }
}

function trunkbalance_check_update() {
    return false; 
}
?>