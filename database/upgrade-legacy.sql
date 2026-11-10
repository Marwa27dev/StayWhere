-- À exécuter une seule fois sur une copie sauvegardée de l’ancienne base.
-- Les dates des réservations et les prix existants sont conservés.
ALTER TABLE reservations
    ADD COLUMN guests INT NOT NULL DEFAULT 1 AFTER check_out,
    ADD INDEX reservation_calendar (stay_id, status, check_in, check_out);

-- Conserver l’historique après le départ du voyageur.
DROP EVENT IF EXISTS AutoDeleteReservations;
