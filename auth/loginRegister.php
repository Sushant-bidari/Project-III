<?php
require "../config/config.php";


/* ============================================================
   BASIC SETTINGS
   ============================================================ */

$activeTab    = 'login';
$empOld       = [];
$empErrors    = [];
$empShowStep2 = false;

$jsOld       = [];
$jsErrors    = [];

$error = '';

/*
|--------------------------------------------------------------------------
| If already logged in
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['username'])) {
    header("Location: " . APPURL);
    exit;
}


/* ============================================================
   VALIDATION FUNCTIONS
   ============================================================ */

/*
|--------------------------------------------------------------------------
| Clean input
|--------------------------------------------------------------------------
*/

function clean_input($value): string
{
    return trim((string)$value);
}


/*
|--------------------------------------------------------------------------
| Full Name Validation
|
| Allows:
|   John Doe
|   Ram Bahadur Thapa
|   Anne-Marie Smith
|   O'Connor
|
| Does NOT allow:
|   John123
|   John@Doe
|   12345
|--------------------------------------------------------------------------
*/

function validate_fullname(string $name): ?string
{
    if ($name === '') {
        return 'Full name is required.';
    }

    if (mb_strlen($name) < 2) {
        return 'Full name must contain at least 2 characters.';
    }

    if (mb_strlen($name) > 100) {
        return 'Full name cannot exceed 100 characters.';
    }

    if (!preg_match("/^[\p{L}]+(?:[ '\-][\p{L}]+)*$/u", $name)) {
        return 'Full name can contain letters, spaces, hyphens and apostrophes only.';
    }

    return null;
}


/*
|--------------------------------------------------------------------------
| Username Validation
|
| Must:
| - Start with a letter
| - 3 to 30 characters
| - Letters, numbers, underscore and dot allowed
|--------------------------------------------------------------------------
*/

function validate_username(string $username): ?string
{
    if ($username === '') {
        return 'Username is required.';
    }

    if (strlen($username) < 3) {
        return 'Username must contain at least 3 characters.';
    }

    if (strlen($username) > 30) {
        return 'Username cannot exceed 30 characters.';
    }

    if (!preg_match('/^[A-Za-z][A-Za-z0-9_.]*$/', $username)) {
        return 'Username must start with a letter and contain only letters, numbers, dots or underscores.';
    }

    if (str_ends_with($username, '.') || str_ends_with($username, '_')) {
        return 'Username cannot end with a dot or underscore.';
    }

    if (strpos($username, '..') !== false || strpos($username, '__') !== false) {
        return 'Username cannot contain consecutive dots or underscores.';
    }

    return null;
}


/*
|--------------------------------------------------------------------------
| Email Validation
|--------------------------------------------------------------------------
*/

function validate_email_address(string $email): ?string
{
    if ($email === '') {
        return 'Email address is required.';
    }

    if (strlen($email) > 120) {
        return 'Email address cannot exceed 120 characters.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Please enter a valid email address.';
    }

    return null;
}


/*
|--------------------------------------------------------------------------
| Nepal Contact Number
|
| Valid:
| 9812345678
| 9712345678
|
| Invalid:
| 9612345678
| 981234567
| 98123456789
| +9779812345678
|--------------------------------------------------------------------------
*/

function validate_nepal_contact(string $contact): ?string
{
    if ($contact === '') {
        return 'Contact number is required.';
    }

    if (!preg_match('/^9[78][0-9]{8}$/', $contact)) {
        return 'Enter a valid Nepali mobile number starting with 97 or 98 and containing exactly 10 digits.';
    }

    return null;
}


/*
|--------------------------------------------------------------------------
| Password Validation
|
| Required:
| - Minimum 8 characters
| - Maximum 128 characters
| - One uppercase
| - One lowercase
| - One number
| - One special character
|--------------------------------------------------------------------------
*/

function validate_password(string $password): ?string
{
    if ($password === '') {
        return 'Password is required.';
    }

    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters long.';
    }

    if (strlen($password) > 128) {
        return 'Password cannot exceed 128 characters.';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        return 'Password must contain at least one uppercase letter.';
    }

    if (!preg_match('/[a-z]/', $password)) {
        return 'Password must contain at least one lowercase letter.';
    }

    if (!preg_match('/[0-9]/', $password)) {
        return 'Password must contain at least one number.';
    }

    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return 'Password must contain at least one special character.';
    }

    return null;
}


/*
|--------------------------------------------------------------------------
| Password Confirmation
|--------------------------------------------------------------------------
*/

function validate_password_confirmation(
    string $password,
    string $confirmation
): ?string {

    if ($confirmation === '') {
        return 'Please re-type your password.';
    }

    if (!hash_equals($password, $confirmation)) {
        return 'Passwords do not match.';
    }

    return null;
}


/*
|--------------------------------------------------------------------------
| Company Website
|--------------------------------------------------------------------------
*/

function validate_company_website(string $website): ?string
{
    if ($website === '') {
        return 'Company website is required.';
    }

    if (!filter_var($website, FILTER_VALIDATE_URL)) {
        return 'Please enter a valid website URL.';
    }

    $scheme = strtolower((string)parse_url($website, PHP_URL_SCHEME));

    if (!in_array($scheme, ['http', 'https'], true)) {
        return 'Website must start with http:// or https://.';
    }

    return null;
}


/* ============================================================
   EMPLOYER SELECT OPTIONS
   ============================================================ */

$ALLOWED_SIZES = [
    '1–10',
    '11–50',
    '51–200',
    '201–500',
    '501–1000',
    '1001–5000',
    '5000+'
];

$ALLOWED_ORGS = [
    'Startup',
    'Small Business',
    'Medium Business',
    'Enterprise',
    'Recruitment Agency',
    'Government',
    'Non-Profit Organization',
    'Educational Institution',
    'Freelancer / Independent Consultant'
];


/* ============================================================
   FETCH REGIONS
   ============================================================ */

$regionRows = [];

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


/* ============================================================
   JOB SEEKER REGISTER
   ============================================================ */

if (isset($_POST['submit'])) {

    $activeTab = 'js';

    $jsOld = $_POST;

    $fullname   = clean_input($_POST['fullname'] ?? '');
    $username   = clean_input($_POST['username'] ?? '');
    $email      = clean_input($_POST['email'] ?? '');
    $contact    = clean_input($_POST['contact'] ?? '');
    $regionId   = isset($_POST['region_id']) ? (int)$_POST['region_id'] : 0;
    $address    = clean_input($_POST['address'] ?? '');
    $password   = $_POST['password'] ?? '';
    $repassword = $_POST['re-password'] ?? '';

    $img  = 'user_placeholderimg.jpg';
    $type = 'Job Seeker';


    /* --------------------------------------------------------
       FULL NAME
       -------------------------------------------------------- */

    $validation = validate_fullname($fullname);

    if ($validation !== null) {
        $jsErrors['fullname'] = $validation;
    }


    /* --------------------------------------------------------
       USERNAME
       -------------------------------------------------------- */

    $validation = validate_username($username);

    if ($validation !== null) {
        $jsErrors['username'] = $validation;
    }


    /* --------------------------------------------------------
       EMAIL
       -------------------------------------------------------- */

    $validation = validate_email_address($email);

    if ($validation !== null) {
        $jsErrors['email'] = $validation;
    }


    /* --------------------------------------------------------
       CONTACT
       -------------------------------------------------------- */

    $validation = validate_nepal_contact($contact);

    if ($validation !== null) {
        $jsErrors['contact'] = $validation;
    }


    /* --------------------------------------------------------
       PASSWORD
       -------------------------------------------------------- */

    $validation = validate_password($password);

    if ($validation !== null) {
        $jsErrors['password'] = $validation;
    }


    /* --------------------------------------------------------
       CONFIRM PASSWORD
       -------------------------------------------------------- */

    $validation = validate_password_confirmation(
        $password,
        $repassword
    );

    if ($validation !== null) {
        $jsErrors['re-password'] = $validation;
    }


    /* --------------------------------------------------------
       REGION
       -------------------------------------------------------- */

    if ($regionId <= 0) {

        $jsErrors['region_id'] = 'Please select a valid region/state.';

    } else {

        $chkReg = $conn->prepare("
            SELECT 1
            FROM job_regions
            WHERE id = :id
              AND status = 1
            LIMIT 1
        ");

        $chkReg->execute([
            ':id' => $regionId
        ]);

        if (!$chkReg->fetchColumn()) {
            $jsErrors['region_id'] = 'Please select a valid region/state.';
        }
    }


    /* --------------------------------------------------------
       ADDRESS
       -------------------------------------------------------- */

    if ($address === '') {

        $jsErrors['address'] = 'Address is required.';

    } elseif (mb_strlen($address) < 5) {

        $jsErrors['address'] =
            'Address must be at least 5 characters long.';

    } elseif (mb_strlen($address) > 255) {

        $jsErrors['address'] =
            'Address cannot exceed 255 characters.';

    } elseif (preg_match('/[\\x00-\\x1F\\x7F]/', $address)) {

        $jsErrors['address'] =
            'Address contains invalid characters.';

    } elseif (!preg_match('/[\\p{L}\\p{N}]/u', $address)) {

        $jsErrors['address'] =
            'Address must contain at least one letter or number.';

    } elseif (
        !preg_match(
            "/^[\\p{L}\\p{N}\\s,.'\\/#()\\-]+$/u",
            $address
        )
    ) {

        $jsErrors['address'] =
            'Address contains invalid characters.';

    } else {

        $address = preg_replace('/\\s+/u', ' ', $address);
    }


    /* --------------------------------------------------------
       DATABASE DUPLICATE CHECK
       -------------------------------------------------------- */

    $duplicateStmt = $conn->prepare("
        SELECT email, username
        FROM users
        WHERE email = :email
           OR username = :username
        LIMIT 1
    ");

    $duplicateStmt->execute([
        ':email'    => $email,
        ':username' => $username
    ]);

    $existingUser = $duplicateStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {

        if (
            !isset($jsErrors['email']) &&
            isset($existingUser['email']) &&
            strcasecmp($existingUser['email'], $email) === 0
        ) {
            $jsErrors['email'] =
                'This email address is already registered.';
        }

        if (
            !isset($jsErrors['username']) &&
            isset($existingUser['username']) &&
            strcasecmp($existingUser['username'], $username) === 0
        ) {
            $jsErrors['username'] =
                'This username is already taken.';
        }
    }


    /* --------------------------------------------------------
       CREATE JOB SEEKER ACCOUNT
       -------------------------------------------------------- */

    if (empty($jsErrors)) {

        try {

            $conn->beginTransaction();

            $insert = $conn->prepare("
                INSERT INTO users
                (
                    fullname,
                    username,
                    email,
                    contact,
                    region_id,
                    address,
                    mypassword,
                    img,
                    type
                )
                VALUES
                (
                    :fullname,
                    :username,
                    :email,
                    :contact,
                    :region_id,
                    :address,
                    :mypassword,
                    :img,
                    :type
                )
            ");

            $insert->execute([
                ':fullname'   => $fullname,
                ':username'   => $username,
                ':email'      => $email,
                ':contact'    => $contact,
                ':region_id'  => $regionId,
                ':address'    => $address,
                ':mypassword' => password_hash(
                    $password,
                    PASSWORD_DEFAULT
                ),
                ':img'        => $img,
                ':type'       => $type
            ]);

            $userId = (int)$conn->lastInsertId();


            /* Default availability */

            $defaultAvailability = 'None';

            $availability = $conn->prepare("
                INSERT INTO availability
                (
                    user_id,
                    monday,
                    tuesday,
                    wednesday,
                    thursday,
                    friday,
                    saturday,
                    sunday
                )
                VALUES
                (
                    :user_id,
                    :availability,
                    :availability,
                    :availability,
                    :availability,
                    :availability,
                    :availability,
                    :availability
                )
            ");

            $availability->execute([
                ':user_id'      => $userId,
                ':availability' => $defaultAvailability
            ]);


            $conn->commit();

            $_SESSION['successMsg'] =
                "Your account has been created.";

            header("Location: loginRegister.php");
            exit;

        } catch (Throwable $e) {

            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $jsErrors['_form'] =
                'Unable to create your account. Please try again.';
        }
    }
}


/* ============================================================
   EMPLOYER REGISTER
   ============================================================ */

if (isset($_POST['employersubmit'])) {

    $activeTab = 'emp';

    $empOld = $_POST;


    /* --------------------------------------------------------
       STEP 1
       -------------------------------------------------------- */

    $fullname   = clean_input($_POST['fullname'] ?? '');
    $username   = clean_input($_POST['username'] ?? '');
    $email      = clean_input($_POST['email'] ?? '');
    $contact    = clean_input($_POST['contact'] ?? '');
    $password   = $_POST['password'] ?? '';
    $repassword = $_POST['re-password'] ?? '';


    /* --------------------------------------------------------
       STEP 2
       -------------------------------------------------------- */

    $company_website  = clean_input($_POST['company_website'] ?? '');
    $industry         = clean_input($_POST['industry'] ?? '');
    $address_line     = clean_input($_POST['address_line'] ?? '');
    $postal_code      = clean_input($_POST['postal_code'] ?? '');
    $established_year = clean_input($_POST['established_year'] ?? '');
    $operating_hours  = clean_input($_POST['operating_hours'] ?? '');

    $business_reg_no = strtoupper(
        clean_input($_POST['business_reg_no'] ?? '')
    );

    $company_size = clean_input(
        $_POST['company_size'] ?? ''
    );

    $org_type = clean_input(
        $_POST['org_type'] ?? ''
    );


    $img  = 'emp_imgplaceholder.jpg';
    $type = 'Employer';


    /* ========================================================
       EMPLOYER STEP 1 VALIDATION
       ======================================================== */


    /* Organization Name */

    $validation = validate_fullname($fullname);

    if ($validation !== null) {
        $empErrors['fullname'] =
            'Organization Name: ' . $validation;
    }


    /* Username */

    $validation = validate_username($username);

    if ($validation !== null) {
        $empErrors['username'] = $validation;
    }


    /* Email */

    $validation = validate_email_address($email);

    if ($validation !== null) {
        $empErrors['email'] = $validation;
    }


    /* Contact */

    $validation = validate_nepal_contact($contact);

    if ($validation !== null) {
        $empErrors['contact'] = $validation;
    }


    /* Password */

    $validation = validate_password($password);

    if ($validation !== null) {
        $empErrors['password'] = $validation;
    }


    /* Re-password */

    $validation = validate_password_confirmation(
        $password,
        $repassword
    );

    if ($validation !== null) {
        $empErrors['re-password'] = $validation;
    }


    /* ========================================================
       EMPLOYER STEP 2 VALIDATION
       ======================================================== */


    /* Website */

    $validation = validate_company_website($company_website);

    if ($validation !== null) {
        $empErrors['company_website'] = $validation;
    }


    /* Industry */

    if ($industry === '') {

        $empErrors['industry'] = 'Industry is required.';

    } elseif (mb_strlen($industry) > 100) {

        $empErrors['industry'] =
            'Industry cannot exceed 100 characters.';
    }


    /* Address */

    if ($address_line === '') {

        $empErrors['address_line'] =
            'Address is required.';

    } elseif (mb_strlen($address_line) < 5) {

        $empErrors['address_line'] =
            'Address must be at least 5 characters long.';

    } elseif (mb_strlen($address_line) > 255) {

        $empErrors['address_line'] =
            'Address cannot exceed 255 characters.';

    } elseif (preg_match('/[\\x00-\\x1F\\x7F]/', $address_line)) {

        $empErrors['address_line'] =
            'Address contains invalid characters.';

    } elseif (!preg_match('/[\\p{L}\\p{N}]/u', $address_line)) {

        $empErrors['address_line'] =
            'Address must contain at least one letter or number.';

    } elseif (
        !preg_match(
            "/^[\\p{L}\\p{N}\\s,.'\\/#()\\-]+$/u",
            $address_line
        )
    ) {

        $empErrors['address_line'] =
            'Address contains invalid characters.';

    } else {

        $address_line = preg_replace('/\\s+/u', ' ', $address_line);
    }


    /* Postal Code */

    if ($postal_code === '') {

        $empErrors['postal_code'] =
            'Postal code is required.';

    } elseif (
        !preg_match(
            '/^[A-Za-z0-9][A-Za-z0-9 -]{2,15}$/',
            $postal_code
        )
    ) {

        $empErrors['postal_code'] =
            'Please enter a valid postal code.';
    }


    /* Established Year */

    $currentYear = (int)date('Y');

    if ($established_year === '') {

        $empErrors['established_year'] =
            'Established year is required.';

    } elseif (
        !preg_match('/^\d{4}$/', $established_year) ||
        (int)$established_year < 1800 ||
        (int)$established_year > $currentYear
    ) {

        $empErrors['established_year'] =
            "Enter a valid year between 1800 and {$currentYear}.";
    }


    /* Operating Hours */

    if ($operating_hours === '') {

        $empErrors['operating_hours'] =
            'Operating hours are required.';

    } elseif (mb_strlen($operating_hours) > 100) {

        $empErrors['operating_hours'] =
            'Operating hours cannot exceed 100 characters.';
    }


    /* Nepal PAN Number */

    if ($business_reg_no === '') {

        $empErrors['business_reg_no'] =
            'Nepal PAN number is required.';

    } elseif (!preg_match('/^\d{9}$/', $business_reg_no)) {

        $empErrors['business_reg_no'] =
            'Enter a valid 9-digit Nepal PAN number.';
    }


    /* Company Size */

    if (
        $company_size !== '' &&
        !in_array(
            $company_size,
            $ALLOWED_SIZES,
            true
        )
    ) {

        $empErrors['company_size'] =
            'Invalid company size.';
    }


    /* Organization Type */

    if (
        $org_type !== '' &&
        !in_array(
            $org_type,
            $ALLOWED_ORGS,
            true
        )
    ) {

        $empErrors['org_type'] =
            'Invalid organization type.';
    }


    /* ========================================================
       DATABASE DUPLICATE CHECK
       ======================================================== */

    $duplicateStmt = $conn->prepare("
        SELECT email, username
        FROM users
        WHERE email = :email
           OR username = :username
        LIMIT 1
    ");

    $duplicateStmt->execute([
        ':email'    => $email,
        ':username' => $username
    ]);

    $existingUser = $duplicateStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {

        if (
            !isset($empErrors['email']) &&
            isset($existingUser['email']) &&
            strcasecmp(
                $existingUser['email'],
                $email
            ) === 0
        ) {

            $empErrors['email'] =
                'This email address is already registered.';
        }

        if (
            !isset($empErrors['username']) &&
            isset($existingUser['username']) &&
            strcasecmp(
                $existingUser['username'],
                $username
            ) === 0
        ) {

            $empErrors['username'] =
                'This username is already taken.';
        }
    }


    /* ========================================================
       BUSINESS REGISTRATION DUPLICATE
       ======================================================== */

    if (!isset($empErrors['business_reg_no'])) {

        $dupe = $conn->prepare("
            SELECT 1
            FROM company_details
            WHERE business_reg_no = :brn
            LIMIT 1
        ");

        $dupe->execute([
            ':brn' => $business_reg_no
        ]);

        if ($dupe->fetchColumn()) {

            $empErrors['business_reg_no'] =
                'This business registration number already exists.';
        }
    }


    /* ========================================================
       DETERMINE EMPLOYER STEP
       ======================================================== */

    $step1Keys = [
        'fullname',
        'username',
        'email',
        'contact',
        'password',
        're-password'
    ];

    $empShowStep2 = true;

    foreach ($step1Keys as $key) {

        if (isset($empErrors[$key])) {

            $empShowStep2 = false;
            break;
        }
    }


    /* ========================================================
       CREATE EMPLOYER ACCOUNT
       ======================================================== */

    if (empty($empErrors)) {

        try {

            $conn->beginTransaction();


            /* Create user */

            $insertUser = $conn->prepare("
                INSERT INTO users
                (
                    fullname,
                    username,
                    email,
                    contact,
                    mypassword,
                    img,
                    type
                )
                VALUES
                (
                    :fullname,
                    :username,
                    :email,
                    :contact,
                    :mypassword,
                    :img,
                    :type
                )
            ");

            $insertUser->execute([

                ':fullname' =>
                    $fullname,

                ':username' =>
                    $username,

                ':email' =>
                    $email,

                ':contact' =>
                    $contact,

                ':mypassword' =>
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    ),

                ':img' =>
                    $img,

                ':type' =>
                    $type
            ]);


            $userId = (int)$conn->lastInsertId();


            /* Create company */

            $insertCompany = $conn->prepare("
                INSERT INTO company_details
                (
                    user_id,
                    company_website,
                    industry,
                    address_line,
                    postal_code,
                    established_year,
                    operating_hours,
                    business_reg_no,
                    company_size,
                    org_type
                )
                VALUES
                (
                    :user_id,
                    :company_website,
                    :industry,
                    :address_line,
                    :postal_code,
                    :established_year,
                    :operating_hours,
                    :business_reg_no,
                    :company_size,
                    :org_type
                )
            ");

            $insertCompany->execute([

                ':user_id' =>
                    $userId,

                ':company_website' =>
                    $company_website,

                ':industry' =>
                    $industry,

                ':address_line' =>
                    $address_line,

                ':postal_code' =>
                    $postal_code,

                ':established_year' =>
                    $established_year,

                ':operating_hours' =>
                    $operating_hours,

                ':business_reg_no' =>
                    $business_reg_no,

                ':company_size' =>
                    ($company_size !== ''
                        ? $company_size
                        : null),

                ':org_type' =>
                    ($org_type !== ''
                        ? $org_type
                        : null)
            ]);


            $conn->commit();


            $_SESSION['successMsg'] =
                "Your employer account has been created.";

            header("Location: loginRegister.php");
            exit;


        } catch (Throwable $e) {

            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $empErrors['_form'] =
                'Unable to create account. Please try again.';

            $empShowStep2 = true;
        }
    }
}


/* ============================================================
   LOGIN
   ============================================================ */

if (isset($_POST['login'])) {

    $activeTab = 'login';

    $email = clean_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';


    if ($email === '') {

        $error = 'Email address is required.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } elseif ($password === '') {

        $error = 'Password is required.';

    } else {

        $login = $conn->prepare("
            SELECT *
            FROM users
            WHERE email = :email
            LIMIT 1
        ");

        $login->execute([
            ':email' => $email
        ]);

        $select = $login->fetch(PDO::FETCH_ASSOC);


        if (
            $select &&
            password_verify(
                $password,
                $select['mypassword']
            )
        ) {

            /*
             * Regenerate session ID after successful login.
             * Helps protect against session fixation.
             */

            session_regenerate_id(true);


            $_SESSION['username'] =
                $select['username'];

            $_SESSION['fullname'] =
                $select['fullname'];

            $_SESSION['id'] =
                $select['id'];

            $_SESSION['type'] =
                $select['type'];

            $_SESSION['email'] =
                $select['email'];

            $_SESSION['contact'] =
                $select['contact'];

            $_SESSION['image'] =
                $select['img'];

            $_SESSION['cv'] =
                $select['cv'] ?? null;


            header("Location: " . APPURL);
            exit;


        } else {

            /*
             * Keep login errors generic.
             * Do not reveal whether an email exists.
             */

            $error =
                'Invalid email or password. Please try again.';
        }
    }
}


/* ============================================================
   HEADER
   ============================================================ */

require "../includes/header.php";

?>

<style>

/* ============================================================
   HERO
   ============================================================ */

.companies-hero {
    position: relative;
    background-size: cover;
    background-position: center;
    padding: 60px 0;
    overflow: hidden;
}

.companies-hero .overlay-dark {
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

.companies-hero .hero-inner {
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
   AUTH CARD
   ============================================================ */

.site-section {
    padding-top: 4rem;
    padding-bottom: 2.5rem;
}

.auth-shell {
    max-width: 800px;
    margin: 0 auto;
}

.card.auth-card {
    border: 0;
    border-radius: 16px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 16px 44px rgba(0,0,0,.08);
    margin-top: -36px;
}


/* ============================================================
   TABS
   ============================================================ */

.auth-tabs {
    display: flex;
    gap: 0;
    border-bottom: 1px solid #e9ecef;
    background: #f1f5f9;
}

.auth-tabs .nav-item {
    flex: 1 1 0;
}

.auth-tabs .nav-link {
    border-radius: 0;
    min-height: 64px;

    display: flex;
    align-items: center;
    justify-content: center;

    gap: .5rem;

    color: #6c757d;
    font-weight: 700;
}

.auth-tabs .nav-link .lbl {
    line-height: 1.1;
    display: flex;
    flex-direction: column;
}

.auth-tabs .nav-link .lbl small {
    color: #8a97a6;
    font-weight: 600;
}

.auth-tabs .nav-link.active {
    background: #fff;
    color: #111827;
    box-shadow: 0 -3px 0 0 #6366f1 inset;
}


/* ============================================================
   CARD BODY
   ============================================================ */

.auth-body {
    padding: 1.25rem 1.25rem 1.75rem;
}

@media (min-width: 768px) {

    .auth-body {
        padding: 1.75rem 2rem 2.25rem;
    }
}


/* ============================================================
   FORM
   ============================================================ */

.form label {
    font-weight: 600;
    color: #495057;
}

.form .form-control,
.form select.form-control {
    border-radius: .5rem;
    min-height: 44px;
}

.form-control:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 .2rem rgba(99,102,241,.12);
}

.input-group-text {
    background: #f8fafc;
}


/* ============================================================
   ERRORS
   ============================================================ */

.field-error {
    display: block;
    margin-top: 5px;
    font-size: .82rem;
    color: #dc3545;
}

.form-control.is-invalid,
select.form-control.is-invalid {
    border-color: #dc3545;
    background-image: none;
}

.form-control.is-valid {
    border-color: #198754;
    background-image: none;
}


/* ============================================================
   HELP TEXT
   ============================================================ */

.text-muted-sm {
    color: #6b7280;
    font-size: .9rem;
}

.password-help {
    display: block;
    margin-top: 6px;
    color: #6b7280;
    font-size: .8rem;
}


/* ============================================================
   BUTTONS
   ============================================================ */

.btn-auth {
    font-weight: 700;
    border-radius: .6rem;
}

.btn-block {
    width: 100%;
}


/* ============================================================
   EMPLOYER WIZARD
   ============================================================ */

.emp-wizard {
    position: relative;
    overflow: hidden;
}

.emp-track {
    display: flex;
    width: 200%;

    transform: translateX(0);

    transition: transform .28s ease;
}

.emp-wizard.show-step-2 .emp-track {
    transform: translateX(-50%);
}

.emp-step {
    width: 50%;
    flex: 0 0 50%;
}

.emp-steps {
    display: flex;
    align-items: center;
    justify-content: center;

    gap: .75rem;

    margin: .25rem 0 1rem;
}

.emp-steps .badge {
    padding: .45rem .65rem;
    border-radius: 999px;
}


/* ============================================================
   MOBILE
   ============================================================ */

@media (max-width: 767.98px) {

    .auth-tabs {
        flex-wrap: wrap;
    }

    .auth-tabs .nav-item {
        flex: 0 0 50%;
        max-width: 50%;
    }
}

@media (max-width: 480px) {

    .auth-tabs .nav-item {
        flex: 0 0 100%;
        max-width: 100%;
    }

    .auth-tabs .nav-link {
        min-height: 56px;
    }
}

</style>


<!-- ============================================================
     HERO
============================================================= -->

<section
    class="companies-hero overlay inner-page bg-image"
    style="background-image:url('../images/tst.jpg');"
    id="home-section"
>

    <div class="overlay-dark"></div>

    <div class="container hero-inner text-center">

        <div class="ch-eyebrow mb-1">
            Account
        </div>

        <h2 class="ch-title mb-2">
            Welcome back
        </h2>

        <p class="ch-sub mb-0">
            Sign in or create an account — for Job Seekers & Employers
        </p>

    </div>

</section>


<!-- ============================================================
     AUTH SECTION
============================================================= -->

<section class="site-section">

    <div class="container auth-shell">

        <div class="card auth-card">


            <!-- ==================================================
                 TABS
            =================================================== -->

            <ul
                class="nav nav-pills nav-justified auth-tabs"
                id="authTabs"
                role="tablist"
            >

                <!-- LOGIN TAB -->

                <li class="nav-item">

                    <a
                        class="nav-link <?php echo ($activeTab === 'login' ? 'active' : ''); ?>"
                        id="tab-login"
                        data-toggle="pill"
                        href="#pills-login"
                        role="tab"
                        aria-controls="pills-login"
                        aria-selected="<?php echo ($activeTab === 'login' ? 'true' : 'false'); ?>"
                    >

                        <i class="fa fa-sign-in mr-2"></i>

                        <span class="lbl">

                            <span>
                                Login
                            </span>

                        </span>

                    </a>

                </li>


                <!-- JOB SEEKER TAB -->

                <li class="nav-item">

                    <a
                        class="nav-link <?php echo ($activeTab === 'js' ? 'active' : ''); ?>"
                        id="tab-js"
                        data-toggle="pill"
                        href="#pills-js"
                        role="tab"
                        aria-controls="pills-js"
                        aria-selected="<?php echo ($activeTab === 'js' ? 'true' : 'false'); ?>"
                    >

                        <i class="fa fa-user-plus mr-2"></i>

                        <span class="lbl">

                            <span>
                                Register
                            </span>

                            <small>
                                (Job Seeker)
                            </small>

                        </span>

                    </a>

                </li>


                <!-- EMPLOYER TAB -->

                <li class="nav-item">

                    <a
                        class="nav-link <?php echo ($activeTab === 'emp' ? 'active' : ''); ?>"
                        id="tab-emp"
                        data-toggle="pill"
                        href="#pills-emp"
                        role="tab"
                        aria-controls="pills-emp"
                        aria-selected="<?php echo ($activeTab === 'emp' ? 'true' : 'false'); ?>"
                    >

                        <i class="fa fa-building mr-2"></i>

                        <span class="lbl">

                            <span>
                                Register
                            </span>

                            <small>
                                (Employer)
                            </small>

                        </span>

                    </a>

                </li>

            </ul>


            <div class="auth-body">

                <div
                    class="tab-content"
                    id="pills-tabContent"
                >


                    <!-- ==================================================
                         LOGIN
                    =================================================== -->

                    <div
                        class="tab-pane fade <?php echo ($activeTab === 'login' ? 'show active' : ''); ?>"
                        id="pills-login"
                        role="tabpanel"
                        aria-labelledby="tab-login"
                    >


                        <!-- SUCCESS MESSAGE -->

                        <?php if (!empty($_SESSION['successMsg'])): ?>

                            <div
                                class="alert alert-success alert-dismissible fade show"
                                role="alert"
                            >

                                <i class="fa fa-check-circle mr-1"></i>

                                <?php
                                echo htmlspecialchars(
                                    $_SESSION['successMsg'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>

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

                            <?php unset($_SESSION['successMsg']); ?>

                        <?php endif; ?>


                        <div class="text-center mb-3">

                            <div class="text-muted small">
                                Login with your registered Email & Password
                            </div>


                            <!-- LOGIN ERROR -->

                            <?php if (!empty($error)): ?>

                                <div
                                    class="alert alert-danger alert-dismissible fade show mt-2"
                                    role="alert"
                                >

                                    <i class="fa fa-exclamation-circle mr-1"></i>

                                    <?php
                                    echo htmlspecialchars(
                                        $error,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

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

                            <?php endif; ?>

                        </div>


                        <!-- LOGIN FORM -->

                        <form
                            action="loginRegister.php"
                            method="POST"
                            novalidate
                            class="form"
                            id="loginForm"
                        >


                            <!-- EMAIL -->

                            <div class="form-group">

                                <label for="loginEmail">
                                    Email address
                                </label>

                                <div class="input-group">

                                    <div class="input-group-prepend">

                                        <span class="input-group-text">

                                            <i class="fa fa-envelope"></i>

                                        </span>

                                    </div>

                                    <input
                                        type="email"
                                        id="loginEmail"
                                        class="form-control"
                                        placeholder="you@example.com"
                                        name="email"
                                        maxlength="120"
                                        autocomplete="email"
                                        required
                                    >

                                </div>

                            </div>


                            <!-- PASSWORD -->

                            <div class="form-group mb-2">

                                <label for="loginPassword">
                                    Password
                                </label>

                                <div class="input-group">

                                    <div class="input-group-prepend">

                                        <span class="input-group-text">

                                            <i class="fa fa-lock"></i>

                                        </span>

                                    </div>

                                    <input
                                        type="password"
                                        id="loginPassword"
                                        class="form-control"
                                        placeholder="Password"
                                        name="password"
                                        autocomplete="current-password"
                                        required
                                    >

                                    <div class="input-group-append">

                                        <button
                                            class="btn btn-outline-secondary"
                                            type="button"
                                            data-toggle="password"
                                            data-target="#loginPassword"
                                        >

                                            <i class="far fa-eye"></i>

                                        </button>

                                    </div>

                                </div>

                            </div>


                            <!-- LOGIN BUTTON -->

                            <button
                                class="btn btn-primary btn-auth btn-block mt-3"
                                type="submit"
                                name="login"
                            >

                                <i class="fa fa-sign-in mr-1"></i>

                                Log in

                            </button>

                        </form>

                    </div>


                    <!-- ==================================================
                         JOB SEEKER REGISTRATION
                    =================================================== -->

                    <div
                        class="tab-pane fade <?php echo ($activeTab === 'js' ? 'show active' : ''); ?>"
                        id="pills-js"
                        role="tabpanel"
                        aria-labelledby="tab-js"
                    >

                        <div class="text-center mb-3">

                            <div class="text-muted-sm">
                                Create your free Job Seeker Account
                            </div>

                        </div>


                        <?php if (!empty($jsErrors['_form'])): ?>

                            <div class="alert alert-danger">

                                <?php
                                echo htmlspecialchars(
                                    $jsErrors['_form'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>

                            </div>

                        <?php endif; ?>


                        <form
                            action="loginRegister.php"
                            method="POST"
                            novalidate
                            class="form"
                            id="jobSeekerForm"
                        >


                            <!-- FULL NAME -->

                            <div class="form-group">

                                <label for="jsFullname">
                                    Full Name
                                </label>

                                <input
                                    type="text"
                                    id="jsFullname"
                                    class="form-control <?php echo isset($jsErrors['fullname']) ? 'is-invalid' : ''; ?>"
                                    placeholder="Your full name"
                                    name="fullname"
                                    minlength="2"
                                    maxlength="100"
                                    autocomplete="name"
                                    value="<?php echo htmlspecialchars($jsOld['fullname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                    required
                                >

                                <?php if (!empty($jsErrors['fullname'])): ?>

                                    <small class="field-error">

                                        <?php
                                        echo htmlspecialchars(
                                            $jsErrors['fullname'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>

                                    </small>

                                <?php endif; ?>

                            </div>


                            <!-- USERNAME + EMAIL -->

                            <div class="form-row">


                                <!-- USERNAME -->

                                <div class="form-group col-md-6">

                                    <label for="jsUsername">
                                        Username
                                    </label>

                                    <input
                                        type="text"
                                        id="jsUsername"
                                        class="form-control <?php echo isset($jsErrors['username']) ? 'is-invalid' : ''; ?>"
                                        placeholder="username"
                                        name="username"
                                        minlength="3"
                                        maxlength="30"
                                        autocomplete="username"
                                        value="<?php echo htmlspecialchars($jsOld['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        required
                                    >

                                    <?php if (!empty($jsErrors['username'])): ?>

                                        <small class="field-error">

                                            <?php
                                            echo htmlspecialchars(
                                                $jsErrors['username'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>

                                        </small>

                                    <?php endif; ?>

                                </div>


                                <!-- EMAIL -->

                                <div class="form-group col-md-6">

                                    <label for="jsEmail">
                                        Email
                                    </label>

                                    <input
                                        type="email"
                                        id="jsEmail"
                                        class="form-control <?php echo isset($jsErrors['email']) ? 'is-invalid' : ''; ?>"
                                        placeholder="Email address"
                                        name="email"
                                        maxlength="120"
                                        autocomplete="email"
                                        value="<?php echo htmlspecialchars($jsOld['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        required
                                    >

                                    <?php if (!empty($jsErrors['email'])): ?>

                                        <small class="field-error">

                                            <?php
                                            echo htmlspecialchars(
                                                $jsErrors['email'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>

                                        </small>

                                    <?php endif; ?>

                                </div>

                            </div>


                            <!-- CONTACT -->

                            <div class="form-group">

                                <label for="jsContact">
                                    Contact Number
                                </label>

                                <input
                                    type="tel"
                                    id="jsContact"
                                    class="form-control <?php echo isset($jsErrors['contact']) ? 'is-invalid' : ''; ?>"
                                    placeholder="98XXXXXXXX"
                                    name="contact"
                                    inputmode="numeric"
                                    maxlength="10"
                                    autocomplete="tel"
                                    value="<?php echo htmlspecialchars($jsOld['contact'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                    required
                                >

                                <small class="form-text text-muted">
                                    Enter a 10-digit Nepali mobile number starting with 97 or 98.
                                </small>

                                <?php if (!empty($jsErrors['contact'])): ?>

                                    <small class="field-error">

                                        <?php
                                        echo htmlspecialchars(
                                            $jsErrors['contact'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>

                                    </small>

                                <?php endif; ?>

                            </div>


                            <!-- REGION + ADDRESS -->

                            <div class="form-row">


                                <!-- REGION -->

                                <div class="form-group col-md-6">

                                    <label for="jsRegion">
                                        Region / State
                                    </label>

                                    <select
                                        class="form-control <?php echo isset($jsErrors['region_id']) ? 'is-invalid' : ''; ?>"
                                        name="region_id"
                                        id="jsRegion"
                                        required
                                    >

                                        <option value="">
                                            Select region/state
                                        </option>

                                        <?php foreach ($regionRows as $rg): ?>

                                            <option
                                                value="<?php echo (int)$rg['id']; ?>"
                                                <?php echo (
                                                    isset($jsOld['region_id']) &&
                                                    (int)$jsOld['region_id'] === (int)$rg['id']
                                                ) ? 'selected' : ''; ?>
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $rg['name'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                );
                                                ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                    <?php if (!empty($jsErrors['region_id'])): ?>

                                        <small class="field-error">

                                            <?php
                                            echo htmlspecialchars(
                                                $jsErrors['region_id'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>

                                        </small>

                                    <?php endif; ?>

                                </div>


                                <!-- ADDRESS -->

                                <div class="form-group col-md-6">

                                    <label for="jsAddress">
                                        Address
                                    </label>

                                    <input
                                        type="text"
                                        id="jsAddress"
                                        class="form-control <?php echo isset($jsErrors['address']) ? 'is-invalid' : ''; ?>"
                                        placeholder="Street address"
                                        name="address"
                                        maxlength="255"
                                        autocomplete="street-address"
                                        value="<?php echo htmlspecialchars($jsOld['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        required
                                    >

                                    <?php if (!empty($jsErrors['address'])): ?>

                                        <small class="field-error">

                                            <?php
                                            echo htmlspecialchars(
                                                $jsErrors['address'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>

                                        </small>

                                    <?php endif; ?>

                                </div>

                            </div>


                            <input
                                type="hidden"
                                value="Job Seeker"
                                name="type"
                            >


                            <!-- PASSWORDS -->

                            <div class="form-row">


                                <!-- PASSWORD -->

                                <div class="form-group col-md-6">

                                    <label for="jsPassword">
                                        Password
                                    </label>

                                    <div class="input-group">

                                        <input
                                            type="password"
                                            id="jsPassword"
                                            class="form-control <?php echo isset($jsErrors['password']) ? 'is-invalid' : ''; ?>"
                                            placeholder="Create password"
                                            name="password"
                                            minlength="8"
                                            maxlength="128"
                                            autocomplete="new-password"
                                            required
                                        >

                                        <div class="input-group-append">

                                            <button
                                                class="btn btn-outline-secondary"
                                                type="button"
                                                data-toggle="password"
                                                data-target="#jsPassword"
                                            >

                                                <i class="far fa-eye"></i>

                                            </button>

                                        </div>

                                    </div>

                                    <small class="password-help">
                                        8+ characters, uppercase, lowercase, number and special character.
                                    </small>

                                    <?php if (!empty($jsErrors['password'])): ?>

                                        <small class="field-error">

                                            <?php
                                            echo htmlspecialchars(
                                                $jsErrors['password'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>

                                        </small>

                                    <?php endif; ?>

                                </div>


                                <!-- CONFIRM PASSWORD -->

                                <div class="form-group col-md-6">

                                    <label for="jsRePassword">
                                        Re-type Password
                                    </label>

                                    <div class="input-group">

                                        <input
                                            type="password"
                                            id="jsRePassword"
                                            class="form-control <?php echo isset($jsErrors['re-password']) ? 'is-invalid' : ''; ?>"
                                            placeholder="Re-type password"
                                            name="re-password"
                                            minlength="8"
                                            maxlength="128"
                                            autocomplete="new-password"
                                            required
                                        >

                                        <div class="input-group-append">

                                            <button
                                                class="btn btn-outline-secondary"
                                                type="button"
                                                data-toggle="password"
                                                data-target="#jsRePassword"
                                            >

                                                <i class="far fa-eye"></i>

                                            </button>

                                        </div>

                                    </div>

                                    <?php if (!empty($jsErrors['re-password'])): ?>

                                        <small class="field-error">

                                            <?php
                                            echo htmlspecialchars(
                                                $jsErrors['re-password'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>

                                        </small>

                                    <?php endif; ?>

                                </div>

                            </div>


                            <!-- REGISTER -->

                            <button
                                class="btn btn-primary btn-auth btn-block"
                                type="submit"
                                name="submit"
                            >

                                <i class="fa fa-user-plus mr-1"></i>

                                Register

                            </button>

                        </form>

                    </div>


                    <!-- ==================================================
                         EMPLOYER REGISTRATION
                    =================================================== -->

                    <div
                        class="tab-pane fade <?php echo ($activeTab === 'emp' ? 'show active' : ''); ?>"
                        id="pills-emp"
                        role="tabpanel"
                        aria-labelledby="tab-emp"
                    >


                        <?php if (!empty($empErrors['_form'])): ?>

                            <div class="alert alert-danger">

                                <?php
                                echo htmlspecialchars(
                                    $empErrors['_form'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>

                            </div>

                        <?php endif; ?>


                        <div class="text-center mb-1">

                            <div class="text-muted-sm">
                                Create your free Employer Account
                            </div>

                        </div>


                        <!-- STEP INDICATOR -->

                        <div class="emp-steps">

                            <span
                                class="badge <?php echo ($activeTab === 'emp' && $empShowStep2) ? 'badge-light' : 'badge-primary'; ?>"
                                id="empStep1Dot"
                            >
                                1
                            </span>

                            <span class="text-muted-sm">
                                Account
                            </span>

                            <span class="mx-1">
                                →
                            </span>

                            <span
                                class="badge <?php echo ($activeTab === 'emp' && $empShowStep2) ? 'badge-primary' : 'badge-light'; ?>"
                                id="empStep2Dot"
                            >
                                2
                            </span>

                            <span class="text-muted-sm">
                                Company
                            </span>

                        </div>


                        <!-- EMPLOYER FORM -->

                        <form
                            id="empForm"
                            action="loginRegister.php"
                            method="POST"
                            novalidate
                            class="form"
                        >

                            <input
                                type="hidden"
                                name="type"
                                value="Employer"
                            >


                            <div
                                class="emp-wizard mt-2 <?php echo ($activeTab === 'emp' && $empShowStep2) ? 'show-step-2' : ''; ?>"
                            >

                                <div class="emp-track">


                                    <!-- ==================================================
                                         EMPLOYER STEP 1
                                    =================================================== -->

                                    <div class="emp-step pr-md-3">


                                        <!-- ORGANIZATION -->

                                        <div class="form-group">

                                            <label for="empFullname">
                                                Organization Name
                                            </label>

                                            <input
                                                type="text"
                                                id="empFullname"
                                                class="form-control <?php echo isset($empErrors['fullname']) ? 'is-invalid' : ''; ?>"
                                                name="fullname"
                                                placeholder="Name of Organization"
                                                minlength="2"
                                                maxlength="100"
                                                value="<?php echo htmlspecialchars($empOld['fullname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                required
                                            >

                                            <?php if (!empty($empErrors['fullname'])): ?>

                                                <small class="field-error">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $empErrors['fullname'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    );
                                                    ?>

                                                </small>

                                            <?php endif; ?>

                                        </div>


                                        <!-- USERNAME + EMAIL -->

                                        <div class="form-row">


                                            <div class="form-group col-md-6">

                                                <label for="empUsername">
                                                    Username
                                                </label>

                                                <input
                                                    type="text"
                                                    id="empUsername"
                                                    class="form-control <?php echo isset($empErrors['username']) ? 'is-invalid' : ''; ?>"
                                                    name="username"
                                                    placeholder="username"
                                                    minlength="3"
                                                    maxlength="30"
                                                    value="<?php echo htmlspecialchars($empOld['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    required
                                                >

                                                <?php if (!empty($empErrors['username'])): ?>

                                                    <small class="field-error">

                                                        <?php
                                                        echo htmlspecialchars(
                                                            $empErrors['username'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        );
                                                        ?>

                                                    </small>

                                                <?php endif; ?>

                                            </div>


                                            <div class="form-group col-md-6">

                                                <label for="empEmail">
                                                    Official Email
                                                </label>

                                                <input
                                                    type="email"
                                                    id="empEmail"
                                                    class="form-control <?php echo isset($empErrors['email']) ? 'is-invalid' : ''; ?>"
                                                    name="email"
                                                    placeholder="Official email"
                                                    maxlength="120"
                                                    value="<?php echo htmlspecialchars($empOld['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    required
                                                >

                                                <?php if (!empty($empErrors['email'])): ?>

                                                    <small class="field-error">

                                                        <?php
                                                        echo htmlspecialchars(
                                                            $empErrors['email'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        );
                                                        ?>

                                                    </small>

                                                <?php endif; ?>

                                            </div>

                                        </div>


                                        <!-- CONTACT + PASSWORD -->

                                        <div class="form-row">


                                            <div class="form-group col-md-6">

                                                <label for="empContact">
                                                    Official Contact
                                                </label>

                                                <input
                                                    type="tel"
                                                    id="empContact"
                                                    class="form-control <?php echo isset($empErrors['contact']) ? 'is-invalid' : ''; ?>"
                                                    name="contact"
                                                    placeholder="98XXXXXXXX"
                                                    inputmode="numeric"
                                                    maxlength="10"
                                                    value="<?php echo htmlspecialchars($empOld['contact'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    required
                                                >

                                                <?php if (!empty($empErrors['contact'])): ?>

                                                    <small class="field-error">

                                                        <?php
                                                        echo htmlspecialchars(
                                                            $empErrors['contact'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        );
                                                        ?>

                                                    </small>

                                                <?php endif; ?>

                                            </div>


                                            <div class="form-group col-md-6">

                                                <label for="empPassword">
                                                    Password
                                                </label>

                                                <div class="input-group">

                                                    <input
                                                        type="password"
                                                        id="empPassword"
                                                        class="form-control <?php echo isset($empErrors['password']) ? 'is-invalid' : ''; ?>"
                                                        name="password"
                                                        placeholder="Create password"
                                                        minlength="8"
                                                        maxlength="128"
                                                        autocomplete="new-password"
                                                        required
                                                    >

                                                    <div class="input-group-append">

                                                        <button
                                                            class="btn btn-outline-secondary"
                                                            type="button"
                                                            data-toggle="password"
                                                            data-target="#empPassword"
                                                        >

                                                            <i class="far fa-eye"></i>

                                                        </button>

                                                    </div>

                                                </div>

                                                <?php if (!empty($empErrors['password'])): ?>

                                                    <small class="field-error">

                                                        <?php
                                                        echo htmlspecialchars(
                                                            $empErrors['password'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        );
                                                        ?>

                                                    </small>

                                                <?php endif; ?>

                                            </div>

                                        </div>


                                        <!-- CONFIRM PASSWORD -->

                                        <div class="form-group">

                                            <label for="empRePassword">
                                                Re-type Password
                                            </label>

                                            <div class="input-group">

                                                <input
                                                    type="password"
                                                    id="empRePassword"
                                                    class="form-control <?php echo isset($empErrors['re-password']) ? 'is-invalid' : ''; ?>"
                                                    name="re-password"
                                                    placeholder="Re-type password"
                                                    minlength="8"
                                                    maxlength="128"
                                                    autocomplete="new-password"
                                                    required
                                                >

                                                <div class="input-group-append">

                                                    <button
                                                        class="btn btn-outline-secondary"
                                                        type="button"
                                                        data-toggle="password"
                                                        data-target="#empRePassword"
                                                    >

                                                        <i class="far fa-eye"></i>

                                                    </button>

                                                </div>

                                            </div>

                                            <small class="password-help">
                                                8+ characters, uppercase, lowercase, number and special character.
                                            </small>

                                            <?php if (!empty($empErrors['re-password'])): ?>

                                                <small class="field-error">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $empErrors['re-password'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    );
                                                    ?>

                                                </small>

                                            <?php endif; ?>

                                        </div>


                                        <!-- NEXT -->

                                        <div class="d-flex justify-content-end">

                                            <button
                                                type="button"
                                                id="empNext"
                                                class="btn btn-primary btn-auth"
                                            >

                                                Next

                                                <i class="fa fa-arrow-right ml-1"></i>

                                            </button>

                                        </div>

                                    </div>


                                    <!-- ==================================================
                                         EMPLOYER STEP 2
                                    =================================================== -->

                                    <div class="emp-step pl-md-3">


                                        <!-- WEBSITE + INDUSTRY -->

                                        <div class="form-row">


                                            <div class="form-group col-md-6">

                                                <label for="companyWebsite">
                                                    Company Website
                                                </label>

                                                <input
                                                    type="url"
                                                    id="companyWebsite"
                                                    class="form-control <?php echo isset($empErrors['company_website']) ? 'is-invalid' : ''; ?>"
                                                    name="company_website"
                                                    placeholder="https://example.com"
                                                    maxlength="255"
                                                    value="<?php echo htmlspecialchars($empOld['company_website'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    required
                                                >

                                                <?php if (!empty($empErrors['company_website'])): ?>

                                                    <small class="field-error">

                                                        <?php
                                                        echo htmlspecialchars(
                                                            $empErrors['company_website'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        );
                                                        ?>

                                                    </small>

                                                <?php endif; ?>

                                            </div>


                                            <div class="form-group col-md-6">

                                                <label for="industry">
                                                    Industry
                                                </label>

                                                <input
                                                    type="text"
                                                    id="industry"
                                                    class="form-control <?php echo isset($empErrors['industry']) ? 'is-invalid' : ''; ?>"
                                                    name="industry"
                                                    placeholder="e.g. Software"
                                                    maxlength="100"
                                                    value="<?php echo htmlspecialchars($empOld['industry'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    required
                                                >

                                                <?php if (!empty($empErrors['industry'])): ?>

                                                    <small class="field-error">

                                                        <?php
                                                        echo htmlspecialchars(
                                                            $empErrors['industry'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        );
                                                        ?>

                                                    </small>

                                                <?php endif; ?>

                                            </div>

                                        </div>


                                        <!-- ADDRESS + POSTAL -->

                                        <div class="form-row">


                                            <div class="form-group col-md-8">

                                                <label for="addressLine">
                                                    Address Line
                                                </label>

                                                <input
                                                    type="text"
                                                    id="addressLine"
                                                    class="form-control <?php echo isset($empErrors['address_line']) ? 'is-invalid' : ''; ?>"
                                                    name="address_line"
                                                    placeholder="Street, City, State"
                                                    maxlength="255"
                                                    value="<?php echo htmlspecialchars($empOld['address_line'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    required
                                                >

                                                <?php if (!empty($empErrors['address_line'])): ?>

                                                    <small class="field-error">

                                                        <?php
                                                        echo htmlspecialchars(
                                                            $empErrors['address_line'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        );
                                                        ?>

                                                    </small>

                                                <?php endif; ?>

                                            </div>


                                            <div class="form-group col-md-4">

                                                <label for="postalCode">
                                                    Postal Code
                                                </label>

                                                <input
                                                    type="text"
                                                    id="postalCode"
                                                    class="form-control <?php echo isset($empErrors['postal_code']) ? 'is-invalid' : ''; ?>"
                                                    name="postal_code"
                                                    maxlength="16"
                                                    value="<?php echo htmlspecialchars($empOld['postal_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    required
                                                >

                                                <?php if (!empty($empErrors['postal_code'])): ?>

                                                    <small class="field-error">

                                                        <?php
                                                        echo htmlspecialchars(
                                                            $empErrors['postal_code'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        );
                                                        ?>

                                                    </small>

                                                <?php endif; ?>

                                            </div>

                                        </div>


                                        <!-- BUSINESS REGISTRATION NUMBER -->

                                        <div class="form-group">

                                            <label for="businessRegNo">

                                                Nepal PAN Number

                                                <span class="text-danger">
                                                    *
                                                </span>

                                            </label>

                                            <input
                                                type="text"
                                                id="businessRegNo"
                                                class="form-control <?php echo isset($empErrors['business_reg_no']) ? 'is-invalid' : ''; ?>"
                                                name="business_reg_no"
                                                placeholder="Enter 9-digit PAN number"
                                                maxlength="20"
                                                value="<?php echo htmlspecialchars($empOld['business_reg_no'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                required
                                            >

                                            <small class="form-text text-muted">
                                                Enter your 9-digit Nepal PAN number.
                                            </small>

                                            <?php if (!empty($empErrors['business_reg_no'])): ?>

                                                <small class="field-error">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $empErrors['business_reg_no'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    );
                                                    ?>

                                                </small>

                                            <?php endif; ?>

                                        </div>


                                        <!-- COMPANY SIZE + ORG TYPE -->

                                        <div class="form-row">


                                            <!-- COMPANY SIZE -->

                                            <div class="form-group col-md-6">

                                                <label for="companySize">
                                                    Company Size / Number of Employees
                                                </label>

                                                <?php
                                                $cs =
                                                    $empOld['company_size']
                                                    ?? '';
                                                ?>

                                                <select
                                                    class="form-control <?php echo isset($empErrors['company_size']) ? 'is-invalid' : ''; ?>"
                                                    name="company_size"
                                                    id="companySize"
                                                >

                                                    <option value="">
                                                        Select…
                                                    </option>

                                                    <?php foreach ($ALLOWED_SIZES as $size): ?>

                                                        <option
                                                            value="<?php echo htmlspecialchars($size, ENT_QUOTES, 'UTF-8'); ?>"
                                                            <?php echo ($cs === $size) ? 'selected' : ''; ?>
                                                        >

                                                            <?php
                                                            echo htmlspecialchars(
                                                                $size,
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            );
                                                            ?>

                                                        </option>

                                                    <?php endforeach; ?>

                                                </select>

                                                <?php if (!empty($empErrors['company_size'])): ?>

                                                    <small class="field-error">

                                                        <?php
                                                        echo htmlspecialchars(
                                                            $empErrors['company_size'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        );
                                                        ?>

                                                    </small>

                                                <?php endif; ?>

                                            </div>


                                            <!-- ORGANIZATION TYPE -->

                                            <div class="form-group col-md-6">

                                                <label for="orgType">
                                                    Type of Organization
                                                </label>

                                                <?php
                                                $ot =
                                                    $empOld['org_type']
                                                    ?? '';
                                                ?>

                                                <select
                                                    class="form-control <?php echo isset($empErrors['org_type']) ? 'is-invalid' : ''; ?>"
                                                    name="org_type"
                                                    id="orgType"
                                                >

                                                    <option value="">
                                                        Select…
                                                    </option>

                                                    <?php foreach ($ALLOWED_ORGS as $org): ?>

                                                        <option
                                                            value="<?php echo htmlspecialchars($org, ENT_QUOTES, 'UTF-8'); ?>"
                                                            <?php echo ($ot === $org) ? 'selected' : ''; ?>
                                                        >

                                                            <?php
                                                            echo htmlspecialchars(
                                                                $org,
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            );
                                                            ?>

                                                        </option>

                                                    <?php endforeach; ?>

                                                </select>


                                                <small
                                                    id="orgHelp"
                                                    class="form-text text-muted"
                                                    style="<?php echo ($ot === 'Freelancer / Independent Consultant') ? 'display:block;' : 'display:none;'; ?>"
                                                >

                                                    Freelancers/Consultants must enter a valid tax or business registration number provided by their local authority.

                                                </small>


                                                <?php if (!empty($empErrors['org_type'])): ?>

                                                    <small class="field-error">

                                                        <?php
                                                        echo htmlspecialchars(
                                                            $empErrors['org_type'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        );
                                                        ?>

                                                    </small>

                                                <?php endif; ?>

                                            </div>

                                        </div>


                                        <!-- YEAR + OPERATING HOURS -->

                                        <div class="form-row">


                                            <div class="form-group col-md-4">

                                                <label for="establishedYear">
                                                    Established Year
                                                </label>

                                                <input
                                                    type="number"
                                                    id="establishedYear"
                                                    class="form-control <?php echo isset($empErrors['established_year']) ? 'is-invalid' : ''; ?>"
                                                    name="established_year"
                                                    min="1800"
                                                    max="<?php echo date('Y'); ?>"
                                                    step="1"
                                                    placeholder="YYYY"
                                                    value="<?php echo htmlspecialchars($empOld['established_year'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    required
                                                >

                                                <?php if (!empty($empErrors['established_year'])): ?>

                                                    <small class="field-error">

                                                        <?php
                                                        echo htmlspecialchars(
                                                            $empErrors['established_year'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        );
                                                        ?>

                                                    </small>

                                                <?php endif; ?>

                                            </div>


                                            <div class="form-group col-md-8">

                                                <label for="operatingHours">
                                                    Operating Hours
                                                </label>

                                                <input
                                                    type="text"
                                                    id="operatingHours"
                                                    class="form-control <?php echo isset($empErrors['operating_hours']) ? 'is-invalid' : ''; ?>"
                                                    name="operating_hours"
                                                    placeholder="Mon–Fri 9:00–17:00"
                                                    maxlength="100"
                                                    value="<?php echo htmlspecialchars($empOld['operating_hours'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    required
                                                >

                                                <?php if (!empty($empErrors['operating_hours'])): ?>

                                                    <small class="field-error">

                                                        <?php
                                                        echo htmlspecialchars(
                                                            $empErrors['operating_hours'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        );
                                                        ?>

                                                    </small>

                                                <?php endif; ?>

                                            </div>

                                        </div>


                                        <!-- BACK + CREATE -->

                                        <div class="d-flex justify-content-between">

                                            <button
                                                type="button"
                                                id="empBack"
                                                class="btn btn-outline-secondary"
                                            >

                                                <i class="fa fa-arrow-left mr-1"></i>

                                                Back

                                            </button>


                                            <button
                                                type="submit"
                                                name="employersubmit"
                                                class="btn btn-success btn-auth"
                                            >

                                                <i class="fa fa-check mr-1"></i>

                                                Create Account

                                            </button>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>


<script>

/* ============================================================
   PASSWORD SHOW / HIDE
   ============================================================ */

document.querySelectorAll(
    '[data-toggle="password"]'
).forEach(function (btn) {

    var targetSelector =
        btn.getAttribute('data-target');

    var target =
        document.querySelector(targetSelector);

    if (!target) {
        return;
    }

    btn.addEventListener('click', function () {

        var isPassword =
            target.type === 'password';

        target.type =
            isPassword ? 'text' : 'password';

        var icon =
            btn.querySelector('i');

        if (icon) {

            icon.classList.toggle(
                'fa-eye',
                !isPassword
            );

            icon.classList.toggle(
                'fa-eye-slash',
                isPassword
            );
        }

    });

});


/* ============================================================
   FRONTEND VALIDATION
   ============================================================ */

document.addEventListener(
    'DOMContentLoaded',
    function () {


        /* ----------------------------------------------------
           FULL NAME
        ---------------------------------------------------- */

        document
            .querySelectorAll('[name="fullname"]')
            .forEach(function (input) {

                input.addEventListener(
                    'input',
                    function () {

                        var value =
                            this.value.trim();

                        var valid =
                            /^[\p{L}]+(?:[ '\-][\p{L}]+)*$/u
                            .test(value);

                        if (
                            value !== '' &&
                            !valid
                        ) {

                            this.setCustomValidity(
                                'Full name can contain letters, spaces, hyphens and apostrophes only.'
                            );

                        } else {

                            this.setCustomValidity('');
                        }

                    }
                );

            });


        /* ----------------------------------------------------
           ADDRESS
        ---------------------------------------------------- */

        document
            .querySelectorAll(
                '[name="address"], [name="address_line"]'
            )
            .forEach(function (input) {

                input.addEventListener(
                    'input',
                    function () {

                        var value =
                            this.value.trim();

                        var valid =
                            /^[\p{L}\p{N}\s,.'\/#()\-]+$/u
                            .test(value);

                        var hasLetterOrNumber =
                            /[\p{L}\p{N}]/u
                            .test(value);

                        if (
                            value !== '' &&
                            (
                                value.length < 5 ||
                                !valid ||
                                !hasLetterOrNumber
                            )
                        ) {

                            this.setCustomValidity(
                                'Enter a valid address using letters, numbers and normal address punctuation.'
                            );

                        } else {

                            this.setCustomValidity('');
                        }
                    }
                );
            });


        /* ----------------------------------------------------
           USERNAME
        ---------------------------------------------------- */

        document
            .querySelectorAll('[name="username"]')
            .forEach(function (input) {

                input.addEventListener(
                    'input',
                    function () {

                        var value =
                            this.value.trim();

                        var valid =
                            /^[A-Za-z][A-Za-z0-9_.]*$/
                            .test(value);

                        if (
                            value !== '' &&
                            !valid
                        ) {

                            this.setCustomValidity(
                                'Username must start with a letter and contain only letters, numbers, dots or underscores.'
                            );

                        } else {

                            this.setCustomValidity('');
                        }

                    }
                );

            });


        /* ----------------------------------------------------
           CONTACT NUMBER
        ---------------------------------------------------- */

        document
            .querySelectorAll('[name="contact"]')
            .forEach(function (input) {

                input.addEventListener(
                    'input',
                    function () {

                        /*
                         * Only numbers allowed.
                         */

                        this.value =
                            this.value.replace(
                                /\D/g,
                                ''
                            );

                        var value =
                            this.value;

                        var valid =
                            /^9[78][0-9]{8}$/
                            .test(value);

                        if (
                            value !== '' &&
                            !valid
                        ) {

                            this.setCustomValidity(
                                'Enter mobile number.'
                            );

                        } else {

                            this.setCustomValidity('');
                        }

                    }
                );

            });


        /* ----------------------------------------------------
           NEPAL PAN NUMBER
        ---------------------------------------------------- */

        var panInput =
            document.getElementById('businessRegNo');

        if (panInput) {

            panInput.addEventListener(
                'input',
                function () {

                    this.value =
                        this.value.replace(/\D/g, '');

                    if (
                        this.value !== '' &&
                        !/^\d{9}$/.test(this.value)
                    ) {

                        this.setCustomValidity(
                            'Enter a valid 9-digit Nepal PAN number.'
                        );

                    } else {

                        this.setCustomValidity('');
                    }
                }
            );
        }


        /* ----------------------------------------------------
           PASSWORD VALIDATION
        ---------------------------------------------------- */

        document
            .querySelectorAll('form')
            .forEach(function (form) {

                var password =
                    form.querySelector(
                        '[name="password"]'
                    );

                var confirmation =
                    form.querySelector(
                        '[name="re-password"]'
                    );

                if (
                    !password ||
                    !confirmation
                ) {
                    return;
                }


                function validatePasswords() {

                    var value =
                        password.value;

                    var message = '';


                    if (
                        value !== '' &&
                        value.length < 8
                    ) {

                        message =
                            'Password must be at least 8 characters long.';

                    } else if (
                        value !== '' &&
                        !/[A-Z]/.test(value)
                    ) {

                        message =
                            'Password must contain at least one uppercase letter.';

                    } else if (
                        value !== '' &&
                        !/[a-z]/.test(value)
                    ) {

                        message =
                            'Password must contain at least one lowercase letter.';

                    } else if (
                        value !== '' &&
                        !/[0-9]/.test(value)
                    ) {

                        message =
                            'Password must contain at least one number.';

                    } else if (
                        value !== '' &&
                        !/[^A-Za-z0-9]/.test(value)
                    ) {

                        message =
                            'Password must contain at least one special character.';
                    }


                    password.setCustomValidity(
                        message
                    );


                    if (
                        confirmation.value !== '' &&
                        value !== confirmation.value
                    ) {

                        confirmation.setCustomValidity(
                            'Passwords do not match.'
                        );

                    } else {

                        confirmation.setCustomValidity('');
                    }

                }


                password.addEventListener(
                    'input',
                    validatePasswords
                );

                confirmation.addEventListener(
                    'input',
                    validatePasswords
                );

            });


        /* ====================================================
           FORM SUBMISSION
        ==================================================== */

        document
            .querySelectorAll('form')
            .forEach(function (form) {

                form.addEventListener(
                    'submit',
                    function (event) {

                        if (!form.checkValidity()) {

                            event.preventDefault();

                            form.classList.add(
                                'was-validated'
                            );

                            var firstInvalid =
                                form.querySelector(
                                    ':invalid'
                                );

                            if (firstInvalid) {

                                firstInvalid.focus();
                            }

                        }

                    }
                );

            });

    }
);


/* ============================================================
   EMPLOYER WIZARD
   ============================================================ */

(function () {

    var wizard =
        document.querySelector(
            '.emp-wizard'
        );

    if (!wizard) {
        return;
    }


    var nextBtn =
        document.getElementById(
            'empNext'
        );

    var backBtn =
        document.getElementById(
            'empBack'
        );

    var form =
        document.getElementById(
            'empForm'
        );

    var dot1 =
        document.getElementById(
            'empStep1Dot'
        );

    var dot2 =
        document.getElementById(
            'empStep2Dot'
        );


    function setStep(step) {

        if (step === 2) {

            wizard.classList.add(
                'show-step-2'
            );

            dot1.className =
                'badge badge-light';

            dot2.className =
                'badge badge-primary';

        } else {

            wizard.classList.remove(
                'show-step-2'
            );

            dot1.className =
                'badge badge-primary';

            dot2.className =
                'badge badge-light';
        }

    }


    function validStep1() {

        var fields = [
            'fullname',
            'username',
            'email',
            'contact',
            'password',
            're-password'
        ];


        for (
            var i = 0;
            i < fields.length;
            i++
        ) {

            var el =
                form.querySelector(
                    '[name="' +
                    fields[i] +
                    '"]'
                );

            if (
                !el ||
                !el.value.trim()
            ) {

                if (el) {
                    el.focus();
                }

                return false;
            }


            if (!el.checkValidity()) {

                el.reportValidity();

                return false;
            }

        }


        var password =
            form.querySelector(
                '[name="password"]'
            );

        var confirmation =
            form.querySelector(
                '[name="re-password"]'
            );


        if (
            password.value !==
            confirmation.value
        ) {

            alert(
                'Passwords do not match.'
            );

            confirmation.focus();

            return false;
        }


        return true;
    }


    if (nextBtn) {

        nextBtn.addEventListener(
            'click',
            function () {

                if (validStep1()) {
                    setStep(2);
                }

            }
        );

    }


    if (backBtn) {

        backBtn.addEventListener(
            'click',
            function () {

                setStep(1);

            }
        );

    }

})();


/* ============================================================
   ORGANIZATION TYPE HELP
   ============================================================ */

(function () {

    var orgSelect =
        document.getElementById(
            'orgType'
        );

    var orgHelp =
        document.getElementById(
            'orgHelp'
        );


    if (
        !orgSelect ||
        !orgHelp
    ) {
        return;
    }


    function toggleOrgHelp() {

        if (
            orgSelect.value ===
            'Freelancer / Independent Consultant'
        ) {

            orgHelp.style.display =
                'block';

        } else {

            orgHelp.style.display =
                'none';
        }

    }


    orgSelect.addEventListener(
        'change',
        toggleOrgHelp
    );

    toggleOrgHelp();

})();

</script>


<?php

require "../includes/footer.php";

?>