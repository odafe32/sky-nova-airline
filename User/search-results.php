<!DOCTYPE html>
<html lang="en">
<head>
    <title>Departure Flights to Paris | Speed of Light Airlines</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Animate.css for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <!-- Feather Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <style>
        body {
            background: #f8fafc;
            min-height: 100vh;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .navbar {
            background: #38a169;
        }
        .navbar-brand {
            color: #fff !important;
            font-weight: bold;
            letter-spacing: 1px;
            font-size: 1.5rem;
        }
        /* Enhanced Header User Section */
        .navbar-user-section {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-left: auto;
        }
        .cart-icon-container {
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 8px 12px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
        .cart-icon-container:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .cart-icon {
            color: #fff;
            font-size: 1.3rem;
            transition: all 0.3s ease;
        }
        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: #fff;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            animation: pulse 2s infinite;
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.4);
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        .user-info:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #48bb78, #38a169);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            color: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        .user-name {
            font-size: 1rem;
            font-weight: 600;
        }
        .navbar-toggler {
            border: none;
        }
        .navbar-toggler:focus {
            box-shadow: none;
        }
        .sidebar {
            background: #38a169;
            min-height: 100vh;
            padding-top: 30px;
        }
        .sidebar .nav-link {
            color: #fff;
            font-weight: 500;
            margin-bottom: 10px;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background: #38a169;
            color: #fff;
        }
        .main-content {
            padding: 40px 20px 80px 20px;
        }
        .results-summary-bar {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            padding: 24px 18px;
            margin: 0 auto 18px auto;
            max-width: 900px;
            text-align: center;
            animation: fadeInDown 1s;
        }
        .results-summary-bar h2 {
            font-size: 1.35rem;
            font-weight: bold;
            color: #38a169;
            margin-bottom: 0;
        }
        .results-summary-bar .summary-detail {
            color: #333;
            font-size: 1.08rem;
            margin-top: 6px;
        }
        .badge-row {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 18px 0 10px 0;
        }
        .flight-badge {
            display: flex;
            align-items: center;
            background: #f6f9fc;
            border-radius: 12px;
            padding: 8px 18px;
            box-shadow: 0 2px 8px rgba(0,83,156,0.04);
            font-weight: 600;
            font-size: 1.05rem;
            color: #38a169;
            gap: 10px;
        }
        .flight-badge img {
            width: 32px;
            height: 32px;
        }
        .sort-bar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 16px;
            margin: 0 0 18px 0;
        }
        .sort-bar label {
            font-weight: 500;
            color: #38a169;
        }
        .sort-bar select {
            border-radius: 8px;
            padding: 4px 12px;
        }
        .flight-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            margin-bottom: 32px;
            padding: 28px 18px;
            transition: transform 0.2s, box-shadow 0.2s;
            animation: fadeInUp 0.7s;
            position: relative;
            overflow: hidden;
        }
        .flight-card:hover {
            transform: scale(1.015) translateY(-4px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.13);
        }
        .airline-logo {
            width: 32px;
            height: 32px;
            object-fit: contain;
            margin-right: 10px;
        }
        .flight-airline {
            font-weight: 600;
            font-size: 1.1rem;
        }
        .flight-included {
            color: #43a047;
            font-size: 0.95rem;
            font-weight: 500;
            margin-bottom: 6px;
        }
        .flight-time {
            font-size: 1.5rem;
            font-weight: bold;
            color: #38a169;
        }
        .flight-airport {
            font-size: 1.1rem;
            font-weight: 500;
            color: #333;
        }
        .flight-duration {
            color: #888;
            font-size: 1rem;
            margin: 0 10px;
        }
        .flight-direct {
            color: #43a047;
            font-size: 0.98rem;
            font-weight: 500;
        }
        .flight-dateplus {
            color: #888;
            font-size: 0.95rem;
            font-weight: 500;
        }
        .flight-price-section {
            text-align: right;
        }
        .flight-price-label {
            color: #888;
            font-size: 0.97rem;
        }
        .flight-price {
            font-size: 1.3rem;
            font-weight: bold;
            color: #38a169;
        }
        .flight-total {
            color: #333;
            font-size: 1.05rem;
            font-weight: 500;
        }
        .select-btn {
            background: linear-gradient(90deg, #38a169 0%, #00c6fb 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            padding: 10px 28px;
            margin-top: 10px;
            transition: background 0.3s, transform 0.2s;
            box-shadow: 0 2px 8px rgba(0,83,156,0.07);
        }
        .select-btn:hover {
            background: linear-gradient(90deg, #00c6fb 0%, #38a169 100%);
            transform: scale(1.04);
        }
        .modal-header {
            background: #38a169;
            color: #fff;
        }
        .modal-title {
            font-weight: bold;
        }
        .flight-detail-modal .modal-content {
            border-radius: 18px;
        }
        .flight-detail-modal .modal-body {
            background: #f8fafc;
        }
        .flight-detail-summary {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,83,156,0.04);
            padding: 18px 12px;
            margin-bottom: 18px;
        }
        .flight-detail-airline-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            margin-right: 10px;
        }
        .flight-detail-section-title {
            font-weight: 600;
            color: #38a169;
            margin-bottom: 8px;
        }
        .flight-detail-label {
            color: #888;
            font-size: 0.97rem;
        }
        .flight-detail-value {
            font-weight: 500;
            font-size: 1.08rem;
        }
        .price-detail-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,83,156,0.04);
            padding: 18px 12px;
            margin-bottom: 18px;
        }
        .price-detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .price-detail-total {
            font-size: 1.15rem;
            font-weight: bold;
            color: #38a169;
        }
        .checkout-btn {
            background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            padding: 12px 32px;
            font-size: 1.1rem;
            transition: background 0.3s, transform 0.2s;
        }
        .checkout-btn:hover {
            background: linear-gradient(90deg, #38f9d7 0%, #43e97b 100%);
            color: #fff;
            transform: scale(1.04);
        }
    </style>
</head>
<body>
    <!-- Navbar (Top) -->
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="../pexels-sevenstormphotography-728824 (1).jpg" alt="Logo" style="width:38px; margin-right:10px;">
                SKYNOVA            </a>
            
            <div class="navbar-user-section">
                <div class="cart-icon-container" onclick="window.location.href='cart.php'">
                    <i data-feather="shopping-cart" class="cart-icon"></i>
                    <div class="cart-badge">2</div>
                </div>
                <div class="user-info">
                    <div class="user-avatar">A</div>
                    <div class="user-name">Albert</div>
                </div>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span><i data-feather="menu"></i></span>
            </button>
        </div>
    </nav>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebarMenu" class="col-lg-2 col-md-3 d-lg-block sidebar collapse">
                <div class="position-sticky">
                    <ul class="nav flex-column mt-4">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php"><i data-feather="home"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="book-flight.php"><i data-feather="send"></i> Book a Flight</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my-bookings.php"><i data-feather="calendar"></i> My Bookings</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="flight-status.php"><i data-feather="map-pin"></i> Flight Status</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php"><i data-feather="user"></i> Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php"><i data-feather="log-out"></i> Logout</a>
                        </li>
                    </ul>
                </div>
            </nav>
            <!-- Main Content -->
            <main class="col-lg-10 col-md-9 ms-sm-auto main-content">
                <div class="results-summary-bar animate__animated animate__fadeInDown">
                    <h2>Departure flights to Paris</h2>
                    <div class="summary-detail">
                        Fri, 8 Aug 2025 | 1 Passenger
                    </div>
                </div>
                <div class="badge-row">
                    <div class="flight-badge">
                        <img src="https://cdn.airpaz.com/cdn-cgi/image/w=2048,h=2048,f=webp/forerunner-next/img/illustration/v2/spot/recommended.png" alt="Recommended">
                        Recommended <span class="ms-2 fw-bold text-success">US$ 2,183.67</span>
                    </div>
                    <div class="flight-badge">
                        <img src="https://cdn.airpaz.com/cdn-cgi/image/w=2048,h=2048,f=webp/forerunner-next/img/illustration/v2/spot/cheapest.png" alt="Cheapest">
                        Cheapest <span class="ms-2 fw-bold text-success">US$ 2,151.39</span>
                    </div>
                    <div class="flight-badge">
                        <img src="https://cdn.airpaz.com/cdn-cgi/image/w=2048,h=2048,f=webp/forerunner-next/img/illustration/v2/spot/fastest.png" alt="Fastest">
                        Fastest <span class="ms-2 fw-bold text-success">US$ 2,183.67</span>
                    </div>
                </div>
                <div class="sort-bar mb-3">
                    <label for="sortBy">Sort by</label>
                    <select id="sortBy" class="form-select form-select-sm" style="width:auto;">
                        <option selected>Combo Price</option>
                        <option>Cheapest</option>
                        <option>Fastest</option>
                        <option>Recommended</option>
                    </select>
                </div>
                <!-- Flight Card 1 -->
                <div class="flight-card row align-items-center animate__animated animate__fadeInUp">
                    <div class="col-md-2 text-center mb-3 mb-md-0">
                        <div class="mb-2">
                            <span class="badge bg-light text-dark fw-bold">Economy</span>
                        </div>
                        <img src="https://cdn.airpaz.com/cdn-cgi/image/w=20,h=20,f=webp/rel-0275/airlines/201x201/AF.png" alt="Air France" class="airline-logo">
                        <div class="flight-airline">Air France</div>
                        <div class="flight-included">Included</div>
                    </div>
                    <div class="col-md-2 text-center mb-3 mb-md-0">
                        <div class="flight-time">23:50</div>
                        <div class="flight-airport">LOS</div>
                    </div>
                    <div class="col-md-2 text-center mb-3 mb-md-0">
                        <div class="flight-duration">
                            <i data-feather="clock"></i> 6h 50m
                        </div>
                        <div class="flight-direct">Direct</div>
                    </div>
                    <div class="col-md-2 text-center mb-3 mb-md-0">
                        <div class="flight-time">07:40</div>
                        <div class="flight-airport">CDG</div>
                        <div class="flight-dateplus">+1d</div>
                    </div>
                    <div class="col-md-2 text-center mb-3 mb-md-0">
                        <div class="flight-price-label">Start from</div>
                        <div class="flight-price">US$ 1,091.84</div>
                        <div class="flight-total">Total US$ 2,183.67 / Pax</div>
                    </div>
                    <div class="col-md-2 flight-price-section">
                        <div class="mb-2">
                            <span class="badge bg-info text-white">Recommended</span>
                        </div>
                        <button class="select-btn animate__animated animate__pulse animate__infinite"
                            data-bs-toggle="modal"
                            data-bs-target="#flightDetailModal"
                            data-flight="af149"
                        >Select</button>
                    </div>
                </div>
                <!-- Flight Card 2 -->
                <div class="flight-card row align-items-center animate__animated animate__fadeInUp">
                    <div class="col-md-2 text-center mb-3 mb-md-0">
                        <div class="mb-2">
                            <span class="badge bg-light text-dark fw-bold">Economy</span>
                        </div>
                        <img src="https://cdn.airpaz.com/cdn-cgi/image/w=20,h=20,f=webp/rel-0275/airlines/201x201/BA.png" alt="British Airways" class="airline-logo">
                        <div class="flight-airline">British Airways</div>
                        <div class="flight-included">Included</div>
                    </div>
                    <div class="col-md-2 text-center mb-3 mb-md-0">
                        <div class="flight-time">22:50</div>
                        <div class="flight-airport">LOS</div>
                    </div>
                    <div class="col-md-2 text-center mb-3 mb-md-0">
                        <div class="flight-duration">
                            <i data-feather="clock"></i> 9h 45m
                        </div>
                        <div class="flight-direct">1 Stop</div>
                    </div>
                    <div class="col-md-2 text-center mb-3 mb-md-0">
                        <div class="flight-time">09:35</div>
                        <div class="flight-airport">CDG</div>
                        <div class="flight-dateplus">+1d</div>
                    </div>
                    <div class="col-md-2 text-center mb-3 mb-md-0">
                        <div class="flight-price-label">Start from</div>
                        <div class="flight-price">US$ 1,075.69</div>
                        <div class="flight-total">Total US$ 2,151.39 / Pax</div>
                    </div>
                    <div class="col-md-2 flight-price-section">
                        <div class="mb-2">
                            <span class="badge bg-info text-white">Cheapest</span>
                        </div>
                        <button class="select-btn animate__animated animate__pulse animate__infinite"
                            data-bs-toggle="modal"
                            data-bs-target="#flightDetailModal"
                            data-flight="ba001"
                        >Select</button>
                    </div>
                </div>
                <!-- More flight cards as needed... -->
            </main>
        </div>
    </div>
    <!-- Flight Details Modal -->
    <div class="modal fade flight-detail-modal" id="flightDetailModal" tabindex="-1" aria-labelledby="flightDetailModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="flightDetailModalLabel"><i data-feather="info"></i> Selected Departure Flight</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="flightDetailModalBody">
            <!-- Flight details will be injected here by JS -->
          </div>
        </div>
      </div>
    </div>
    <footer class="footer">
        &copy; <span id="year"></span> SKYNOVA Airlines. All Rights Reserved.  
    </footer>
    <!-- Bootstrap JS, Feather Icons, and Custom JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <!-- ... (rest of your HTML remains unchanged above) ... -->
<script>
    feather.replace();
    document.getElementById('year').textContent = new Date().getFullYear();

    // Sidebar toggler for mobile
    document.querySelectorAll('.navbar-toggler').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var sidebar = document.getElementById('sidebarMenu');
            if (sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            } else {
                sidebar.classList.add('show');
            }
        });
    });
    // Close sidebar on nav-link click (mobile)
    document.querySelectorAll('.sidebar .nav-link').forEach(function(link) {
        link.addEventListener('click', function() {
            var sidebar = document.getElementById('sidebarMenu');
            if (window.innerWidth < 992) {
                sidebar.classList.remove('show');
            }
        });
    });

    // Cart icon click animation
    const cartIconEl = document.querySelector('.cart-icon-container');
    if (cartIconEl) {
        cartIconEl.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    }

    // Demo flight details data
    const flightDetails = {
        af149: {
            route: "Lagos (LOS) &rarr; Paris (CDG)",
            date: "Fri, 8 Aug 2025",
            time: "23:50 - 07:40",
            stops: "Direct",
            airline: "Air France",
            logo: "https://cdn.airpaz.com/cdn-cgi/image/w=50,h=50,f=webp/rel-0275/airlines/201x201/AF.png",
            flight_no: "AF149",
            aircraft: "Boeing 777-200",
            fare: "Economy Lite",
            depart_time: "23:50",
            depart_date: "8 Aug 2025",
            depart_airport: "Lagos (LOS)",
            depart_airport_full: "Murtala Muhammed International Airport",
            duration: "6h 50m",
            arrive_time: "07:40",
            arrive_date: "9 Aug 2025",
            arrive_airport: "Paris (CDG)",
            arrive_airport_full: "Paris Charles de Gaulle Airport",
            baggage: "Cabin Baggage 12kg",
            refundable: "Non Refundable",
            reschedulable: "Non Reschedulable",
            ticket_time: "Estimated ticket issued <2 h",
            price: 2168.10,
            discount: 0.06,
            total: 2168.04
        },
        ba001: {
            route: "Lagos (LOS) &rarr; Paris (CDG)",
            date: "Fri, 8 Aug 2025",
            time: "22:50 - 09:35",
            stops: "1 Stop",
            airline: "British Airways",
            logo: "https://cdn.airpaz.com/cdn-cgi/image/w=50,h=50,f=webp/rel-0275/airlines/201x201/BA.png",
            flight_no: "BA001",
            aircraft: "Boeing 787-9",
            fare: "Economy Lite",
            depart_time: "22:50",
            depart_date: "8 Aug 2025",
            depart_airport: "Lagos (LOS)",
            depart_airport_full: "Murtala Muhammed International Airport",
            duration: "9h 45m",
            arrive_time: "09:35",
            arrive_date: "9 Aug 2025",
            arrive_airport: "Paris (CDG)",
            arrive_airport_full: "Paris Charles de Gaulle Airport",
            baggage: "Cabin Baggage 12kg",
            refundable: "Non Refundable",
            reschedulable: "Non Reschedulable",
            ticket_time: "Estimated ticket issued <2 h",
            price: 2151.39,
            discount: 0.06,
            total: 2151.33
        }
    };

    // Handle Select button click to show modal with details
    document.querySelectorAll('.select-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var flightKey = this.getAttribute('data-flight');
            var d = flightDetails[flightKey] || flightDetails['af149'];
            var html = `
                <div class="flight-detail-summary mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <span class="fw-bold me-2">Selected Departure Flight</span>
                    </div>
                    <div class="mb-2">
                        <span class="fw-bold">${d.route}</span>
                    </div>
                    <div class="mb-2">
                        ${d.date} | ${d.time} | ${d.stops}
                    </div>
                    <button id="changeFlightBtn" class="btn btn-link p-0 mb-2" style="color:#38a169;font-weight:600;">Change Flight</button>
                    <div class="d-flex align-items-center mb-2">
                        <img src="${d.logo}" alt="${d.airline}" class="flight-detail-airline-logo">
                        <div>
                            <div class="fw-bold">${d.airline}</div>
                            <div class="text-muted">${d.flight_no}</div>
                            <div class="text-muted">${d.aircraft}</div>
                            <div class="text-muted">${d.fare}</div>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <div class="flight-detail-label">Departure</div>
                            <div class="flight-detail-value">${d.depart_time} | ${d.depart_date}</div>
                            <div class="flight-detail-value">${d.depart_airport}</div>
                            <div class="flight-detail-label">${d.depart_airport_full}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="flight-detail-label">Arrival</div>
                            <div class="flight-detail-value">${d.arrive_time} <span class="flight-dateplus">+1d</span> | ${d.arrive_date}</div>
                            <div class="flight-detail-value">${d.arrive_airport}</div>
                            <div class="flight-detail-label">${d.arrive_airport_full}</div>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <div class="flight-detail-label">Duration</div>
                            <div class="flight-detail-value">${d.duration}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="flight-detail-label">Baggage</div>
                            <div class="flight-detail-value">${d.baggage}</div>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <div class="flight-detail-label">${d.refundable}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="flight-detail-label">${d.reschedulable}</div>
                        </div>
                    </div>
                    <div class="flight-detail-label">${d.ticket_time}</div>
                </div>
                <div class="price-detail-card mb-3">
                    <div class="flight-detail-section-title mb-2">Price Detail</div>
                    <div class="price-detail-row">
                        <span>LOS</span>
                        <span>CDG</span>
                    </div>
                    <div class="price-detail-row">
                        <span>US$ ${d.price.toFixed(2)}</span>
                    </div>
                    <div class="price-detail-row">
                        <span>Total Discount</span>
                        <span class="text-success">-US$ ${d.discount.toFixed(2)}</span>
                    </div>
                    <div class="price-detail-row price-detail-total">
                        <span>Total Price</span>
                        <span>US$ ${d.total.toFixed(2)}</span>
                    </div>
                    <div class="price-detail-row">
                        <span>For 1 pax</span>
                        <span class="text-muted">Include Tax</span>
                    </div>
                </div>
                <div class="text-center">
                    <button class="checkout-btn">Checkout</button>
                </div>
            `;
            document.getElementById('flightDetailModalBody').innerHTML = html;
            feather.replace();

            // Attach event for Change Flight button
            setTimeout(function() {
                var changeBtn = document.getElementById('changeFlightBtn');
                if (changeBtn) {
                    changeBtn.onclick = function(e) {
                        e.preventDefault();
                        if (confirm('Are you sure you want to change your flight?')) {
                            // Hide modal and redirect to book-flight.php
                            var modal = bootstrap.Modal.getInstance(document.getElementById('flightDetailModal'));
                            modal.hide();
                            setTimeout(function() {
                                window.location.href = 'search-results.php';
                            }, 400);
                        }
                    }
                }
            }, 100);
        });
    });
</script>
</body>
</html>