<?php
$link = 'staydetails.php?' . http_build_query(array_filter([
    'id' => $stay['id'], 'check_in' => $arrival ?? '', 'check_out' => $departure ?? '', 'guests' => $guestsValue ?? '',
]));
?>
<article class="stay-card">
  <a class="card-link" href="<?= e($link) ?>">
    <div class="card-photo"><img src="<?= e(image_path($stay['image_url'])) ?>" alt="" width="520" height="400" <?= ($cardIndex ?? 0) > 3 ? 'loading="lazy"' : '' ?>></div>
    <div class="card-body">
      <p class="card-location"><?= icon('pin') ?><?= e($stay['location']) ?></p>
      <h3><?= e($stay['title']) ?></h3>
      <p class="card-guests">Jusqu’à <?= (int) $stay['max_guests'] ?> voyageurs</p>
      <p class="card-price"><strong><?= e(money($stay['price_per_night'])) ?></strong><span> / nuit</span></p>
    </div>
  </a>
  <?php if ($navRole === 'guest'): ?>
    <form class="card-heart" method="post" action="wishlist-action.php">
      <?= csrf_field() ?><input type="hidden" name="stay_id" value="<?= (int) $stay['id'] ?>">
      <input type="hidden" name="return" value="<?= e(basename($_SERVER['SCRIPT_NAME']) . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING'])) ?>">
      <input type="hidden" name="save" value="<?= empty($stay['saved']) ? 1 : 0 ?>">
      <button class="icon-button <?= empty($stay['saved']) ? '' : 'saved' ?>" aria-pressed="<?= empty($stay['saved']) ? 'false' : 'true' ?>" title="<?= empty($stay['saved']) ? 'Ajouter aux favoris' : 'Retirer des favoris' ?>" aria-label="<?= empty($stay['saved']) ? 'Ajouter aux favoris' : 'Retirer des favoris' ?>"><?= icon('heart') ?></button>
    </form>
  <?php endif; ?>
</article>
