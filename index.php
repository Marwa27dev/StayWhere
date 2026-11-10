<?php
session_start();
require_once __DIR__ . '/config.php';
$stays = catalog($pdo, $_SESSION['user']['id'] ?? 0, limit: 8);
$pageTitle = 'Des séjours au Maroc et ailleurs';
require __DIR__ . '/headers/head.php';
?>
<main>
  <section class="site-wrap hero-wrap" aria-labelledby="hero-title">
    <div class="hero">
      <div class="hero-content">
        <span class="hero-kicker"><i></i> DU MAROC À L’AILLEURS</span>
        <h1 id="hero-title">L’évasion<br>commence <em>ici.</em></h1>
        <p>Des riads de la médina aux terrasses de l’Atlantique, trouvez un endroit où vous sentir chez vous.</p>
      </div>
      <span class="hero-location"><?= icon('pin') ?> Marrakech, Maroc</span>
    </div>
    <?php require __DIR__ . '/headers/search-form.php'; ?>
  </section>
  <section class="site-wrap collection" aria-labelledby="collection-title">
    <div class="destination-row" aria-label="Destinations à découvrir">
      <a class="destination active" href="search.php"><?= icon('compass') ?> Toutes les destinations</a>
      <a class="destination" href="search.php?location=Marrakech"><?= icon('arch') ?> Marrakech</a>
      <a class="destination" href="search.php?location=Essaouira"><?= icon('waves') ?> Essaouira</a>
      <a class="destination" href="search.php?location=Imlil"><?= icon('mountain') ?> Haut Atlas</a>
      <a class="destination" href="search.php?location=F%C3%A8s"><?= icon('pin') ?> Fès</a>
    </div>
    <div class="section-heading">
      <div><p class="eyebrow">LE CARNET DE SÉJOURS</p><h2 id="collection-title">Où poser vos valises ?</h2><p>Quatre murs et tout un voyage à raconter.</p></div>
      <a class="button outline" href="search.php">Tous les logements <?= icon('arrow') ?></a>
    </div>
    <?php if ($stays): ?>
      <div class="stay-grid"><?php foreach ($stays as $cardIndex => $stay) require __DIR__ . '/headers/card.php'; ?></div>
    <?php else: ?>
      <div class="empty-state"><?= icon('arch') ?><h3>Le carnet attend ses premières adresses</h3><a class="button outline" href="become-host.php">Devenir hôte</a></div>
    <?php endif; ?>
  </section>
  <section class="site-wrap host-strip"><div><p class="eyebrow">CÔTÉ HÔTE</p><h2>Une adresse à partager ?</h2><p>Publiez votre logement et accueillez vos prochains voyageurs.</p></div><a href="become-host.php" class="button primary">Devenir hôte <?= icon('arrow') ?></a></section>
</main>
<?php require __DIR__ . '/headers/footer.php'; ?>
</body>
</html>
