<?php
\defined('_JEXEC') or die;
/** @var \Colorpack\Component\AiSearch\Site\View\Search\HtmlView $this */
$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$showCat = $this->settings->showCategory();
$showDate = $this->settings->showDate($this->layoutName);
$showImg = $this->settings->listShowImage();
[$w, $h] = $this->settings->listImage();
?>
<div class="ai-list">
  <?php foreach ($this->items as $d) :
      $meta = trim(($showCat ? $d->category : '') . ($showCat && $showDate ? ' · ' : '') . ($showDate ? substr((string) $d->created, 0, 10) : ''));
      $tags = array_slice($this->tagList($d), 0, $this->settings->maxTags());
      $img = $showImg ? $this->imageUrl($d) : '';
  ?>
  <a class="ai-item<?php echo $img !== '' ? ' ai-item-img' : ''; ?>" href="<?php echo $e($this->link($d)); ?>">
    <?php if ($img !== '') : ?><span class="ai-thumb" style="--tw:<?php echo $w; ?>px;--th:<?php echo $h; ?>px;--tr:<?php echo $w; ?>/<?php echo $h; ?>"><img src="<?php echo $e($img); ?>" alt="" width="<?php echo $w; ?>" height="<?php echo $h; ?>" loading="lazy"></span><?php endif; ?>
    <span class="ai-item-body">
      <?php if ($meta !== '') : ?><div class="ai-cat"><?php echo $e($meta); ?></div><?php endif; ?>
      <span class="ai-t"><?php echo $e($d->title); ?></span>
      <div class="ai-snip"><?php echo $e($this->excerpt($d)); ?></div>
      <?php if ($tags) : ?><div class="ai-tags"><?php foreach ($tags as $t) : ?><span class="ai-tag"><?php echo $e($t); ?></span><?php endforeach; ?></div><?php endif; ?>
    </span>
  </a>
  <?php endforeach; ?>
</div>
