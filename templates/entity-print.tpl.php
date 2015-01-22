<?php

/**
 * @file
 * PDF template for printing.
 *
 * Available variables are:
 *  - $entity - The entity itself.
 *  - $entity_array - The renderable array of this entity.
 */
?>

<html>
  <head>
    <title>PDF</title>
  </head>
  <body>
    <?php print render($entity_array); ?>
  </body>
</html>
