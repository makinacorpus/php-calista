<?php
/**
 * @file
 * Main dashboard pages.
 */

/**
 * Main dashboard page.
 */
function udashboard_page() {
  return [
    '#theme'  => 'udashboard_page',
    '#portlets' => udashboard_portlet_registry_get()->getPortletsForAccount(\Drupal::currentUser()),
  ];
}
