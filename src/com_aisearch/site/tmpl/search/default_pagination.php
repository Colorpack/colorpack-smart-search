<?php
\defined('_JEXEC') or die;
/** @var \Colorpack\Component\AiSearch\Site\View\Search\HtmlView $this */
$pages = (int) ceil($this->total / max(1, $this->perPage));
if ($pages <= 1) {
    return;
}
$current = (int) floor($this->start / $this->perPage) + 1;
$win = $this->pageWindow($pages, $current);
?>
<?php if ($this->yoo) : ?>
<nav aria-label="แบ่งหน้า">
  <ul class="uk-pagination uk-flex-center uk-margin-medium-top">
    <?php if ($current > 1) : ?><li><a href="<?php echo $this->pageUrl(($current - 2) * $this->perPage); ?>" aria-label="ก่อนหน้า"><span uk-pagination-previous></span></a></li><?php endif; ?>
    <?php foreach ($win as $p) : ?>
      <?php if ($p === '…') : ?><li class="uk-disabled"><span>…</span></li>
      <?php elseif ($p === $current) : ?><li class="uk-active"><span aria-current="page"><?php echo $p; ?></span></li>
      <?php else : ?><li><a href="<?php echo $this->pageUrl(($p - 1) * $this->perPage); ?>"><?php echo $p; ?></a></li><?php endif; ?>
    <?php endforeach; ?>
    <?php if ($current < $pages) : ?><li><a href="<?php echo $this->pageUrl($current * $this->perPage); ?>" aria-label="ถัดไป"><span uk-pagination-next></span></a></li><?php endif; ?>
  </ul>
</nav>
<?php elseif ($this->bs) : ?>
<nav aria-label="แบ่งหน้า" class="mt-4">
  <ul class="pagination justify-content-center flex-wrap">
    <?php if ($current > 1) : ?><li class="page-item"><a class="page-link" href="<?php echo $this->pageUrl(($current - 2) * $this->perPage); ?>" aria-label="ก่อนหน้า">&lsaquo;</a></li><?php endif; ?>
    <?php foreach ($win as $p) : ?>
      <?php if ($p === '…') : ?><li class="page-item disabled"><span class="page-link">…</span></li>
      <?php elseif ($p === $current) : ?><li class="page-item active" aria-current="page"><span class="page-link"><?php echo $p; ?></span></li>
      <?php else : ?><li class="page-item"><a class="page-link" href="<?php echo $this->pageUrl(($p - 1) * $this->perPage); ?>"><?php echo $p; ?></a></li><?php endif; ?>
    <?php endforeach; ?>
    <?php if ($current < $pages) : ?><li class="page-item"><a class="page-link" href="<?php echo $this->pageUrl($current * $this->perPage); ?>" aria-label="ถัดไป">&rsaquo;</a></li><?php endif; ?>
  </ul>
</nav>
<?php else : ?>
<nav class="ai-pager" aria-label="แบ่งหน้า">
  <?php if ($current > 1) : ?><a href="<?php echo $this->pageUrl(($current - 2) * $this->perPage); ?>" aria-label="ก่อนหน้า">‹</a><?php endif; ?>
  <?php foreach ($win as $p) : ?>
    <?php if ($p === '…') : ?><span>…</span>
    <?php elseif ($p === $current) : ?><span class="on" aria-current="page"><?php echo $p; ?></span>
    <?php else : ?><a href="<?php echo $this->pageUrl(($p - 1) * $this->perPage); ?>"><?php echo $p; ?></a><?php endif; ?>
  <?php endforeach; ?>
  <?php if ($current < $pages) : ?><a href="<?php echo $this->pageUrl($current * $this->perPage); ?>" aria-label="ถัดไป">›</a><?php endif; ?>
</nav>
<?php endif; ?>
