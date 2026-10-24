<?php
require_once __DIR__ . '/../db/connect.php';

function register($data, $connect2db, &$result, &$resultClass)
{
    $firstname = trim($data['firstname'] ?? '');
    $lastname  = trim($data['lastname'] ?? '');
    $email     = trim($data['email'] ?? '');
    $password  = $data['password'] ?? '';
    $username  = trim($data['username'] ?? $email);
    $full_name = trim("$firstname $lastname");

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($connect2db, "
        INSERT INTO accounts (firstname, lastname, full_name, username, email, password, confirm_password, role)
        VALUES (?, ?, ?, ?, ?, ?, '', 'customer')
    ");
    
    if (!$stmt) {
        $resultClass = "error";
        $result = mysqli_error($connect2db);
        return;
    }

    mysqli_stmt_bind_param($stmt, "ssssss", $firstname, $lastname, $full_name, $username, $email, $hashedPassword);

    if (!mysqli_stmt_execute($stmt)) {
        $resultClass = "error";
        $result = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        return;
    }

    $account_id = mysqli_insert_id($connect2db);
    mysqli_stmt_close($stmt);

    $stmtCustomer = mysqli_prepare($connect2db, "
        INSERT INTO customer (account_id, firstname, lastname, full_name, username, email, password)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    if ($stmtCustomer) {
        mysqli_stmt_bind_param($stmtCustomer, "issssss", $account_id, $firstname, $lastname, $full_name, $username, $email, $hashedPassword);
        mysqli_stmt_execute($stmtCustomer);
        mysqli_stmt_close($stmtCustomer);
    }

    $resultClass = "success";
    $result = "Registered Successfully";
}

function login($data, $connect2db, &$result, &$resultClass)
{
    $login_input = trim($data['login_input'] ?? '');
    $password    = $data['password'] ?? '';

    $stmt = mysqli_prepare($connect2db, "
        SELECT id, firstname, lastname, email, password, role
        FROM accounts
        WHERE username = ? OR email = ?
        LIMIT 1
    ");

    if (!$stmt) {
        $resultClass = "error";
        $result = mysqli_error($connect2db);
        return;
    }

    mysqli_stmt_bind_param($stmt, "ss", $login_input, $login_input);
    mysqli_stmt_execute($stmt);
    $query_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($query_result) === 0) {
        $resultClass = "error";
        $result = "Account not found";
        mysqli_stmt_close($stmt);
        return;
    }

    $row = mysqli_fetch_assoc($query_result);
    mysqli_stmt_close($stmt);

    if (!password_verify($password, $row['password'])) {
        $resultClass = "error";
        $result = "Invalid password";
        return;
    }

    $userRole = strtolower($row['role'] ?? 'customer');

    if ($userRole === 'admin') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id']        = $row['id'];
        $_SESSION['admin_name']      = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')) ?: 'Admin';
        
        // Direct route to Admin Dashboard in ../admin/
        header("Location: ../admin/dashboard.php");
        exit();
    } else {
        $_SESSION['user_id']   = $row['id'];
        $_SESSION['full_name'] = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
        $_SESSION['user']      = [
            'id'        => $row['id'],
            'firstname' => $row['firstname'],
            'lastname'  => $row['lastname'],
            'email'     => $row['email']
        ];

        // Route to Customer Dashboard in pages/
        header("Location: dashboard.php");
        exit();
    }
}

function book_reservation($data, $connect2db, &$result, &$resultClass)
{
    $full_name        = trim($data['full_name'] ?? '');
    $email            = trim($data['email'] ?? '');
    $phone            = trim($data['phone'] ?? '');
    $reservation_date = trim($data['reservation_date'] ?? '');
    $time_slot        = trim($data['shift'] ?? '');
    $guest_count      = intval($data['guest_count'] ?? 0);
    $package_type     = trim($data['package_type'] ?? '');

    // Only accept the shifts offered on the booking form, so the overlap checks below can't be bypassed
    $whole_day = '24 Hours Exclusive';
    $allowed_shifts = ['Day Shift (8:00 AM - 5:00 PM)', 'Night Shift (6:00 PM - 12:00 MN)', $whole_day];
    if (!in_array($time_slot, $allowed_shifts, true)) {
        $resultClass = "error";
        $result = "Please choose a valid shift.";
        return;
    }

    // 0. CHECK IF DATE/SHIFT IS BLOCKED BY ADMIN (a 24-hour booking is blocked by any blockout on that date)
    $stmtSched = mysqli_prepare($connect2db, "
        SELECT reason FROM schedules
        WHERE blocked_date = ?
        AND (time_slot = 'All Shifts' OR time_slot = ? OR ? = '$whole_day')
        LIMIT 1
    ");
    if ($stmtSched) {
        mysqli_stmt_bind_param($stmtSched, "sss", $reservation_date, $time_slot, $time_slot);
        mysqli_stmt_execute($stmtSched);
        $sched_res = mysqli_stmt_get_result($stmtSched);

        if ($sched_res && mysqli_num_rows($sched_res) > 0) {
            $blocked_data = mysqli_fetch_assoc($sched_res);
            $reason = htmlspecialchars($blocked_data['reason']);
            $resultClass = "error";
            $result = "Sorry, the resort is unavailable on " . htmlspecialchars($reservation_date) . " (" . htmlspecialchars($time_slot) . "). Reason: " . $reason;
            mysqli_stmt_close($stmtSched);
            return;
        }
        mysqli_stmt_close($stmtSched);
    }

    // 1. Booking requires a logged-in customer (book-now.php redirects guests to login)
    $user_id = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;

    if (!$user_id) {
        $resultClass = "error";
        $result = "Please log in to book a reservation.";
        return;
    }

    // Serialize bookings so two customers submitting at the same moment can't both pass the conflict check
    mysqli_query($connect2db, "SELECT GET_LOCK('lacorteza_booking', 10)");

    // 2. CHECK FOR CONFLICTING BOOKINGS (a 24-hour booking overlaps both the day and night shifts)
    $stmtCheck = mysqli_prepare($connect2db, "
        SELECT id FROM reservations
        WHERE reservation_date = ?
          AND (time_slot = ? OR time_slot = '$whole_day' OR ? = '$whole_day')
          AND status IN ('Pending', 'Confirmed', 'Paid')
        LIMIT 1
    ");

    if ($stmtCheck) {
        mysqli_stmt_bind_param($stmtCheck, "sss", $reservation_date, $time_slot, $time_slot);
        mysqli_stmt_execute($stmtCheck);
        $conflict_result = mysqli_stmt_get_result($stmtCheck);

        if ($conflict_result && mysqli_num_rows($conflict_result) > 0) {
            $resultClass = "error";
            $result = "Sorry, this date and shift is already booked or pending review. Please choose another schedule.";
            mysqli_stmt_close($stmtCheck);
            mysqli_query($connect2db, "SELECT RELEASE_LOCK('lacorteza_booking')");
            return;
        }
        mysqli_stmt_close($stmtCheck);
    }

    // 3. Compute total amount
    $total_amount = 0.00;
    $stmtPkg = mysqli_prepare($connect2db, "SELECT price FROM packages WHERE title = ? LIMIT 1");
    if ($stmtPkg) {
        mysqli_stmt_bind_param($stmtPkg, "s", $package_type);
        mysqli_stmt_execute($stmtPkg);
        $pkg_query = mysqli_stmt_get_result($stmtPkg);

        if ($pkg_query && mysqli_num_rows($pkg_query) > 0) {
            $total_amount = floatval(mysqli_fetch_assoc($pkg_query)['price']);
        } else {
            if (strpos($package_type, '10,000') !== false || $package_type === 'Day / Night Shift Exclusive') {
                $total_amount = 10000.00;
            } elseif (strpos($package_type, '18,000') !== false || $package_type === '24 Hours Exclusive Stay') {
                $total_amount = 18000.00;
            }
        }
        mysqli_stmt_close($stmtPkg);
    }

    $status = 'Pending';

    // 4. Insert reservation into database (now includes phone_number)
    $stmtRes = mysqli_prepare($connect2db, "
        INSERT INTO reservations (user_id, phone_number, reservation_date, time_slot, guest_count, package_type, total_amount, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if ($stmtRes) {
        mysqli_stmt_bind_param($stmtRes, "isssisds", $user_id, $phone, $reservation_date, $time_slot, $guest_count, $package_type, $total_amount, $status);

        if (!mysqli_stmt_execute($stmtRes)) {
            $resultClass = "error";
            $result = "Failed to submit reservation: " . mysqli_stmt_error($stmtRes);
            mysqli_stmt_close($stmtRes);
            mysqli_query($connect2db, "SELECT RELEASE_LOCK('lacorteza_booking')");
            return;
        }
        mysqli_stmt_close($stmtRes);
    }
    mysqli_query($connect2db, "SELECT RELEASE_LOCK('lacorteza_booking')");

    $resultClass = "success";
    $result = "Reservation submitted successfully!";
    
    header("Location: reservation.php?msg=booked");
    exit();
}
