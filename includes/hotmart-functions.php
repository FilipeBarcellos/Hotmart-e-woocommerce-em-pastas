<?php
/**
 * Divide um nome completo em primeiro e último nome.
 *
 * @param string $full_name Nome completo.
 * @return array Array com o primeiro e último nome.
 */
function hotmart_split_full_name($full_name) { // Novo nome da função
    $parts = explode(' ', $full_name);
    $last_name = array_pop($parts);
    $first_name = implode(' ', $parts);
    return array($first_name, $last_name);
}
