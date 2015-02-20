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
  <?php print drupal_get_css();?>
</head>
<body>
<div class="page">
  <?php print render($entity_array); ?>
</div>
</body>
</html>
