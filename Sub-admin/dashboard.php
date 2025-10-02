<?php
session_start();

// Authentication Check - Redirect if not logged in or not a sub-admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'sub_admin') {
    header("Location: ../login.php");
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'airlines';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get sub-admin information
$sub_admin_id = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT full_name, email, status FROM sub_admins WHERE sub_admin_id = ?");
    $stmt->execute([$sub_admin_id]);
    $sub_admin = $stmt->fetch();
    
    if ($sub_admin) {
        $admin_name = $sub_admin['full_name'];
        $admin_email = $sub_admin['email'];
        $admin_status = $sub_admin['status'];
    } else {
        $admin_name = 'Sub Admin';
        $admin_email = '';
        $admin_status = 'active';
    }
} catch (PDOException $e) {
    $admin_name = 'Sub Admin';
    $admin_email = '';
    $admin_status = 'active';
}

// Get admin's first name and initial for display
$first_name = explode(' ', $admin_name)[0];
$admin_initial = strtoupper(substr($first_name, 0, 1));

// Get upcoming flights count
$upcoming_flights = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM flights WHERE flight_date >= CURDATE()");
    $result = $stmt->fetch();
    $upcoming_flights = $result['count'] ?? 0;
} catch (PDOException $e) {
    $upcoming_flights = 0;
}

// Get total bookings count
$total_bookings = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bookings");
    $result = $stmt->fetch();
    $total_bookings = $result['count'] ?? 0;
} catch (PDOException $e) {
    $total_bookings = 0;
}

// Get active users count
$active_users = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
    $result = $stmt->fetch();
    $active_users = $result['count'] ?? 0;
} catch (PDOException $e) {
    $active_users = 0;
}

// Get today's flights count
$todays_flights = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM flights WHERE DATE(flight_date) = CURDATE()");
    $result = $stmt->fetch();
    $todays_flights = $result['count'] ?? 0;
} catch (PDOException $e) {
    $todays_flights = 0;
}

// Get today's new bookings count
$todays_bookings = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bookings WHERE DATE(created_at) = CURDATE()");
    $result = $stmt->fetch();
    $todays_bookings = $result['count'] ?? 0;
} catch (PDOException $e) {
    $todays_bookings = 0;
}

// Get pending approvals count (pending bookings)
$pending_approvals = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'");
    $result = $stmt->fetch();
    $pending_approvals = $result['count'] ?? 0;
} catch (PDOException $e) {
    $pendinfdfgfdgfgdfdffgdfgffgfgdfgg_approvals = 0;
}

// Get today's active users count (users who have activity today)
$todays_active_users = 0;
try {
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT user_id) as count 
        FROM bookings 
        WHERE DATE(created_at) = CURDATE()
    ");
    $result = $stmt->fetch();
    $todays_active_users = $result['count'] ?? 0;
} catch (PDOException $e) {
    $todays_active_users = 0;
}

// Get recent activity (last 24 hours)
$recent_activities = [];
try {
    $stmt = $pdo->query("
        SELECT 
            'booking' as type,
            b.booking_ref as reference,
            b.created_at,
            u.full_name as user_name,
            f.flight_no,
            f.origin,
            f.destination,
            b.status,
            b.payment_status
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN flights f ON b.flight_id = f.flight_id
        WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        
        UNION ALL
        
        SELECT 
            'flight' as type,
            f.flight_no as reference,
            f.created_at,
            '' as user_name,
            f.flight_no,
            f.origin,
            f.destination,
            f.status,
            '' as payment_status
        FROM flights f
        WHERE f.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        
        UNION ALL
        
        SELECT 
            'user' as type,
            u.full_name as reference,
            u.created_at,
            u.full_name as user_name,
            '' as flight_no,
            '' as origin,
            '' as destination,
            u.status,
            '' as payment_status
        FROM users u
        WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $recent_activities = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_activities = [];
}

// Helper function to calculate time elapsed
function time_elapsed_string($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Dashboard - Speed of Light Airlines Sub Admin" />
    <meta name="keywords" content="airline admin, dashboard, flights, bookings, users">
    <meta name="author" content="Speed of Light Airlines" />
    <title>Sub Admin Dashboard | Speed of Light Airlines</title>
    <link rel="icon" href="../User/assets/images/airline-favicon.ico" type="image/x-icon">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <!-- Feather Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{ --brand:#00539C; --brand-dark:#003366; --bg:#f8fafc; --card-bg:rgba(255,255,255,0.96); --muted:#64748b; --success:#22c55e; --warning:#f59e0b; --danger:#ef4444; --info:#3b82f6; }
        *{box-sizing:border-box}
        body{background:var(--bg);font-family:'Inter','Segoe UI',Arial,sans-serif;min-height:100vh;overflow-x:hidden}
        
        /* Sidebar */
        .sidebar{position:fixed;left:0;top:0;bottom:0;width:260px;background:var(--brand-dark);box-shadow:2px 0 20px rgba(0,0,0,.08);z-index:1000;color:#fff;padding:20px 16px}
        .brand{display:flex;align-items:center;gap:10px;margin-bottom:18px}
        .brand img{width:38px;height:38px;border-radius:50%;box-shadow:0 2px 8px rgba(0,0,0,.3)}
        .brand-title{font-weight:800;letter-spacing:1px}
        .nav-section{margin-top:8px}
        .nav-link{color:#e5e7eb;border-radius:12px;padding:12px 14px;margin-bottom:8px;display:flex;align-items:center;gap:12px;transition:all .25s ease;position:relative;overflow:hidden}
        .nav-link:hover,.nav-link.active{background:var(--brand);color:#fff;transform:translateX(4px);box-shadow:0 6px 18px rgba(0,83,156,.25)}
        .nav-link i{width:18px;height:18px}
        
        /* Topbar */
        .topbar{position:sticky;top:0;z-index:900;background:linear-gradient(135deg, rgba(0,83,156,.9), rgba(0,51,102,.9));color:#fff;padding:12px 16px;margin-left:260px;backdrop-filter:blur(10px);box-shadow:0 2px 20px rgba(0,0,0,.1)}
        .topbar .right{display:flex;align-items:center;gap:14px;margin-left:auto}
        .user-info{display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.12);padding:6px 12px;border-radius:10px}
        .user-avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#48bb78,#38a169);display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff}
        
        /* Content & cards */
        .content{margin-left:260px;padding:22px 18px}
        .page-title h1{font-weight:800;letter-spacing:.5px;background:linear-gradient(45deg,#00539C,#003366);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
        .smart-card{background:var(--card-bg);border:1px solid rgba(0,83,156,.08);border-radius:20px;padding:18px;box-shadow:0 8px 24px rgba(0,83,156,.10);position:relative;overflow:hidden;transition:transform .35s cubic-bezier(.175,.885,.32,1.275),box-shadow .35s}
        .smart-card:hover{transform:translateY(-6px) scale(1.01);box-shadow:0 18px 50px rgba(0,83,156,.20)}
        .smart-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#00539C,#003366)}
        .reveal{opacity:0;transform:translateY(24px);transition:opacity .6s ease, transform .6s ease}
        .reveal.visible{opacity:1;transform:translateY(0)}
        
        /* Welcome Section */
        .welcome-banner{background:linear-gradient(135deg, #00539C 0%, #003366 100%);color:#fff;border-radius:20px;padding:30px;margin-bottom:30px;position:relative;overflow:hidden}
        .welcome-banner::before{content:'';position:absolute;top:-50%;right:-20%;width:200px;height:200px;background:rgba(255,255,255,.05);border-radius:50%;transform:rotate(45deg)}
        .welcome-banner::after{content:'';position:absolute;bottom:-30%;left:-10%;width:150px;height:150px;background:rgba(255,255,255,.03);border-radius:50%}
        .welcome-content{position:relative;z-index:2}
        .welcome-title{font-size:2.2rem;font-weight:800;margin-bottom:8px}
        .welcome-subtitle{font-size:1.1rem;opacity:.9;margin-bottom:20px}
        .welcome-time{font-size:.95rem;opacity:.8}
        
        /* Metric Cards */
        .metric-card{background:var(--card-bg);border-radius:20px;padding:25px;text-align:center;position:relative;overflow:hidden;transition:all .3s ease;border:1px solid rgba(0,83,156,.08)}
        .metric-card:hover{transform:translateY(-8px);box-shadow:0 20px 40px rgba(0,83,156,.15)}
        .metric-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px}
        .metric-card.flights::before{background:linear-gradient(90deg,#3b82f6,#1d4ed8)}
        .metric-card.bookings::before{background:linear-gradient(90deg,#22c55e,#16a34a)}
        .metric-card.users::before{background:linear-gradient(90deg,#f59e0b,#d97706)}
        .metric-icon{width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 15px;font-size:24px;color:#fff}
        .metric-card.flights .metric-icon{background:linear-gradient(135deg,#3b82f6,#1d4ed8)}
        .metric-card.bookings .metric-icon{background:linear-gradient(135deg,#22c55e,#16a34a)}
        .metric-card.users .metric-icon{background:linear-gradient(135deg,#f59e0b,#d97706)}
        .metric-number{font-size:2.5rem;font-weight:800;color:var(--brand-dark);margin-bottom:5px}
        .metric-label{font-size:1rem;color:var(--muted);font-weight:600}
        .metric-change{font-size:.85rem;margin-top:8px;padding:4px 8px;border-radius:12px;display:inline-block}
        .metric-change.positive{background:rgba(34,197,94,.1);color:#16a34a}
        .metric-change.negative{background:rgba(239,68,68,.1);color:#dc2626}
        
        /* Quick Actions */
        .quick-action{background:var(--card-bg);border-radius:16px;padding:20px;text-align:center;transition:all .3s ease;border:1px solid rgba(0,83,156,.08);text-decoration:none;color:inherit;display:block}
        .quick-action:hover{transform:translateY(-4px);box-shadow:0 12px 30px rgba(0,83,156,.15);color:inherit;text-decoration:none}
        .quick-action-icon{width:50px;height:50px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:20px;color:#fff;background:linear-gradient(135deg,var(--brand),var(--brand-dark))}
        .quick-action-title{font-weight:600;color:var(--brand-dark);margin-bottom:5px}
        .quick-action-desc{font-size:.85rem;color:var(--muted)}
        
        /* Recent Activity */
        .activity-item{display:flex;align-items:center;gap:15px;padding:15px 0;border-bottom:1px solid rgba(0,83,156,.08)}
        .activity-item:last-child{border-bottom:none}
        .activity-icon{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;color:#fff}
        .activity-icon.booking{background:linear-gradient(135deg,#22c55e,#16a34a)}
        .activity-icon.flight{background:linear-gradient(135deg,#3b82f6,#1d4ed8)}
        .activity-icon.user{background:linear-gradient(135deg,#f59e0b,#d97706)}
        .activity-content{flex:1}
        .activity-title{font-weight:600;color:var(--brand-dark);margin-bottom:2px}
        .activity-desc{font-size:.85rem;color:var(--muted)}
        .activity-time{font-size:.8rem;color:var(--muted);white-space:nowrap}
        
        /* Responsive */
        @media(max-width:991px){
            .sidebar{left:-260px;transition:left .3s}
            .sidebar.show{left:0}
            .topbar,.content{margin-left:0}
            .welcome-title{font-size:1.8rem}
            .metric-number{font-size:2rem}
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <img src="../pexels-sevenstormphotography-728824 (1).jpg" alt="Logo">
            <div class="brand-title">SOLA</div>
        </div>
        <nav class="nav-section">
            <a href="dashboard.php" class="nav-link active"><i data-feather="home"></i> Dashboard</a>
            <a href="manage-flights.php" class="nav-link"><i data-feather="navigation"></i> Manage Flight</a>
            <a href="bookings.php" class="nav-link"><i data-feather="calendar"></i> Bookings</a>
            <a href="users.php" class="nav-link"><i data-feather="users"></i>User Management</a>
            <a href="profile.php" class="nav-link"><i data-feather="user"></i> Profile</a>
            <a href="logout.php" class="nav-link"><i data-feather="log-out"></i> Logout</a>
        </nav>
    </aside>

    <!-- Topbar -->
    <header class="topbar d-flex align-items-center">
        <button class="btn btn-sm btn-light d-lg-none" id="menuToggle"><i data-feather="menu"></i></button>
        
        <div class="right ms-auto">
            <div class="user-info ms-2">
                <div class="user-avatar"><?php echo $admin_initial; ?></div>
                <div class="fw-bold"><?php echo htmlspecialchars($first_name); ?></div>
            </div>
        </div>
    </header>

    <!-- Content -->
    <main class="content">
        <!-- Welcome Banner -->
        <div class="welcome-banner reveal">
            <div class="welcome-content">
                <div class="welcome-title">Welcome back, <?php echo htmlspecialchars($first_name); ?>!</div>
                <div class="welcome-subtitle">Here's what's happening with Speed of Light Airlines today</div>
                <div class="welcome-time" id="currentTime"></div>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="metric-card flights reveal">
                    <div class="metric-icon">
                        <i data-feather="navigation"></i>
                    </div>
                    <div class="metric-number" id="flightsCount"><?php echo $upcoming_flights; ?></div>
                    <div class="metric-label">Upcoming Flights</div>
                    <div class="metric-change positive">
                        <i data-feather="trending-up" style="width:12px;height:12px"></i> Live data
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card bookings reveal">
                    <div class="metric-icon">
                        <i data-feather="calendar"></i>
                    </div>
                    <div class="metric-number" id="bookingsCount"><?php echo $total_bookings; ?></div>
                    <div class="metric-label">Total Bookings</div>
                    <div class="metric-change positive">
                        <i data-feather="trending-up" style="width:12px;height:12px"></i> +<?php echo $todays_bookings; ?> today
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card users reveal">
                    <div class="metric-icon">
                        <i data-feather="users"></i>
                    </div>
                    <div class="metric-number" id="usersCount"><?php echo $active_users; ?></div>
                    <div class="metric-label">Active Users</div>
                    <div class="metric-change positive">
                        <i data-feather="trending-up" style="width:12px;height:12px"></i> Live data
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Recent Activity -->
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="smart-card reveal">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Recent Activity</h5>
                        <small class="text-muted">Last 24 hours</small>
                    </div>
                    <div id="activityList">
                        <?php if (empty($recent_activities)): ?>
                            <div class="activity-item">
                                <div class="activity-icon booking">
                                    <i data-feather="info"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">No recent activity</div>
                                    <div class="activity-desc">No activities in the last 24 hours</div>
                                </div>
                                <div class="activity-time">-</div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="activity-item">
                                    <?php if ($activity['type'] === 'booking'): ?>
                                        <div class="activity-icon booking">
                                            <i data-feather="calendar"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-title">
                                                <?php if ($activity['payment_status'] === 'Paid'): ?>
                                                    Booking confirmed
                                                <?php else: ?>
                                                    New booking created
                                                <?php endif; ?>
                                            </div>
                                            <div class="activity-desc">
                                                <?php echo htmlspecialchars($activity['user_name']); ?> booked 
                                                <?php echo htmlspecialchars($activity['origin'] . ' → ' . $activity['destination']); ?> 
                                                (<?php echo htmlspecialchars($activity['flight_no']); ?>)
                                            </div>
                                        </div>
                                    <?php elseif ($activity['type'] === 'flight'): ?>
                                        <div class="activity-icon flight">
                                            <i data-feather="navigation"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-title">New flight added</div>
                                            <div class="activity-desc">
                                                Flight <?php echo htmlspecialchars($activity['flight_no']); ?> 
                                                (<?php echo htmlspecialchars($activity['origin'] . ' → ' . $activity['destination']); ?>) added
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="activity-icon user">
                                            <i data-feather="user-plus"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-title">New user registered</div>
                                            <div class="activity-desc">
                                                <?php echo htmlspecialchars($activity['user_name']); ?> created an account
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="activity-time"><?php echo time_elapsed_string($activity['created_at']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="smart-card reveal mb-4">
                    <h5 class="fw-bold mb-3">Quick Actions</h5>
                    <div class="row g-3">
                        <div class="col-6">
                            <a href="manage-flights.php" class="quick-action">
                                <div class="quick-action-icon">
                                    <i data-feather="plus"></i>
                                </div>
                                <div class="quick-action-title">Add Flight</div>
                                <div class="quick-action-desc">Schedule new flight</div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="bookings.php" class="quick-action">
                                <div class="quick-action-icon">
                                    <i data-feather="calendar"></i>
                                </div>
                                <div class="quick-action-title">View Bookings</div>
                                <div class="quick-action-desc">Manage reservations</div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="users.php" class="quick-action">
                                <div class="quick-action-icon">
                                    <i data-feather="users"></i>
                                </div>
                                <div class="quick-action-title">Manage Users</div>
                                <div class="quick-action-desc">User accounts</div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="profile.php" class="quick-action">
                                <div class="quick-action-icon">
                                    <i data-feather="settings"></i>
                                </div>
                                <div class="quick-action-title">Settings</div>
                                <div class="quick-action-desc">Account settings</div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Today's Summary -->
                <div class="smart-card reveal">
                    <h5 class="fw-bold mb-3">Today's Summary</h5>
                    <div class="row g-3 text-center">
                        <div class="col-6">
                            <div class="fw-bold text-primary fs-4"><?php echo $todays_flights; ?></div>
                            <div class="small text-muted">Flights Today</div>
                        </div>
                        <div class="col-6">
                            <div class="fw-bold text-success fs-4"><?php echo $todays_bookings; ?></div>
                            <div class="small text-muted">New Bookings</div>
                        </div>
                        <div class="col-6">
                            <div class="fw-bold text-warning fs-4"><?php echo $pending_approvals; ?></div>
                            <div class="small text-muted">Pending Approvals</div>
                        </div>
                        <div class="col-6">
                            <div class="fw-bold text-info fs-4"><?php echo $todays_active_users; ?></div>
                            <div class="small text-muted">Active Users</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        feather.replace();

        // Sidebar toggle for mobile
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        if (menuToggle) menuToggle.addEventListener('click', () => { sidebar.classList.toggle('show'); });

        // Reveal on scroll animation
        const revealEls = document.querySelectorAll('.reveal');
        const io = new IntersectionObserver((entries)=>{ 
            entries.forEach(e=>{ 
                if(e.isIntersecting){ 
                    e.target.classList.add('visible'); 
                    io.unobserve(e.target);
                } 
            }); 
        },{threshold:.1, rootMargin:'0px 0px -50px 0px'});
        revealEls.forEach(el=>io.observe(el));

        // Update current time
        function updateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            document.getElementById('currentTime').textContent = now.toLocaleDateString('en-US', options);
        }
        updateTime();
        setInterval(updateTime, 60000); // Update every minute

        // Animate numbers on load
        function animateNumber(element, target, duration = 2000) {
            const start = 0;
            const increment = target / (duration / 16);
            let current = start;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current);
            }, 16);
        }

        // Animate metric numbers when they become visible
        const metricObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const numberEl = entry.target.querySelector('.metric-number');
                    if (numberEl && !numberEl.classList.contains('animated')) {
                        numberEl.classList.add('animated');
                        const target = parseInt(numberEl.textContent);
                        animateNumber(numberEl, target);
                    }
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('.metric-card').forEach(card => {
            metricObserver.observe(card);
        });
    </script>
</body>
</html>
</qodoArtifact>

