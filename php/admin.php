<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISJ Admin - Control Panel</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-top">
                <h2 class="brand">ISJ Admin</h2>
            </div>
            
            <nav class="sidebar-nav">
                <button class="nav-btn active" onclick="switchTab('home')"><span>🏠</span> Home</button>
                <button class="nav-btn" onclick="switchTab('docs')"><span>📂</span> Manage Docs</button>
                <button class="nav-btn" onclick="switchTab('users')"><span>👥</span> Manage Users</button>
            </nav>

            <div class="sidebar-footer">
                <a href="logout.php" class="nav-btn logout"><span>🚪</span> Logout</a>
            </div>
        </aside>

        <div class="main-layout">
            <header class="admin-header">
                <div class="header-search">
                    <input type="text" placeholder="Search system...">
                </div>
                <div class="admin-profile">
                    <span class="role-badge">Super Admin</span>
                    <div class="avatar">JD</div>
                </div>
            </header>

            <main class="content-body">
                
                <section id="tab-home" class="admin-tab">
                    <h2 class="page-title">Dashboard Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-info"><h3>124</h3><p>Total Users</p></div>
                            <div class="stat-icon">👤</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-info"><h3>540</h3><p>Total Documents</p></div>
                            <div class="stat-icon">📄</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-info"><h3>12</h3><p>Pending Review</p></div>
                            <div class="stat-icon">⏳</div>
                        </div>
                    </div>
                </section>

                <section id="tab-docs" class="admin-tab" style="display:none;">
                    <h2 class="page-title">Manage Documents</h2>
                    <div class="data-table-container">
                        <table class="isj-table">
                            <thead>
                                <tr><th>Title</th><th>Department</th><th>Status</th><th>Action</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>Syllabus_L3_ISJ.pdf</td><td>Cycles</td><td><span class="status-badge">Active</span></td><td><button class="btn-edit">Edit</button></td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!--<section id="tab-users" class="admin-tab" style="display:none;">
                    <h2 class="page-title">Manage Users</h2>
                    <div class="data-table-container">
                        <table class="isj-table">
                            <thead>
                                <tr><th>Name</th><th>Email</th><th>Role</th><th>Action</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>Jean Dupont</td><td>j.dupont@isj.cm</td><td>Student</td><td><button class="btn-edit">Modify</button></td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>-->
                <section id="tab-users" class="admin-tab" style="display:none;">
    <div class="page-header-flex">
        <h2 class="page-title">User Management</h2>
        <button class="btn-create" onclick="openUserModal()"><span>+</span> Create New User</button>
    </div>

    <div class="data-table-container">
        <table class="isj-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Jean Dupont</td>
                    <td>Student</td>
                    <td><span class="status-pill active">Active</span></td>
                    <td class="action-cell">
                        <button class="btn-icon edit" title="Modify">✏️</button>
                        <button class="btn-icon toggle" title="Deactivate">🚫</button>
                        <button class="btn-icon delete" title="Delete">🗑️</button>
                    </td>
                </tr>
                <tr>
                    <td>Marie Sali</td>
                    <td>Teacher</td>
                    <td><span class="status-pill inactive">Inactive</span></td>
                    <td class="action-cell">
                        <button class="btn-icon edit">✏️</button>
                        <button class="btn-icon toggle" title="Activate">✅</button>
                        <button class="btn-icon delete">🗑️</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
            </main>

            <footer class="admin-footer">
                <p>Institut Saint Jean © 2026 | Document Management System v1.0</p>
            </footer>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.admin-tab').forEach(tab => tab.style.display = 'none');
            // Show selected tab
            document.getElementById('tab-' + tabName).style.display = 'block';
            // Update active button styling
            document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('active'));
            event.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>