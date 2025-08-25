<?php
// PHP版本的API接口

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    switch ($method) {
        case 'GET':
            handleGet($pdo, $action);
            break;
        case 'POST':
            handlePost($pdo, $action);
            break;
        case 'PUT':
            handlePut($pdo, $action);
            break;
        case 'DELETE':
            handleDelete($pdo, $action);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGet($pdo, $action) {
    switch ($action) {
        case 'categories':
            $stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY order_index");
            $categories = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $categories]);
            break;
            
        case 'links':
            $category_id = $_GET['category_id'] ?? null;
            $sql = "SELECT l.*, c.name as category_name, c.color as category_color 
                    FROM navigation_links l 
                    JOIN categories c ON l.category_id = c.id 
                    WHERE l.is_active = 1";
            
            if ($category_id) {
                $sql .= " AND l.category_id = :category_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['category_id' => $category_id]);
            } else {
                $stmt = $pdo->query($sql . " ORDER BY c.order_index, l.order_index");
            }
            
            $links = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $links]);
            break;
            
        case 'stats':
            $stats = [
                'total_categories' => $pdo->query("SELECT COUNT(*) FROM categories WHERE is_active = 1")->fetchColumn(),
                'total_links' => $pdo->query("SELECT COUNT(*) FROM navigation_links WHERE is_active = 1")->fetchColumn(),
                'total_clicks' => $pdo->query("SELECT SUM(click_count) FROM navigation_links")->fetchColumn()
            ];
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        default:
            // 返回所有数据
            $stmt = $pdo->query("
                SELECT c.*, 
                       JSON_ARRAYAGG(
                           JSON_OBJECT(
                               'id', l.id,
                               'title', l.title,
                               'url', l.url,
                               'description', l.description,
                               'icon_url', l.icon_url,
                               'click_count', l.click_count
                           )
                       ) as links
                FROM categories c
                LEFT JOIN navigation_links l ON c.id = l.category_id AND l.is_active = 1
                WHERE c.is_active = 1
                GROUP BY c.id
                ORDER BY c.order_index
            ");
            
            $data = $stmt->fetchAll();
            // 解析JSON字符串
            foreach ($data as &$item) {
                $item['links'] = json_decode($item['links'], true);
                if ($item['links'][0]['id'] === null) {
                    $item['links'] = [];
                }
            }
            
            echo json_encode(['success' => true, 'data' => $data]);
            break;
    }
}

function handlePost($pdo, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'click':
            $link_id = $input['link_id'] ?? null;
            if (!$link_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Link ID required']);
                return;
            }
            
            $stmt = $pdo->prepare("UPDATE navigation_links SET click_count = click_count + 1 WHERE id = ?");
            $stmt->execute([$link_id]);
            
            echo json_encode(['success' => true, 'message' => 'Click count updated']);
            break;
            
        case 'search':
            $query = $input['query'] ?? '';
            if (strlen($query) < 2) {
                echo json_encode(['success' => true, 'data' => []]);
                return;
            }
            
            $sql = "SELECT l.*, c.name as category_name, c.color as category_color 
                    FROM navigation_links l 
                    JOIN categories c ON l.category_id = c.id 
                    WHERE l.is_active = 1 AND (l.title LIKE :query OR l.description LIKE :query) 
                    ORDER BY l.click_count DESC, l.title ASC";
            
            $stmt = $pdo->prepare($sql);
            $searchTerm = '%' . $query . '%';
            $stmt->execute(['query' => $searchTerm]);
            $results = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $results]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function handlePut($pdo, $action) {
    // 预留更新接口
    http_response_code(501);
    echo json_encode(['error' => 'Not implemented']);
}

function handleDelete($pdo, $action) {
    // 预留删除接口
    http_response_code(501);
    echo json_encode(['error' => 'Not implemented']);
}
?>