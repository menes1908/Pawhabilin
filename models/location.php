<?php
if(!function_exists('location_get_all_by_user')){
    function location_get_all_by_user(mysqli $conn, int $user_id): array {
        $stmt = $conn->prepare("SELECT * FROM locations WHERE users_id=? AND location_active=1 ORDER BY location_is_default DESC, location_created_at DESC");
        $stmt->bind_param('i',$user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_all(MYSQLI_ASSOC);
    }
}

if(!function_exists('location_get_by_id_for_user')){
    function location_get_by_id_for_user(mysqli $conn, int $user_id, int $location_id): ?array {
        $stmt = $conn->prepare("SELECT * FROM locations WHERE users_id=? AND location_id=? AND location_active=1 LIMIT 1");
        $stmt->bind_param('ii',$user_id,$location_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res ?: null;
    }
}

if(!function_exists('location_insert')){
    function location_insert(mysqli $conn, int $user_id, array $data): ?array {
        $label = $data['label'] ?? null;
        $recipient = $data['recipient_name'] ?? '';
        $phone = $data['phone'] ?? null;
        $line1 = $data['line1'] ?? '';
        $line2 = $data['line2'] ?? null;
        $barangay = $data['barangay'] ?? null;
        $city = $data['city'] ?? '';
        $province = $data['province'] ?? '';
        $isDefault = !empty($data['is_default']) ? 1 : 0;

        if($isDefault){
            $conn->begin_transaction();
            try {
                if($clear = $conn->prepare("UPDATE locations SET location_is_default=0 WHERE users_id=?")){
                    $clear->bind_param('i',$user_id);
                    $clear->execute();
                    $clear->close();
                }
                $sql = "INSERT INTO locations (users_id, location_label, location_recipient_name, location_phone, location_address_line1, location_address_line2, location_barangay, location_city, location_province, location_is_default) VALUES (?,?,?,?,?,?,?,?,?,?)";
                $stmt = $conn->prepare($sql);
                if(!$stmt){ $conn->rollback(); return null; }
                $stmt->bind_param('issssssssi',$user_id,$label,$recipient,$phone,$line1,$line2,$barangay,$city,$province,$isDefault);
                if(!$stmt->execute()){ $stmt->close(); $conn->rollback(); return null; }
                $id = $conn->insert_id; $stmt->close();
                $conn->commit();
                return location_get_by_id_for_user($conn,$user_id,$id);
            } catch(Throwable $e){
                $conn->rollback();
                return null;
            }
        }
        $sql = "INSERT INTO locations (users_id, location_label, location_recipient_name, location_phone, location_address_line1, location_address_line2, location_barangay, location_city, location_province, location_is_default) VALUES (?,?,?,?,?,?,?,?,?,?)";
        $stmt = $conn->prepare($sql);
        if(!$stmt) return null;
        $stmt->bind_param('issssssssi',$user_id,$label,$recipient,$phone,$line1,$line2,$barangay,$city,$province,$isDefault);
        if(!$stmt->execute()){ $stmt->close(); return null; }
        $id = $conn->insert_id; $stmt->close();
        return location_get_by_id_for_user($conn,$user_id,$id);
    }
}
