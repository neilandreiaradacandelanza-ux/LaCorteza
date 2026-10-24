<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simple security check for admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../pages/login.php");
    exit();
}

require_once __DIR__ . '/../db/connect.php';

// NEW: Features that are pre-checked when adding a new package (edit this list as you like)
$default_features = [
    'Full access to Main Pool & Kiddie Pool',
    'Free use of tables & chairs (up to 30 pax)',
    'Free parking space inside venue',
];

$success = '';
$error = '';

// Handle Add / Update Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_package') {
        $id          = intval($_POST['package_id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $price       = floatval($_POST['price'] ?? 0);
        $badge       = trim($_POST['badge'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status      = trim($_POST['status'] ?? 'Active');

        // NEW: Checked checkboxes arrive as an array; join them back into the "A|B|C" format rates.php reads
        $inclusion_items = [];
        foreach ((array)($_POST['inclusions'] ?? []) as $item) {
            $item = trim(str_replace('|', '', $item)); // a stray | would split one feature into two
            if ($item !== '' && !in_array($item, $inclusion_items, true)) {
                $inclusion_items[] = $item;
            }
        }
        $inclusions = implode('|', $inclusion_items);

        if (empty($title) || $price <= 0) {
            $error = "Package title and a valid price are required.";
        } else {
            if ($id > 0) {
                // Update existing package
                $stmt = mysqli_prepare($connect2db, "UPDATE packages SET title = ?, price = ?, badge = ?, description = ?, inclusions = ?, status = ? WHERE id = ?");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "sdssssi", $title, $price, $badge, $description, $inclusions, $status, $id);
                }
            } else {
                // Insert new package
                $stmt = mysqli_prepare($connect2db, "INSERT INTO packages (title, price, badge, description, inclusions, status) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "sdssss", $title, $price, $badge, $description, $inclusions, $status);
                }
            }

            if ($stmt && mysqli_stmt_execute($stmt)) {
                $success = "Package saved successfully!";
            } else {
                $error = "Database error: " . mysqli_error($connect2db);
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['package_id'] ?? 0);
        mysqli_query($connect2db, "DELETE FROM packages WHERE id = $id");
        $success = "Package deleted successfully.";
    } elseif ($action === 'delete_feature') {
        // NEW (delete): Remove one feature from every package that includes it
        $feature_to_delete = trim($_POST['feature'] ?? '');
        $updated_count = 0;

        if ($feature_to_delete !== '') {
            $all = mysqli_query($connect2db, "SELECT id, inclusions FROM packages");
            $updateStmt = mysqli_prepare($connect2db, "UPDATE packages SET inclusions = ? WHERE id = ?");

            while ($all && $updateStmt && ($pkg = mysqli_fetch_assoc($all))) {
                $kept = [];
                $found = false;
                foreach (explode('|', $pkg['inclusions'] ?? '') as $item) {
                    $item = trim($item);
                    if ($item === '') continue;
                    if ($item === $feature_to_delete) {
                        $found = true;
                        continue;
                    }
                    $kept[] = $item;
                }

                if ($found) {
                    $new_inclusions = implode('|', $kept);
                    $pkg_id = intval($pkg['id']);
                    mysqli_stmt_bind_param($updateStmt, "si", $new_inclusions, $pkg_id);
                    mysqli_stmt_execute($updateStmt);
                    $updated_count++;
                }
            }
            if ($updateStmt) {
                mysqli_stmt_close($updateStmt);
            }
        }

        $success = 'Removed "' . $feature_to_delete . '" from ' . $updated_count . ' package(s).';
    }
}

// Fetch all packages
$packages_result = mysqli_query($connect2db, "SELECT * FROM packages ORDER BY id ASC");
$packages = [];
if ($packages_result) {
    while ($row = mysqli_fetch_assoc($packages_result)) {
        $packages[] = $row;
    }
}

// NEW: Checklist options = defaults + every feature already used by any package
// NEW (delete): Also count how many packages use each feature, for the delete confirmation
$feature_options = $default_features;
$feature_usage = [];
foreach ($packages as $pkg) {
    foreach (explode('|', $pkg['inclusions'] ?? '') as $feature) {
        $feature = trim($feature);
        if ($feature === '') continue;
        if (!in_array($feature, $feature_options, true)) {
            $feature_options[] = $feature;
        }
        $feature_usage[$feature] = ($feature_usage[$feature] ?? 0) + 1;
    }
}

// NEW: Show "X ..." items as ❌ (not included), everything else as ✅
function feature_label($feature) {
    if (stripos($feature, 'X ') === 0) {
        return '❌ ' . htmlspecialchars(substr($feature, 2));
    }
    return '✅ ' . htmlspecialchars($feature);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rates &amp; Packages - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    
    <link rel="icon" type="image/png" href="../css/images/logo.png">
</head>
<body>

<!-- NAVBAR (LOGO & LOGOUT ONLY) -->
    <header class="navbar navbar-centered">
        <div class="nav-brand">
            <a href="dashboard.php" class="logo-link">
                <img src="../css/images/logo.png" alt="La Corteza Logo" class="brand-logo-img">
            </a>
        </div>
        <!-- Hamburger: shown on small screens, opens the links and account actions -->
        <button type="button" class="nav-toggle" aria-label="Open menu" aria-expanded="false" onclick="const bar = this.closest('.navbar'); const open = bar.classList.toggle('nav-open'); this.setAttribute('aria-expanded', open); this.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');">
            <span></span><span></span><span></span>
        </button>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="packages.php" class="active">Rates &amp; Packages</a>
             <a href="schedules.php"> Schedules</a>
        </nav>
        <div class="nav-actions">
            <span class="user-id">👤 Administrator (<?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>)</span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>
    <div class="dashboard-container">

        <!-- HEADER -->
        <div class="welcome-banner">
            <div>
                <h1>Manage <span>Rates &amp; Packages</span></h1>
                <p>Add, edit, or remove the packages customers can book.</p>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="success" style="color: green; margin-bottom: 15px; padding: 10px; background: #e8f5e9; border-radius: 4px;"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error" style="color: red; margin-bottom: 15px; padding: 10px; background: #ffebee; border-radius: 4px;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- ADD / EDIT PACKAGE FORM -->
        <div class="booking-card">
            <span class="card-title-badge" id="form_badge">New package</span>
            <h3 id="form_heading">Add / Edit Package</h3>
            <p class="card-desc">Fill in the details below. Click Edit on a package to load it here.</p>

            <form action="packages.php" method="POST">
                <input type="hidden" name="action" value="save_package">
                <input type="hidden" name="package_id" id="pkg_id">

                <div class="form-grid-2 margin-bottom-md">
                    <div class="form-group">
                        <label for="pkg_title">Package Title</label>
                        <input type="text" class="form-input" name="title" id="pkg_title" required placeholder="e.g. Exclusive Resort Use">
                    </div>
                    <div class="form-group">
                        <label for="pkg_price">Price (₱)</label>
                        <input type="number" class="form-input" step="0.01" name="price" id="pkg_price" required placeholder="10000.00">
                    </div>
                </div>

                <div class="form-grid-2 margin-bottom-md">
                    <div class="form-group">
                        <label for="pkg_badge">Badge (Optional)</label>
                        <input type="text" class="form-input" name="badge" id="pkg_badge" placeholder="e.g. Most Popular">
                    </div>
                    <div class="form-group">
                        <label for="pkg_status">Status</label>
                        <select name="status" id="pkg_status" class="form-input">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group margin-bottom-md">
                    <label for="pkg_desc">Description</label>
                    <textarea name="description" id="pkg_desc" rows="2" class="form-input" placeholder="Short summary of the package..."></textarea>
                </div>

                <!-- NEW: Inclusions checklist (replaces the pipe-separated textarea) -->
                <div class="form-group margin-bottom-md">
                    <label>Inclusions (check everything this package includes)</label>
                    <div class="feature-list" id="feature_list">
                        <?php foreach ($feature_options as $feature): ?>
                            <!-- NEW (delete): row = checkbox label + delete button (button can't be inside the label) -->
                            <div class="feature-option">
                                <label class="feature-check">
                                    <input type="checkbox" name="inclusions[]" value="<?php echo htmlspecialchars($feature); ?>" <?php echo in_array($feature, $default_features, true) ? 'checked' : ''; ?>>
                                    <span><?php echo feature_label($feature); ?></span>
                                </label>
                                <?php if (!in_array($feature, $default_features, true)): ?>
                                    <button type="button" class="feature-delete" title="Remove this feature"
                                            data-feature="<?php echo htmlspecialchars($feature); ?>"
                                            data-usage="<?php echo $feature_usage[$feature] ?? 0; ?>"
                                            onclick="deleteFeature(this)">✕</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="feature-add">
                        <input type="text" id="new_feature" class="form-input" placeholder="Add a custom feature (start with &quot;X &quot; for not included)">
                        <button type="button" class="btn-outline-sm" onclick="addCustomFeature()">+ Add</button>
                    </div>
                </div>

                <div class="form-grid-2">
                    <button type="submit" class="btn-primary btn-full-width">Save Package</button>
                    <button type="button" class="btn-outline-sm btn-full-width" onclick="resetForm()">Cancel / Clear</button>
                </div>
            </form>

            <!-- NEW (delete): Separate hidden form, because forms can't be nested inside the package form -->
            <form id="delete_feature_form" action="packages.php" method="POST" style="display: none;">
                <input type="hidden" name="action" value="delete_feature">
                <input type="hidden" name="feature" id="delete_feature_value">
            </form>
        </div>

        <!-- EXISTING PACKAGES -->
        <div class="section-header">
            <h2>Existing Packages List</h2>
            <p>All packages currently in the system.</p>
        </div>

        <div class="reservations-list">
            <?php foreach ($packages as $row): ?>
                <div class="reservation-card">
                    <div class="res-card-header">
                        <div>
                            <span class="res-id">Package #<?php echo $row['id']; ?></span>
                            <h3 class="res-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                        </div>
                        <span class="status-badge <?php echo ($row['status'] ?? 'Active') === 'Active' ? 'status-confirmed' : 'status-cancelled'; ?>">
                            <?php echo htmlspecialchars($row['status'] ?? 'Active'); ?>
                        </span>
                    </div>

                    <div class="res-card-body">
                        <div class="res-info-item">
                            <span class="info-label">Price</span>
                            <span class="info-value price">₱<?php echo number_format($row['price'], 2); ?></span>
                        </div>
                        <div class="res-info-item">
                            <span class="info-label">Badge</span>
                            <span class="info-value"><?php echo htmlspecialchars($row['badge'] ?: '-'); ?></span>
                        </div>
                        <div class="res-info-item">
                            <span class="info-label">Description</span>
                            <span class="info-value"><?php echo htmlspecialchars($row['description'] ?: '-'); ?></span>
                        </div>
                    </div>

                    <div class="res-card-footer">
                        <span class="res-created">Edit details or remove this package</span>
                        <div class="res-actions">
                            <button type="button" class="btn-secondary-sm" style="border: none; cursor: pointer;" onclick='editPackage(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES); ?>)'>Edit</button>
                            <form action="packages.php" method="POST" onsubmit="return confirm('Delete this package?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="package_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="logout-btn" style="background: transparent; cursor: pointer;">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($packages)): ?>
                <div class="empty-state-card">
                    <h3>No packages yet</h3>
                    <p>Use the form above to add your first package.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <script>
        // NEW: Same defaults as the PHP list, used when the form is cleared
        const DEFAULT_FEATURES = <?php echo json_encode($default_features, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

        // NEW: Same ✅ / ❌ display rule as rates.php
        function featureLabel(feature) {
            return feature.toUpperCase().startsWith('X ') ? '❌ ' + feature.substring(2) : '✅ ' + feature;
        }

        // NEW: Check a feature's box, creating the checkbox if it isn't in the list yet
        function checkFeature(feature) {
            const boxes = document.querySelectorAll('#feature_list input[type="checkbox"]');
            for (const box of boxes) {
                if (box.value === feature) {
                    box.checked = true;
                    return;
                }
            }

            // NEW (delete): build the same row structure as the PHP loop (checkbox label + delete button)
            const row = document.createElement('div');
            row.className = 'feature-option';

            const label = document.createElement('label');
            label.className = 'feature-check';

            const box = document.createElement('input');
            box.type = 'checkbox';
            box.name = 'inclusions[]';
            box.value = feature;
            box.checked = true;

            const text = document.createElement('span');
            text.textContent = featureLabel(feature);

            const del = document.createElement('button');
            del.type = 'button';
            del.className = 'feature-delete';
            del.title = 'Remove this feature';
            del.textContent = '✕';
            del.dataset.feature = feature;
            del.dataset.usage = '0'; // not saved in any package yet
            del.onclick = function () { deleteFeature(del); };

            label.appendChild(box);
            label.appendChild(text);
            row.appendChild(label);
            row.appendChild(del);
            document.getElementById('feature_list').appendChild(row);
        }

        // NEW (delete): Unsaved features are just removed; saved ones are removed from every package after confirming
        function deleteFeature(button) {
            const feature = button.dataset.feature;
            const usage = parseInt(button.dataset.usage, 10) || 0;

            if (usage === 0) {
                button.closest('.feature-option').remove();
                return;
            }

            const message = 'Remove "' + feature + '" from ' + usage + ' package(s)?\n\nThis updates those packages right away and cannot be undone.';
            if (confirm(message)) {
                document.getElementById('delete_feature_value').value = feature;
                document.getElementById('delete_feature_form').submit();
            }
        }

        // NEW: Uncheck everything, then check only the given features
        function setCheckedFeatures(features) {
            document.querySelectorAll('#feature_list input[type="checkbox"]').forEach(box => box.checked = false);
            features.forEach(checkFeature);
        }

        // NEW: "+ Add" button for custom features
        function addCustomFeature() {
            const input = document.getElementById('new_feature');
            const feature = input.value.replace(/\|/g, '').trim();
            if (feature === '') return;
            checkFeature(feature);
            input.value = '';
        }

        // NEW: Pressing Enter in the custom feature box adds it instead of submitting the form
        document.getElementById('new_feature').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addCustomFeature();
            }
        });

        function editPackage(pkg) {
            document.getElementById('pkg_id').value = pkg.id;
            document.getElementById('pkg_title').value = pkg.title;
            document.getElementById('pkg_price').value = pkg.price;
            document.getElementById('pkg_badge').value = pkg.badge || '';
            document.getElementById('pkg_desc').value = pkg.description || '';
            document.getElementById('pkg_status').value = pkg.status || 'Active';

            // NEW: Split "A|B|C" from the database and pre-check those boxes
            const saved = (pkg.inclusions || '').split('|').map(f => f.trim()).filter(f => f !== '');
            setCheckedFeatures(saved);

            document.getElementById('form_badge').textContent = 'Editing #' + pkg.id;
            window.scrollTo({top: 0, behavior: 'smooth'});
        }

        function resetForm() {
            document.getElementById('pkg_id').value = '';
            document.getElementById('pkg_title').value = '';
            document.getElementById('pkg_price').value = '';
            document.getElementById('pkg_badge').value = '';
            document.getElementById('pkg_desc').value = '';
            document.getElementById('pkg_status').value = 'Active';
            document.getElementById('new_feature').value = '';
            setCheckedFeatures(DEFAULT_FEATURES); // NEW: back to the default checked items
            document.getElementById('form_badge').textContent = 'New package';
        }
    </script>

     <!-- FOOTER -->
    <footer style="margin-top: 60px; padding: 24px 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 13px; color: #666; background-color: #fff;">
        <p>&copy; <?php echo date('Y'); ?> La Corteza Pool & Venue. All rights reserved.</p>
    </footer>   
</body>
</html>
