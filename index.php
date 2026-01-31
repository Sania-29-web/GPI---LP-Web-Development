<?php
include 'db.php';

/* ---------- INPUTS ---------- */
$search = isset($_GET['query']) ? trim($_GET['query']) : "";
$state_filter = isset($_GET['state']) ? trim($_GET['state']) : "";

/* ---------- INDIAN STATES (28) ---------- */
/* ---------- DYNAMIC STATES FETCH ---------- */
// This query gets unique states that actually exist in the 'colleges' table
$state_query = "SELECT DISTINCT state FROM colleges WHERE state IS NOT NULL AND state != '' ORDER BY state ASC";
$state_result = mysqli_query($conn, $state_query);

$states = [];
while ($row = mysqli_fetch_assoc($state_result)) {
    $states[] = $row['state'];
}

/* ---------- CITY FILTER ---------- */
$city_filter = isset($_GET['city']) ? trim($_GET['city']) : "";

/* ---------- FETCH CITIES BASED ON STATE ---------- */
$cities = [];
if ($state_filter !== "") {
    $city_query = "
        SELECT DISTINCT city 
        FROM colleges 
        WHERE state = '".mysqli_real_escape_string($conn, $state_filter)."'
        AND city IS NOT NULL AND city != ''
        ORDER BY city ASC
    ";
    $city_result = mysqli_query($conn, $city_query);
    while ($c = mysqli_fetch_assoc($city_result)) {
        $cities[] = $c['city'];
    }
}

/* ---------- FUZZY SEARCH ---------- */
function isSimilar($search, $target) {
    if ($search === "") return true;
    $search = strtolower($search);
    $target = strtolower($target);

    return (
        strpos($target, $search) !== false ||
        levenshtein($search, $target) <= 3 ||
        metaphone($search) === metaphone($target)
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>College Discovery Portal</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="style.css">
<link rel="stylesheet"
 href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

<header>
  <div class="logo">Cloud<span>Counselage</span></div>
</header>

<div class="hero">
    <h1>Discover Your Future</h1>
    <p>Find the finest educational institutions across India with verified data.</p>

  <form method="GET" class="search-container">
    <!-- SEARCH -->
    <div class="search-box">
        <i class="fa fa-search"></i>
        <input type="text" name="query"
               placeholder="Search college or course"
               value="<?php echo htmlspecialchars($search); ?>">
    </div>

    <div class="location-filters">

    <!-- STATE -->
    <select name="state" onchange="this.form.submit()">
        <option value="">All States</option>
        <?php foreach ($states as $s) { ?>
            <option value="<?php echo $s; ?>" <?php if ($state_filter === $s) echo 'selected'; ?>>
                <?php echo $s; ?>
            </option>
        <?php } ?>
    </select>

    <!-- CITY -->
    <?php if ($state_filter !== "") { ?>
        <select name="city" onchange="this.form.submit()">
            <option value="">All Cities</option>
            <?php foreach ($cities as $city) { ?>
                <option value="<?php echo $city; ?>" <?php if ($city_filter === $city) echo 'selected'; ?>>
                    <?php echo $city; ?>
                </option>
            <?php } ?>
        </select>
    <?php } ?>

</div>
</form>
</div>

<div class="container">
<div class="results-grid">
<?php
$sql = "SELECT * FROM colleges WHERE 1";

if ($state_filter !== "") {
    $sql .= " AND state = '".mysqli_real_escape_string($conn, $state_filter)."'";
}

if ($city_filter !== "") {
    $sql .= " AND city = '".mysqli_real_escape_string($conn, $city_filter)."'";
}

$result = mysqli_query($conn, $sql);
$found = false;

while ($row = mysqli_fetch_assoc($result)) {

    if (
        isSimilar($search, $row['name']) ||
        isSimilar($search, $row['courses'])
    ) {
        $found = true;

        $link = trim($row['website_link']);
        if ($link === "" || $link === NULL) {
            $link = "https://www.google.com/search?q=" .
                    urlencode($row['name'] . " college");
        }
?>
<div class="card">
    <h3><?php echo htmlspecialchars($row['name']); ?></h3>

    <div class="info">
        <i class="fa fa-location-dot"></i>
        <?php echo htmlspecialchars($row['city']); ?>, 
        <?php echo htmlspecialchars($row['state']); ?>
    </div>

    <div class="info">
    <i class="fa fa-phone"></i>
    <a href="tel:<?php echo $row['contact']; ?>" style="text-decoration:none; color:inherit;">
        <?php echo htmlspecialchars($row['contact'] ?: 'N/A'); ?>
    </a>
</div>

<div class="info">
    <i class="fa fa-envelope"></i>
    <a href="mailto:<?php echo $row['email']; ?>" style="text-decoration:none; color:inherit;">
        <?php echo htmlspecialchars($row['email'] ?: 'N/A'); ?>
    </a>
</div>
    <div class="tag-container">
        <?php foreach (explode(',', $row['courses']) as $c) { ?>
            <span class="tag"><?php echo trim($c); ?></span>
        <?php } ?>
    </div>

    <a class="visit-btn" target="_blank" href="<?php echo $link; ?>">
        View Full Details
    </a>
</div>
<?php } } ?>

<?php if (!$found) { ?>
<div class="no-data">
    <i class="fa fa-building-columns"></i>
    <h3>No colleges found</h3>
    <p>Try changing the state or search keyword.</p>
</div>
<?php } ?>

</div>
</div>

<footer>
    <div class="footer-logo">Cloud<span>Counselage</span></div>
    <div class="footer-note">
        © 2026 College Discovery Portal · Verified Academic Data
    </div>
</footer>

</body>
</html>