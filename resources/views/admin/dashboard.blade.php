@extends('layouts.app')

@section('title', 'Welcome')

@section('content')
        <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: #6f42c1;
            color: white;
        }
        .card-metric {
            border-radius: 10px;
            padding: 20px;
            color: white;
        }
        .bg-gradient-red { background: linear-gradient(135deg, #ff7eb3, #ff758c); }
        .bg-gradient-blue { background: linear-gradient(135deg, #48c6ef, #6f86d6); }
        .bg-gradient-green { background: linear-gradient(135deg, #2ecc71, #27ae60); }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-2 sidebar p-3">
            <h4>Dashboard</h4>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link text-white" href="#">Overview</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="#">Charts</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="#">Tables</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="col-md-10 p-4">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card card-metric bg-gradient-red">
                        <h5>Weekly Sales</h5>
                        <h2>$15,000</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-metric bg-gradient-blue">
                        <h5>Weekly Orders</h5>
                        <h2>45,633</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-metric bg-gradient-green">
                        <h5>Visitors Online</h5>
                        <h2>95,741</h2>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row">
                <div class="col-md-6">
                    <canvas id="barChart"></canvas>
                </div>
                <div class="col-md-6">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>

            <!-- Table -->
            <div class="mt-4">
                <h5>Recent Tickets</h5>
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>Assignee</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Last Update</th>
                        <th>Tracking ID</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>David Grey</td>
                        <td>Fund not received</td>
                        <td><span class="badge bg-success">Done</span></td>
                        <td>Dec 5, 2024</td>
                        <td>WD-12345</td>
                    </tr>
                    <tr>
                        <td>Stella Johnson</td>
                        <td>High loading time</td>
                        <td><span class="badge bg-warning">Progress</span></td>
                        <td>Dec 12, 2024</td>
                        <td>WD-12346</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<script>
    const ctx1 = document.getElementById('barChart').getContext('2d');
    new Chart(ctx1, {
        type: 'bar',
        data: { labels: ['Jan', 'Feb', 'Mar', 'Apr'], datasets: [{ data: [10, 20, 30, 40], backgroundColor: ['red', 'blue', 'green', 'purple'] }] },
    });

    const ctx2 = document.getElementById('pieChart').getContext('2d');
    new Chart(ctx2, {
        type: 'pie',
        data: { labels: ['Search', 'Direct', 'Bookmark'], datasets: [{ data: [30, 30, 40], backgroundColor: ['cyan', 'magenta', 'yellow'] }] },
    });
</script>
</body>
</html>

@endsection