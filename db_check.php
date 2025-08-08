<?php
echo "Database check starting...\n";

try {
    $host = 'localhost';
    $dbname = 'rdv_impots_matsiatra';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Database connection successful\n";
    
    // Check if appointment_types table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'appointment_types'");
    if ($stmt->rowCount() > 0) {
        echo "appointment_types table exists\n";
        
        // Check data
        $stmt = $pdo->query("SELECT COUNT(*) FROM appointment_types");
        $count = $stmt->fetchColumn();
        echo "Found $count appointment types\n";
        
        if ($count > 0) {
            $stmt = $pdo->query("SELECT * FROM appointment_types ORDER BY nom_motif");
            $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($types as $type) {
                echo "- " . $type['nom_motif'] . "\n";
            }
        }
    } else {
        echo "appointment_types table does not exist\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "Database check completed.\n";
?>
