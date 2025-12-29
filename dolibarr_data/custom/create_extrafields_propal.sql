-- Script pour créer les extrafields pour les propositions commerciales
-- Ces champs supplémentaires permettent de renseigner les informations de formation

-- 1. Intitulé de formation (Chaîne de caractères)
INSERT INTO llx_extrafields (
    name, entity, elementtype, label, type, size, required, default_value, 
    arrayofkeyval, pos, alwayseditable, perms, list, help, showoncombobox, 
    showonlist, computed, unique, fk_object, status, rowid
) VALUES (
    'intitule_de_formation', 1, 'propal', 'Intitulé de formation', 'varchar', 255, 0, NULL,
    NULL, 1, 1, NULL, 1, NULL, 1, 1, 0, 0, NULL, 1, NULL
) ON DUPLICATE KEY UPDATE label='Intitulé de formation';

-- 2. Nombre jours formation (Décimal)
INSERT INTO llx_extrafields (
    name, entity, elementtype, label, type, size, required, default_value,
    arrayofkeyval, pos, alwayseditable, perms, list, help, showoncombobox,
    showonlist, computed, unique, fk_object, status, rowid
) VALUES (
    'nombre_jours_formation', 1, 'propal', 'Nombre jours formation', 'double', '24,8', 0, NULL,
    NULL, 100, 1, NULL, 1, NULL, 1, 1, 0, 0, NULL, 1, NULL
) ON DUPLICATE KEY UPDATE label='Nombre jours formation';

-- 3. Tarif global HT (Prix)
INSERT INTO llx_extrafields (
    name, entity, elementtype, label, type, size, required, default_value,
    arrayofkeyval, pos, alwayseditable, perms, list, help, showoncombobox,
    showonlist, computed, unique, fk_object, status, rowid
) VALUES (
    'tarif_global_ht', 1, 'propal', 'Tarif global HT', 'price', NULL, 0, NULL,
    NULL, 100, 1, NULL, 1, NULL, 1, 1, 0, 0, NULL, 1, NULL
) ON DUPLICATE KEY UPDATE label='Tarif global HT';

-- 4. Objectifs pédagogiques (Texte long)
INSERT INTO llx_extrafields (
    name, entity, elementtype, label, type, size, required, default_value,
    arrayofkeyval, pos, alwayseditable, perms, list, help, showoncombobox,
    showonlist, computed, unique, fk_object, status, rowid
) VALUES (
    'objectifs_pedagogiques', 1, 'propal', 'Objectifs pédagogiques', 'text', 2000, 0, NULL,
    NULL, 100, 1, NULL, 1, NULL, 1, 1, 0, 0, NULL, 1, NULL
) ON DUPLICATE KEY UPDATE label='Objectifs pédagogiques';

-- 5. Lieu prévisionnel (Chaîne de caractères)
INSERT INTO llx_extrafields (
    name, entity, elementtype, label, type, size, required, default_value,
    arrayofkeyval, pos, alwayseditable, perms, list, help, showoncombobox,
    showonlist, computed, unique, fk_object, status, rowid
) VALUES (
    'lieu_previsionnel', 1, 'propal', 'Lieu prévisionnel', 'varchar', 255, 0, NULL,
    NULL, 100, 0, NULL, 1, NULL, 1, 1, 0, 0, NULL, 1, NULL
) ON DUPLICATE KEY UPDATE label='Lieu prévisionnel';

-- 6. Type formation (Liste de sélection)
INSERT INTO llx_extrafields (
    name, entity, elementtype, label, type, size, required, default_value,
    arrayofkeyval, pos, alwayseditable, perms, list, help, showoncombobox,
    showonlist, computed, unique, fk_object, status, rowid
) VALUES (
    'type_formation', 1, 'propal', 'Type formation', 'select', NULL, 0, NULL,
    'Intra-entreprise\nInter-entreprise\nE-learning\nPrésentiel\nDistanciel', 100, 1, NULL, 1, NULL, 0, 1, 0, 0, NULL, 1, NULL
) ON DUPLICATE KEY UPDATE label='Type formation';

