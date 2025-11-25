<?php
session_start();

if (!isset($_SESSION['cdc']) || empty($_SESSION['cdc']['username'])) {
    header('Location: role_login.php');
    exit;
}

$cdc = $_SESSION['cdc'];
$user = $cdc;

$id_kampus = $user['id_kampus'] ?? null;
$nama_kampus = "Tidak diketahui";

if ($id_kampus) {
    $api_url = "http://localhost:8000/api/kampus/" . urlencode($id_kampus);

    $context = stream_context_create([
        "http" => [
            "method" => "GET",
            "timeout" => 5,
            "ignore_errors" => true
        ]
    ]);

    $response = @file_get_contents($api_url, false, $context);

    if ($response !== false) {
        $data = json_decode($response, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($data['nama_kampus'])) {
                $nama_kampus = $data['nama_kampus'];
            } elseif (isset($data['message'])) {
                $nama_kampus = "Tidak diketahui (" . $data['message'] . ")";
            } else {
                $nama_kampus = "Tidak diketahui (Format respons tidak sesuai)";
            }
        } else {
            $nama_kampus = "Tidak diketahui (JSON error: " . json_last_error_msg() . ")";
        }
    } else {
        $nama_kampus = "Tidak diketahui (API tidak dapat diakses)";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Meta for Compatibility -->
    <meta charset="utf-8">
    <title>Approval CDC</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <!-- Icon -->
    <link rel="icon" href="./assets/img/iconM.png" type="image/x-icon" />
    <link href="./assets/img/iconM.png" rel="apple-touch-icon" type="image/x-icon">

    <link rel='stylesheet' href='./core/component/sweetalert2.min.css'>
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- CSS Files -->
    <link rel="stylesheet" href="./assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="./assets/css/atlantis.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Fonts and icons -->
    <script src="./assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: {
                "families": ["Lato:300,400,700,900"]
            },
            custom: {
                "families": ["Flaticon", "Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"],
                urls: ['./assets/css/fonts.min.css']
            },
            active: function() {
                sessionStorage.fonts = true;
            }
        });
    </script>

    <!-- CKEDITOR -->
    <script src="./library/ckeditor/ckeditor.js"></script>

    <script src='./core/component/jquery.min.js'></script>
    <script>
        $(function() {});
    </script>
    <script defer src='./core/component/sweetalert2.min.js'></script>
    <script defer src='./core/component/soloalert.js'></script>

    <style type="text/css">
        /* Posisi relatif untuk ikon agar badge bisa ditempatkan relatif terhadapnya */
        .notification-icon {
            position: relative;
            /* Sesuaikan ukuran ikon jika diperlukan */
        }

        /* Badge notifikasi kecil hijau */
        .custom-notification-badge {
            position: absolute;
            top: -8px;
            /* Sesuaikan posisi badge secara vertikal */
            right: -8px;
            /* Sesuaikan posisi badge secara horizontal */
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            /* Ukuran badge */
            font-size: 10px;
            /* Ukuran angka */
            line-height: 1;
            min-width: 16px;
            /* Pastikan ukuran minimal badge */
            text-align: center;
            /* Pusatkan angka di dalam badge */
        }

        .fc-sun {
            color: red;
            /* Mengubah warna font menjadi merah pada hari Minggu */
        }

        .disabled2 {
            pointer-events: none;
        }

        .not-avail {
            text-decoration: line-through;
            pointer-events: none;
            color: #808080;
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            -webkit-box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.3);
            box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.3);
            background: #fff;
        }

        ::-webkit-scrollbar-thumb {
            background: #6c757d;
        }

        .wrap {
            white-space: normal !important;
            word-wrap: break-word;
            min-width: 140px;
            max-width: 140px;
            /* max-width:150px; */
        }

        .wrap2 {
            white-space: normal !important;
            word-wrap: break-word;
            min-width: 170px;
            max-width: 170px;
            /* max-width:150px; */
        }

        .main-panel {
            padding-top: 50px;
        }

        .btn-xs {
            padding: 4px 8px;
            /* lebih kecil dari btn-sm */
            font-size: 0.75rem;
            /* teks sedikit lebih kecil */
            line-height: 1.2;
            /* supaya height-nya rendah */
            border-radius: 4px;
            /* tetap sedikit membulat */
        }

        .badge.waiting {
            background-color: #ffc107;
            color: #fff;
            border-radius: 8px;
            padding: 5px 10px;
            font-weight: 500;
        }

        .badge.approved {
            background-color: #28a745;
            color: #fff;
            border-radius: 8px;
            padding: 5px 10px;
            font-weight: 500;
        }

        .badge.rejected {
            background-color: #dc3545;
            color: #fff;
            border-radius: 8px;
            padding: 5px 10px;
            font-weight: 500;
        }

        .badge-empty {
            display: inline-block;
            background-color: #adb5bd;
            /* abu-abu */
            color: #fff;
            border-radius: 8px;
            padding: 5px 10px;
            font-weight: 500;
            text-align: center;
            cursor: default;
            pointer-events: none;
            opacity: 0.85;
            min-width: 60px;
            /* supaya tetap rata tengah walau cuma strip */
        }


        /* Export Button Styling */
        .btn-danger,
        .btn-success {
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-success:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Prevent focus on background elements when SweetAlert is open */
        .swal2-shown .wrapper,
        .swal2-shown body> :not(.swal2-container) {
            pointer-events: none;
            user-select: none;
        }

        .swal2-container {
            pointer-events: none;
        }

        .swal2-popup {
            pointer-events: all;
        }

        /* Ensure buttons in background are not focusable */
        .swal2-shown button:not(.swal2-popup button),
        .swal2-shown input:not(.swal2-popup input),
        .swal2-shown select:not(.swal2-popup select),
        .swal2-shown textarea:not(.swal2-popup textarea),
        .swal2-shown a:not(.swal2-popup a) {
            pointer-events: none;
            opacity: 0.5;
        }
    </style>

</head>

<body>

    <div class="wrapper">

        <div class="modal fade" id="Modalkalender" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header no-bd">
                        <h5 class="modal-title">
                            <span class="fw-mediumbold">
                                Calendar</span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="calendar"></div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-primary btn-block" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-header">
            <!-- Logo Header -->
            <div class="logo-header" data-background-color="blue2">
                <a href="#" class="logo">
                    <img src="./assets/img/my_internship_logo_grey5.png" alt="navbar brand" class="navbar-brand" style="width: 180px; height: auto;">
                </a>
                <button class="navbar-toggler sidenav-toggler ml-auto" type="button" data-toggle="collapse" data-target="collapse" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon">
                        <i class="icon-menu"></i>
                    </span>
                </button>
                <button class="topbar-toggler more"><i class="icon-options-vertical"></i></button>
                <div class="nav-toggle">
                    <button class="btn btn-toggle toggle-sidebar">
                        <i class="icon-menu"></i>
                    </button>
                </div>
            </div>
            <!-- End Logo Header -->

            <!-- Navbar Header -->
            <nav class="navbar navbar-header navbar-expand-lg" data-background-color="blue">
                <div class="container-fluid">
                    <div class="collapse" id="search-nav">
                        <ul class="navbar-nav navbar-left topbar-nav nav-search mr-md-3 align-items-center">

                            <!-- Tanggal -->
                            <li class="nav-item dropdown hidden-caret">
                                <a aria-label="Current Date and Calendar" class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" aria-expanded="false">
                                    <span id="date">Wed, 08 Oct 2025</span>
                                </a>
                                <ul class="float-right dropdown-menu dropdown-calendar dropdown-user animated fadeIn">
                                    <div class="dropdown-user-scroll scrollbar-outer">
                                        <div class="card-body text-center text-accent-1">
                                            <h3>Wed, 08 Oct 2025M</h3>
                                        </div>
                                    </div>
                                </ul>
                            </li>

                            <!-- Jam -->
                            <li class="nav-item dropdown hidden-caret">
                                <a aria-label="Current Time" class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" aria-expanded="false">
                                    <span id="clock">22 : 12 : 24</span>
                                </a>
                                <ul class="float-right dropdown-menu dropdown-calendar dropdown-user animated fadeIn">
                                    <div class="dropdown-user-scroll scrollbar-outer">
                                        <div class="card-body text-center text-accent-1 ">
                                            <h3>Jakarta, Indonesia</h3>
                                            <h1>
                                                <span id="clock2">22 : 12 : 24</span>
                                            </h1>
                                        </div>
                                    </div>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <ul class="navbar-nav topbar-nav ml-md-auto align-items-center">
                        <li class="nav-item toggle-nav-search hidden-caret">
                            <a class="nav-link" data-toggle="collapse" href="#search-nav" role="button" aria-expanded="false" aria-controls="search-nav">
                                <i class="fa fa-clock"></i>
                            </a>
                        </li>
                        <li class="nav-item dropdown hidden-caret">
                            <a class="nav-link" href="#" role="button" data-toggle="modal" data-target="#Modalkalender" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-calendar"></i>
                            </a>
                        </li>

                        <!-- Notification -->
                        <li class="nav-item dropdown hidden-caret" id="notification">
                            <a class="nav-link dropdown-toggle" href="#" id="notifDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-bell"></i>
                                <span id="count_notification"></span>
                            </a>
                            <ul class='dropdown-menu messages-notif-box animated fadeIn' aria-labelledby='notifDropdown' id=''>
                                <li>
                                    <div class='dropdown-title'>New Notification</div>
                                </li>
                                <li>
                                    <div class='dropdown-title'>You don't have new notification</div>
                                </li>
                            </ul>
                        </li>

                        <!-- Profil -->
                        <li class="nav-item dropdown hidden-caret">
                            <a class="dropdown-toggle profile-pic" data-toggle="dropdown" href="#" aria-expanded="false">
                                <div class="avatar-sm">
                                    <img src="assets/img/profile.png" alt="..." class="avatar-img rounded-circle">
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-user animated fadeIn">
                                <div class="dropdown-user-scroll scrollbar-outer">
                                    <li>
                                        <div class="user-box">
                                            <div class="avatar-lg"><img src="./assets/img/profile.png" alt="image profile" class="avatar-img rounded"></div>
                                            <div class="u-text">
                                                <h5><?php echo htmlspecialchars($user['name']); ?></h5>
                                                <p class="text-muted"> CDC at :<br><?= htmlspecialchars($nama_kampus) ?></p>
                                                <a href="#" class="btn btn-xs btn-secondary btn-sm">View Profile</a>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="cdc_dashboard.php">My Dashboard</a>
                                        <a class="dropdown-item" href="#">My Profile</a>
                                        <a class="dropdown-item" href="#">My Company</a>
                                        <!-- <a class="dropdown-item" href="#">Inbox</a> -->
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#">Home</a>
                                        <a class="dropdown-item" href="#">Announcements</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#" onclick="logout_confirm()">Logout</a>
                                    </li>
                                </div>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
            <!-- End Navbar -->
        </div>

        <!-- Sidebar -->
        <div class="sidebar sidebar-style-2">
            <div class="sidebar-wrapper scrollbar scrollbar-inner">
                <div class="sidebar-content">
                    <div class="user">
                        <div class="avatar-sm float-left mr-2">
                            <img src="./assets/img/profile.png" alt="..." class="avatar-img rounded-circle">
                        </div>
                        <div class="info">
                            <a data-toggle="collapse" href="#collapseExample" aria-expanded="true">
                                <span>
                                    <span class="wrap2"><?php echo htmlspecialchars($user['name']); ?></span>
                                    <span class="user-level wrap2">CDC at : <br><?php echo htmlspecialchars($nama_kampus); ?></span>
                                </span>
                            </a>
                            <div class="clearfix"></div>
                        </div>
                    </div>
                    <ul class="nav nav-primary">
                        <li class="nav-item">
                            <a href="dashboard_cdc.php" class="collapsed" aria-expanded="false">
                                <i class="fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item ">
                            <a href="index.php?page=student_offer" class="collapsed" aria-expanded="false">
                                <i class="fas fa-clipboard-list"></i>
                                <p>Home</p>
                            </a>
                        </li>
                        <li class="nav-section">
                            <span class="sidebar-mini-icon">
                                <i class="fa fa-ellipsis-h"></i>
                            </span>
                            <h4 class="text-section">Lecturer Menu</h4>
                        </li>
                        <li class="nav-item active">
                            <a href="approval_of_letter.php" class="collapsed" aria-expanded="false">
                                <i class="fas fa-briefcase"></i>
                                <p>Approval Of Letter</p>
                            </a>
                        </li>
                        <li class="nav-section">
                            <span class="sidebar-mini-icon">
                                <i class="fa fa-ellipsis-h"></i>
                            </span>
                            <h4 class="text-section">Account</h4>
                        </li>
                        <li class="nav-item">
                            <a href="https://wa.me/6281364440803" target="_blank" class="collapsed"
                                aria-expanded="false">
                                <i class="fas fa-question"></i>
                                <p>Helpdesk</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" onclick="logout_confirm()" class="collapsed" aria-expanded="false">
                                <i class="fas fa-sign-out-alt"></i>
                                <p>Logout</p>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- End Sidebar -->

        <div class="main-panel">
            <!-- Approval Status Header - FULL WIDTH -->
            <div class="panel-header bg-primary-gradient">
                <div class="page-inner py-5">
                    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row">
                        <div>
                            <h1 class="text-white pb-2 fw-bold">Approval Status</h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Container -->
            <div class="page-inner mt--5">
                <div class="row mt--2">
                    <!-- Filter Section -->
                    <div class="col-sm-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Filter</div>
                            </div>
                            <div class="card-body">
                                <form id="filterForm" method="GET" action="">
                                    <div class="row align-items-end">
                                        <!-- Filter By Study Program -->
                                        <div class="col-md mb-3">
                                            <label for="filter_study_program" class="form-label">Filter By Study Program</label>
                                            <select class="form-control" id="filter_study_program" name="study_program" onchange="applyFilter()">
                                                <option value="">Select Study Program</option>
                                            </select>
                                        </div>

                                        <!-- Filter By Student Name -->
                                        <div class="col-md mb-3">
                                            <label for="filter_student_name" class="form-label">Filter by Student Name</label>
                                            <input type="text" class="form-control" id="filter_student_name" name="student_name" placeholder="Enter Student Name" onkeyup="applyFilter()">
                                        </div>

                                        <!-- Filter By Approval Coordinator -->
                                        <div class="col-md mb-3">
                                            <label for="filter_coordinator" class="form-label">Filter by Approval Coordinator</label>
                                            <select class="form-control" id="filter_coordinator" name="coordinator" onchange="applyFilter()">
                                                <option value="">ALL</option>
                                                <option value="approved">Approved</option>
                                                <option value="waiting">Waiting</option>
                                                <option value="rejected">Rejected</option>
                                            </select>
                                        </div>

                                        <!-- Filter By Approval CDC -->
                                        <div class="col-md mb-3">
                                            <label for="filter_cdc" class="form-label">Filter by Approval CDC</label>
                                            <select class="form-control" id="filter_cdc" name="cdc" onchange="applyFilter()">
                                                <option value="">ALL</option>
                                                <option value="approve">Approve</option>
                                                <option value="waiting">Waiting</option>
                                                <option value="reject">Reject</option>
                                            </select>
                                        </div>

                                        <!-- Filter By Result Company -->
                                        <div class="col-md mb-3">
                                            <label for="filter_company" class="form-label">Filter by Result Company</label>
                                            <select class="form-control" id="filter_company" name="company" onchange="applyFilter()">
                                                <option value="">ALL</option>
                                                <option value="accepted">Accepted</option>
                                                <option value="rejected">Rejected</option>
                                            </select>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Export Section - BARU -->
                    <div class="col-sm-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Export Internship Data</div>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-end">
                                    <!-- Filter Tahun -->
                                    <div class="col-md-3 mb-3">
                                        <label for="export_year" class="form-label">
                                            <i class="fas fa-calendar-alt"></i> Select Year
                                        </label>
                                        <select class="form-control" id="export_year">
                                            <option value="2023">2023</option>
                                            <option value="2024">2024</option>
                                            <option value="2025" selected>2025</option>
                                            <option value="2026">2026</option>
                                            <option value="2027">2027</option>
                                        </select>
                                    </div>

                                    <!-- Button Export PDF -->
                                    <div class="col-md-2 mb-3">
                                        <button class="btn btn-danger btn-block" onclick="exportToPDF()">
                                            <i class="fas fa-file-pdf"></i> Export PDF
                                        </button>
                                    </div>

                                    <!-- Button Export Excel -->
                                    <div class="col-md-2 mb-3">
                                        <button class="btn btn-success btn-block" onclick="exportToExcel()">
                                            <i class="fas fa-file-excel"></i> Export Excel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table Section -->
                    <div class="col-md-12">
                        <div class="card full-height">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" id="approvalTable">
                                        <thead>
                                            <tr class="text-center">
                                                <th style="width: 50px;">No</th>
                                                <th style="width: 120px; cursor: pointer;" onclick="sortTable()">
                                                    Date
                                                    <i id="sortIcon" class="fas fa-sort"></i>
                                                </th>
                                                <th style="width: 120px;">NIM</th>
                                                <th style="width: 270px;">Name</th>
                                                <th style="width: 150px;">Approval Coordinator</th>
                                                <th style="width: 150px;">Approval CDC</th>
                                                <th style="width: 150px;">Result</th>
                                                <th style="width: 180px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tableBody">
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Pagination -->
                                <div class="mt-3">
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination justify-content-center">
                                            <li class="page-item disabled">
                                                <a class="page-link" href="#" tabindex="-1">Previous</a>
                                            </li>
                                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                            <li class="page-item">
                                                <a class="page-link" href="#">Next</a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="footer">
                <div class="container">
                    <nav class="pull-left">
                    </nav>
                    <div class="copyright ml-auto">
                        © 2025, made with <i class="fa fa-heart heart text-danger"></i> by <a href="https://github.com/nelifauziyah88/myinternship-development">PBLIFPagi3A-3</a>
                    </div>
                </div>
            </footer>
        </div>

        <!--   Core JS Files   -->
        <script src="./assets/js/core/popper.min.js"></script>
        <script src="./assets/js/core/bootstrap.min.js"></script>
        <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/xlsx-js-style/dist/xlsx.min.js"></script>

        <script>
            // ============================================
            // KONFIGURASI & INISIALISASI
            // ============================================

            // Data user CDC dari PHP session
            const currentUserId = <?= json_encode($user['id_upkpk'] ?? "-") ?>;
            const currentUserName = <?= json_encode($user['name'] ?? "-") ?>;
            const cdcKampusId = "<?php echo $id_kampus; ?>";

            // Base URL untuk API
            const apiBase = "http://localhost:8000/api";

            let allSubmissions = [];
            let sortAscending = true;


            // ============================================
            // EVENT LISTENER - LOAD AWAL
            // ============================================

            document.addEventListener("DOMContentLoaded", function() {
                loadStudyPrograms(); // Load dropdown study program untuk filter
                loadSubmissions(); // Load data submissions default (tanpa filter)
            });


            // ============================================
            // FUNGSI UTAMA - LOAD DATA SUBMISSIONS
            // ============================================

            /**
             * Load submissions dengan atau tanpa filter
             * Fungsi ini menggabungkan loadSubmissions() dan loadSubmissionsWithFilter()
             * 
             * @param {boolean} useFilter - true jika menggunakan filter, false untuk load semua data
             */
            async function loadSubmissions(useFilter = false) {
                const body = document.getElementById("tableBody");
                body.innerHTML = "<tr><td colspan='8' class='text-center'>Loading...</td></tr>";

                try {
                    let apiUrl = `${apiBase}/cdc/submissions`;

                    // Jika menggunakan filter, build query params
                    if (useFilter) {
                        const studyProgram = document.getElementById("filter_study_program").value;
                        const studentName = document.getElementById("filter_student_name").value;
                        const coordinator = document.getElementById("filter_coordinator").value;
                        const cdcFilter = document.getElementById("filter_cdc").value;
                        const company = document.getElementById("filter_company").value;

                        let queryParams = new URLSearchParams();
                        queryParams.append('id_kampus', cdcKampusId);

                        if (studyProgram) queryParams.append('study_program', studyProgram);
                        if (studentName) queryParams.append('student_name', studentName);
                        if (coordinator) queryParams.append('coordinator', coordinator);
                        if (cdcFilter) queryParams.append('cdc', cdcFilter);
                        if (company) queryParams.append('company', company);

                        apiUrl = `${apiBase}/cdc/submissions-filtered?${queryParams.toString()}`;
                    }

                    // Fetch data dari API
                    const res = await fetch(apiUrl);
                    const json = await res.json();

                    // Simpan data ke variabel global
                    allSubmissions = json.data || [];

                    if (!json.success || !allSubmissions.length) {
                        body.innerHTML = "<tr><td colspan='8' class='text-center text-muted'>No data found.</td></tr>";
                        return;
                    }

                    // Render table
                    renderTable(allSubmissions);

                    // Validasi response
                    if (!json.success || !json.data.length) {
                        body.innerHTML = "<tr><td colspan='8' class='text-center text-muted'>No data found.</td></tr>";
                        return;
                    }

                    // Render table rows
                    body.innerHTML = "";
                    json.data.forEach((item, i) => {
                        body.innerHTML += buildTableRow(item, i);
                    });

                } catch (err) {
                    console.error("Error loading submissions:", err);
                    body.innerHTML = "<tr><td colspan='8' class='text-danger text-center'>Error loading data.</td></tr>";
                }
            }

            // ============================================
            // HELPER BARU
            // ============================================
            function renderTable(data) {
                const body = document.getElementById("tableBody");
                body.innerHTML = "";
                data.forEach((item, i) => {
                    body.innerHTML += buildTableRow(item, i);
                });
            }


            // ============================================
            // FUNGSI HELPER - BUILD TABLE ROW
            // ============================================

            /**
             * Build single table row untuk submission
             * 
             * @param {Object} item - Data submission dari API
             * @param {number} index - Index row untuk numbering
             * @returns {string} HTML string untuk table row
             */
            function buildTableRow(item, index) {
                const date = formatDate(item.created_at);
                const koorHtml = buildKoordinatorBadge(item);
                const cdcHtml = buildCDCApprovalHtml(item);
                const resultHtml = buildResultBadge(item);

                return `
        <tr>
            <td class="text-center">${index + 1}</td>
            <td class="text-center">${date}</td>
            <td class="text-center">${item.nim}</td>
            <td>${item.student_name}</td>
            <td class="text-center">${koorHtml}</td>
            <td class="text-center">${cdcHtml}</td>
            <td class="text-center">${resultHtml}</td>
            <td>
                <button class="btn btn-info btn-sm" onclick="viewDetail(${item.id_letter})">
                    <i class="fa fa-eye"></i> Detail Submission
                </button>
            </td>
        </tr>
    `;
            }


            // ============================================
            // FUNGSI HELPER - BUILD BADGE STATUS
            // ============================================

            /**
             * Build badge untuk status approval koordinator
             * 
             * @param {Object} item - Data submission
             * @returns {string} HTML string untuk badge koordinator
             */
            function buildKoordinatorBadge(item) {
                const updatedDate = formatDate(item.updated_at);

                switch (item.koor_approval) {
                    case "WAITING":
                        return `<span class='badge waiting'>Waiting</span>`;

                    case "ACCEPTED":
                        return `
                <div class="text-center">
                    <span class='badge approved'>Approved</span>
                    <div class="text-muted" style="font-size:12px;margin-top:2px;">${updatedDate}</div>
                </div>
            `;

                    case "REJECTED":
                        return `
                <div class="text-center">
                    <span class='badge rejected'>Rejected</span>
                    <div class="text-muted" style="font-size:12px;margin-top:2px;">${updatedDate}</div>
                </div>
            `;

                    default:
                        return "-";
                }
            }

            /**
             * Build HTML untuk approval CDC
             * Logic: 
             * - Jika koordinator REJECTED → CDC otomatis REJECTED (tanpa tanggal & tanpa reason)
             * - Jika koordinator WAITING → CDC belum bisa action (tampil -)
             * - Jika koordinator ACCEPTED & CDC WAITING → tampil dropdown action
             * - Jika CDC sudah ACCEPTED/REJECTED → tampil badge dengan tanggal
             * 
             * @param {Object} item - Data submission
             * @returns {string} HTML string untuk CDC approval
             */
            function buildCDCApprovalHtml(item) {
                const updatedDate = formatDate(item.updated_at);

                // Case 1: Koordinator REJECTED → CDC otomatis REJECTED
                if (item.koor_approval === "REJECTED") {
                    return `
            <div class="text-center">
                <span class="badge rejected">Rejected</span>
                <div class="text-muted" style="font-size:12px;margin-top:2px;">-</div>
            </div>
        `;
                }

                // Case 2: Koordinator masih WAITING → CDC belum bisa action
                if (item.koor_approval === "WAITING") {
                    return `-`;
                }

                // Case 3: Koordinator ACCEPTED & CDC WAITING → tampil dropdown action
                if (item.cdc_approval === "WAITING" && item.koor_approval === "ACCEPTED") {
                    return `
            <div class="dropdown text-center">
                <button class="btn btn-warning btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
                    Waiting
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item text-success" href="#" onclick="handleApproval(${item.id_letter}, 'ACCEPTED')">
                        <i class="fas fa-check text-success"></i> Approve
                    </a>
                    <a class="dropdown-item text-danger" href="#" onclick="handleApproval(${item.id_letter}, 'REJECTED')">
                        <i class="fas fa-times text-danger"></i> Reject
                    </a>
                </div>
            </div>
        `;
                }

                // Case 4: CDC sudah ACCEPTED
                if (item.cdc_approval === "ACCEPTED") {
                    return `
            <div class="text-center">
                <span class="badge approved">Approved</span>
                <div class="text-muted" style="font-size:12px;margin-top:2px;">${updatedDate}</div>
            </div>
        `;
                }

                // Case 5: CDC sudah REJECTED (tampil badge + button show reason)
                if (item.cdc_approval === "REJECTED") {
                    return `
            <div class="text-center">
                <span class="badge rejected">Rejected</span>
                <div class="text-muted" style="font-size:12px;margin-top:2px;">${updatedDate}</div>
                <button class="btn btn-sm btn-light mt-1" onclick="viewReason(${item.id_letter})" title="Show reason">
                    <i class="fas fa-comment"></i> Show reason
                </button>
            </div>
        `;
                }

                // Default
                return `-`;
            }

            /**
             * Build badge untuk result company dengan button view reply
             * 
             * @param {Object} item - Data submission lengkap
             * @returns {string} HTML string untuk badge result
             */
            function buildResultBadge(item) {
                const acceptance = item.acceptance_status;

                // Jika belum ada acceptance status dari company
                if (!acceptance || acceptance === '-') {
                    return `<span class="badge-empty">-</span>`;
                }

                // Jika ACCEPTED
                if (acceptance === 'ACCEPTED') {
                    return `
            <div class="text-center">
                <span class="badge approved">Accepted</span>
                <button class="btn btn-sm btn-info mt-1" onclick="viewCompanyReply(${item.id_letter})">
                    <i class="fas fa-file-alt"></i> View Reply
                </button>
            </div>
        `;
                }

                // Jika REJECTED
                if (acceptance === 'REJECTED') {
                    return `
            <div class="text-center">
                <span class="badge rejected">Rejected</span>
                <button class="btn btn-sm btn-info mt-1" onclick="viewCompanyReply(${item.id_letter})">
                    <i class="fas fa-file-alt"></i> View Reply
                </button>
            </div>
        `;
                }

                return `<span class="badge-empty">-</span>`;
            }

            /**
             * Sort table by date
             * Toggle between ascending and descending
             */
            function sortTable() {
                sortAscending = !sortAscending;

                const sortIcon = document.getElementById("sortIcon");
                if (sortAscending) {
                    sortIcon.className = "fas fa-sort-up";
                    allSubmissions.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                } else {
                    sortIcon.className = "fas fa-sort-down";
                    allSubmissions.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                }

                renderTable(allSubmissions);
            }

            // ============================================
            // FUNGSI HELPER - FORMAT TANGGAL
            // ============================================

            /**
             * Format timestamp menjadi tanggal DD/MM/YYYY
             * 
             * @param {string} timestamp - Timestamp dari database
             * @returns {string} Formatted date atau "-" jika null
             */
            function formatDate(timestamp) {
                if (!timestamp) return "-";
                return new Date(timestamp).toLocaleDateString("en-GB");
            }


            // ============================================
            // FUNGSI APPROVAL - HANDLE USER ACTION
            // ============================================

            /**
             * Handle approval action (Approve/Reject)
             * Untuk REJECTED, akan muncul textarea untuk input alasan
             * Untuk ACCEPTED, langsung konfirmasi
             * 
             * @param {number} id - ID letter submission
             * @param {string} status - Status approval (ACCEPTED/REJECTED)
             */
            async function handleApproval(id, status) {
                let comment = null;

                // Jika reject, minta alasan dulu
                if (status === "REJECTED") {
                    const {
                        value: reason,
                        isConfirmed
                    } = await Swal.fire({
                        title: "Why are you rejecting?",
                        text: "Please provide your reason for rejecting this submission.",
                        input: "textarea",
                        inputPlaceholder: "Write the reason here...",
                        inputAttributes: {
                            'aria-label': 'Reason for rejection'
                        },
                        showCancelButton: true,
                        cancelButtonText: "Cancel",
                        confirmButtonText: "Submit",
                        preConfirm: (value) => {
                            if (!value || !value.trim()) {
                                Swal.showValidationMessage("Reason is required.");
                                return false;
                            }
                            return value.trim();
                        }
                    });

                    if (!isConfirmed) return;
                    comment = reason;
                }

                // Konfirmasi action
                const confirm = await Swal.fire({
                    title: "Confirm?",
                    text: `You are about to mark this submission as ${status}`,
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, confirm"
                });

                if (!confirm.isConfirmed) return;

                // Kirim ke API
                await sendApprovalToAPI(id, status, comment);
            }

            /**
             * Kirim approval ke API
             * 
             * @param {number} id - ID letter submission
             * @param {string} status - Status approval (ACCEPTED/REJECTED)
             * @param {string|null} comment - Alasan reject (optional)
             */
            async function sendApprovalToAPI(id, status, comment = null) {
                try {
                    const res = await fetch(`${apiBase}/cdc/approval`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            id_letter: id,
                            status,
                            user_id: currentUserId,
                            user_name: currentUserName,
                            comment
                        })
                    });

                    const json = await res.json();

                    if (json.success) {
                        Swal.fire("Success!", json.message, "success");
                        loadSubmissions(); // Reload data
                    } else {
                        Swal.fire("Error", json.message, "error");
                    }
                } catch (err) {
                    console.error("Error sending approval:", err);
                    Swal.fire("Error", err.message, "error");
                }
            }


            // ============================================
            // FUNGSI REASON - VIEW & EDIT
            // ============================================

            /**
             * View rejection reason dengan opsi edit
             * 
             * @param {number} id_letter - ID letter submission
             */
            async function viewReason(id_letter) {
                try {
                    const res = await fetch(`${apiBase}/cdc/reason/${id_letter}`);

                    if (!res.ok) {
                        const j = await res.json().catch(() => ({
                            message: 'Unknown error'
                        }));
                        return Swal.fire("Error", j.message || "Reason not found", "error");
                    }

                    const json = await res.json();
                    const reason = json.comment || "-";

                    // Tampilkan reason dengan opsi edit
                    const result = await Swal.fire({
                        title: "Rejection reason",
                        html: `<div style="text-align:left; white-space:pre-wrap;">${escapeHtml(reason)}</div>`,
                        showCancelButton: false,
                        showDenyButton: true,
                        denyButtonText: "Edit",
                        confirmButtonText: "Close"
                    });

                    // Jika user klik Edit
                    if (result.isDenied) {
                        editReason(id_letter, reason);
                    }
                } catch (err) {
                    console.error("Error viewing reason:", err);
                    Swal.fire("Error", err.message, "error");
                }
            }

            /**
             * Edit rejection reason
             * 
             * @param {number} id_letter - ID letter submission
             * @param {string} currentReason - Current reason text
             */
            async function editReason(id_letter, currentReason) {
                const {
                    value: newReason,
                    isConfirmed
                } = await Swal.fire({
                    title: "Edit rejection reason",
                    input: "textarea",
                    inputValue: currentReason || "",
                    inputPlaceholder: "Write the reason here...",
                    showCancelButton: true,
                    cancelButtonText: "Cancel",
                    confirmButtonText: "Save",
                    preConfirm: (value) => {
                        if (!value || !value.trim()) {
                            Swal.showValidationMessage("Reason is required.");
                            return false;
                        }
                        return value.trim();
                    }
                });

                if (!isConfirmed) return;

                // Kirim update ke API
                try {
                    const res = await fetch(`${apiBase}/cdc/history/${id_letter}/edit`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            comment: newReason
                        })
                    });

                    const json = await res.json();

                    if (json.success) {
                        Swal.fire("Success", json.message, "success");
                        loadSubmissions(); // Reload data
                    } else {
                        Swal.fire("Error", json.message, "error");
                    }
                } catch (err) {
                    console.error("Error editing reason:", err);
                    Swal.fire("Error", err.message, "error");
                }
            }

            /**
             * Escape HTML untuk prevent XSS injection di SweetAlert
             * 
             * @param {string} str - String yang akan di-escape
             * @returns {string} Escaped string
             */
            function escapeHtml(str) {
                if (!str) return "";
                return String(str)
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }


            // ============================================
            // FUNGSI FILTER & STUDY PROGRAM
            // ============================================

            /**
             * Load study programs untuk dropdown filter
             * Hanya load program studi yang sesuai dengan kampus CDC
             */
            async function loadStudyPrograms() {
                if (!cdcKampusId) {
                    console.error("CDC Kampus ID tidak tersedia");
                    return;
                }

                try {
                    const res = await fetch(`${apiBase}/cdc/study-programs/${cdcKampusId}`);
                    const json = await res.json();

                    if (json.success && json.data.length > 0) {
                        const select = document.getElementById("filter_study_program");
                        select.innerHTML = '<option value="">All Study Programs</option>';

                        json.data.forEach(item => {
                            const option = document.createElement("option");
                            option.value = item.kode_prodi;
                            option.textContent = `${item.kode_prodi} - ${item.program_name}`;
                            select.appendChild(option);
                        });
                    }
                } catch (err) {
                    console.error("Error loading study programs:", err);
                }
            }

            /**
             * Apply filter - dipanggil saat user mengubah filter
             * Fungsi ini akan reload submissions dengan parameter filter
             */
            function applyFilter() {
                loadSubmissions(true); // true = gunakan filter
            }


            // ============================================
            // FUNGSI NAVIGASI & UTILITY
            // ============================================

            /**
             * View detail submission - redirect ke halaman detail
             * 
             * @param {number} id - ID letter submission
             */
            function viewDetail(id) {
                console.log("Opening submission detail for:", id);
                window.location.href = `detail_submissions_cdc.php?id=${id}`;
            }

            /**
             * Logout confirmation & redirect
             * Menghapus session PHP dan localStorage
             */
            function logout_confirm() {
                let _token = $('meta[name="csrf-token"]').attr('content');

                Swal.fire({
                    title: 'Logout from your account ?',
                    text: 'Are you sure you want to end the current session?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Yes, I'm sure!",
                    cancelButtonText: "Cancel"
                }).then((result) => {
                    if (result.isConfirmed) {
                        // AJAX logout ke PHP
                        $.ajax({
                            url: "session_logout.php",
                            type: "POST",
                            data: {
                                'token': _token
                            },
                            success: function() {
                                setTimeout(function() {
                                    localStorage.removeItem('first');
                                    localStorage.removeItem('first_chime');
                                    localStorage.removeItem('next_chime');
                                    window.location.href = 'role_login.php';
                                }, 200);
                            },
                            error: function() {
                                Swal.fire('Error', 'Logout failed, please try again.', 'error');
                            }
                        });
                    }
                });
            }

            // ============================================================
            // FUNGSI VIEW COMPANY REPLY - ENHANCED VERSION
            // ============================================================

            /**
             * View company reply - tampilkan file dengan informasi lengkap
             * 
             * @param {number} id_letter - ID letter submission
             */
            async function viewCompanyReply(id_letter) {
                try {
                    // Fetch company reply data dari API
                    const res = await fetch(`${apiBase}/lecturer/company-reply/${id_letter}`);

                    if (!res.ok) {
                        const json = await res.json().catch(() => ({
                            message: 'Unknown error'
                        }));
                        return Swal.fire("Error", json.message || "Failed to load company reply", "error");
                    }

                    const json = await res.json();
                    const data = json.data;

                    // Case 1: Ada file upload
                    if (data.company_reply_letter && data.company_reply_letter !== '-') {
                        const fileName = data.company_reply_letter;
                        const fileUrl = `./${data.company_reply_letter}`;

                        // Tentukan apakah PDF atau gambar
                        const isPDF = fileName.toLowerCase().endsWith('.pdf');

                        // Badge styling berdasarkan status
                        let statusBadge = '';
                        if (data.acceptance_status === 'ACCEPTED') {
                            statusBadge = '<span class="badge approved">Accepted</span>';
                        } else if (data.acceptance_status === 'REJECTED') {
                            statusBadge = '<span class="badge rejected">Rejected</span>';
                        } else {
                            statusBadge = '<span class="badge waiting">Waiting</span>';
                        }

                        // Format reply date dari updated_at (ketika mahasiswa upload file)
                        let replyDateFormatted = '-';
                        if (data.updated_at) {
                            const date = new Date(data.updated_at);
                            replyDateFormatted = date.toLocaleDateString('en-GB', {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric'
                            });
                        }

                        // Informasi mahasiswa dan perusahaan
                        const infoSection = `
                <div style="background: #ffffff; padding: 25px; border-radius: 10px; margin-bottom: 20px; border: 2px solid #e3e6f0; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e3e6f0;">
                        <h4 style="margin: 0; color: #5a5c69; font-weight: 600;">
                            <i class="fas fa-file-contract" style="color: #4e73df;"></i> Company Reply Letter
                        </h4>
                        ${statusBadge}
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        <div>
                            <p style="margin: 0 0 8px 0; color: #858796; font-weight: 500; font-size: 13px;">
                                <i class="fas fa-user-graduate" style="color: #4e73df; width: 18px;"></i> Student Name:
                            </p>
                            <p style="margin: 0 0 0 26px; font-size: 15px; color: #5a5c69; font-weight: 500;">${data.student_name || '-'}</p>
                        </div>
                        <div>
                            <p style="margin: 0 0 8px 0; color: #858796; font-weight: 500; font-size: 13px;">
                                <i class="fas fa-id-card" style="color: #4e73df; width: 18px;"></i> NIM:
                            </p>
                            <p style="margin: 0 0 0 26px; font-size: 15px; color: #5a5c69; font-weight: 500;">${data.nim || '-'}</p>
                        </div>
                        <div>
                            <p style="margin: 0 0 8px 0; color: #858796; font-weight: 500; font-size: 13px;">
                                <i class="fas fa-building" style="color: #4e73df; width: 18px;"></i> Company Name:
                            </p>
                            <p style="margin: 0 0 0 26px; font-size: 15px; color: #5a5c69; font-weight: 500;">${data.company_name || '-'}</p>
                        </div>
                        <div>
                            <p style="margin: 0 0 8px 0; color: #858796; font-weight: 500; font-size: 13px;">
                                <i class="fas fa-calendar-alt" style="color: #4e73df; width: 18px;"></i> Upload Date:
                            </p>
                            <p style="margin: 0 0 0 26px; font-size: 15px; color: #5a5c69; font-weight: 500;">${replyDateFormatted}</p>
                        </div>
                    </div>
                </div>
            `;

                        let modalContent = '';

                        if (isPDF) {
                            // PDF Preview dengan embed
                            modalContent = `
                    ${infoSection}
                    <div style="border: 2px solid #e0e0e0; border-radius: 8px; overflow: hidden; margin-bottom: 20px;">
                        <div style="background: #f5f5f5; padding: 10px; border-bottom: 2px solid #e0e0e0;">
                            <i class="fas fa-file-pdf text-danger"></i> 
                            <strong>${fileName.split('/').pop()}</strong>
                        </div>
                        <div style="width: 100%; height: 500px;">
                            <embed src="${fileUrl}" type="application/pdf" width="100%" height="100%" />
                        </div>
                    </div>
                    <div class="text-center" style="display: flex; gap: 10px; justify-content: center;">
                        <button onclick="window.open('${fileUrl}', '_blank')" class="btn btn-info">
                            <i class="fas fa-external-link-alt"></i> Open in New Tab
                        </button>
                        <a href="${fileUrl}" download="${fileName.split('/').pop()}" class="btn btn-primary">
                            <i class="fas fa-download"></i> Download File
                        </a>
                    </div>
                `;
                        } else {
                            // Image Preview
                            modalContent = `
                    ${infoSection}
                    <div style="border: 2px solid #e0e0e0; border-radius: 8px; overflow: hidden; margin-bottom: 20px;">
                        <div style="background: #f5f5f5; padding: 10px; border-bottom: 2px solid #e0e0e0;">
                            <i class="fas fa-image text-primary"></i> 
                            <strong>${fileName.split('/').pop()}</strong>
                        </div>
                        <div style="width: 100%; padding: 20px; background: #fafafa;">
                            <img src="${fileUrl}" alt="Company Reply" style="max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);" />
                        </div>
                    </div>
                    <div class="text-center" style="display: flex; gap: 10px; justify-content: center;">
                        <button onclick="window.open('${fileUrl}', '_blank')" class="btn btn-info">
                            <i class="fas fa-external-link-alt"></i> Open in New Tab
                        </button>
                        <a href="${fileUrl}" download="${fileName.split('/').pop()}" class="btn btn-primary">
                            <i class="fas fa-download"></i> Download File
                        </a>
                    </div>
                `;
                        }

                        // Tampilkan modal dengan file
                        Swal.fire({
                            title: '', // Kosongkan karena sudah ada info di dalam content
                            html: modalContent,
                            width: '900px',
                            showConfirmButton: true,
                            confirmButtonText: 'Close',
                            customClass: {
                                popup: 'animated fadeIn'
                            }
                        });
                    }
                    // Case 2: REJECTED tanpa file (overdue)
                    else if (data.acceptance_status === 'REJECTED' && data.isOverdue) {
                        Swal.fire({
                            title: "Company Reply - REJECTED",
                            html: `
                    <div style="background: #fff3cd; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107; margin: 15px 0;">
                        <div style="text-align: left; margin-bottom: 15px;">
                            <p style="margin: 5px 0;"><strong><i class="fas fa-user-graduate"></i> Student:</strong> ${data.student_name || '-'}</p>
                            <p style="margin: 5px 0;"><strong><i class="fas fa-id-card"></i> NIM:</strong> ${data.nim || '-'}</p>
                            <p style="margin: 5px 0;"><strong><i class="fas fa-building"></i> Company:</strong> ${data.company_name || '-'}</p>
                        </div>
                        <hr style="border-color: #ffc107;">
                        <div style="text-align: left;">
                            <strong><i class="fas fa-exclamation-triangle text-warning"></i> Reason:</strong>
                            <p style="margin-top: 10px; line-height: 1.6; color: #856404;">${data.reason}</p>
                        </div>
                    </div>
                `,
                            icon: 'warning',
                            confirmButtonText: 'Close'
                        });
                    }
                    // Case 3: Tidak ada data sama sekali
                    else {
                        Swal.fire({
                            title: "No Reply File Yet",
                            html: `
                    <div style="text-align: left; padding: 20px; background: #f8f9fa; border-radius: 8px; margin: 15px 0;">
                        <p style="margin: 5px 0;"><strong><i class="fas fa-user-graduate"></i> Student:</strong> ${data.student_name || '-'}</p>
                        <p style="margin: 5px 0;"><strong><i class="fas fa-id-card"></i> NIM:</strong> ${data.nim || '-'}</p>
                        <p style="margin: 5px 0;"><strong><i class="fas fa-building"></i> Company:</strong> ${data.company_name || '-'}</p>
                        <hr>
                        <p style="color: #6c757d; margin-top: 15px;">
                            <i class="fas fa-info-circle"></i> The student has not received a reply letter within 14 days after the internship interview.
                        </p>
                    </div>
                `,
                            icon: 'info',
                            confirmButtonText: 'Close'
                        });
                    }

                } catch (err) {
                    console.error("Error viewing company reply:", err);
                    Swal.fire("Error", "Failed to load company reply: " + err.message, "error");
                }
            }

            // ============================================================
            // FUNGSI EXPORT DATA MAGANG - CDC VERSION
            // ============================================================

            /**
             * Fetch data magang dari API dengan filter yang sama seperti tabel
             */
            async function fetchInternshipData() {
                const year = document.getElementById("export_year").value;
                const studyProgram = document.getElementById("filter_study_program").value; // Ambil dari filter

                try {
                    // Build URL dengan query params
                    let url = `${apiBase}/cdc/export-internship?year=${year}`;

                    // Jika ada filter prodi, tambahkan ke URL
                    if (studyProgram && studyProgram.trim() !== '') {
                        url += `&study_program=${encodeURIComponent(studyProgram)}`;
                    }

                    const res = await fetch(url);
                    const json = await res.json();

                    if (!json.success) {
                        Swal.fire({
                            icon: "info",
                            title: "No Data Found",
                            text: json.message || `No internship data found`,
                            confirmButtonText: "OK"
                        });
                        return null;
                    }

                    if (!json.data || json.data.length === 0) {
                        Swal.fire({
                            icon: "info",
                            title: "No Data Found",
                            text: `No active internship students found`,
                            confirmButtonText: "OK"
                        });
                        return null;
                    }

                    console.log(`✅ Export data loaded: ${json.count} students`);
                    console.log(`📊 Filter: ${json.filter}`);
                    return json.data;
                } catch (err) {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Failed to fetch data: " + err.message,
                        confirmButtonText: "OK"
                    });
                    return null;
                }
            }

            /**
             * Format tanggal ke DD/MM/YYYY
             */
            function formatDate(dateString) {
                if (!dateString) return "-";
                const date = new Date(dateString);
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                return `${day}/${month}/${year}`;
            }

            /**
             * Export to PDF
             */
            async function exportToPDF() {
                const year = document.getElementById("export_year").value;
                const studyProgram = document.getElementById("filter_study_program").value;
                const filterText = studyProgram ? `Program: ${studyProgram}` : 'All Programs';

                Swal.fire({
                    title: 'Generating PDF...',
                    text: 'Please wait',
                    allowOutsideClick: false,
                    onOpen: () => {
                        Swal.showLoading();
                    }
                });

                const data = await fetchInternshipData();

                if (!data) {
                    return; // HENTIKAN! JANGAN TUTUP SWEET ALERT
                }

                Swal.close();

                if (!data || data.length === 0) {
                    return;
                }

                try {
                    const {
                        jsPDF
                    } = window.jspdf;
                    const doc = new jsPDF('landscape', 'mm', 'a4');

                    // Header
                    doc.setFontSize(16);
                    doc.setFont(undefined, 'bold');
                    doc.text('INTERNSHIP DATA REPORT', doc.internal.pageSize.getWidth() / 2, 15, {
                        align: 'center'
                    });

                    doc.setFontSize(12);
                    doc.setFont(undefined, 'normal');
                    doc.text(`Year: ${year}`, doc.internal.pageSize.getWidth() / 2, 22, {
                        align: 'center'
                    });
                    doc.text(`Filter: ${filterText}`, doc.internal.pageSize.getWidth() / 2, 28, {
                        align: 'center'
                    });
                    doc.text(`Total Students: ${data.length}`, doc.internal.pageSize.getWidth() / 2, 34, {
                        align: 'center'
                    });

                    // Table
                    const tableData = data.map((item, index) => [
                        index + 1,
                        item.nim || '-',
                        item.student_name || '-',
                        item.program_study || '-',
                        formatDate(item.start_date),
                        formatDate(item.end_date),
                        item.company_name || '-',
                        item.hrd_name || '-',
                        item.internship_position || '-',
                        item.internship_period || '-'
                    ]);

                    doc.autoTable({
                        startY: 40,
                        head: [
                            ['No', 'NIM', 'Name', 'Study Program', 'Start Date', 'End Date', 'Company', 'HRD', 'Position', 'Period']
                        ],
                        body: tableData,
                        styles: {
                            fontSize: 8,
                            cellPadding: 2,
                            overflow: 'linebreak'
                        },
                        headStyles: {
                            fillColor: [41, 128, 185],
                            textColor: 255,
                            fontStyle: 'bold',
                            halign: 'center'
                        },
                        columnStyles: {
                            0: {
                                cellWidth: 10,
                                halign: 'center'
                            },
                            1: {
                                cellWidth: 20,
                                halign: 'center'
                            },
                            2: {
                                cellWidth: 35
                            },
                            3: {
                                cellWidth: 40
                            },
                            4: {
                                cellWidth: 22,
                                halign: 'center'
                            },
                            5: {
                                cellWidth: 22,
                                halign: 'center'
                            },
                            6: {
                                cellWidth: 35
                            },
                            7: {
                                cellWidth: 30
                            },
                            8: {
                                cellWidth: 30
                            },
                            9: {
                                cellWidth: 20,
                                halign: 'center'
                            }
                        },
                        alternateRowStyles: {
                            fillColor: [245, 245, 245]
                        }
                    });

                    // Footer
                    const pageCount = doc.internal.getNumberOfPages();
                    for (let i = 1; i <= pageCount; i++) {
                        doc.setPage(i);
                        doc.setFontSize(8);
                        doc.text(`Page ${i} of ${pageCount}`, doc.internal.pageSize.getWidth() / 2, doc.internal.pageSize.getHeight() - 10, {
                            align: 'center'
                        });
                        doc.text(`Generated on ${new Date().toLocaleDateString('en-GB')}`, doc.internal.pageSize.getWidth() - 15, doc.internal.pageSize.getHeight() - 10, {
                            align: 'right'
                        });
                    }

                    doc.save(`Data_Magang_${year}_${filterText.replace(/[^a-zA-Z0-9]/g, '_')}.pdf`);

                    Swal.fire({
                        icon: "success",
                        title: "Success",
                        text: "PDF downloaded successfully!",
                        confirmButtonText: "OK"
                    });

                } catch (err) {
                    console.error("PDF generation error:", err);
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Failed to generate PDF: " + err.message,
                        confirmButtonText: "OK"
                    });
                }
            }

            /**
             * Export to Excel - CDC VERSION WITH FULL STYLING
             */
            async function exportToExcel() {
                const year = document.getElementById("export_year").value;
                const studyProgram = document.getElementById("filter_study_program").value;
                const filterText = studyProgram ? `Program: ${studyProgram}` : 'All Programs';

                // UBAH didOpen menjadi onOpen
                Swal.fire({
                    title: 'Generating Excel...',
                    text: 'Please wait',
                    allowOutsideClick: false,
                    onOpen: () => { // ← CHANGED FROM didOpen
                        Swal.showLoading();
                    }
                });

                const data = await fetchInternshipData();

                if (!data) {
                    return; // HENTIKAN! JANGAN TUTUP SWEET ALERT
                }

                Swal.close();

                if (!data || data.length === 0) {
                    return;
                }

                try {
                    const wb = XLSX.utils.book_new();

                    // Mapping program studi
                    const programMap = {
                        "AB": "Administrasi Bisnis Terapan",
                        "AK": "Akuntansi",
                        "AM": "Akuntansi Manajerial",
                        "AN": "Animasi",
                        "Bengkalis-IF": "Teknik Informatika",
                        "DBG": "Distribusi Barang",
                        "EM": "Teknik Elektronika Manufaktur",
                        "GM": "Teknik Geomatika",
                        "IF": "Teknik Informatika",
                        "IF-FR": "Teknik Informatika",
                        "INS": "Teknik Instrumentasi",
                        "LPI": "Logistik Perdagangan Internasional",
                        "ME-FR": "Teknik Mesin",
                        "MJ": "Teknologi Rekayasa Multimedia",
                        "MK": "Teknik Mekatronika",
                        "MS": "Teknik Mesin",
                        "OT": "Teknik Otomasi",
                        "PPI": "Program Profesi Insinyur",
                        "RE": "Teknik Robotika",
                        "RKS": "Rekayasa Keamanan Siber",
                        "RPE": "Teknologi Rekayasa Pembangkit Energi",
                        "TPKP": "Teknik Perancangan dan Konstruksi Kapal",
                        "TPPU": "Teknik Perawatan Pesawat Udara",
                        "TRE": "Teknologi Rekayasa Elektronika",
                        "TRPL": "Teknologi Rekayasa Perangkat Lunak",

                        // Default ALL
                        "All": "All Program Study",
                        "ALL": "All Program Study"
                    };

                    // Ambil filter yang dipilih
                    const selectedStudyProgram = document.getElementById("filter_study_program")?.value || "ALL";

                    // Konversi ke teks yang benar
                    let filterText = programMap[selectedStudyProgram] || selectedStudyProgram;

                    const excelData = [
                        ['INTERNSHIP DATA REPORT'],
                        [`Year: ${year}`],
                        [`Filter: ${filterText}`],
                        [`Total Students: ${data.length}`],
                        [],
                        ['No', 'NIM', 'Name', 'Study Program', 'Start Date', 'End Date', 'Company', 'HRD', 'Position', 'Period'],
                        ...data.map((item, index) => [
                            index + 1,
                            item.nim || '-',
                            item.student_name || '-',
                            item.program_study || '-',
                            formatDate(item.start_date),
                            formatDate(item.end_date),
                            item.company_name || '-',
                            item.hrd_name || '-',
                            item.internship_position || '-',
                            item.internship_period || '-'
                        ])
                    ];

                    const ws = XLSX.utils.aoa_to_sheet(excelData);

                    // Column widths
                    ws['!cols'] = [{
                            wch: 5
                        },
                        {
                            wch: 15
                        },
                        {
                            wch: 25
                        },
                        {
                            wch: 40
                        },
                        {
                            wch: 12
                        },
                        {
                            wch: 12
                        },
                        {
                            wch: 35
                        },
                        {
                            wch: 25
                        },
                        {
                            wch: 25
                        },
                        {
                            wch: 12
                        }
                    ];

                    // Merges
                    ws['!merges'] = [{
                            s: {
                                r: 0,
                                c: 0
                            },
                            e: {
                                r: 0,
                                c: 9
                            }
                        },
                        {
                            s: {
                                r: 1,
                                c: 0
                            },
                            e: {
                                r: 1,
                                c: 9
                            }
                        },
                        {
                            s: {
                                r: 2,
                                c: 0
                            },
                            e: {
                                r: 2,
                                c: 9
                            }
                        },
                        {
                            s: {
                                r: 3,
                                c: 0
                            },
                            e: {
                                r: 3,
                                c: 9
                            }
                        }
                    ];

                    const borderStyle = {
                        top: {
                            style: 'thin',
                            color: {
                                rgb: '000000'
                            }
                        },
                        bottom: {
                            style: 'thin',
                            color: {
                                rgb: '000000'
                            }
                        },
                        left: {
                            style: 'thin',
                            color: {
                                rgb: '000000'
                            }
                        },
                        right: {
                            style: 'thin',
                            color: {
                                rgb: '000000'
                            }
                        }
                    };

                    const headerStyle = {
                        font: {
                            name: 'Times New Roman',
                            sz: 11,
                            bold: true,
                            color: {
                                rgb: 'FFFFFF'
                            }
                        },
                        fill: {
                            fgColor: {
                                rgb: '4472C4'
                            }
                        },
                        alignment: {
                            horizontal: 'center',
                            vertical: 'center'
                        },
                        border: borderStyle
                    };

                    const dataStyle = {
                        font: {
                            name: 'Times New Roman',
                            sz: 11
                        },
                        alignment: {
                            vertical: 'center'
                        },
                        border: borderStyle
                    };

                    const dataCenterStyle = {
                        font: {
                            name: 'Times New Roman',
                            sz: 11
                        },
                        alignment: {
                            horizontal: 'center',
                            vertical: 'center'
                        },
                        border: borderStyle
                    };

                    // === TITLE & SUBTITLE CENTER ===
                    ws['A1'].s = {
                        font: {
                            name: 'Times New Roman',
                            sz: 16,
                            bold: true
                        },
                        alignment: {
                            horizontal: 'center',
                            vertical: 'center'
                        }
                    };

                    ws['A2'].s = {
                        font: {
                            name: 'Times New Roman',
                            sz: 12
                        },
                        alignment: {
                            horizontal: 'center',
                            vertical: 'center'
                        }
                    };

                    ws['A3'].s = {
                        font: {
                            name: 'Times New Roman',
                            sz: 12
                        },
                        alignment: {
                            horizontal: 'center',
                            vertical: 'center'
                        }
                    };

                    ws['A4'].s = {
                        font: {
                            name: 'Times New Roman',
                            sz: 12
                        },
                        alignment: {
                            horizontal: 'center',
                            vertical: 'center'
                        }
                    };

                    // === HEADER ROW STYLING ===
                    const headerCols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
                    headerCols.forEach(col => {
                        const cellRef = col + '6'; // Row 6 adalah header
                        if (ws[cellRef]) ws[cellRef].s = headerStyle;
                    });

                    // === DATA ROWS STYLING ===
                    const dataStartRow = 7; // Row 7 mulai data
                    for (let row = dataStartRow; row < dataStartRow + data.length; row++) {
                        headerCols.forEach((col, colIndex) => {
                            const cellRef = col + row;
                            if (ws[cellRef]) {
                                // Kolom yang center: No (0), NIM (1), Start Date (4), End Date (5), Period (9)
                                if (colIndex === 0 || colIndex === 1 || colIndex === 4 || colIndex === 5 || colIndex === 9) {
                                    ws[cellRef].s = dataCenterStyle;
                                } else {
                                    ws[cellRef].s = dataStyle;
                                }
                            }
                        });
                    }

                    XLSX.utils.book_append_sheet(wb, ws, `Internship ${year}`);

                    // Filename dengan filter info
                    const fileName = `Data_Magang_${year}_${filterText.replace(/[^a-zA-Z0-9]/g, '_')}.xlsx`;
                    XLSX.writeFile(wb, fileName);

                    Swal.fire({
                        icon: "success",
                        title: "Success",
                        text: "Excel downloaded successfully!",
                        confirmButtonText: "OK"
                    });

                } catch (err) {
                    console.error("Excel generation error:", err);
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Failed to generate Excel: " + err.message,
                        confirmButtonText: "OK"
                    });
                }
            }
        </script>
</body>

</html>
