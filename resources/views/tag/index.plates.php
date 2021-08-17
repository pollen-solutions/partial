<?php
/**
 * @var Pollen\Partial\PartialTemplateInterface $this
 */
?>
<?php $this->before(); ?>
<<?php echo $this->get('tag'); ?> <?php $this->htmlAttrs(); ?>
<?php if ($this->get('singleton')) : ?>
/>
<?php else : ?>
><?php echo $this->get('content'); ?></<?php echo $this->get('tag'); ?>>
<?php endif; ?>
<?php $this->after();