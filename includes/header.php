<?php
require_once __DIR__ . '/../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>

    <!-- Bootstrap 5.3 CSS (Offline) -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- FontAwesome (Offline) -->
    <link rel="stylesheet" href="assets/css/all.min.css">

    <style>
        :root {
            --primary-color:
                <?php echo PRIMARY_COLOR; ?>
            ;
            --secondary-color:
                <?php echo SECONDARY_COLOR; ?>
            ;
            --header-font:
                <?php echo HEADER_FONT; ?>
            ;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
        }

        /* Hospital Professional Header */
        .hospital-header {
            background: white;
            border-bottom: 4px solid var(--primary-color);
            padding: 15px 0;
            margin-bottom: 20px;
        }

        .hospital-logo {
            max-height: 80px;
        }

        .hospital-name {
            font-family: var(--header-font);
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .hospital-address {
            font-size: 14px;
            color: #555;
        }

        /* Sidebar Styling */
        #sidebar-wrapper {
            min-height: 100vh;
            margin-left: -15rem;
            transition: margin .25s ease-out;
            background-color: var(--secondary-color);
            color: white;
        }

        #sidebar-wrapper .sidebar-heading {
            padding: 0.875rem 1.25rem;
            font-size: 1.2rem;
            background-color: rgba(0, 0, 0, 0.2);
        }

        #sidebar-wrapper .list-group {
            width: 15rem;
        }

        #sidebar-wrapper .list-group-item {
            background-color: transparent;
            color: rgba(255, 255, 255, 0.8);
            border: none;
            padding: 12px 20px;
        }

        #sidebar-wrapper .list-group-item:hover,
        #sidebar-wrapper .list-group-item.active {
            background-color: var(--primary-color);
            color: white;
        }

        #page-content-wrapper {
            min-width: 100vw;
        }

        #wrapper.toggled #sidebar-wrapper {
            margin-left: 0;
        }

        @media (min-width: 768px) {
            #sidebar-wrapper {
                margin-left: 0;
            }

            #page-content-wrapper {
                min-width: 0;
                width: 100%;
            }

            #wrapper.toggled #sidebar-wrapper {
                margin-left: -15rem;
            }
        }

        /* Print Styling */
        @media print {

            .no-print,
            #sidebar-wrapper,
            .navbar {
                display: none !important;
            }

            #page-content-wrapper {
                margin: 0;
                padding: 0;
                width: 100%;
            }

            .hospital-header {
                border-bottom: 2px solid black;
            }
        }
    </style>
</head>

<body>

    <div class="d-flex" id="wrapper">
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php include_once 'sidebar.php'; ?>
            <?php
        endif; ?>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <?php if (isset($_SESSION['user_id'])): ?>
                <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom px-3 no-print">
                    <button class="btn btn-primary" id="menu-toggle"><i class="fas fa-bars"></i></button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto mt-2 mt-lg-0 align-items-center">
                            <!-- Feature 4: Global Patient Search -->
                            <li class="nav-item me-3 position-relative">
                                <div class="input-group input-group-sm" style="width: 250px;">
                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                    <input type="text" id="global-patient-search" class="form-control border-start-0" placeholder="Search Patient (Name/MR/Phone)" autocomplete="off">
                                </div>
                                <div id="search-results-dropdown" class="dropdown-menu w-100 position-absolute" style="display:none; max-height: 300px; overflow-y: auto; z-index: 1050;"></div>
                            </li>

                            <!-- Feature 1: Notification Bell -->
                            <li class="nav-item dropdown me-3">
                                <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-bell"></i>
                                    <span id="notif-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none; font-size: 0.6rem;">
                                        0
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown" id="notif-menu" style="min-width: 300px; max-height: 400px; overflow-y: auto;">
                                    <li><span class="dropdown-item text-muted text-center small">No new notifications</span></li>
                                </ul>
                            </li>

                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                                    data-bs-toggle="dropdown">
                                    <i class="fas fa-user-circle"></i> <?php echo $_SESSION['full_name'] ?? 'User'; ?>
                                    (<?php echo $_SESSION['role'] ?? ''; ?>)
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
                <?php
            endif; ?>

            <div class="container-fluid p-0">
                <!-- Universal Hospital Header (Visible on Prints and Top of pages) -->
                <div class="hospital-header text-center">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center text-md-end">
                            <img src="<?php echo APP_LOGO; ?>" alt="Logo" class="hospital-logo">
                        </div>
                        <div class="col-md-8 text-center text-md-start">
                            <a class="navbar-brand me-0" href="index.php">
                                <img src="<?php echo APP_LOGO; ?>" alt="Logo" height="40"
                                    class="d-inline-block align-text-top me-2">
                                <?php echo APP_SHORT_NAME; ?>
                            </a>
                            <div class="hospital-address">
                                <i class="fas fa-map-marker-alt"></i> <?php echo APP_ADDRESS; ?><br>
                                <i class="fas fa-phone"></i> <?php echo APP_PHONE; ?> | <i class="fas fa-envelope"></i>
                                <?php echo APP_EMAIL; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4">
                    <!-- Page Content Starts Here -->

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Search functionality
    const searchInput = document.getElementById('global-patient-search');
    const searchResults = document.getElementById('search-results-dropdown');

    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const q = this.value.trim();
            if (q.length < 2) {
                searchResults.style.display = 'none';
                return;
            }
            
            debounceTimer = setTimeout(() => {
                fetch('api_search_patients.php?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    searchResults.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const a = document.createElement('a');
                            a.className = 'dropdown-item border-bottom py-2';
                            a.href = 'patient_view.php?id=' + item.id;
                            a.innerHTML = `<strong>${item.full_name}</strong><br><small class="text-muted">${item.mr_number} | ${item.phone}</small>`;
                            searchResults.appendChild(a);
                        });
                        searchResults.style.display = 'block';
                    } else {
                        searchResults.innerHTML = '<span class="dropdown-item text-muted">No results found</span>';
                        searchResults.style.display = 'block';
                    }
                });
            }, 300);
        });

        // Hide search when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    }

    // Notification polling
    const notifBadge = document.getElementById('notif-badge');
    const notifMenu = document.getElementById('notif-menu');

    function fetchNotifications() {
        if (!notifBadge) return;
        fetch('api_notifications.php')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (data.count > 0) {
                    notifBadge.innerText = data.count;
                    notifBadge.style.display = 'block';
                    notifMenu.innerHTML = '';
                    data.notifications.forEach(n => {
                        const li = document.createElement('li');
                        li.innerHTML = `<a class="dropdown-item border-bottom text-wrap" href="#" onclick="markNotifRead(${n.id}, event)" style="white-space: normal;">
                            <small class="text-primary fw-bold">${n.type}</small><br>
                            <span class="small">${n.message}</span>
                        </a>`;
                        notifMenu.appendChild(li);
                    });
                } else {
                    notifBadge.style.display = 'none';
                    notifMenu.innerHTML = '<li><span class="dropdown-item text-muted text-center small">No new notifications</span></li>';
                }
            }
        }).catch(e => console.error("Notif fetch error"));
    }

    window.markNotifRead = function(id, e) {
        if(e) e.preventDefault();
        fetch('api_notifications.php?action=read&id=' + id)
        .then(() => fetchNotifications());
    }

    // Initial fetch and poll every 30s
    if (notifBadge) {
        fetchNotifications();
        setInterval(fetchNotifications, 30000);
    }
});
</script>