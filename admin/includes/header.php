<?php
if (!defined('IN_ADMIN')) {
    define('IN_ADMIN', true);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= htmlspecialchars($page_title ?? 'لوحة التحكم') ?> - <?= htmlspecialchars(cfg()->site_name ?? '') ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="/assets/img/brand/favicon.png" type="image/png">
    
    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Tajawal:300,400,500,700,900&display=swap">
    
    <!-- Icons -->
    <link rel="stylesheet" href="/assets/vendor/nucleo/css/nucleo.css">
    <link rel="stylesheet" href="/assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    
    <!-- Argon CSS -->
    <link rel="stylesheet" href="/assets/css/argon.min.css">
    <link rel="stylesheet" href="/assets/css/custom.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary: #5e72e4;
            --secondary: #6c757d;
            --success: #2dce89;
            --info: #11cdef;
            --warning: #fb6340;
            --danger: #f5365c;
            --light: #f8f9fe;
            --dark: #32325d;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
        }
        
        .bg-gradient-primary {
            background: linear-gradient(87deg, #5e72e4 0, #825ee4 100%) !important;
        }
    </style>
</head>

<body class="g-sidenav-show rtl">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <!-- Main content -->
    <div class="main-content" id="panel">
        <!-- Topnav -->
        <?php include __DIR__ . '/../includes/navbar.php'; ?>
        
        <!-- Header -->
        <div class="header bg-gradient-primary pb-6">
            <div class="container-fluid">
                <div class="header-body">
                    <div class="row align-items-center py-4">
                        <div class="col-lg-6 col-7">
                            <h6 class="h2 text-white d-inline-block mb-0"><?= $page_title ?? '' ?></h6>
                            <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                                <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                                    <li class="breadcrumb-item"><a href="/admin"><i class="fas fa-home"></i></a></li>
                                    <?php if (isset($breadcrumbs)): ?>
                                        <?php foreach ($breadcrumbs as $text => $url): ?>
                                            <?php if (is_numeric($text)): ?>
                                                <li class="breadcrumb-item active" aria-current="page"><?= $url ?></li>
                                            <?php else: ?>
                                                <li class="breadcrumb-item"><a href="<?= $url ?>"><?= $text ?></a></li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Page content -->
        <div class="container-fluid mt--6">
            <?php if (!empty($notice)): ?>
                <div class="alert alert-<?= $notice['type'] ?? 'info' ?> alert-dismissible fade show" role="alert">
                    <span class="alert-text"><?= $notice['message'] ?? '' ?></span>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
