<?php
// Product model utilities aligned with products table schema
// Schema reference:
// products table columns (active):
// products_id, products_name, products_pet_type, products_description,
// products_category ENUM('food','accessory','necessity','toy'), products_price DECIMAL(10,2), products_original_price DECIMAL(10,2),
// products_stock (varchar - may contain non-numeric, we cast), products_image_url, products_active TINYINT(1), products_created_at
// Removed legacy columns: products_badge, products_rating, products_variants

if (!function_exists('product_allowed_categories')) {
    function product_allowed_categories(): array {
        return ['food','accessory','necessity','toy'];
    }
}

if (!function_exists('product_is_valid_category')) {
    function product_is_valid_category(?string $cat): bool {
        if ($cat === null || $cat === '') return false;
        return in_array($cat, product_allowed_categories(), true);
    }
}

if (!function_exists('product_category_label')) {
    function product_category_label(?string $cat): string {
        $map = [
            'food' => 'Food',
            'accessory' => 'Accessories',
            'necessity' => 'Grooming / Necessity',
            'toy' => 'Treats / Toys'
        ];
        return $map[$cat] ?? 'Other';
    }
}

if (!function_exists('product_sort_clause')) {
    function product_sort_clause(string $sort): string {
        return match($sort) {
            'price_asc' => 'products_price ASC',
            'price_desc' => 'products_price DESC',
            'name_asc' => 'products_name ASC',
            'name_desc' => 'products_name DESC',
            'stock_desc' => 'CAST(products_stock AS SIGNED) DESC',
            default => 'products_created_at DESC'
        };
    }
}

if (!function_exists('product_build_where')) {
    function product_build_where(array $filters, array &$params, string &$types): string {
        $clauses = ['products_active = 1'];
        if (!empty($filters['q'])) {
            $clauses[] = 'products_name LIKE ?';
            $params[] = '%'.$filters['q'].'%';
            $types .= 's';
        }
        if (!empty($filters['cat']) && product_is_valid_category($filters['cat'])) {
            $clauses[] = 'products_category = ?';
            $params[] = $filters['cat'];
            $types .= 's';
        }
        return 'WHERE '.implode(' AND ', $clauses);
    }
}

if (!function_exists('product_fetch_paginated')) {
    function product_fetch_paginated(mysqli $conn, array $filters): array {
        $limit = isset($filters['limit']) ? max(1, (int)$filters['limit']) : 12;
        $page = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
        $offset = ($page - 1) * $limit;
        $params = []; $types = '';
        $where = product_build_where($filters, $params, $types);
        $sort = product_sort_clause($filters['sort'] ?? 'new');

        // Count total
        $sqlCount = "SELECT COUNT(*) FROM products $where";
        $stmt = $conn->prepare($sqlCount);
        if ($types !== '') $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->bind_result($total);
        $stmt->fetch();
        $stmt->close();

        $pages = $total > 0 ? (int)ceil($total / $limit) : 1;
        if ($page > $pages) { $page = $pages; $offset = ($page - 1) * $limit; }

    $sql = "SELECT products_id, products_name, products_pet_type, products_description, products_category, products_price, products_original_price, products_stock, products_image_url, products_active, products_created_at FROM products $where ORDER BY $sort LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        if ($types !== '') {
            $bindTypes = $types . 'ii';
            $bindParams = array_merge($params, [$limit, $offset]);
            $stmt->bind_param($bindTypes, ...$bindParams);
        } else {
            $stmt->bind_param('ii', $limit, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return [
            'items' => $items,
            'total' => (int)$total,
            'pages' => (int)$pages,
            'page' => (int)$page,
            'limit' => (int)$limit
        ];
    }
}

if (!function_exists('product_get_by_id')) {
    function product_get_by_id(mysqli $conn, int $id): ?array {
    $sql = "SELECT products_id, products_name, products_pet_type, products_description, products_category, products_price, products_original_price, products_stock, products_image_url, products_active, products_created_at FROM products WHERE products_id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }
}

?>
