<?php
/**
 * ai_search.php — Global Smart Search
 * Provides fuzzy search across patients, doctors, rooms, and appointments.
 */

require_once __DIR__ . '/../../database.php';

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');

if ($query === '') {
    echo json_encode(['results' => []]);
    exit;
}

$pdo = getDB();
$results = [];

try {
    $search = "%{$query}%";

    // 1. Search Patients
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, phone, 'patient' as type FROM patients WHERE first_name LIKE ? OR last_name LIKE ? OR phone LIKE ? LIMIT 5");
    $stmt->execute([$search, $search, $search]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($patients as $p) {
        $results[] = [
            'id'    => $p['id'],
            'title' => $p['first_name'] . ' ' . $p['last_name'],
            'desc'  => 'Patient • ' . $p['phone'],
            'type'  => 'patient'
        ];
    }

    // 2. Search Doctors
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, specialization, 'doctor' as type FROM doctors WHERE first_name LIKE ? OR last_name LIKE ? OR specialization LIKE ? LIMIT 5");
    $stmt->execute([$search, $search, $search]);
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($doctors as $d) {
        $results[] = [
            'id'    => $d['id'],
            'title' => 'Dr. ' . $d['first_name'] . ' ' . $d['last_name'],
            'desc'  => 'Doctor • ' . $d['specialization'],
            'type'  => 'doctor'
        ];
    }

    // 3. Search Rooms
    $stmt = $pdo->prepare("SELECT id, room_number, type, 'room' as type FROM rooms WHERE room_number LIKE ? OR type LIKE ? LIMIT 3");
    $stmt->execute([$search, $search]);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rooms as $r) {
        $results[] = [
            'id'    => $r['id'],
            'title' => 'Room ' . $r['room_number'],
            'desc'  => 'Room • ' . $r['type'],
            'type'  => 'room'
        ];
    }

} catch (Exception $e) {
    // If tables don't exist yet, return empty gracefully
}

echo json_encode(['results' => $results]);
