<?php
$currentPage = 'banners';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../dal/BannerDAL.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$bannerDAL = new BannerDAL();
$adminName = $_SESSION['user_name'] ?? 'Admin';
$message = '';
$messageType = 'success';

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
    $mediaType = ($_POST['media_type'] ?? 'image') === 'video' ? 'video' : 'image';
    $title = trim($_POST['title'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);

    if ($mediaType === 'image') {
        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['banner_image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];

            if ((in_array($file['type'], $allowedTypes, true) || in_array($ext, $allowedExts, true)) && $file['size'] < 15 * 1024 * 1024) {
                $filename = 'banner_' . time() . '_' . uniqid() . '.' . $ext;
                $dest = __DIR__ . '/../assets/uploads/banners/' . $filename;
                if (!is_dir(dirname($dest))) {
                    @mkdir(dirname($dest), 0775, true);
                }
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    require_once __DIR__ . '/../services/ImageOptimizer.php';
                    $optimizedPath = ImageOptimizer::optimizeBanner($dest, 96);
                    $optimizedFilename = basename($optimizedPath);

                    $bannerDAL->create($title, 'assets/uploads/banners/' . $optimizedFilename, $link, $sortOrder, 'image', null);
                    $message = 'Image banner uploaded successfully!';
                } else {
                    $message = 'Failed to move uploaded image.';
                    $messageType = 'error';
                }
            } else {
                $message = 'Invalid image. Please use JPG, PNG, or WebP under 15MB.';
                $messageType = 'error';
            }
        } else {
            $message = 'Please select a banner image file.';
            $messageType = 'error';
        }
    } else {
        // Video banner
        $videoSavedPath = null;
        $posterSavedPath = null;

        if (isset($_FILES['banner_video']) && $_FILES['banner_video']['error'] === UPLOAD_ERR_OK) {
            $vFile = $_FILES['banner_video'];
            $vExt = strtolower(pathinfo($vFile['name'], PATHINFO_EXTENSION));
            $allowedVidExts = ['mp4', 'webm', 'mov', 'ogg', 'm4v'];

            if (in_array($vExt, $allowedVidExts, true) && $vFile['size'] < 100 * 1024 * 1024) {
                $vFilename = 'banner_vid_' . time() . '_' . uniqid() . '.' . $vExt;
                $vDest = __DIR__ . '/../assets/uploads/banners/' . $vFilename;
                if (!is_dir(dirname($vDest))) {
                    @mkdir(dirname($vDest), 0775, true);
                }
                if (move_uploaded_file($vFile['tmp_name'], $vDest)) {
                    $videoSavedPath = 'assets/uploads/banners/' . $vFilename;
                }
            } else {
                $message = 'Invalid video file. Please use MP4, WebM, or MOV under 100MB.';
                $messageType = 'error';
            }
        } elseif (!empty($_POST['video_url'])) {
            $videoSavedPath = trim($_POST['video_url']);
        }

        // Optional poster image
        if (isset($_FILES['video_poster']) && $_FILES['video_poster']['error'] === UPLOAD_ERR_OK) {
            $pFile = $_FILES['video_poster'];
            $pExt = strtolower(pathinfo($pFile['name'], PATHINFO_EXTENSION));
            if (in_array($pExt, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $pFilename = 'banner_poster_' . time() . '_' . uniqid() . '.' . $pExt;
                $pDest = __DIR__ . '/../assets/uploads/banners/' . $pFilename;
                if (move_uploaded_file($pFile['tmp_name'], $pDest)) {
                    require_once __DIR__ . '/../services/ImageOptimizer.php';
                    $optimizedPoster = ImageOptimizer::optimizeBanner($pDest, 90);
                    $posterSavedPath = 'assets/uploads/banners/' . basename($optimizedPoster);
                }
            }
        }

        if ($videoSavedPath) {
            $bannerDAL->create($title, $posterSavedPath, $link, $sortOrder, 'video', $videoSavedPath);
            $message = 'Animated video banner added successfully!';
        } elseif (!$message) {
            $message = 'Please select a video file (.mp4 / .webm) or provide a video URL.';
            $messageType = 'error';
        }
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $bannerDAL->delete((int)$_POST['id']);
    header('Location: ' . BASE_URL . 'admin/banners.php');
    exit;
}

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $newStatus = $_POST['status'] === 'active' ? 'inactive' : 'active';
    $bannerDAL->updateStatus((int)$_POST['id'], $newStatus);
    header('Location: ' . BASE_URL . 'admin/banners.php');
    exit;
}

$banners = $bannerDAL->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/partials/favicon.php'; ?>
    <title>Banners - Admin | <?php echo ADMIN_BRAND; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .banner-type-tabs { display:flex; gap:10px; margin-bottom:20px; }
        .banner-tab-btn {
            padding:10px 22px; border:1.5px solid #002868; background:#fff; color:#001f52;
            font-size:13px; font-weight:600; border-radius:10px; cursor:pointer; font-family:'Poppins',sans-serif;
            transition:all 0.25s ease; display:flex; align-items:center; gap:8px;
        }
        .banner-tab-btn.active {
            background:#001f52; color:#fff; border-color:#001f52; box-shadow:0 4px 14px rgba(0,40,104,0.25);
        }
        .field-label { display:block; font-size:12px; font-weight:600; color:#333; margin-bottom:6px; }
        .field-hint { font-size:11px; color:#777; font-weight:400; margin-left:4px; }
        .form-input {
            width:100%; padding:10px 14px; border:1.5px solid #e4e8f0; border-radius:10px;
            font-family:'Poppins',sans-serif; font-size:13px; box-sizing:border-box;
        }
        .form-input:focus { outline:none; border-color:#001f52; box-shadow:0 0 0 3px rgba(0,40,104,0.15); }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/partials/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/partials/topbar.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Banner Management</h1>
                        <p class="page-subtitle">Manage homepage slider banners with image or autoplay animated video</p>
                    </div>
                </div>

                <?php if ($message): ?>
                <div style="padding:14px 20px; border-radius:10px; margin-bottom:20px; font-size:13px; <?php echo $messageType === 'error' ? 'background:#fef2f2; color:#991b1b; border:1px solid #fecaca;' : 'background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0;'; ?>">
                    <i class="fas fa-<?php echo $messageType === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i> <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <!-- Upload Form -->
                <div style="background:#fff; border:1px solid #eee; border-radius:14px; padding:28px; margin-bottom:28px; box-shadow:0 4px 20px rgba(0,0,0,0.03);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; flex-wrap:wrap; gap:12px;">
                        <h3 style="font-size:16px; font-weight:700; color:#0d1137; margin:0;">
                            <i class="fas fa-plus-circle" style="color:#001f52;"></i> Add New Homepage Banner
                        </h3>
                        <div class="banner-type-tabs">
                            <button type="button" class="banner-tab-btn active" id="btnTypeImage" onclick="selectMediaType('image')">
                                <i class="fas fa-image"></i> Image Banner
                            </button>
                            <button type="button" class="banner-tab-btn" id="btnTypeVideo" onclick="selectMediaType('video')">
                                <i class="fas fa-video"></i> Video Banner (MP4)
                            </button>
                        </div>
                    </div>

                    <form method="POST" enctype="multipart/form-data" id="bannerUploadForm">
                        <input type="hidden" name="action" value="upload">
                        <input type="hidden" name="media_type" id="mediaTypeInput" value="image">

                        <!-- Image Banner Section -->
                        <div id="imageFieldsGroup">
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                                <div>
                                    <label class="field-label">Banner Image <span class="field-hint">(1920x650 or 1200x450 recommended, Max 15MB)</span></label>
                                    <input type="file" name="banner_image" id="bannerImageInput" accept="image/*" class="form-input" style="padding:8px 12px;">
                                </div>
                                <div>
                                    <label class="field-label">Banner Title <span class="field-hint">(optional)</span></label>
                                    <input type="text" name="title" placeholder="e.g. Rare Royal Blue Ceylon Sapphires" class="form-input">
                                </div>
                            </div>
                        </div>

                        <!-- Video Banner Section -->
                        <div id="videoFieldsGroup" style="display:none;">
                            <div style="padding:14px 18px; background:#f8fafd; border:1px solid #e8f0fe; border-radius:10px; margin-bottom:16px; font-size:12px; color:#001f52; line-height:1.6;">
                                <i class="fas fa-info-circle"></i> <strong>Animated Video Banner:</strong> Videos will automatically loop and play smoothly on page load. On mobile screens, video banners will be displayed in an expanded, prominent vertical format for a stunning visual showcase!
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                                <div>
                                    <label class="field-label">Video File <span class="field-hint">(MP4 / WebM / MOV, Max 100MB)</span></label>
                                    <input type="file" name="banner_video" id="bannerVideoInput" accept="video/mp4,video/webm,video/quicktime,video/*" class="form-input" style="padding:8px 12px;">
                                </div>
                                <div>
                                    <label class="field-label">Or Direct Video URL <span class="field-hint">(optional external MP4 link)</span></label>
                                    <input type="url" name="video_url" placeholder="https://example.com/gemstone-tour.mp4" class="form-input">
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                                <div>
                                    <label class="field-label">Poster / Fallback Image <span class="field-hint">(optional preview before video loads)</span></label>
                                    <input type="file" name="video_poster" accept="image/*" class="form-input" style="padding:8px 12px;">
                                </div>
                                <div>
                                    <label class="field-label">Banner Title <span class="field-hint">(optional)</span></label>
                                    <input type="text" name="video_title" id="videoTitleField" placeholder="e.g. Sri Lankan Sapphire Heritage Video" class="form-input" oninput="document.querySelector('input[name=title]').value = this.value">
                                </div>
                            </div>
                        </div>

                        <!-- Common Fields: Link & Sort Order -->
                        <div style="display:grid; grid-template-columns:2fr 100px 140px; gap:16px; align-items:end;">
                            <div>
                                <label class="field-label">Target Link <span class="field-hint">(optional URL when banner is clicked)</span></label>
                                <input type="text" name="link" placeholder="e.g. shop.php?category=ceylon-sapphires or about.php" class="form-input">
                            </div>
                            <div>
                                <label class="field-label">Sort Order</label>
                                <input type="number" name="sort_order" value="0" min="0" class="form-input">
                            </div>
                            <div>
                                <button type="submit" style="width:100%; padding:11px 20px; background:#001f52; color:#fff; border:none; border-radius:10px; font-family:'Poppins',sans-serif; font-size:13px; font-weight:600; cursor:pointer; height:42px; display:flex; align-items:center; justify-content:center; gap:8px;">
                                    <i class="fas fa-save"></i> Save Banner
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Banners Grid -->
                <h3 style="font-size:15px; font-weight:700; color:#0d1137; margin-bottom:16px;">Existing Banners (<?php echo count($banners); ?>)</h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:20px;">
                    <?php foreach ($banners as $b): 
                        $isVideo = (($b['media_type'] ?? '') === 'video') || (!empty($b['video']));
                        $mediaSrc = $isVideo ? ($b['video'] ?? '') : ($b['image'] ?? '');
                    ?>
                    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:14px; overflow:hidden; box-shadow:0 3px 12px rgba(0,0,0,0.04); display:flex; flex-direction:column;">
                        <!-- Media Preview Container -->
                        <div style="position:relative; width:100%; height:180px; background:#111; overflow:hidden;">
                            <?php if ($isVideo): ?>
                                <video src="<?php echo htmlspecialchars(media_image_url($b['video'])); ?>" 
                                       poster="<?php echo !empty($b['image']) ? htmlspecialchars(media_image_url($b['image'])) : ''; ?>"
                                       autoplay muted loop playsinline 
                                       style="width:100%; height:100%; object-fit:cover; display:block;">
                                </video>
                                <span style="position:absolute; top:10px; left:10px; background:rgba(0, 40, 104, 0.9); color:#fff; font-size:10px; font-weight:700; padding:4px 9px; border-radius:6px; letter-spacing:0.04em; text-transform:uppercase; display:flex; align-items:center; gap:5px; box-shadow:0 2px 8px rgba(0,0,0,0.3);">
                                    <i class="fas fa-video"></i> Video Banner
                                </span>
                            <?php else: ?>
                                <img src="<?php echo htmlspecialchars(media_image_url($b['image'])); ?>" alt="" style="width:100%; height:100%; object-fit:cover; display:block;">
                                <span style="position:absolute; top:10px; left:10px; background:rgba(0, 40, 104, 0.9); color:#fff; font-size:10px; font-weight:700; padding:4px 9px; border-radius:6px; letter-spacing:0.04em; text-transform:uppercase; display:flex; align-items:center; gap:5px; box-shadow:0 2px 8px rgba(0,0,0,0.3);">
                                    <i class="fas fa-image"></i> Image Banner
                                </span>
                            <?php endif; ?>

                            <span style="position:absolute; top:10px; right:10px; background:<?php echo $b['status'] === 'active' ? '#16a34a' : '#ef4444'; ?>; color:#fff; font-size:10px; font-weight:700; padding:3px 8px; border-radius:6px; text-transform:uppercase;">
                                <?php echo ucfirst($b['status']); ?>
                            </span>
                        </div>

                        <!-- Banner Info & Controls -->
                        <div style="padding:16px; flex:1; display:flex; flex-direction:column; justify-content:space-between; gap:12px;">
                            <div>
                                <strong style="font-size:14px; color:#0d1137; display:block; margin-bottom:4px;">
                                    <?php echo htmlspecialchars($b['title'] ?: ($isVideo ? 'Video Banner #' . $b['id'] : 'Banner #' . $b['id'])); ?>
                                </strong>
                                <?php if (!empty($b['link'])): ?>
                                <span style="display:block; font-size:11px; color:#001f52; word-break:break-all;">
                                    <i class="fas fa-link"></i> <?php echo htmlspecialchars($b['link']); ?>
                                </span>
                                <?php endif; ?>
                                <span style="display:block; font-size:11px; color:#888; margin-top:4px;">
                                    Sort Order: <strong><?php echo (int)($b['sort_order'] ?? 0); ?></strong>
                                </span>
                            </div>

                            <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #f1f3f7; padding-top:12px;">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo $b['status']; ?>">
                                    <button type="submit" style="padding:7px 12px; background:#f0f2f8; border:none; border-radius:8px; cursor:pointer; font-size:12px; font-weight:500; display:flex; align-items:center; gap:6px;" title="Toggle Visibility">
                                        <i class="fas fa-<?php echo $b['status'] === 'active' ? 'eye-slash' : 'eye'; ?>"></i>
                                        <?php echo $b['status'] === 'active' ? 'Disable' : 'Enable'; ?>
                                    </button>
                                </form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this banner?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                    <button type="submit" style="padding:7px 12px; background:#fef2f2; color:#ef4444; border:none; border-radius:8px; cursor:pointer; font-size:12px; font-weight:500; display:flex; align-items:center; gap:6px;">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if (empty($banners)): ?>
                    <div style="padding:50px 20px; text-align:center; color:#999; font-size:14px; grid-column:1/-1; background:#fff; border-radius:14px; border:1px dashed #d1d5db;">
                        <i class="fas fa-photo-video" style="font-size:36px; display:block; margin-bottom:12px; color:#cbd5e1;"></i>
                        No banners uploaded yet. Upload an image or animated video banner above to display on your homepage.
                    </div>
                    <?php endif; ?>
                </div>

                <div class="admin-footer" style="margin-top:40px;">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo ADMIN_BRAND; ?>. All rights reserved.</p>
                </div>
            </div>
        </main>
    </div>

    <script>
    function selectMediaType(type) {
        var input = document.getElementById('mediaTypeInput');
        var btnImage = document.getElementById('btnTypeImage');
        var btnVideo = document.getElementById('btnTypeVideo');
        var imgGroup = document.getElementById('imageFieldsGroup');
        var vidGroup = document.getElementById('videoFieldsGroup');
        var imgInput = document.getElementById('bannerImageInput');

        if (type === 'video') {
            input.value = 'video';
            btnVideo.classList.add('active');
            btnImage.classList.remove('active');
            imgGroup.style.display = 'none';
            vidGroup.style.display = 'block';
            imgInput.removeAttribute('required');
        } else {
            input.value = 'image';
            btnImage.classList.add('active');
            btnVideo.classList.remove('active');
            imgGroup.style.display = 'block';
            vidGroup.style.display = 'none';
        }
    }

    document.getElementById('menuToggle').addEventListener('click', function() {
        document.querySelector('.admin-layout').classList.toggle('sidebar-collapsed');
    });
    </script>
</body>
</html>
