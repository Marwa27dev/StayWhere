<?php
session_start();
require_once __DIR__ . '/config.php';
$location = input($_GET, 'location');
$arrival = input($_GET, 'check_in');
$departure = input($_GET, 'check_out');
$guestsValue = input($_GET, 'guests');
$budget = input($_GET, 'budget');
$sort = input($_GET, 'sort');
$guests = guest_count($guestsValue);
$error = null;
if (($arrival !== '' || $departure !== '') && booking_nights($arrival, $departure) === null) {
    $error = 'Choisissez une arrivée à partir d’aujourd’hui et un départ après l’arrivée. La durée maximale est de 90 nuits.';
} elseif ($guestsValue !== '' && !$guests) {
    $error = 'Indiquez un nombre de voyageurs supérieur à zéro.';
} elseif ($budget !== '' && !guest_count($budget)) {
    $error = 'Indiquez un budget par nuit supérieur à zéro.';
}
$stays = $error ? [] : catalog($pdo, $_SESSION['user']['id'] ?? 0, $location, $arrival, $departure, $guests, guest_count($budget), $sort);
$pageTitle = 'Rechercher un logement';
require __DIR__ . '/headers/head.php';
?>
<main class="site-wrap page-stack">
  <div class="section-heading">
    <div><p class="eyebrow">TROUVER VOTRE SÉJOUR</p><h1><?= $location !== '' ? 'Séjours à ' . e($location) : 'Explorer les logements' ?></h1></div>
  </div>
  <?php require __DIR__ . '/headers/search-form.php'; ?>
  <div class="results-bar">
    <p><strong><?= count($stays) ?></strong> logement<?= count($stays) > 1 ? 's' : '' ?> trouvé<?= count($stays) > 1 ? 's' : '' ?></p>
    <form class="filter-form" action="search.php" method="get">
      <?php foreach (['location' => $location, 'check_in' => $arrival, 'check_out' => $departure, 'guests' => $guestsValue] as $name => $value): ?>
        <input type="hidden" name="<?= $name ?>" value="<?= e($value) ?>">
      <?php endforeach; ?>
      <label>Budget max / nuit <input type="number" name="budget" min="1" value="<?= e($budget) ?>" placeholder="<?= e(STAY_CURRENCY) ?>"></label>
      <label>Trier <select name="sort"><option value="">Plus récents</option><option value="asc" <?= $sort === 'asc' ? 'selected' : '' ?>>Prix croissant</option><option value="desc" <?= $sort === 'desc' ? 'selected' : '' ?>>Prix décroissant</option></select></label>
      <button class="button outline" type="submit">Appliquer</button>
    </form>
  </div>
  <?php if ($error): ?><p class="notice error" role="alert"><?= e($error) ?></p>
  <?php elseif (!$stays): ?><div class="empty-state"><?= icon('search') ?><h2>Aucun séjour trouvé</h2><p>Essayez une autre destination, d’autres dates ou un budget différent.</p><a class="button outline" href="search.php">Réinitialiser la recherche</a></div>
  <?php else: ?><div class="stay-grid"><?php foreach ($stays as $cardIndex => $stay) require __DIR__ . '/headers/card.php'; ?></div><?php endif; ?>
</main>
<?php require __DIR__ . '/headers/footer.php'; ?>
</body>
</html>
