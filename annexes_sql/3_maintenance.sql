--REQUETES SQL 

-- cas 1 : urgence - hausse prix carburant  donc hausse prix menus +2% 
SELECT id, titre, prix_par_personne FROM menu;
UPDATE menu SET prix_par_personne = ROUND(prix_par_personne * 1.02, 2);
SELECT id, titre, prix_par_personne FROM menu;


-- cas 2 : Hausse annuelle automatique des menus de 1% pour compenser l'inflation
-- 1. planification annuelle de l'evenement tous les 01 janvier 
SET GLOBAL event_scheduler = ON;
CREATE EVENT hausse_annuelle_prix
ON SCHEDULE EVERY 1 YEAR 
STARTS '2027-01-01 00:00:00'
DO 
UPDATE menu SET prix_par_personne = ROUND(prix_par_personne*1.01, 2);

-- 2. déclenchement manuel de l'evenement annuel et verification
SELECT id, titre, prix_par_personne FROM menu;
ALTER EVENT hausse_annuelle_prix ON SCHEDULE AT NOW();
SELECT id, titre, prix_par_personne FROM menu;
