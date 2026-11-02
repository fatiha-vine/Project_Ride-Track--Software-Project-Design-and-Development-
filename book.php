
<?php
date_default_timezone_set('Asia/Dhaka');

require 'config.php';

$u = need();
sweep();

$id = (int)($_GET['trip'] ?? 0);
$from = (int)($_GET['from'] ?? 0);
$to = (int)($_GET['to'] ?? 0);

if ($id <= 0) {
    exit('Invalid trip ID.');
}

// Find trip information
$t = q(
    "SELECT
        t.*,
        r.from_city,
        r.to_city,
        r.fare,
        r.type,
        b.capacity,
        b.operator
     FROM trips t
     JOIN routes r ON r.id = t.route_id
     JOIN buses b ON b.id = t.bus_id
     WHERE t.id = ?",
    [$id]
)->fetch();

if (!$t) {
    exit('Trip not found. Please select another bus.');
}

if ($t['status'] === 'cancelled') {
    exit('This trip has been cancelled.');
}

// Compare departure and current time in Dhaka timezone
$departure = strtotime(
    $t['depart_date'] . ' ' . $t['depart_time']
);

if ($departure === false) {
    exit('Invalid trip departure date or time.');
}


// Route segment fare
$sf = null;

if ($from > 0 && $to > 0) {
    $sf = seg_fare($t['route_id'], $from, $to);

    if (!$sf) {
        exit('Invalid route stops. Please select your route again.');
    }
}

if ($sf) {
    $t['fare'] = $sf;
}

$fs = $sf ? $from : null;
$ts = $sf ? $to : null;

// Stop names
$sn = $sf
    ? q(
        'SELECT
            (SELECT name FROM stops WHERE id = ?) AS a,
            (SELECT name FROM stops WHERE id = ?) AS b',
        [$fs, $ts]
    )->fetch()
    : null;

// Student discount
$disc = (
    is_student($u) &&
    $t['type'] === 'local'
)
    ? round($t['fare'] * setting('student_pct', 50) / 100, 2)
    : 0;

$fare = max(0, $t['fare'] - $disc);

$err = '';
$local = ($t['type'] === 'local');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        chk();

        // Recheck departure time when submitting booking
        if (strtotime($t['depart_date'] . ' ' . $t['depart_time']) <= time()) {
            throw new Exception('Booking closed: this bus has already departed.');
        }

        $seat = $local
            ? null
            : (int)($_POST['seat'] ?? 0);

        $pdo->beginTransaction();

        // Lock trip row during booking
        q(
            'SELECT id FROM trips WHERE id = ? FOR UPDATE',
            [$id]
        );

        // Local bus capacity check
        if ($local) {
            $count = q(
                'SELECT COUNT(*) FROM bookings
                 WHERE trip_id = ? AND active = 1',
                [$id]
            )->fetchColumn();

            if ($count >= $t['capacity']) {
                throw new Exception('This bus is full.');
            }
        } else {
            if ($seat < 1 || $seat > $t['capacity']) {
                throw new Exception('Please choose a valid seat.');
            }

            $takenSeat = q(
                'SELECT id FROM bookings
                 WHERE trip_id = ?
                 AND seat_no = ?
                 AND active = 1',
                [$id, $seat]
            )->fetch();

            if ($takenSeat) {
                throw new Exception('This seat is already booked. Choose another seat.');
            }
        }

        // bKash online payment
        if (bk_on()) {

            q(
                "INSERT INTO bookings
                (
                    user_id,
                    trip_id,
                    seat_no,
                    fare,
                    discount,
                    status,
                    pay_method,
                    from_stop,
                    to_stop
                )
                VALUES (?, ?, ?, ?, ?, 'pending', 'bkash', ?, ?)",
                [
                    $u['id'],
                    $id,
                    $seat,
                    $fare,
                    $disc,
                    $fs,
                    $ts
                ]
            );

            $bid = $pdo->lastInsertId();

            $p = bk_create(
                $fare,
                "RT$bid",
                base() . "/bkash_callback.php?k=b&id=$bid"
            );

            if (!$p || empty($p['paymentID']) || empty($p['bkashURL'])) {
                throw new Exception('bKash is unavailable right now. Please try again.');
            }

            q(
                'UPDATE bookings SET pay_ref = ? WHERE id = ?',
                [$p['paymentID'], $bid]
            );

            $pdo->commit();

            header('Location: ' . $p['bkashURL']);
            exit;

        } else {

            // Demo payment mode
            $ref = strtoupper(trim($_POST['ref'] ?? ''));

            if (!preg_match('/^[A-Z0-9]{6,12}$/', $ref)) {
                throw new Exception(
                    'Enter a valid demo transaction ID (6–12 letters or digits).'
                );
            }

            q(
                "INSERT INTO bookings
                (
                    user_id,
                    trip_id,
                    seat_no,
                    fare,
                    discount,
                    status,
                    pay_method,
                    pay_ref,
                    from_stop,
                    to_stop
                )
                VALUES (?, ?, ?, ?, ?, 'confirmed', 'bkash', ?, ?, ?)",
                [
                    $u['id'],
                    $id,
                    $seat,
                    $fare,
                    $disc,
                    $ref,
                    $fs,
                    $ts
                ]
            );

            $bid = $pdo->lastInsertId();

            $pdo->commit();

            notify(
                $u['id'],
                $local
                    ? 'Local bus ticket confirmed.'
                    : "Booking confirmed: seat $seat."
            );

            header("Location: ticket.php?id=$bid");
            exit;
        }

    } catch (Throwable $x) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $err = $x->getMessage();
    }
}

// Get booked seats
$taken = array_column(
    q(
        'SELECT seat_no FROM bookings
         WHERE trip_id = ? AND active = 1',
        [$id]
    )->fetchAll(),
    'seat_no'
);

head('Book seat');
?>

<h2>
<?= e(
    $sn
        ? $sn['a'] . ' to ' . $sn['b']
        : $t['from_city'] . ' to ' . $t['to_city']
) ?>
</h2>

<p class="muted">
    <?= e($t['depart_date']) ?>
    at <?= substr($t['depart_time'], 0, 5) ?>
</p>

<p class="muted">
    Operator: <?= e($t['operator'] ?? '') ?>
</p>

<?php if ($err): ?>
    <p class="err"><?= e($err) ?></p>
<?php endif; ?>

<form method="post" class="card">

    <?= tok() ?>

    <?php if ($local): ?>

        <h3>Local bus</h3>

        <p class="muted">
            No seat reservation. Pay the fare and board any free seat, or stand.
        </p>

    <?php else: ?>

        <h3>Choose your seat</h3>

        <div class="seats">

            <?php for ($i = 1; $i <= $t['capacity']; $i++): ?>

                <label>
                    <input
                        type="radio"
                        name="seat"
                        value="<?= $i ?>"
                        <?= in_array($i, $taken) ? 'disabled' : '' ?>
                        required
                    >

                    <span><?= $i ?></span>
                </label>

            <?php endfor; ?>

        </div>

        <p id="sel" class="muted">
            No seat selected
        </p>

    <?php endif; ?>

    <h3>Fare</h3>

    <p>
        Original ৳<?= number_format($t['fare'], 2) ?>

        <?php if ($disc > 0): ?>
            · Student discount −৳<?= number_format($disc, 2) ?>
        <?php endif; ?>

        · <strong>
            Total ৳<?= number_format($fare, 2) ?>
        </strong>
    </p>

    <?php if ($local && !is_student($u)): ?>

        <p class="muted">
            Student?
            <a href="student.php">Verify your student ID</a>
            for the local bus discount.
        </p>

    <?php endif; ?>

    <h3>Payment method</h3>

    <label>
        Payment method

        <select name="method" id="method">
            <option value="bkash">bKash</option>
        </select>
    </label>

    <?php if (!bk_on()): ?>

        <label id="refbox">
            bKash transaction ID (demo mode)

            <input
                name="ref"
                placeholder="e.g. 8N7A6D5EE1"
                required
            >
        </label>

        <p class="muted">
            Demo mode: enter a sample transaction ID to test booking.
            This does not make a real bKash payment.
        </p>

    <?php else: ?>

        <p class="muted" id="refbox">
            You will be redirected to bKash to complete payment.
        </p>

    <?php endif; ?>

    <button class="btn">
        Pay and confirm booking
    </button>

</form>

<?php foot(); ?>
