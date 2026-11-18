<?php
/**
 * Profile Controller
 * Handles profile update actions
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/UserDAL.php';
require_once __DIR__ . '/../dal/AddressDAL.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'update_profile':
            updateProfile();
            break;
        case 'change_password':
            changePassword();
            break;
        case 'update_photo':
            updatePhoto();
            break;
        case 'add_address':
            addAddress();
            break;
        case 'update_address':
            updateAddress();
            break;
        case 'delete_address':
            deleteAddress();
            break;
        case 'set_default_address':
            setDefaultAddress();
            break;
        default:
            header('Location: ' . BASE_URL . 'profile.php');
            exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($action) {
        case 'delete_address':
            deleteAddress();
            break;
        case 'set_default_address':
            setDefaultAddress();
            break;
        default:
            header('Location: ' . BASE_URL . 'profile.php');
            exit;
    }
}

function updateProfile() {
    $userId = $_SESSION['user_id'];
    $name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $email_updates = isset($_POST['email_updates']) ? 1 : 0;

    if (empty($name)) {
        $_SESSION['profile_error'] = 'Full name is required.';
        header('Location: ' . BASE_URL . 'profile.php?section=profile');
        exit;
    }

    try {
        $database = new Database();
        $conn = $database->getConnection();

        $query = "UPDATE users SET name = :name, phone = :phone, dob = :dob, 
                  address = :address, city = :city, district = :district, 
                  postal_code = :postal_code, email_updates = :email_updates,
                  updated_at = NOW() WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':name' => $name,
            ':phone' => $phone,
            ':dob' => $dob,
            ':address' => $address,
            ':city' => $city,
            ':district' => $district,
            ':postal_code' => $postal_code,
            ':email_updates' => $email_updates,
            ':id' => $userId
        ]);

        $_SESSION['user_name'] = $name;
        $_SESSION['profile_success'] = 'Profile updated successfully!';
    } catch (Exception $e) {
        $_SESSION['profile_error'] = 'Failed to update profile. Please try again.';
    }

    header('Location: ' . BASE_URL . 'profile.php?section=profile');
    exit;
}

function changePassword() {
    $userId = $_SESSION['user_id'];
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $_SESSION['profile_error'] = 'All password fields are required.';
        header('Location: ' . BASE_URL . 'profile.php?section=password');
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        $_SESSION['profile_error'] = 'New passwords do not match.';
        header('Location: ' . BASE_URL . 'profile.php?section=password');
        exit;
    }

    if (strlen($newPassword) < 6) {
        $_SESSION['profile_error'] = 'New password must be at least 6 characters.';
        header('Location: ' . BASE_URL . 'profile.php?section=password');
        exit;
    }

    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            $_SESSION['profile_error'] = 'Current password is incorrect.';
            header('Location: ' . BASE_URL . 'profile.php?section=password');
            exit;
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':password' => $hashedPassword, ':id' => $userId]);

        $_SESSION['profile_success'] = 'Password changed successfully!';
    } catch (Exception $e) {
        $_SESSION['profile_error'] = 'Failed to change password. Please try again.';
    }

    header('Location: ' . BASE_URL . 'profile.php?section=password');
    exit;
}

function updatePhoto() {
    $userId = $_SESSION['user_id'];

    if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['profile_error'] = 'Please select a valid image file.';
        header('Location: ' . BASE_URL . 'profile.php?section=profile');
        exit;
    }

    $file = $_FILES['profile_photo'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowedTypes)) {
        $_SESSION['profile_error'] = 'Only JPG, PNG, and WEBP images are allowed.';
        header('Location: ' . BASE_URL . 'profile.php?section=profile');
        exit;
    }

    if ($file['size'] > $maxSize) {
        $_SESSION['profile_error'] = 'Image size must be less than 5MB.';
        header('Location: ' . BASE_URL . 'profile.php?section=profile');
        exit;
    }

    $uploadDir = __DIR__ . '/../assets/uploads/profiles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $userId . '_' . uniqid() . '.' . $ext;
    $destination = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        try {
            $database = new Database();
            $conn = $database->getConnection();

            // Delete old photo
            $stmt = $conn->prepare("SELECT profile_photo FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch();
            if (!empty($user['profile_photo'])) {
                $oldFile = $uploadDir . $user['profile_photo'];
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }

            $stmt = $conn->prepare("UPDATE users SET profile_photo = :photo, updated_at = NOW() WHERE id = :id");
            $stmt->execute([':photo' => $filename, ':id' => $userId]);

            $_SESSION['profile_success'] = 'Profile photo updated!';
        } catch (Exception $e) {
            $_SESSION['profile_error'] = 'Failed to save photo. Please try again.';
        }
    } else {
        $_SESSION['profile_error'] = 'Failed to upload photo.';
    }

    header('Location: ' . BASE_URL . 'profile.php?section=profile');
    exit;
}

function addAddress() {
    $userId = $_SESSION['user_id'];
    $data = [
        'label' => trim($_POST['label'] ?? 'Home'),
        'full_name' => trim($_POST['addr_name'] ?? ''),
        'phone' => trim($_POST['addr_phone'] ?? ''),
        'address' => trim($_POST['addr_address'] ?? ''),
        'city' => trim($_POST['addr_city'] ?? ''),
        'district' => trim($_POST['addr_district'] ?? ''),
        'postal_code' => trim($_POST['addr_postal'] ?? ''),
        'is_default' => isset($_POST['is_default']) ? 1 : 0
    ];

    if (empty($data['address'])) {
        $_SESSION['profile_error'] = 'Address is required.';
        header('Location: ' . BASE_URL . 'profile.php?section=address');
        exit;
    }

    try {
        $addressDAL = new AddressDAL();
        $addressDAL->create($userId, $data);
        $_SESSION['profile_success'] = 'Address added successfully!';
    } catch (Exception $e) {
        $_SESSION['profile_error'] = 'Failed to add address: ' . $e->getMessage();
    }

    header('Location: ' . BASE_URL . 'profile.php?section=address');
    exit;
}

function updateAddress() {
    $userId = $_SESSION['user_id'];
    $addressId = (int)($_POST['address_id'] ?? 0);

    if (!$addressId) {
        header('Location: ' . BASE_URL . 'profile.php?section=address');
        exit;
    }

    $data = [
        'label' => trim($_POST['label'] ?? 'Home'),
        'full_name' => trim($_POST['addr_name'] ?? ''),
        'phone' => trim($_POST['addr_phone'] ?? ''),
        'address' => trim($_POST['addr_address'] ?? ''),
        'city' => trim($_POST['addr_city'] ?? ''),
        'district' => trim($_POST['addr_district'] ?? ''),
        'postal_code' => trim($_POST['addr_postal'] ?? ''),
        'is_default' => isset($_POST['is_default']) ? 1 : 0
    ];

    if (empty($data['address'])) {
        $_SESSION['profile_error'] = 'Address is required.';
        header('Location: ' . BASE_URL . 'profile.php?section=address');
        exit;
    }

    try {
        $addressDAL = new AddressDAL();
        $addressDAL->update($addressId, $userId, $data);
        $_SESSION['profile_success'] = 'Address updated successfully!';
    } catch (Exception $e) {
        $_SESSION['profile_error'] = 'Failed to update address. Please try again.';
    }

    header('Location: ' . BASE_URL . 'profile.php?section=address');
    exit;
}

function deleteAddress() {
    $userId = $_SESSION['user_id'];
    $addressId = (int)($_POST['address_id'] ?? $_GET['id'] ?? 0);

    if (!$addressId) {
        header('Location: ' . BASE_URL . 'profile.php?section=address');
        exit;
    }

    try {
        $addressDAL = new AddressDAL();
        $addressDAL->delete($addressId, $userId);
        $_SESSION['profile_success'] = 'Address deleted.';
    } catch (Exception $e) {
        $_SESSION['profile_error'] = 'Failed to delete address.';
    }

    header('Location: ' . BASE_URL . 'profile.php?section=address');
    exit;
}

function setDefaultAddress() {
    $userId = $_SESSION['user_id'];
    $addressId = (int)($_POST['address_id'] ?? $_GET['id'] ?? 0);

    if (!$addressId) {
        header('Location: ' . BASE_URL . 'profile.php?section=address');
        exit;
    }

    try {
        $addressDAL = new AddressDAL();
        $addressDAL->setDefault($addressId, $userId);
        $_SESSION['profile_success'] = 'Default address updated.';
    } catch (Exception $e) {
        $_SESSION['profile_error'] = 'Failed to set default address.';
    }

    header('Location: ' . BASE_URL . 'profile.php?section=address');
    exit;
}
