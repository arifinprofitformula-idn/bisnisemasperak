<?php
include 'config.php';
include 'fungsi.php';

echo "<h2>Database Tables Check</h2>";

// Cek tabel yang ada
$tables = db_select("SHOW TABLES");
echo "<h3>Tables:</h3>";
foreach ($tables as $table) {
    echo "<p>" . implode(', ', $table) . "</p>";
}

// Cek struktur tabel sa_setting
echo "<h3>Structure of sa_setting:</h3>";
try {
    $structure = db_select("DESCRIBE sa_setting");
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($structure as $field) {
        echo "<tr>";
        foreach ($field as $value) {
            echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Cek isi tabel sa_setting
echo "<h3>Content of sa_setting:</h3>";
try {
    $settings = db_select("SELECT * FROM sa_setting LIMIT 10");
    if (!empty($settings)) {
        echo "<table border='1'>";
        $first = true;
        foreach ($settings as $setting) {
            if ($first) {
                echo "<tr>";
                foreach (array_keys($setting) as $key) {
                    echo "<th>" . htmlspecialchars($key) . "</th>";
                }
                echo "</tr>";
                $first = false;
            }
            echo "<tr>";
            foreach ($setting as $value) {
                echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data in sa_setting table</p>";
    }
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>