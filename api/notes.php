<?php
require_once 'auth_check.php';

// Step 4: GET Endpoint (Read)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $connect2db->prepare("SELECT id, title, content, created_at FROM notes WHERE user_id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $notes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($notes);
    exit();
}

// Step 5: POST Endpoint (Create)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (empty($data['title'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Title is required.']);
        exit();
    }

    $content = $data['content'] ?? '';

    $stmt = $connect2db->prepare("INSERT INTO notes (user_id, title, content) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $_SESSION['user_id'], $data['title'], $content);
    
    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            'id' => $stmt->insert_id,
            'title' => $data['title'],
            'content' => $content
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $stmt->error]);
    }
    exit();
}

// Step 6: PUT Endpoint (Update)
// Step 6: PUT Endpoint (Update)
// Step 6: PUT Endpoint (Update)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (empty($data['id']) || empty($data['title'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID and Title are required.']);
        exit();
    }

    $content = $data['content'] ?? '';
    $stmt = $connect2db->prepare("UPDATE notes SET title = ?, content = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ssii', $data['title'], $content, $data['id'], $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(['message' => 'Note updated successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Update failed.']);
    }
    exit();
}

// Step 7: DELETE Endpoint
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = $_GET['id'] ?? 0;

    $stmt = $connect2db->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $_SESSION['user_id']);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Note not found.']);
        exit();
    }

    http_response_code(200);
    echo json_encode(['message' => 'Note deleted successfully.']);
    exit();
}

// Step 8: Handle Unsupported Methods
http_response_code(405);
echo json_encode(['error' => 'Method not allowed.']);