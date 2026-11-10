<style>
.aisearch .ai-yoo-card{display:flex;flex-direction:column}
.aisearch .ai-yoo-body{display:flex;flex-direction:column;flex:1 1 auto}
.aisearch .ai-imgbox{display:block;width:100%;overflow:hidden;background:#fde6ec}
.aisearch .ai-meta-wrap{margin-top:auto;padding-top:16px;display:flex;flex-wrap:wrap;gap:6px}
.aisearch .ai-meta-wrap .el-meta{display:inline-block;margin:0}
.aisearch .ai-card .ai-body{display:flex;flex-direction:column;flex:1 1 auto}
.aisearch .ai-card .ai-text{margin-bottom:12px}
.aisearch.ai-equal .el-title,.aisearch.ai-equal .ai-card .ai-title{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;line-clamp:2;overflow:hidden;line-height:1.4;min-height:2.8em}
.aisearch.ai-equal .el-content,.aisearch.ai-equal .ai-card .ai-text{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3;line-clamp:3;overflow:hidden;line-height:1.6;min-height:4.8em}
</style>
<?php
\defined('_JEXEC') or die;
/** @var \Colorpack\Component\AiSearch\Site\View\Search\HtmlView $this */
$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
[$w, $h] = $this->settings->cardRatio();
$cols = $this->settings->columns();
$showCat = $this->settings->showCategory();
$showDate = $this->settings->showDate($this->layoutName);
?>
<?php if ($this->yoo) : ?>
<div class="uk-grid-match uk-child-width-1-1 uk-child-width-1-<?php echo $cols; ?>@m uk-margin-top" uk-grid>
  <?php foreach ($this->items as $d) : $img = $this->imageUrl($d); ?>
  <div>
    <div class="uk-flex uk-flex-column">
      <a class="el-item ai-yoo-card uk-card uk-card-default uk-card-small uk-card-hover uk-transition-toggle uk-flex-1 uk-link-toggle uk-position-relative uk-position-z-index" href="<?php echo $e($this->link($d)); ?>" title="<?php echo $e($d->title); ?>">
        <?php if ($img !== '') : ?>
        <div class="uk-card-media-top"><div class="uk-inline-clip ai-imgbox" style="aspect-ratio:<?php echo $w; ?>/<?php echo $h; ?>"><img class="el-image uk-transition-scale-up uk-transition-opaque" src="<?php echo $e($img); ?>" alt="" width="<?php echo $w; ?>" height="<?php echo $h; ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover"></div></div>
        <?php endif; ?>
        <div class="uk-card-body uk-margin-remove-first-child ai-yoo-body">
          <h4 class="el-title uk-card-title uk-margin-top uk-margin-remove-bottom"><?php echo $e($d->title); ?></h4>
          <div class="el-content uk-panel uk-margin-top uk-margin-remove-bottom"><?php echo $e($this->excerpt($d)); ?></div>
          <?php echo $this->explainHtml($d); ?>
          <?php $fi = $this->footerItems($d); if ($fi) : ?><div class="ai-meta-wrap"><?php foreach ($fi as $x) : ?><span class="el-meta uk-text-small uk-text-primary"><?php echo $e($x); ?></span><?php endforeach; ?></div><?php endif; ?>
        </div>
      </a>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php elseif ($this->bs) : ?>
<div class="row row-cols-1 row-cols-md-<?php echo min(2, $cols); ?> row-cols-lg-<?php echo $cols; ?> g-3 mt-1">
  <?php foreach ($this->items as $d) : $img = $this->imageUrl($d); ?>
  <div class="col">
    <a class="card h-100 ai-bs-card text-decoration-none text-reset shadow-sm" href="<?php echo $e($this->link($d)); ?>" title="<?php echo $e($d->title); ?>">
      <?php if ($img !== '') : ?><div class="ai-imgbox" style="aspect-ratio:<?php echo $w; ?>/<?php echo $h; ?>"><img class="card-img-top" src="<?php echo $e($img); ?>" alt="" width="<?php echo $w; ?>" height="<?php echo $h; ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover"></div><?php endif; ?>
      <div class="card-body d-flex flex-column">
        <h4 class="card-title h6 ai-title"><?php echo $e($d->title); ?></h4>
        <p class="card-text small text-body-secondary ai-text"><?php echo $e($this->excerpt($d)); ?></p>
        <?php echo $this->explainHtml($d); ?>
        <?php $fi = $this->footerItems($d); if ($fi) : ?><div class="ai-meta-wrap"><?php foreach ($fi as $x) : ?><span class="badge rounded-pill ai-meta"><?php echo $e($x); ?></span><?php endforeach; ?></div><?php endif; ?>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>
<?php else : ?>
<div class="ai-grid">
  <?php foreach ($this->items as $d) : $img = $this->imageUrl($d); ?>
  <a class="ai-card" href="<?php echo $e($this->link($d)); ?>" title="<?php echo $e($d->title); ?>">
    <?php if ($img !== '') : ?><div class="ai-imgbox" style="aspect-ratio:<?php echo $w; ?>/<?php echo $h; ?>"><img src="<?php echo $e($img); ?>" alt="" width="<?php echo $w; ?>" height="<?php echo $h; ?>" loading="lazy" style="height:100%"></div><?php endif; ?>
    <div class="ai-body">
      <h4 class="ai-title"><?php echo $e($d->title); ?></h4>
      <p class="ai-text"><?php echo $e($this->excerpt($d)); ?></p>
      <?php echo $this->explainHtml($d); ?>
      <?php $fi = $this->footerItems($d); if ($fi) : ?><div class="ai-meta-wrap"><?php foreach ($fi as $x) : ?><span class="ai-meta"><?php echo $e($x); ?></span><?php endforeach; ?></div><?php endif; ?>
    </div>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
