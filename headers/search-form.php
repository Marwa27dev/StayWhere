<form class="search-form" action="search.php" method="get" data-dates aria-label="Rechercher un logement">
  <label><?= icon('pin') ?><span>Destination<input name="location" maxlength="120" value="<?= e($location ?? '') ?>" placeholder="Ville ou région" list="destinations"></span></label>
  <label><?= icon('calendar') ?><span>Arrivée<input type="date" name="check_in" min="<?= date('Y-m-d') ?>" value="<?= e($arrival ?? '') ?>" data-arrival></span></label>
  <label><?= icon('calendar') ?><span>Départ<input type="date" name="check_out" min="<?= date('Y-m-d') ?>" value="<?= e($departure ?? '') ?>" data-departure></span></label>
  <label><?= icon('users') ?><span>Voyageurs<input type="number" name="guests" min="1" value="<?= e($guestsValue ?? '') ?>" placeholder="Ajouter"></span></label>
  <?php if (!empty($budget)): ?><input type="hidden" name="budget" value="<?= e($budget) ?>"><?php endif; ?>
  <?php if (!empty($sort)): ?><input type="hidden" name="sort" value="<?= e($sort) ?>"><?php endif; ?>
  <button class="button primary" type="submit"><?= icon('search') ?> Rechercher</button>
  <datalist id="destinations"><option value="Marrakech"><option value="Essaouira"><option value="Imlil"><option value="Fès"></datalist>
</form>
