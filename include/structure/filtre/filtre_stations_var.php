<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
FILTER - Station filter logic
Initialises filter variables and collects form data:
- Region (Province or Island) / Municipality / Round / Hydro region (watershed) / River
- Data type (Rain, Flow, Piezometry)
- Active stations / Continuous measurement stations / Operating stations

Also loads the data from the DB used in filter processing and page display.
----------------------------------------
*/

//---------------------------------------------------------------
// LOAD SAVED FILTERS FROM DATABASE
// Read TABLE_USER_FILTER for the current user.
// These values are used as fallback when the form has not been submitted.

$saved_filters = [];

$sql_saved = "SELECT filter_id, filter_value 
              FROM " . TABLE_USER_FILTER . " 
              WHERE id_user = ?";
$stmt = $sql_link->prepare($sql_saved);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result_saved = $stmt->get_result();
while ($saved_filter_row = $result_saved->fetch_assoc())
{
    $saved_filters[$saved_filter_row['filter_id']] = $saved_filter_row['filter_value'];
}
$stmt->close();

// Helper: returns POST value if available, then saved value, then default
function get_filter_value(string $key, mixed $default, array $saved): mixed
{
    if (isset($_POST[$key])) { return $_POST[$key]; }
    if (isset($saved[$key])) { return $saved[$key]; }
    return $default;
}


//---------------------------------------------------------------
// FILTER PARAMETERS — FORM DATA COLLECTION

// Free text search
$search_station = '';
$where_search   = '';


// Station origin (Hydro, Meteo, ...)
$from_select = [1]; // default: service id 1

if ($affiche_select_from)
{
    if (isset($_POST['is_filtre_submitted']))
    {
        if (!empty($_POST['fromServices']))
        {
            // Form submitted with at least one checkbox checked
            $from_select = array_map('intval', (array)$_POST['fromServices']);
        }
        else
        {
            // Form submitted with all checkboxes unchecked
            $from_select = [];
        }
    }
    elseif (isset($saved_filters['from_services']))
    {
        // No form submission — restore from saved filter
        $from_select = array_filter(
            array_map('intval', explode(',', $saved_filters['from_services']))
        );
    }
}

// Build WHERE clause for services
if (empty($from_select))
{
    $where_and_from = " AND 1=0"; // no service selected
}
else
{
    $clean_ids      = array_map('intval', $from_select);
    $liste_ids      = implode(',', $clean_ids);
    $where_and_from = " AND s.id_service IN (" . $liste_ids . ")";
}


// Data type (Flow / Rain / Piezometry)
$select_type_encours = (int)get_filter_value('select_type_data', 0, $saved_filters);
$where_and_type      = '';

if ($affiche_select_type && $select_type_encours > 0)
{
    $where_and_type = " AND s.station_type = " . $select_type_encours;
}


// Free search (unused since dec25)
if ($affiche_search)
{
    if (isset($_POST['search_station']) || isset($_GET['search_station']) || isset($_GET['search_st']))
    {
        if (isset($_POST['search_station']))  { $search_station = post_secure($sql_link, $_POST['search_station']); }
        if (isset($_GET['search_station']))   { $search_station = post_secure($sql_link, $_GET['search_station']); }
        if (isset($_GET['search_st']))        { $search_station = post_secure($sql_link, $_GET['search_st']); }

        $where_search = search_station($search_station, '');
    }
}


// Region: Province (NC) or Island (PF, WF)
$select_region_encours    = (int)get_filter_value('select_region', 0, $saved_filters);
$where_and_region         = '';
$where_and_region_commune = '';

if ($select_region_encours > 0)
{
    $where_and_region         = " AND s.id_region = "  . $select_region_encours;
    $where_and_region_commune = " AND r.id_region = "  . $select_region_encours;
}


// Municipality
$select_commune_encours = (int)get_filter_value('select_commune', 0, $saved_filters);
$where_and_commune      = '';

if ($select_commune_encours > 0)
{
    $where_and_commune = " AND s.id_commune = " . $select_commune_encours;
}


// Round (tournée)
$select_tournee_encours = (int)get_filter_value('select_tournee', 0, $saved_filters);
$where_and_tournee      = '';
$where_and_tournee_2    = '';

if ($select_tournee_encours > 0)
{
    $where_and_tournee = " AND t.id_tournee = " . $select_tournee_encours;

    $where_and_tournee_2 = " AND EXISTS (
                                SELECT 1 FROM " . TABLE_STATION_TO_TOURNEE . " t
                                WHERE t.id_station = s.id_station 
                                AND t.id_tournee = " . $select_tournee_encours . "
                            )";
}


// Hydrological region (watershed)
$select_regionhydro_encours = (int)get_filter_value('select_regionhydro', 0, $saved_filters);
$where_and_regionhydro      = '';

if ($select_regionhydro_encours > 0)
{
    $where_and_regionhydro = " AND s.id_regionhydro = " . $select_regionhydro_encours;
}


// River
$select_riviere_encours = 0;
$where_and_riviere      = '';

if ($affiche_select_riviere)
{
    $select_riviere_encours = (int)get_filter_value('select_riviere', 0, $saved_filters);

    if ($select_riviere_encours > 0)
    {
        $where_and_riviere = " AND s.id_riviere = " . $select_riviere_encours;
    }
}


//---------------------------------------------------------------
// STATION STATUS FILTERS (active / continuous / operational)

$where_and_active = " AND s.active_station = 1"; // default: active stations only
$where_and_suivi  = '';
$where_and_armee  = '';

if ($affiche_select_statut_station)
{
    // Active / historical / all
    $select_active_encours = (int)get_filter_value('select_active', 1, $saved_filters);

    if     ($select_active_encours == 0) { $where_and_active = ""; }
    elseif ($select_active_encours == 1) { $where_and_active = " AND s.active_station = 1"; }
    elseif ($select_active_encours == 2) { $where_and_active = " AND s.active_station = 0"; }

    // Continuous measurement / punctual / all
    $select_suivi_encours = (int)get_filter_value('select_suivi', 0, $saved_filters);

    if     ($select_suivi_encours == 1) { $where_and_suivi = " AND s.suivi = 1"; }
    elseif ($select_suivi_encours == 2) { $where_and_suivi = " AND s.suivi = 0"; }

    // Operational status (armed / faulty)
    $select_armee_encours = (int)get_filter_value('select_armee', 0, $saved_filters);

    if ($select_armee_encours == 1) { $where_and_armee = " AND s.armee = 1"; }
}


// Single station selector
$select_station_encours = 0;
$where_and_station      = '';

if ($affiche_select_station)
{
    $select_station_encours = (int)get_filter_value('select_station', 0, $saved_filters);

    if ($select_station_encours > 0)
    {
        $where_and_station = " AND s.id_station = " . $select_station_encours;

        // Retrieve the data type linked to the selected station.
        // $where_and_type_piezo is included so a saved station that the calling
        // page cannot display (data_diag_piezo.php restricts to station_type=5)
        // fails this check and resets the filter to "all stations" below.
        // Without it the page silently renders an empty list: the saved station
        // exists, so the filter survives, but the page's own type restriction
        // then excludes it from every result.
        $sql_station_type = "SELECT DISTINCT s.id_station, s.id_service, s.nom_station, 
                                             s.code_station, s.station_type
                             FROM "   . TABLE_STATION . " s 
                             LEFT JOIN " . TABLE_STATION_TO_TOURNEE . " t ON t.id_station = s.id_station
                             WHERE 1=1 " .
                             ($where_and_type_piezo ?? '') .
                             $where_and_station      .
                             $where_and_from         .
                             $where_and_type         .
                             $where_and_region       .
                             $where_and_commune      .
                             $where_and_regionhydro  .
                             $where_and_riviere      .
                             $where_and_tournee      .
                             $where_and_active       .
                             $where_and_suivi        .
                             $where_and_armee;

        $station_type_query  = tep_db_query($sql_link, $sql_station_type);
        $station_type        = tep_db_fetch_array($station_type_query);
        if (isset($station_type['station_type']))
        {
            $select_type_encours = $station_type['station_type'];
        }
        else
        {
            $select_station_encours = 0;
            $where_and_station      = '';
        }
    }
}


//---------------------------------------------------------------
// DATABASE LOOKUPS
// All reference data is loaded into arrays for easy access and
// to avoid redundant queries during rendering.


// Service / origin list
$sql_fromData    = "SELECT DISTINCT id_service, name, description
                    FROM "  . TABLE_SERVICE . "
                    ORDER BY id_service ASC";
$fromData_query  = tep_db_query($sql_link, $sql_fromData);
while ($fromData = tep_db_fetch_array($fromData_query))
{
    $fromData_array[$fromData['id_service']] = [
        'name'        => html_entity_decode($fromData['name']        ?? ''),
        'description' => html_entity_decode($fromData['description'] ?? ''),
    ];
}


// Data type list (Flow, Rain, Piezometry, ...)
$sql_eq_type    = "SELECT DISTINCT id_eq_type, nom_eq_type, type_color_border, type_color_background
                   FROM "  . TABLE_EQ_TYPE . "
                   WHERE active_eq_type = 1
                   ORDER BY order_eq_type ASC";
$eq_type_query  = tep_db_query($sql_link, $sql_eq_type);
while ($eq_type = tep_db_fetch_array($eq_type_query))
{
    $eq_type_array[$eq_type['id_eq_type']] = [
        'nom_eq_type'           => html_entity_decode($eq_type['nom_eq_type']           ?? ''),
        'type_color_border'     => html_entity_decode($eq_type['type_color_border']     ?? ''),
        'type_color_background' => html_entity_decode($eq_type['type_color_background'] ?? ''),
    ];
}


// Region / territory list
$sql_region    = "SELECT DISTINCT id_region, nom_region
                  FROM "  . TABLE_REGION . "
                  WHERE id_territoire = " . $territoire_id;
$region_query  = tep_db_query($sql_link, $sql_region);
while ($region = tep_db_fetch_array($region_query))
{
    $region_array[$region['id_region']] = html_entity_decode($region['nom_region'] ?? '');
}


// Municipality list (filtered by region if selected)
$sql_commune    = "SELECT DISTINCT c.id_commune, c.nom_commune
                   FROM "      . TABLE_COMMUNE . " c
                   JOIN "      . TABLE_REGION  . " r ON c.id_region = r.id_region
                   WHERE r.id_territoire = " . $territoire_id . $where_and_region_commune . "
                   ORDER BY c.nom_commune ASC";
$commune_query  = tep_db_query($sql_link, $sql_commune);
while ($commune = tep_db_fetch_array($commune_query))
{
    $commune_array[$commune['id_commune']] = html_entity_decode($commune['nom_commune'] ?? '');
}


// Round list
$sql_tournee    = "SELECT DISTINCT t.id, t.nom, t.id_territoire
                   FROM "  . TABLE_TOURNEE . " t
                   WHERE t.id_territoire = " . $territoire_id . "
                   ORDER BY nom ASC";
$tournee_query  = tep_db_query($sql_link, $sql_tournee);
while ($tournee = tep_db_fetch_array($tournee_query))
{
    $tournee_array[$tournee['id']] = html_entity_decode($tournee['nom'] ?? '');
}


// Hydrological region list (watershed)
$sql_regionhydro    = "SELECT DISTINCT rh.id, rh.nom, rh.id_territoire
                       FROM "  . TABLE_REGIONHYDRO . " rh
                       WHERE rh.id_territoire = " . $territoire_id . "
                       ORDER BY nom ASC";
$regionhydro_query  = tep_db_query($sql_link, $sql_regionhydro);
while ($regionhydro = tep_db_fetch_array($regionhydro_query))
{
    $regionhydro_array[$regionhydro['id']] = html_entity_decode($regionhydro['nom'] ?? '');
}


// River list
$sql_riviere    = "SELECT DISTINCT r.id, r.nom, r.id_territoire
                   FROM "  . TABLE_RIVIERE . " r
                   WHERE r.id_territoire = " . $territoire_id . "
                   ORDER BY nom ASC";
$riviere_query  = tep_db_query($sql_link, $sql_riviere);
while ($riviere = tep_db_fetch_array($riviere_query))
{
    $riviere_array[$riviere['id']] = html_entity_decode($riviere['nom'] ?? '');
}


// Station list (only when the station selector is enabled)
if ($affiche_select_station)
{
    $sql_station    = "SELECT DISTINCT s.id_station, s.nom_station, s.code_station
                       FROM "      . TABLE_STATION . " s
                       JOIN "      . TABLE_REGION  . " r ON s.id_region = r.id_region
                       LEFT JOIN " . TABLE_STATION_TO_TOURNEE . " t ON t.id_station = s.id_station
                       WHERE 1=1 " .
                       $where_and_station     .
                       $where_and_from        .
                       $where_and_type        .
                       $where_and_region      .
                       $where_and_commune     .
                       $where_and_regionhydro .
                       $where_and_riviere     .
                       $where_and_tournee     .
                       $where_and_active      .
                       $where_and_suivi       .
                       $where_and_armee;

    $station_query = tep_db_query($sql_link, $sql_station);
    while ($station = tep_db_fetch_array($station_query))
    {
        $station_array[$station['id_station']] = [
            'code_station' => html_entity_decode($station['code_station'] ?? ''),
            'nom_station'  => html_entity_decode($station['nom_station']  ?? ''),
        ];
    }
}