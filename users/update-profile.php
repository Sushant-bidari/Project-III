<?php
require "../config/config.php";

/* ============================================================
   AUTHENTICATION
   ============================================================ */

if (!isset($_SESSION['username'])) {
    header("Location: " . APPURL);
    exit();
}

/* ============================================================
   RESUMES AJAX ENDPOINTS
   ============================================================ */

if (!empty($_POST['ajax']) && isset($_POST['action'])) {

    header('Content-Type: application/json');

    $meId   = (int)($_SESSION['id'] ?? 0);
    $meType = $_SESSION['type'] ?? '';

    if (!$meId || $meType !== 'Job Seeker') {
        echo json_encode([
            'ok' => false,
            'error' => 'Not authorized'
        ]);
        exit;
    }

    /* ---------- Helpers ---------- */

    function safe_ext($name, $allowed = ['pdf', 'doc', 'docx'])
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return in_array($ext, $allowed, true) ? $ext : null;
    }

    function cv_dir_fs()
    {
        return __DIR__ . '/user-cvs/';
    }

    function cv_dir_url()
    {
        return 'user-cvs/';
    }

    $action = $_POST['action'];

    /* ========================================================
       LIST RESUMES
       ======================================================== */

    if ($action === 'list_resumes') {

        try {

            $q = $conn->prepare("
                SELECT id, filename, original_name, is_primary, created_at
                FROM resumes
                WHERE user_id = :u
                ORDER BY is_primary DESC, id DESC
            ");

            $q->execute([
                ':u' => $meId
            ]);

            $rows = $q->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'ok' => true,
                'items' => $rows
            ]);

        } catch (Throwable $e) {

            echo json_encode([
                'ok' => false,
                'error' => 'Unable to load resumes'
            ]);
        }

        exit;
    }

    /* ========================================================
       UPLOAD NEW RESUME
       ======================================================== */

    if ($action === 'upload_resume') {

        try {

            if (
                !isset($_FILES['resume_new']) ||
                $_FILES['resume_new']['error'] !== UPLOAD_ERR_OK
            ) {
                echo json_encode([
                    'ok' => false,
                    'error' => 'No file uploaded'
                ]);
                exit;
            }

            $file = $_FILES['resume_new'];

            $ext = safe_ext($file['name']);

            if (!$ext) {
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid file type. Only PDF, DOC and DOCX are allowed.'
                ]);
                exit;
            }

            /* Maximum 5 MB */
            if ($file['size'] > 5 * 1024 * 1024) {
                echo json_encode([
                    'ok' => false,
                    'error' => 'Resume size must be less than 5 MB.'
                ]);
                exit;
            }

            $dir = cv_dir_fs();

            if (!is_dir($dir)) {
                if (!mkdir($dir, 0775, true)) {
                    echo json_encode([
                        'ok' => false,
                        'error' => 'Unable to create resume directory.'
                    ]);
                    exit;
                }
            }

            $newName =
                'cv_' .
                $meId . '_' .
                time() . '_' .
                bin2hex(random_bytes(4)) .
                '.' .
                $ext;

            $destination = $dir . $newName;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {

                echo json_encode([
                    'ok' => false,
                    'error' => 'Upload failed. Check the user-cvs folder permissions.'
                ]);

                exit;
            }

            /* ---------- Primary resume ---------- */

            $makePrimary = !empty($_POST['make_primary']) ? 1 : 0;

            if ($makePrimary) {

                $reset = $conn->prepare("
                    UPDATE resumes
                    SET is_primary = 0
                    WHERE user_id = :u
                ");

                $reset->execute([
                    ':u' => $meId
                ]);
            }

            /* If this is the user's first resume, make it primary */

            $hasAny = $conn->prepare("
                SELECT id
                FROM resumes
                WHERE user_id = :u
                LIMIT 1
            ");

            $hasAny->execute([
                ':u' => $meId
            ]);

            if (!$hasAny->fetchColumn()) {
                $makePrimary = 1;
            }

            /* ---------- Insert ---------- */

            $ins = $conn->prepare("
                INSERT INTO resumes
                (
                    user_id,
                    filename,
                    original_name,
                    is_primary
                )
                VALUES
                (
                    :u,
                    :fn,
                    :on,
                    :p
                )
            ");

            $ins->execute([
                ':u'  => $meId,
                ':fn' => $newName,
                ':on' => basename($file['name']),
                ':p'  => $makePrimary
            ]);

            echo json_encode([
                'ok' => true
            ]);

        } catch (Throwable $e) {

            echo json_encode([
                'ok' => false,
                'error' => 'Server error while uploading resume.'
            ]);
        }

        exit;
    }

    /* ========================================================
       SET PRIMARY RESUME
       ======================================================== */

    if ($action === 'set_primary') {

        $rid = (int)($_POST['id'] ?? 0);

        if ($rid <= 0) {
            echo json_encode([
                'ok' => false,
                'error' => 'Invalid resume.'
            ]);
            exit;
        }

        try {

            /* Verify ownership */

            $own = $conn->prepare("
                SELECT id
                FROM resumes
                WHERE id = :id
                AND user_id = :u
                LIMIT 1
            ");

            $own->execute([
                ':id' => $rid,
                ':u'  => $meId
            ]);

            if (!$own->fetchColumn()) {

                echo json_encode([
                    'ok' => false,
                    'error' => 'Resume not found.'
                ]);

                exit;
            }

            /* Remove primary from all */

            $conn->prepare("
                UPDATE resumes
                SET is_primary = 0
                WHERE user_id = :u
            ")->execute([
                ':u' => $meId
            ]);

            /* Set selected resume as primary */

            $conn->prepare("
                UPDATE resumes
                SET is_primary = 1
                WHERE id = :id
                AND user_id = :u
            ")->execute([
                ':id' => $rid,
                ':u'  => $meId
            ]);

            echo json_encode([
                'ok' => true
            ]);

        } catch (Throwable $e) {

            echo json_encode([
                'ok' => false,
                'error' => 'Failed to set primary resume.'
            ]);
        }

        exit;
    }

    /* ========================================================
       DELETE RESUME
       ======================================================== */

    if ($action === 'delete_resume') {

        $rid = (int)($_POST['id'] ?? 0);

        if ($rid <= 0) {
            echo json_encode([
                'ok' => false,
                'error' => 'Invalid resume.'
            ]);
            exit;
        }

        try {

            $q = $conn->prepare("
                SELECT filename, is_primary
                FROM resumes
                WHERE id = :id
                AND user_id = :u
                LIMIT 1
            ");

            $q->execute([
                ':id' => $rid,
                ':u'  => $meId
            ]);

            $rowR = $q->fetch(PDO::FETCH_ASSOC);

            if (!$rowR) {

                echo json_encode([
                    'ok' => false,
                    'error' => 'Resume not found.'
                ]);

                exit;
            }

            /* Delete physical file */

            $filename = basename($rowR['filename']);
            $path = cv_dir_fs() . $filename;

            if (is_file($path)) {
                @unlink($path);
            }

            /* Delete database row */

            $conn->prepare("
                DELETE FROM resumes
                WHERE id = :id
                AND user_id = :u
            ")->execute([
                ':id' => $rid,
                ':u'  => $meId
            ]);

            /* If primary was deleted, promote newest resume */

            if ((int)$rowR['is_primary'] === 1) {

                $next = $conn->prepare("
                    SELECT id
                    FROM resumes
                    WHERE user_id = :u
                    ORDER BY id DESC
                    LIMIT 1
                ");

                $next->execute([
                    ':u' => $meId
                ]);

                $nid = (int)($next->fetchColumn() ?: 0);

                if ($nid > 0) {

                    $conn->prepare("
                        UPDATE resumes
                        SET is_primary = 1
                        WHERE id = :id
                    ")->execute([
                        ':id' => $nid
                    ]);
                }
            }

            echo json_encode([
                'ok' => true
            ]);

        } catch (Throwable $e) {

            echo json_encode([
                'ok' => false,
                'error' => 'Failed to delete resume.'
            ]);
        }

        exit;
    }

    /* ========================================================
       REPLACE RESUME
       ======================================================== */

    if ($action === 'replace_resume') {

        $rid = (int)($_POST['id'] ?? 0);

        if ($rid <= 0) {

            echo json_encode([
                'ok' => false,
                'error' => 'Invalid resume.'
            ]);

            exit;
        }

        if (
            !isset($_FILES['resume_file']) ||
            $_FILES['resume_file']['error'] !== UPLOAD_ERR_OK
        ) {

            echo json_encode([
                'ok' => false,
                'error' => 'No file uploaded.'
            ]);

            exit;
        }

        try {

            $file = $_FILES['resume_file'];

            $ext = safe_ext($file['name']);

            if (!$ext) {

                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid file type. Only PDF, DOC and DOCX are allowed.'
                ]);

                exit;
            }

            if ($file['size'] > 5 * 1024 * 1024) {

                echo json_encode([
                    'ok' => false,
                    'error' => 'Resume size must be less than 5 MB.'
                ]);

                exit;
            }

            /* Get old resume */

            $q = $conn->prepare("
                SELECT filename
                FROM resumes
                WHERE id = :id
                AND user_id = :u
                LIMIT 1
            ");

            $q->execute([
                ':id' => $rid,
                ':u'  => $meId
            ]);

            $old = $q->fetch(PDO::FETCH_ASSOC);

            if (!$old) {

                echo json_encode([
                    'ok' => false,
                    'error' => 'Resume not found.'
                ]);

                exit;
            }

            $dir = cv_dir_fs();

            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            $newName =
                'cv_' .
                $meId . '_' .
                time() . '_' .
                bin2hex(random_bytes(4)) .
                '.' .
                $ext;

            $destination = $dir . $newName;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {

                echo json_encode([
                    'ok' => false,
                    'error' => 'Upload failed.'
                ]);

                exit;
            }

            /* Delete old file */

            $oldFilename = basename($old['filename']);
            $oldPath = $dir . $oldFilename;

            if (is_file($oldPath)) {
                @unlink($oldPath);
            }

            /* Update DB */

            $upd = $conn->prepare("
                UPDATE resumes
                SET
                    filename = :fn,
                    original_name = :on
                WHERE id = :id
                AND user_id = :u
            ");

            $upd->execute([
                ':fn' => $newName,
                ':on' => basename($file['name']),
                ':id' => $rid,
                ':u'  => $meId
            ]);

            echo json_encode([
                'ok' => true
            ]);

        } catch (Throwable $e) {

            echo json_encode([
                'ok' => false,
                'error' => 'Failed to replace resume.'
            ]);
        }

        exit;
    }

    /* Unknown AJAX action */

    echo json_encode([
        'ok' => false,
        'error' => 'Unknown action.'
    ]);

    exit;
}

/* ============================================================
   NORMAL PAGE FLOW
   ============================================================ */

/*
 * IMPORTANT FIX:
 *
 * $_GET values are strings.
 * $_SESSION['id'] may be an integer.
 *
 * Therefore:
 *
 * (int)$_GET['upd_id']
 * and
 * (int)$_SESSION['id']
 *
 * are compared.
 */

if (isset($_GET['upd_id'])) {

    $id = (int)($_GET['upd_id'] ?? 0);
    $sessionId = (int)($_SESSION['id'] ?? 0);

    /* ---------- ID validation ---------- */

    if ($id <= 0 || $sessionId <= 0 || $sessionId !== $id) {

        header("Location: " . APPURL);
        exit();
    }

    /* ---------- Get user ---------- */

    $select = $conn->prepare("
        SELECT *
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

    $select->execute([
        ':id' => $id
    ]);

    $row = $select->fetch(PDO::FETCH_OBJ);

    if (!$row) {

        echo "User not found.";
        exit();
    }

    /* ========================================================
       JOB SEEKER REGIONS
       ======================================================== */

    $regionRows = [];

    if ($row->type === 'Job Seeker') {

        try {

            $rs = $conn->query("
                SELECT id, name
                FROM job_regions
                WHERE status = 1
                ORDER BY name
            ");

            $regionRows = $rs->fetchAll(PDO::FETCH_ASSOC);

        } catch (Throwable $e) {

            $regionRows = [];
        }
    }

    /* ========================================================
       COMPANY DETAILS
       ======================================================== */

    $companyDetails = null;

    if ($row->type === 'Employer') {

        $detailsStmt = $conn->prepare("
            SELECT *
            FROM company_details
            WHERE user_id = :user_id
            LIMIT 1
        ");

        $detailsStmt->execute([
            ':user_id' => $id
        ]);

        $companyDetails = $detailsStmt->fetch(PDO::FETCH_OBJ);
    }

    /* ========================================================
       UPDATE PROFILE
       ======================================================== */

    if (isset($_POST['submit'])) {

        /* ---------- Basic information ---------- */

        $fullname = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $title    = trim($_POST['title'] ?? '');
        $contact  = trim($_POST['contact'] ?? '');
        $bio      = trim($_POST['bio'] ?? '');

        $facebook = trim($_POST['facebook'] ?? '');
        $twitter  = trim($_POST['twitter'] ?? '');
        $linkedin = trim($_POST['linkedin'] ?? '');

        $isJobSeeker = ($row->type === 'Job Seeker');

        /* ---------- Job seeker extras ---------- */

        $skills = $isJobSeeker
            ? trim($_POST['skills'] ?? '')
            : null;

        $education = $isJobSeeker
            ? trim($_POST['education'] ?? '')
            : null;

        /* ====================================================
           REGION + ADDRESS
           ==================================================== */

        $regionId = null;
        $address = null;

        if ($isJobSeeker) {

            $regionId = isset($_POST['region_id']) &&
                        $_POST['region_id'] !== ''
                ? (int)$_POST['region_id']
                : null;

            $address = trim($_POST['address'] ?? '');

            if ($address === '') {
                $address = null;
            }

            /* Validate region */

            if ($regionId !== null) {

                $rgChk = $conn->prepare("
                    SELECT id
                    FROM job_regions
                    WHERE id = :id
                    AND status = 1
                    LIMIT 1
                ");

                $rgChk->execute([
                    ':id' => $regionId
                ]);

                if (!$rgChk->fetchColumn()) {

                    $_SESSION['message'] = [
                        'type' => 'danger',
                        'text' => 'Please select a valid region/state.'
                    ];

                    header(
                        "Location: update-profile.php?upd_id=" . $id
                    );

                    exit();
                }
            }
        }

        /* ====================================================
           PROFILE IMAGE
           ==================================================== */

        $img = $row->img;

        $dir_img = __DIR__ . '/user-images/';

        if (
            isset($_FILES['img']) &&
            !empty($_FILES['img']['name']) &&
            $_FILES['img']['error'] === UPLOAD_ERR_OK
        ) {

            $imageExt = strtolower(
                pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION)
            );

            $allowedImages = [
                'jpg',
                'jpeg',
                'png',
                'gif',
                'webp'
            ];

            if (!in_array($imageExt, $allowedImages, true)) {

                $_SESSION['message'] = [
                    'type' => 'danger',
                    'text' => 'Invalid image type. Please use JPG, PNG, GIF or WEBP.'
                ];

                header(
                    "Location: update-profile.php?upd_id=" . $id
                );

                exit();
            }

            if ($_FILES['img']['size'] > 5 * 1024 * 1024) {

                $_SESSION['message'] = [
                    'type' => 'danger',
                    'text' => 'Profile image must be less than 5 MB.'
                ];

                header(
                    "Location: update-profile.php?upd_id=" . $id
                );

                exit();
            }

            if (!is_dir($dir_img)) {
                mkdir($dir_img, 0775, true);
            }

            /* Delete old image */

            if (!empty($row->img)) {

                $oldImage = $dir_img . basename($row->img);

                if (is_file($oldImage)) {
                    @unlink($oldImage);
                }
            }

            /* Generate unique filename */

            $img =
                'user_' .
                $id . '_' .
                time() . '_' .
                bin2hex(random_bytes(3)) .
                '.' .
                $imageExt;

            $imageDestination = $dir_img . $img;

            if (
                !move_uploaded_file(
                    $_FILES['img']['tmp_name'],
                    $imageDestination
                )
            ) {

                $_SESSION['message'] = [
                    'type' => 'danger',
                    'text' => 'Image upload failed.'
                ];

                header(
                    "Location: update-profile.php?upd_id=" . $id
                );

                exit();
            }
        }

        /* ====================================================
           DATABASE UPDATE
           ==================================================== */

        try {

            /*
             * IMPORTANT:
             * Do not update CV here.
             *
             * Resume is managed separately through the resumes table.
             */

            $update = $conn->prepare("
                UPDATE users SET

                    fullname = :fullname,
                    username = :username,
                    email = :email,
                    title = :title,
                    contact = :contact,
                    bio = :bio,
                    facebook = :facebook,
                    twitter = :twitter,
                    linkedin = :linkedin,
                    img = :img,
                    skills = :skills,
                    education = :education,
                    region_id = :region_id,
                    address = :address

                WHERE id = :id
            ");

            $update->bindValue(
                ':fullname',
                $fullname,
                PDO::PARAM_STR
            );

            $update->bindValue(
                ':username',
                $username,
                PDO::PARAM_STR
            );

            $update->bindValue(
                ':email',
                $email,
                PDO::PARAM_STR
            );

            $update->bindValue(
                ':title',
                $title,
                PDO::PARAM_STR
            );

            $update->bindValue(
                ':contact',
                $contact,
                PDO::PARAM_STR
            );

            $update->bindValue(
                ':bio',
                $bio,
                PDO::PARAM_STR
            );

            $update->bindValue(
                ':facebook',
                $facebook,
                PDO::PARAM_STR
            );

            $update->bindValue(
                ':twitter',
                $twitter,
                PDO::PARAM_STR
            );

            $update->bindValue(
                ':linkedin',
                $linkedin,
                PDO::PARAM_STR
            );

            $update->bindValue(
                ':img',
                $img,
                PDO::PARAM_STR
            );

            if ($isJobSeeker && $skills !== null) {

                $update->bindValue(
                    ':skills',
                    $skills,
                    PDO::PARAM_STR
                );

            } else {

                $update->bindValue(
                    ':skills',
                    null,
                    PDO::PARAM_NULL
                );
            }

            if ($isJobSeeker && $education !== null) {

                $update->bindValue(
                    ':education',
                    $education,
                    PDO::PARAM_STR
                );

            } else {

                $update->bindValue(
                    ':education',
                    null,
                    PDO::PARAM_NULL
                );
            }

            /* ---------- FIXED NULL HANDLING ---------- */

            if ($isJobSeeker && $regionId !== null) {

                $update->bindValue(
                    ':region_id',
                    $regionId,
                    PDO::PARAM_INT
                );

            } else {

                $update->bindValue(
                    ':region_id',
                    null,
                    PDO::PARAM_NULL
                );
            }

            if ($isJobSeeker && $address !== null) {

                $update->bindValue(
                    ':address',
                    $address,
                    PDO::PARAM_STR
                );

            } else {

                $update->bindValue(
                    ':address',
                    null,
                    PDO::PARAM_NULL
                );
            }

            $update->bindValue(
                ':id',
                $id,
                PDO::PARAM_INT
            );

            $update->execute();

            /* =================================================
               EMPLOYER COMPANY DETAILS
               ================================================= */

            if (!$isJobSeeker) {

                $company_website =
                    trim($_POST['company_website'] ?? '');

                $industry =
                    trim($_POST['industry'] ?? '');

                $address_line =
                    trim($_POST['address_line'] ?? '');

                $postal_code =
                    trim($_POST['postal_code'] ?? '');

                $established_year =
                    trim($_POST['established_year'] ?? '');

                $operating_hours =
                    trim($_POST['operating_hours'] ?? '');

                /* Check existing company details */

                $check = $conn->prepare("
                    SELECT id
                    FROM company_details
                    WHERE user_id = :user_id
                    LIMIT 1
                ");

                $check->execute([
                    ':user_id' => $id
                ]);

                $companyId = $check->fetchColumn();

                if ($companyId) {

                    /* ---------- UPDATE ---------- */

                    $updateDetails = $conn->prepare("
                        UPDATE company_details SET

                            company_website = :company_website,
                            industry = :industry,
                            address_line = :address_line,
                            postal_code = :postal_code,
                            established_year = :established_year,
                            operating_hours = :operating_hours,
                            updated_at = CURRENT_TIMESTAMP

                        WHERE user_id = :user_id
                    ");

                } else {

                    /* ---------- INSERT ---------- */

                    $updateDetails = $conn->prepare("
                        INSERT INTO company_details
                        (
                            company_website,
                            industry,
                            address_line,
                            postal_code,
                            established_year,
                            operating_hours,
                            user_id
                        )
                        VALUES
                        (
                            :company_website,
                            :industry,
                            :address_line,
                            :postal_code,
                            :established_year,
                            :operating_hours,
                            :user_id
                        )
                    ");
                }

                $updateDetails->execute([
                    ':company_website' => $company_website,
                    ':industry' => $industry,
                    ':address_line' => $address_line,
                    ':postal_code' => $postal_code,
                    ':established_year' => $established_year,
                    ':operating_hours' => $operating_hours,
                    ':user_id' => $id
                ]);
            }

            /* ---------- Success ---------- */

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Your profile has been updated successfully!'
            ];

        } catch (PDOException $e) {

            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'Failed to update profile: ' . $e->getMessage()
            ];
        }

        header(
            "Location: update-profile.php?upd_id=" . $id
        );

        exit();
    }

} else {

    echo "404";
    exit();
}

require "../includes/header.php";
?>

<style>

/* ============================================================
   GENERAL
   ============================================================ */

.section-hero.inner-page,
.section-hero.inner-page > .container > .row {
    height: 175px;
}

.site-section {
    padding-top: 2rem;
}

.profile-wrap .card {
    border: 0;
    border-radius: 14px;
    box-shadow: 0 6px 24px rgba(0,0,0,.06);
}

.profile-wrap .card-header {
    background: #fff;
    border-bottom: 1px solid rgba(0,0,0,.06);
    border-top-left-radius: 14px;
    border-top-right-radius: 14px;
    padding: 14px 16px;
}

.profile-wrap .card-header h6 {
    margin: 0;
    font-weight: 800;
}

.profile-wrap .help {
    font-size: .875rem;
    color: #6b7280;
}

/* ============================================================
   HERO
   ============================================================ */

.profile-hero {
    position: relative;
    background-size: cover;
    background-position: center;
    padding: 60px 0;
    overflow: hidden;
}

.profile-hero .overlay-dark {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(
            1200px 400px at 10% -10%,
            rgba(99,102,241,.22),
            transparent 60%
        ),
        radial-gradient(
            1200px 400px at 90% 0%,
            rgba(6,182,212,.18),
            transparent 60%
        ),
        linear-gradient(
            180deg,
            rgba(2,6,23,.65),
            rgba(2,6,23,.80)
        );
}

.profile-hero .hero-inner {
    position: relative;
    z-index: 2;
}

.ch-eyebrow {
    color: #c7d2fe;
    text-transform: uppercase;
    letter-spacing: .16em;
    font-weight: 700;
    font-size: .75rem;
}

.ch-title {
    color: #fff;
    font-weight: 800;
}

.ch-sub {
    color: #e2e8f0;
}

/* ============================================================
   STICKY NAV
   ============================================================ */

.profile-subnav {
    position: sticky;
    top: 0;
    z-index: 20;
    background: #fff;
    border-bottom: 1px solid #e9ecef;
}

.profile-subnav .nav {
    gap: .5rem;
    padding: .75rem 0;
}

.profile-subnav .nav-link {
    border: 1px solid #e5e7eb;
    border-radius: 999px;
    padding: .45rem .85rem;
    color: #475569;
    font-weight: 700;
}

.profile-subnav .nav-link.active {
    background: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}

/* ============================================================
   FORM
   ============================================================ */

.form-control,
.custom-select {
    min-height: 46px;
}

.input-group-text {
    background: #f8fafc;
}

/* ============================================================
   AVATAR
   ============================================================ */

.avatar-box {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px;
    border: 1px dashed rgba(0,0,0,.12);
    border-radius: 10px;
    background: #fafafa;
}

.avatar {
    width: 80px;
    height: 80px;
    border-radius: 10px;
    object-fit: cover;
    background: #f1f5f9;
    border: 1px solid rgba(0,0,0,.06);
}

/* ============================================================
   TAGS
   ============================================================ */

.tags-input {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    padding: 6px;
    border: 1px solid rgba(0,0,0,.12);
    border-radius: .375rem;
    background: #fff;
    min-height: 42px;
}

.tags-input-field {
    border: 0;
    outline: 0;
    min-width: 180px;
    flex: 1 0 160px;
    padding: 4px 6px;
}

.tags-input .tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f1f5f9;
    color: #0f172a;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: .875rem;
}

.tags-input .tag .x {
    cursor: pointer;
    opacity: .7;
}

.tags-input .tag .x:hover {
    opacity: 1;
}

/* ============================================================
   RESUMES
   ============================================================ */

.resume-table td,
.resume-table th {
    vertical-align: middle;
}

.resume-file {
    max-width: 360px;
}

.resume-row .badge-primary {
    background: #2563eb;
}

.resume-row .badge-success {
    background: #16a34a;
}

.resume-row .badge-warning {
    background: #f59e0b;
    color: #111827;
}

.resume-row .fa-star {
    color: #0d6efd;
}

.btn-icon {
    background: transparent;
    border: 0;
    padding: .25rem;
    line-height: 1;
}

.btn-icon i {
    font-size: 1rem;
}

</style>

<!-- ============================================================
     HERO
     ============================================================ -->

<section
    class="profile-hero overlay inner-page bg-image"
    style="background-image:url('../images/tst.jpg');"
>

    <div class="overlay-dark"></div>

    <div class="container hero-inner text-center">

        <div class="ch-eyebrow mb-1">
            Account
        </div>

        <h2 class="ch-title mb-2">
            Update Profile
        </h2>

        <p class="ch-sub mb-0">
            Keep your details fresh to stand out.
        </p>

    </div>

</section>

<!-- ============================================================
     SUB NAV
     ============================================================ -->

<nav class="profile-subnav">

    <div class="container">

        <ul class="nav">

            <li class="nav-item">
                <a class="nav-link active" href="#sec-basic">
                    Basic Info
                </a>
            </li>

            <?php if ($row->type === "Job Seeker"): ?>

                <li class="nav-item">
                    <a class="nav-link" href="#sec-prof">
                        Professional
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#sec-resumes">
                        Resumes
                    </a>
                </li>

            <?php else: ?>

                <li class="nav-item">
                    <a class="nav-link" href="#sec-company">
                        Company
                    </a>
                </li>

            <?php endif; ?>

            <li class="nav-item">
                <a class="nav-link" href="#sec-social">
                    Social
                </a>
            </li>

        </ul>

    </div>

</nav>

<!-- ============================================================
     MAIN SECTION
     ============================================================ -->

<section class="site-section" id="next-section">

    <div class="container profile-wrap">

        <!-- SESSION MESSAGE -->

        <?php

        if (isset($_SESSION['message'])) {

            $type = $_SESSION['message']['type'];
            $text = $_SESSION['message']['text'];

            ?>

            <div
                class="alert alert-<?php echo htmlspecialchars($type); ?> alert-dismissible fade show"
                role="alert"
            >

                <?php echo htmlspecialchars($text); ?>

                <button
                    type="button"
                    class="close"
                    data-dismiss="alert"
                    aria-label="Close"
                >
                    <span aria-hidden="true">
                        &times;
                    </span>
                </button>

            </div>

            <?php

            unset($_SESSION['message']);
        }

        ?>

        <!-- ====================================================
             PROFILE FORM
             ==================================================== -->

        <form
            action="update-profile.php?upd_id=<?php echo (int)$id; ?>"
            method="POST"
            enctype="multipart/form-data"
        >

            <div class="row">

                <!-- =================================================
                     LEFT SIDE
                     ================================================= -->

                <div class="col-lg-7 mb-4">

                    <!-- BASIC INFORMATION -->

                    <div class="card mb-4" id="sec-basic">

                        <div class="card-header">
                            <h6 class="mb-0">
                                Basic Information
                            </h6>
                        </div>

                        <div class="card-body">

                            <!-- Full Name -->

                            <div class="form-group">

                                <label for="fullname">
                                    <?php
                                    echo ($row->type === "Employer")
                                        ? "Employer Name"
                                        : "Full Name";
                                    ?>
                                </label>

                                <input
                                    type="text"
                                    id="fullname"
                                    name="fullname"
                                    value="<?php echo htmlspecialchars($row->fullname ?? ''); ?>"
                                    class="form-control"
                                >

                            </div>

                            <!-- Username + Email -->

                            <div class="form-row">

                                <div class="form-group col-md-6">

                                    <label for="username">
                                        Username
                                    </label>

                                    <div class="input-group">

                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fa fa-user"></i>
                                            </span>
                                        </div>

                                        <input
                                            type="text"
                                            id="username"
                                            name="username"
                                            value="<?php echo htmlspecialchars($row->username ?? ''); ?>"
                                            class="form-control"
                                        >

                                    </div>

                                </div>

                                <div class="form-group col-md-6">

                                    <label for="email">
                                        Email
                                    </label>

                                    <div class="input-group">

                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fa fa-envelope"></i>
                                            </span>
                                        </div>

                                        <input
                                            type="text"
                                            id="email"
                                            name="email"
                                            value="<?php echo htmlspecialchars($row->email ?? ''); ?>"
                                            class="form-control"
                                        >

                                    </div>

                                </div>

                            </div>

                            <!-- Title -->

                            <?php if ($row->type === "Job Seeker"): ?>

                                <div class="form-group">

                                    <label for="title">
                                        Title
                                    </label>

                                    <input
                                        type="text"
                                        id="title"
                                        name="title"
                                        value="<?php echo htmlspecialchars($row->title ?? ''); ?>"
                                        class="form-control"
                                        placeholder="e.g., Frontend Developer"
                                    >

                                </div>

                            <?php else: ?>

                                <input
                                    type="hidden"
                                    name="title"
                                    value=""
                                >

                            <?php endif; ?>

                            <!-- Contact -->

                            <div class="form-group">

                                <label for="contact">
                                    Contact
                                </label>

                                <div class="input-group">

                                    <div class="input-group-prepend">

                                        <span class="input-group-text">
                                            <i class="fa fa-phone"></i>
                                        </span>

                                    </div>

                                    <input
                                        type="text"
                                        id="contact"
                                        name="contact"
                                        value="<?php echo htmlspecialchars($row->contact ?? ''); ?>"
                                        class="form-control"
                                    >

                                </div>

                            </div>

                            <!-- REGION + ADDRESS -->

                            <?php if ($row->type === "Job Seeker"): ?>

                                <div class="form-row">

                                    <div class="form-group col-md-6">

                                        <label for="region_id">
                                            Region / State
                                        </label>

                                        <select
                                            id="region_id"
                                            name="region_id"
                                            class="form-control"
                                        >

                                            <option value="">
                                                Select region/state
                                            </option>

                                            <?php foreach (($regionRows ?? []) as $rg):

                                                $regionOptionId = (int)$rg['id'];

                                                $selected =
                                                    ($regionOptionId === (int)($row->region_id ?? 0))
                                                        ? 'selected'
                                                        : '';

                                            ?>

                                                <option
                                                    value="<?php echo $regionOptionId; ?>"
                                                    <?php echo $selected; ?>
                                                >
                                                    <?php echo htmlspecialchars($rg['name']); ?>
                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                    </div>

                                    <div class="form-group col-md-6">

                                        <label for="address">
                                            Address
                                        </label>

                                        <input
                                            type="text"
                                            id="address"
                                            name="address"
                                            value="<?php echo htmlspecialchars($row->address ?? ''); ?>"
                                            class="form-control"
                                            placeholder="Street address"
                                        >

                                    </div>

                                </div>

                            <?php endif; ?>

                            <!-- BIO -->

                            <div class="form-group">

                                <label for="bio">

                                    <?php
                                    echo ($row->type === "Job Seeker")
                                        ? "Bio"
                                        : "About Employer";
                                    ?>

                                </label>

                                <textarea
                                    id="bio"
                                    name="bio"
                                    rows="6"
                                    class="form-control"
                                    placeholder="Provide a simple bio"
                                ><?php echo htmlspecialchars($row->bio ?? ''); ?></textarea>

                                <small class="text-muted d-block mt-1">
                                    A clear, concise summary looks great.
                                </small>

                            </div>

                        </div>

                    </div>

                    <!-- =================================================
                         PROFESSIONAL DETAILS
                         ================================================= -->

                    <?php if ($row->type === "Job Seeker"): ?>

                        <div
                            class="card mb-4"
                            id="sec-prof"
                        >

                            <div class="card-header">

                                <h6 class="mb-0">
                                    Professional Details
                                </h6>

                            </div>

                            <div class="card-body">

                                <!-- Skills -->

                                <div class="form-group">

                                    <label>
                                        Skills
                                    </label>

                                    <div
                                        id="skillsChips"
                                        class="tags-input"
                                    >

                                        <input
                                            type="text"
                                            class="tags-input-field"
                                            placeholder="Type a skill and press Enter"
                                            aria-label="Add skill"
                                        >

                                    </div>

                                    <input
                                        type="hidden"
                                        name="skills"
                                        id="skillsHidden"
                                    >

                                    <small class="text-muted d-block mt-1">

                                        Examples:
                                        HTML, CSS, JavaScript.

                                        Press
                                        <b>Enter</b>
                                        or
                                        <b>,</b>
                                        to add.

                                    </small>

                                </div>

                                <!-- Education -->

                                <div class="form-group">

                                    <label>
                                        Education
                                    </label>

                                    <div
                                        id="eduChips"
                                        class="tags-input"
                                    >

                                        <input
                                            type="text"
                                            class="tags-input-field"
                                            placeholder="Add education item and press Enter"
                                            aria-label="Add education"
                                        >

                                    </div>

                                    <input
                                        type="hidden"
                                        name="education"
                                        id="educationHidden"
                                    >

                                    <small class="text-muted d-block mt-1">

                                        Examples:
                                        BCA, BSc in IT, MSc in CS.

                                        Press
                                        <b>Enter</b>
                                        or
                                        <b>,</b>
                                        to add.

                                    </small>

                                </div>

                            </div>

                        </div>

                    <?php endif; ?>

                </div>

                <!-- =================================================
                     RIGHT SIDE
                     ================================================= -->

                <div class="col-lg-5 mb-4">

                    <!-- PROFILE IMAGE -->

                    <div class="card mb-4">

                        <div class="card-header">

                            <h6 class="mb-0">
                                Profile Image
                            </h6>

                        </div>

                        <div class="card-body">

                            <div class="form-group">

                                <label class="d-block">
                                    Upload Profile Image
                                </label>

                                <div class="avatar-box mb-2">

                                    <?php

                                    $currentImagePath =
                                        __DIR__ .
                                        '/user-images/' .
                                        basename($row->img ?? '');

                                    ?>

                                    <?php if (
                                        !empty($row->img) &&
                                        is_file($currentImagePath)
                                    ): ?>

                                        <img
                                            src="user-images/<?php echo htmlspecialchars(basename($row->img)); ?>"
                                            alt="Current Profile Image"
                                            class="avatar"
                                        >

                                        <div class="text-muted small">
                                            Current image
                                        </div>

                                    <?php else: ?>

                                        <img
                                            src="../images/user-placeholder.png"
                                            alt="No Image"
                                            class="avatar"
                                        >

                                        <div class="text-muted small">
                                            No image uploaded
                                        </div>

                                    <?php endif; ?>

                                </div>

                                <input
                                    type="file"
                                    name="img"
                                    class="form-control"
                                    accept=".jpg,.jpeg,.png,.gif,.webp"
                                >

                                <small class="text-muted d-block mt-1">
                                    JPG/PNG/GIF/WEBP, square works best.
                                </small>

                            </div>

                        </div>

                    </div>

                    <!-- =================================================
                         RESUME MANAGER
                         ================================================= -->

                    <?php if ($row->type === "Job Seeker"): ?>

                        <div
                            class="card mb-4"
                            id="sec-resumes"
                        >

                            <div class="card-header">

                                <h6 class="mb-0">
                                    Resumes
                                </h6>

                            </div>

                            <div class="card-body">

                                <!-- Upload New Resume -->

                                <div class="mb-3">

                                    <label class="mb-1">
                                        Upload New Resume
                                        (PDF/DOC/DOCX)
                                    </label>

                                    <div class="form-row">

                                        <div class="col-12">

                                            <input
                                                type="file"
                                                id="resume_new"
                                                class="form-control"
                                                accept=".pdf,.doc,.docx"
                                            >

                                        </div>

                                    </div>

                                    <div class="form-check mt-2">

                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            id="resume_make_primary"
                                        >

                                        <label
                                            class="form-check-label"
                                            for="resume_make_primary"
                                        >
                                            Make this my primary resume
                                        </label>

                                    </div>

                                    <button
                                        type="button"
                                        id="resumeUploadBtn"
                                        class="btn btn-sm btn-outline-primary mt-2"
                                    >

                                        <i class="fa fa-upload"></i>
                                        Upload

                                    </button>

                                    <div
                                        id="resumeUploadMsg"
                                        class="small mt-1 text-muted"
                                    ></div>

                                </div>

                                <!-- Resume List -->

                                <div class="table-responsive">

                                    <table
                                        class="table table-sm table-hover align-middle resume-table mb-0"
                                    >

                                        <thead class="thead-light">

                                            <tr>

                                                <th style="width:36px;">
                                                </th>

                                                <th>
                                                    Resume
                                                </th>

                                                <th style="width:160px;">
                                                    Uploaded
                                                </th>

                                                <th
                                                    class="text-right"
                                                    style="width:160px;"
                                                >
                                                    Actions
                                                </th>

                                            </tr>

                                        </thead>

                                        <tbody id="resumeRows">

                                            <tr>

                                                <td
                                                    colspan="4"
                                                    class="text-muted"
                                                >
                                                    Loading…
                                                </td>

                                            </tr>

                                        </tbody>

                                    </table>

                                </div>

                            </div>

                        </div>

                    <?php endif; ?>

                    <!-- =================================================
                         COMPANY DETAILS
                         ================================================= -->

                    <?php if ($row->type === "Employer"): ?>

                        <div
                            class="card mb-4"
                            id="sec-company"
                        >

                            <div class="card-header">

                                <h6 class="mb-0">
                                    Company Details
                                </h6>

                            </div>

                            <div class="card-body">

                                <!-- Website -->

                                <div class="form-group">

                                    <label for="company_website">
                                        Company Website
                                    </label>

                                    <div class="input-group">

                                        <div class="input-group-prepend">

                                            <span class="input-group-text">
                                                <i class="fa fa-globe"></i>
                                            </span>

                                        </div>

                                        <input
                                            type="text"
                                            id="company_website"
                                            name="company_website"
                                            value="<?php echo htmlspecialchars($companyDetails->company_website ?? ''); ?>"
                                            class="form-control"
                                            placeholder="https://"
                                        >

                                    </div>

                                </div>

                                <!-- Industry -->

                                <div class="form-group">

                                    <label for="industry">
                                        Industry
                                    </label>

                                    <div class="input-group">

                                        <div class="input-group-prepend">

                                            <span class="input-group-text">
                                                <i class="fa fa-industry"></i>
                                            </span>

                                        </div>

                                        <input
                                            type="text"
                                            id="industry"
                                            name="industry"
                                            value="<?php echo htmlspecialchars($companyDetails->industry ?? ''); ?>"
                                            class="form-control"
                                        >

                                    </div>

                                </div>

                                <!-- Address -->

                                <div class="form-group">

                                    <label for="address_line">
                                        Address
                                    </label>

                                    <div class="input-group">

                                        <div class="input-group-prepend">

                                            <span class="input-group-text">
                                                <i class="fa fa-map-marker"></i>
                                            </span>

                                        </div>

                                        <input
                                            type="text"
                                            id="address_line"
                                            name="address_line"
                                            value="<?php echo htmlspecialchars($companyDetails->address_line ?? ''); ?>"
                                            class="form-control"
                                        >

                                    </div>

                                </div>

                                <!-- Postal + Year -->

                                <div class="form-row">

                                    <div class="form-group col-md-6">

                                        <label for="postal_code">
                                            Postal Code
                                        </label>

                                        <input
                                            type="text"
                                            id="postal_code"
                                            name="postal_code"
                                            value="<?php echo htmlspecialchars($companyDetails->postal_code ?? ''); ?>"
                                            class="form-control"
                                        >

                                    </div>

                                    <div class="form-group col-md-6">

                                        <label for="established_year">
                                            Established Year
                                        </label>

                                        <input
                                            type="text"
                                            id="established_year"
                                            name="established_year"
                                            value="<?php echo htmlspecialchars($companyDetails->established_year ?? ''); ?>"
                                            class="form-control"
                                        >

                                    </div>

                                </div>

                                <!-- Operating Hours -->

                                <div class="form-group">

                                    <label for="operating_hours">
                                        Operating Hours
                                    </label>

                                    <div class="input-group">

                                        <div class="input-group-prepend">

                                            <span class="input-group-text">
                                                <i class="fa fa-clock"></i>
                                            </span>

                                        </div>

                                        <input
                                            type="text"
                                            id="operating_hours"
                                            name="operating_hours"
                                            value="<?php echo htmlspecialchars($companyDetails->operating_hours ?? ''); ?>"
                                            class="form-control"
                                            placeholder="e.g., Mon-Fri, 9am-5pm"
                                        >

                                    </div>

                                </div>

                            </div>

                        </div>

                    <?php endif; ?>

                    <!-- =================================================
                         SOCIAL LINKS
                         ================================================= -->

                    <div
                        class="card mb-4"
                        id="sec-social"
                    >

                        <div class="card-header">

                            <h6 class="mb-0">
                                Social Links
                            </h6>

                        </div>

                        <div class="card-body">

                            <!-- Facebook -->

                            <div class="form-group">

                                <label for="facebook">
                                    Facebook
                                </label>

                                <div class="input-group">

                                    <div class="input-group-prepend">

                                        <span class="input-group-text">
                                            <i class="fa fa-facebook"></i>
                                        </span>

                                    </div>

                                    <input
                                        type="text"
                                        id="facebook"
                                        name="facebook"
                                        value="<?php echo htmlspecialchars($row->facebook ?? ''); ?>"
                                        class="form-control"
                                        placeholder="https://facebook.com/..."
                                    >

                                </div>

                            </div>

                            <!-- Twitter -->

                            <div class="form-group">

                                <label for="twitter">
                                    Twitter / X
                                </label>

                                <div class="input-group">

                                    <div class="input-group-prepend">

                                        <span class="input-group-text">
                                            <i class="fa fa-twitter"></i>
                                        </span>

                                    </div>

                                    <input
                                        type="text"
                                        id="twitter"
                                        name="twitter"
                                        value="<?php echo htmlspecialchars($row->twitter ?? ''); ?>"
                                        class="form-control"
                                        placeholder="https://x.com/..."
                                    >

                                </div>

                            </div>

                            <!-- LinkedIn -->

                            <div class="form-group">

                                <label for="linkedin">
                                    LinkedIn
                                </label>

                                <div class="input-group">

                                    <div class="input-group-prepend">

                                        <span class="input-group-text">
                                            <i class="fa fa-linkedin"></i>
                                        </span>

                                    </div>

                                    <input
                                        type="text"
                                        id="linkedin"
                                        name="linkedin"
                                        value="<?php echo htmlspecialchars($row->linkedin ?? ''); ?>"
                                        class="form-control"
                                        placeholder="https://linkedin.com/in/..."
                                    >

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- =================================================
                     SUBMIT
                     ================================================= -->

                <div class="col-12 text-right">

                    <button
                        type="submit"
                        name="submit"
                        class="btn btn-primary"
                    >

                        <i class="fa fa-save mr-1"></i>
                        Update Profile Details

                    </button>

                </div>

            </div>

        </form>

    </div>

</section>

<!-- ============================================================
     RESUME JAVASCRIPT
     ============================================================ -->

<script>

(function($) {

    var isSeeker =
        <?php echo json_encode($row->type === 'Job Seeker'); ?>;

    if (!isSeeker) {
        return;
    }

    /* ========================================================
       LOAD RESUMES
       ======================================================== */

    function loadResumes() {

        $('#resumeRows').html(
            '<tr>' +
            '<td colspan="4" class="text-muted">' +
            'Loading…' +
            '</td>' +
            '</tr>'
        );

        $.post(
            'update-profile.php?upd_id=<?php echo (int)$id; ?>',
            {
                ajax: 1,
                action: 'list_resumes'
            },
            function(res) {

                if (!res || !res.ok) {

                    $('#resumeRows').html(
                        '<tr>' +
                        '<td colspan="4" class="text-danger">' +
                        'Failed to load resumes.' +
                        '</td>' +
                        '</tr>'
                    );

                    return;
                }

                var items = res.items || [];

                if (!items.length) {

                    $('#resumeRows').html(
                        '<tr>' +
                        '<td colspan="4" class="text-muted">' +
                        'No resumes uploaded yet.' +
                        '</td>' +
                        '</tr>'
                    );

                    return;
                }

                function fmtDate(dstr) {

                    if (!dstr) {
                        return '';
                    }

                    var y = dstr.substr(0, 4);
                    var m = dstr.substr(5, 2);
                    var d = dstr.substr(8, 2);

                    var months = [
                        'Jan',
                        'Feb',
                        'Mar',
                        'Apr',
                        'May',
                        'Jun',
                        'Jul',
                        'Aug',
                        'Sep',
                        'Oct',
                        'Nov',
                        'Dec'
                    ];

                    var mi =
                        Math.max(
                            1,
                            Math.min(
                                12,
                                parseInt(m, 10)
                            )
                        ) - 1;

                    return (
                        parseInt(d, 10) +
                        ' ' +
                        months[mi] +
                        ', ' +
                        y
                    );
                }

                var html = '';

                items.forEach(function(it) {

                    var isPrimary =
                        Number(it.is_primary) === 1;

                    var viewUrl =
                        <?php echo json_encode('user-cvs/'); ?> +
                        encodeURIComponent(it.filename);

                    var display =
                        it.original_name ||
                        it.filename ||
                        'resume';

                    var uploaded =
                        fmtDate(it.created_at);

                    /* ---------- Primary star ---------- */

                    var primaryCol;

                    if (isPrimary) {

                        primaryCol =
                            '<i class="fa fa-star text-primary" ' +
                            'title="Primary"></i>';

                    } else {

                        primaryCol =
                            '<button ' +
                            'type="button" ' +
                            'class="btn-icon act-set" ' +
                            'data-id="' + it.id + '" ' +
                            'title="Set primary">' +

                            '<i class="far fa-star"></i>' +

                            '</button>';
                    }

                    /* ---------- Actions ---------- */

                    var actions =

                        '<label ' +
                        'class="btn-icon mb-0 mr-1" ' +
                        'title="Replace">' +

                        '<i class="fa fa-sync-alt"></i>' +

                        '<input ' +
                        'type="file" ' +
                        'class="d-none act-replace" ' +
                        'data-id="' + it.id + '" ' +
                        'accept=".pdf,.doc,.docx">' +

                        '</label>' +

                        '<button ' +
                        'type="button" ' +
                        'class="btn-icon text-danger act-del" ' +
                        'data-id="' + it.id + '" ' +
                        'title="Delete">' +

                        '<i class="fa fa-trash"></i>' +

                        '</button>';

                    /* ---------- Escape display name ---------- */

                    var safeDisplay =
                        $('<div>')
                        .text(display)
                        .html();

                    html +=

                        '<tr class="resume-row">' +

                        '<td class="text-center">' +
                        primaryCol +
                        '</td>' +

                        '<td>' +

                        '<div ' +
                        'class="text-truncate resume-file" ' +
                        'title="' + safeDisplay + '">' +

                        '<a ' +
                        'href="' + viewUrl + '" ' +
                        'target="_blank" ' +
                        'rel="noopener">' +

                        safeDisplay +

                        '</a>' +

                        '</div>' +

                        '</td>' +

                        '<td>' +
                        uploaded +
                        '</td>' +

                        '<td class="text-right">' +
                        actions +
                        '</td>' +

                        '</tr>';
                });

                $('#resumeRows').html(html);

            },
            'json'
        );
    }

    /* ========================================================
       UPLOAD NEW RESUME
       ======================================================== */

    $('#resumeUploadBtn').on('click', function() {

        var f =
            $('#resume_new')[0].files[0];

        if (!f) {

            $('#resumeUploadMsg')
                .text('Please choose a file.');

            return;
        }

        var fd = new FormData();

        fd.append('ajax', 1);
        fd.append('action', 'upload_resume');
        fd.append('resume_new', f);

        fd.append(
            'make_primary',
            $('#resume_make_primary').is(':checked')
                ? 1
                : 0
        );

        $('#resumeUploadMsg')
            .text('Uploading…');

        $.ajax({

            url:
                'update-profile.php?upd_id=<?php echo (int)$id; ?>',

            method: 'POST',

            data: fd,

            processData: false,

            contentType: false,

            dataType: 'json'

        }).done(function(res) {

            if (res && res.ok) {

                $('#resumeUploadMsg')
                    .text('Uploaded successfully.');

                $('#resume_new').val('');

                $('#resume_make_primary')
                    .prop('checked', false);

                loadResumes();

            } else {

                $('#resumeUploadMsg')
                    .text(
                        res && res.error
                            ? res.error
                            : 'Upload failed.'
                    );
            }

        }).fail(function() {

            $('#resumeUploadMsg')
                .text('Upload failed.');

        });

    });

    /* ========================================================
       RESUME ACTIONS
       ======================================================== */

    $('#resumeRows')

        /* ---------- Set Primary ---------- */

        .on('click', '.act-set', function() {

            var resumeId =
                $(this).data('id');

            $.post(

                'update-profile.php?upd_id=<?php echo (int)$id; ?>',

                {
                    ajax: 1,
                    action: 'set_primary',
                    id: resumeId
                },

                function(res) {

                    if (res && res.ok) {

                        loadResumes();

                    } else {

                        alert(
                            res.error ||
                            'Failed to set primary resume.'
                        );
                    }

                },

                'json'
            );
        })

        /* ---------- Delete ---------- */

        .on('click', '.act-del', function() {

            if (!confirm('Delete this resume?')) {
                return;
            }

            var resumeId =
                $(this).data('id');

            $.post(

                'update-profile.php?upd_id=<?php echo (int)$id; ?>',

                {
                    ajax: 1,
                    action: 'delete_resume',
                    id: resumeId
                },

                function(res) {

                    if (res && res.ok) {

                        loadResumes();

                    } else {

                        alert(
                            res.error ||
                            'Delete failed.'
                        );
                    }

                },

                'json'
            );
        })

        /* ---------- Replace ---------- */

        .on('change', '.act-replace', function() {

            var resumeId =
                $(this).data('id');

            var f =
                this.files[0];

            if (!f) {
                return;
            }

            var fd = new FormData();

            fd.append('ajax', 1);
            fd.append('action', 'replace_resume');
            fd.append('id', resumeId);
            fd.append('resume_file', f);

            $.ajax({

                url:
                    'update-profile.php?upd_id=<?php echo (int)$id; ?>',

                method: 'POST',

                data: fd,

                processData: false,

                contentType: false,

                dataType: 'json'

            }).done(function(res) {

                if (res && res.ok) {

                    loadResumes();

                } else {

                    alert(
                        res.error ||
                        'Replace failed.'
                    );
                }

            }).fail(function() {

                alert('Replace failed.');

            });

        });

    /* ---------- Initial Load ---------- */

    loadResumes();

})(jQuery);

</script>

<!-- ============================================================
     SKILLS / EDUCATION CHIPS
     ============================================================ -->

<script>

(function($) {

    function makeChips(
        $wrap,
        $hidden,
        initialCSV
    ) {

        var items = [];

        function normalize(str) {

            return (str || '')
                .replace(/\s+/g, ' ')
                .replace(/[<>]/g, '')
                .trim();
        }

        function toList(str) {

            if (!str) {
                return [];
            }

            return str
                .split(/[\n,;•]+/)
                .map(normalize)
                .filter(Boolean);
        }

        var $input =
            $wrap.find('.tags-input-field');

        function render() {

            $wrap.find('.tag').remove();

            items.forEach(function(t, i) {

                var $tag =
                    $('<span class="tag"></span>')
                    .text(t);

                var $x =
                    $('<span ' +
                      'class="x" ' +
                      'aria-label="Remove" ' +
                      'title="Remove">&times;</span>');

                $x.on('click', function() {

                    items.splice(i, 1);

                    render();
                });

                $tag.append($x);

                $tag.insertBefore($input);
            });

            $hidden.val(
                items.join(', ')
            );
        }

        function add(val) {

            val = normalize(val);

            if (!val) {
                return;
            }

            var exists =
                items.some(function(x) {

                    return (
                        x.toLowerCase() ===
                        val.toLowerCase()
                    );

                });

            if (!exists) {

                items.push(val);

                render();
            }
        }

        /* Initial values */

        items = toList(initialCSV);

        render();

        /* Enter / comma */

        $input.on('keydown', function(e) {

            if (
                e.key === 'Enter' ||
                e.key === ','
            ) {

                e.preventDefault();

                add($input.val());

                $input.val('');

            } else if (
                e.key === 'Backspace' &&
                !$input.val() &&
                items.length
            ) {

                items.pop();

                render();
            }
        });

        /* Paste */

        $input.on('paste', function(e) {

            var clipboard =
                e.originalEvent.clipboardData ||
                window.clipboardData;

            var text =
                clipboard.getData('text');

            if (
                text &&
                /[,;\n]/.test(text)
            ) {

                e.preventDefault();

                toList(text).forEach(add);
            }
        });

        return {
            add: add,

            get: function() {
                return items.slice();
            }
        };
    }

    $(function() {

        var initSkills =
            <?php echo json_encode(
                (string)($row->skills ?? '')
            ); ?>;

        var initEdu =
            <?php echo json_encode(
                (string)($row->education ?? '')
            ); ?>;

        if ($('#skillsChips').length) {

            makeChips(
                $('#skillsChips'),
                $('#skillsHidden'),
                initSkills
            );
        }

        if ($('#eduChips').length) {

            makeChips(
                $('#eduChips'),
                $('#educationHidden'),
                initEdu
            );
        }

    });

})(jQuery);

</script>

<!-- ============================================================
     SMOOTH SCROLL + ACTIVE SECTION
     ============================================================ -->

<script>

(function() {

    /* Smooth scroll */

    document
        .querySelectorAll(
            '.profile-subnav .nav-link'
        )
        .forEach(function(a) {

            a.addEventListener(
                'click',
                function(e) {

                    var id =
                        this.getAttribute('href');

                    if (
                        !id ||
                        id.charAt(0) !== '#'
                    ) {
                        return;
                    }

                    var el =
                        document.querySelector(id);

                    if (!el) {
                        return;
                    }

                    e.preventDefault();

                    var offset = 80;

                    var top =
                        el.getBoundingClientRect().top +
                        window.pageYOffset -
                        offset;

                    window.scrollTo({
                        top: top,
                        behavior: 'smooth'
                    });

                }
            );

        });

    /* Active section */

    var links =
        Array.from(
            document.querySelectorAll(
                '.profile-subnav .nav-link'
            )
        );

    var map =
        links.map(function(l) {

            return [
                l,
                document.querySelector(
                    l.getAttribute('href')
                )
            ];

        });

    if (
        'IntersectionObserver' in window
    ) {

        var obs =
            new IntersectionObserver(
                function(entries) {

                    entries.forEach(
                        function(ent) {

                            if (
                                ent.isIntersecting
                            ) {

                                var id =
                                    '#' +
                                    ent.target.id;

                                links.forEach(
                                    function(l) {

                                        l.classList.toggle(
                                            'active',
                                            l.getAttribute('href') === id
                                        );

                                    }
                                );
                            }

                        }
                    );

                },
                {
                    rootMargin:
                        '-50% 0px -40% 0px',

                    threshold: 0.01
                }
            );

        map.forEach(function(pair) {

            if (pair[1]) {
                obs.observe(pair[1]);
            }

        });
    }

})();

</script>

<?php
require "../includes/footer.php";
?>