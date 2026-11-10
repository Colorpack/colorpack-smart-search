<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

/** @var \Colorpack\Component\AiSearch\Site\View\Search\HtmlView $this */
$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$active = Factory::getApplication()->getMenu()->getActive();
$onMenu = $active && $active->component === 'com_aisearch';
$action = Route::_('index.php?option=com_aisearch&view=search' . ($onMenu ? '&Itemid=' . (int) $active->id : ''));
$layout = $this->layoutName;
$isDemo = $layout === 'list';   // the List view carries the demo-page look
$pretty = $isDemo || $this->settings->searchStyle() === 'pretty';
$brand = $this->settings->brandColor();
$font = $this->fontInfo();
$sw = $this->settings->searchWidth();
$bsForm = $this->bs && !$pretty;   // Bootstrap input group (Cassiopeia) when the pretty box is switched off
$themeForm = $this->yoo && !$pretty;   // UIkit search box is inline-block: needs block to centre; our own box is a flex row
$formStyle = $sw > 0 ? 'display:' . ($themeForm || $bsForm ? 'block' : 'flex') . ';max-width:' . $sw . 'px;margin-left:auto;margin-right:auto' : '';
?>
<style>
.aisearch{--ai-line:#f0dde2;--ai-muted:#7a6a6e}
@supports (color:color-mix(in srgb,red 20%,white)){.aisearch{--ai-line:color-mix(in srgb,var(--ai-brand) 22%,white)}}
/* sizes follow the demo page (base 16px, line-height 1.65) or the template when chosen */
.aisearch .ai-head,.aisearch form.ai-form,.aisearch .ai-chips,.aisearch .ai-chips-label,.aisearch .ai-count,.aisearch .ai-list,.aisearch .ai-pager,.aisearch .ai-grid,.aisearch .ai-row{font-size:var(--ai-b);line-height:var(--ai-lh)}
.aisearch form.ai-form{display:flex;gap:8px;margin:0 auto 20px}
.aisearch form.ai-form input.ai-input{flex:1;min-width:0;box-sizing:border-box;height:auto;min-height:0;padding:14px 18px;border:2px solid var(--ai-line);border-radius:14px;font:inherit;font-size:var(--ai-b);line-height:var(--ai-lh);background:#fff;color:inherit;box-shadow:none;-webkit-appearance:none;appearance:none}
.aisearch form.ai-form input.ai-input:focus{outline:none;border-color:var(--ai-brand)}
.aisearch form.ai-form button.ai-btn{box-sizing:border-box;height:auto;min-height:0;padding:0 22px;border:0;border-radius:14px;background:var(--ai-brand);color:#fff;font:inherit;font-size:var(--ai-b);line-height:var(--ai-lh);font-weight:700;cursor:pointer;white-space:nowrap;text-transform:none}
.aisearch form.ai-form button.ai-btn:hover{filter:brightness(.93)}
.aisearch .ai-head{text-align:center;margin:0 0 18px}
.aisearch .ai-head h1{margin:.4em 0 .2em;font-size:calc(var(--ai-b) * 1.6);font-weight:700;line-height:var(--ai-lh)}
.aisearch .ai-head p{margin:0 auto;font-size:var(--ai-b);color:var(--ai-muted);max-width:44em}
.aisearch .ai-badge{display:inline-block;background:color-mix(in srgb,var(--ai-brand) 14%,white);color:var(--ai-brand);font-size:calc(var(--ai-b) * 0.8);line-height:var(--ai-lh);font-weight:700;padding:2px 14px;border-radius:99px}
.aisearch .ai-chips-label{text-align:center;color:var(--ai-muted);font-size:calc(var(--ai-b) * 0.8);margin:0 0 6px}
.aisearch .ai-chips{display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin:0 auto 18px}
.aisearch .ai-chips a{display:inline-block;padding:5px 14px;border:1px solid var(--ai-line);border-radius:99px;background:#fff;color:inherit;text-decoration:none;font-size:calc(var(--ai-b) * 0.85);line-height:var(--ai-lh)}
.aisearch .ai-chips a:hover{border-color:var(--ai-brand);color:var(--ai-brand)}
.aisearch.ai-demo{max-width:var(--ai-w,760px);margin-left:auto;margin-right:auto}
.aisearch.ai-tint{background:#fff7f8;border-radius:22px;padding:28px 24px}
.aisearch .ai-bar{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin:14px 0 8px}
.aisearch .ai-bar .ai-count,.aisearch .ai-bar .uk-h6{margin:0}
.aisearch .ai-count{display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px;color:var(--ai-muted);font-size:calc(var(--ai-b) * 0.85)}
.aisearch .ai-toggle{display:inline-flex;gap:4px}
.aisearch .ai-toggle a{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border:1px solid var(--ai-line);border-radius:99px;background:#fff;color:var(--ai-muted);text-decoration:none;font-size:calc(var(--ai-b) * 0.85);line-height:var(--ai-lh)}
.aisearch .ai-toggle a svg{width:14px;height:14px;fill:currentColor}
.aisearch .ai-toggle a:hover{border-color:var(--ai-brand);color:var(--ai-brand)}
.aisearch .ai-toggle a[aria-pressed=true]{background:var(--ai-brand);border-color:var(--ai-brand);color:#fff}
.aisearch .ai-item{display:block;background:#fff;border:1px solid var(--ai-line);border-radius:14px;padding:14px 18px;margin:0 0 10px;color:inherit;text-decoration:none}
.aisearch .ai-item:hover{border-color:var(--ai-brand)}
.aisearch .ai-item .ai-cat{font-size:calc(var(--ai-b) * 0.78);line-height:var(--ai-lh);font-weight:700;color:var(--ai-brand)}
.aisearch .ai-item .ai-t{display:block;font-weight:700;font-size:calc(var(--ai-b) * 1.05);line-height:var(--ai-lh)}
.aisearch .ai-item:hover .ai-t{color:var(--ai-brand)}
.aisearch .ai-item .ai-snip{color:var(--ai-muted);font-size:calc(var(--ai-b) * 0.92);line-height:var(--ai-lh);margin:4px 0}
.aisearch .ai-item .ai-tags{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px}
.aisearch .ai-item.ai-item-img{display:flex;gap:16px;align-items:flex-start}
.aisearch .ai-thumb{flex:none;width:var(--tw);height:var(--th);border-radius:10px;overflow:hidden;background:#fde6ec}
.aisearch .ai-thumb img{display:block;width:100%;height:100%;object-fit:cover}
.aisearch .ai-item-body{flex:1 1 auto;min-width:0;display:block}
@media (max-width:560px){.aisearch .ai-item.ai-item-img{flex-direction:column}.aisearch .ai-thumb{width:100%;height:auto;aspect-ratio:var(--tr)}}
.aisearch .ai-item .ai-tag{font-size:calc(var(--ai-b) * 0.75);line-height:var(--ai-lh);background:color-mix(in srgb,var(--ai-brand) 12%,white);color:var(--ai-brand);padding:1px 10px;border-radius:99px}
.aisearch .pagination{--bs-pagination-color:var(--ai-brand);--bs-pagination-hover-color:var(--ai-brand);--bs-pagination-active-bg:var(--ai-brand);--bs-pagination-active-border-color:var(--ai-brand);--bs-pagination-focus-color:var(--ai-brand)}
.aisearch .ai-bs-card:hover{border-color:var(--ai-brand)}
.aisearch .ai-bsform .btn-primary{background:var(--ai-brand);border-color:var(--ai-brand)}
.aisearch .ai-bsform .form-control:focus{border-color:var(--ai-brand);box-shadow:none}
.aisearch.ai-busy form.ai-form,.aisearch.ai-busy form.uk-search{opacity:.85}
.aisearch.ai-busy .ai-input,.aisearch.ai-busy .uk-search-input{cursor:progress}
.aisearch.ai-busy .ai-btn{opacity:.75;cursor:progress}
.aisearch .ai-wait{display:none;align-items:center;justify-content:center;gap:10px;margin:0 auto 12px;color:var(--ai-muted);font-size:calc(var(--ai-b) * .9)}
.aisearch.ai-busy .ai-wait{display:flex}
.aisearch .ai-wait::before{content:"";width:1em;height:1em;border:2px solid var(--ai-brand);border-right-color:transparent;border-radius:50%;animation:ai-spin .8s linear infinite}
@keyframes ai-spin{to{transform:rotate(360deg)}}
#ssb-bar{position:fixed;left:0;right:0;top:0;height:3px;z-index:2147483000;background:rgba(59,143,224,.18);overflow:hidden;pointer-events:none}
#ssb-bar i{position:absolute;top:0;bottom:0;width:40%;background:var(--ai-brand,#3b8fe0);border-radius:3px;animation:ssb-slide 1.1s ease-in-out infinite}
@keyframes ssb-slide{0%{left:-40%}100%{left:100%}}
@media (prefers-reduced-motion:reduce){.aisearch .ai-wait::before{animation:none}#ssb-bar i{animation:none;width:100%;opacity:.6}}
</style>
<?php if (!$this->yoo && !$this->bs) : ?>
<style>
.aisearch .ai-count{color:var(--ai-muted);font-size:.9em;margin:0 0 12px}
.aisearch .ai-grid{display:grid;gap:16px;grid-template-columns:repeat(var(--ai-cols,3),1fr)}
@media (max-width:900px){.aisearch .ai-grid{grid-template-columns:repeat(2,1fr)}}
@media (max-width:560px){.aisearch .ai-grid{grid-template-columns:1fr}}
.aisearch .ai-card{display:flex;flex-direction:column;border:1px solid var(--ai-line);border-radius:14px;overflow:hidden;background:#fff;text-decoration:none;color:inherit;box-shadow:0 2px 10px rgba(0,0,0,.05)}
.aisearch .ai-card:hover{box-shadow:0 6px 18px rgba(0,0,0,.12)}
.aisearch .ai-card img{display:block;width:100%;object-fit:cover}
.aisearch .ai-body{padding:14px 18px 16px}
.aisearch .ai-title{font-weight:700;font-size:1.05em;margin:0 0 6px;line-height:1.4}
.aisearch .ai-text{color:var(--ai-muted);font-size:.92em;margin:0 0 10px}
.aisearch .ai-meta{display:inline-block;background:#fde6ec;color:var(--ai-brand);font-size:.8em;font-weight:700;padding:2px 12px;border-radius:99px}
.aisearch .ai-row{display:flex;gap:16px;padding:12px 0;border-bottom:1px solid var(--ai-line);text-decoration:none;color:inherit;max-width:820px}
.aisearch .ai-row img{flex:none;object-fit:cover;border-radius:10px}
.aisearch .ai-pager{display:flex;flex-wrap:wrap;gap:6px;margin:24px 0}
.aisearch .ai-pager a,.aisearch .ai-pager span{min-width:38px;text-align:center;padding:6px 10px;border:1px solid var(--ai-line);border-radius:8px;text-decoration:none;color:inherit}
.aisearch .ai-pager .on{background:var(--ai-brand);color:#fff;border-color:var(--ai-brand)}
</style>
<?php endif; ?>
<div class="aisearch<?php echo $isDemo ? ' ai-demo' . ($this->settings->panelTint() ? ' ai-tint' : '') : ''; ?><?php echo ($layout === 'card' && $this->settings->equalCards()) ? ' ai-equal' : ''; ?>" data-mode="<?php echo $e($this->mode); ?>" data-layout="<?php echo $e($layout); ?>" style="--ai-cols:<?php echo (int) $this->settings->columns(); ?>;--ai-brand:<?php echo $e($brand); ?>;--ai-b:<?php echo $e($font['base']); ?>;--ai-lh:<?php echo $e($font['lh']); ?><?php echo $font['family'] !== '' ? ';font-family:' . $e($font['family']) : ''; ?><?php echo $sw > 0 ? ';--ai-w:' . (int) $sw . 'px' : ''; ?>">
  <?php if ($this->settings->showHeader() && ($this->settings->headerTitle() !== '' || $this->settings->headerText() !== '')) : ?>
  <div class="ai-head">
    <?php if ($this->settings->headerBadge() !== '') : ?><span class="ai-badge"><?php echo $e($this->settings->headerBadge()); ?></span><?php endif; ?>
    <?php if ($this->settings->headerTitle() !== '') : ?><h1><?php echo $e($this->settings->headerTitle()); ?></h1><?php endif; ?>
    <?php if ($this->settings->headerText() !== '') : ?><p><?php echo $e($this->settings->headerText()); ?></p><?php endif; ?>
  </div>
  <?php endif; ?>
  <form role="search" action="<?php echo $action; ?>" method="get" class="<?php echo $bsForm ? 'ai-bsform' : ($themeForm ? 'uk-search uk-search-medium uk-width-1-1' : 'ai-form'); ?>"<?php echo $formStyle !== '' ? ' style="' . $formStyle . '"' : ''; ?>>
    <?php if (!$onMenu) : ?><input type="hidden" name="option" value="com_aisearch"><input type="hidden" name="view" value="search"><?php endif; ?>
    <?php if ($bsForm) : ?>
      <div class="input-group"><input type="search" name="q" value="<?php echo $e($this->q); ?>" placeholder="<?php echo $e($this->settings->placeholderText()); ?>" class="form-control" required aria-label="ค้นหา"><button type="submit" class="btn btn-primary">ค้นหา</button></div>
    <?php elseif ($themeForm) : ?>
      <input type="search" name="q" value="<?php echo $e($this->q); ?>" placeholder="ค้นหา" class="uk-search-input" required aria-label="ค้นหา">
      <button uk-search-icon class="uk-search-icon-flip" type="submit" aria-label="ค้นหา"></button>
    <?php else : ?>
      <input type="search" name="q" value="<?php echo $e($this->q); ?>" placeholder="<?php echo $e($this->settings->placeholderText()); ?>" class="ai-input" required aria-label="ค้นหา">
      <button type="submit" class="ai-btn">ค้นหา</button>
    <?php endif; ?>
  </form>
  <div class="ai-wait" role="status" aria-live="polite" hidden><span><?php echo $e('กำลังค้นหา…'); ?></span></div>
  <?php $sug = $this->suggestions(); if ($sug['items']) : ?>
  <?php if ($sug['label'] !== '') : ?><div class="ai-chips-label"><?php echo $e($sug['label']); ?></div><?php endif; ?>
  <div class="ai-chips">
    <?php foreach ($sug['items'] as $ex) : ?><a href="<?php echo $e($action . (str_contains($action, '?') ? '&' : '?') . 'q=' . rawurlencode($ex)); ?>"><?php echo $e($ex); ?></a><?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php if (!$this->indexed) : ?>
    <p>ระบบค้นหายังไม่พร้อมใช้งาน</p>
  <?php elseif ($this->q !== '') : ?>
    <div class="ai-bar">
    <div class="<?php echo ($this->yoo && !$isDemo) ? 'uk-h6' : 'ai-count'; ?>"><span><?php echo $isDemo ? 'พบ ' . number_format($this->total) . ' บทความที่เกี่ยวข้อง' : 'ค้นพบ ' . number_format($this->total) . ' บทความ'; ?></span></div>
    <?php if ($this->canToggle) : $isCard = $layout === 'card'; ?>
    <div class="ai-toggle" role="group" aria-label="รูปแบบการแสดงผล">
      <a href="<?php echo $this->viewUrl('list'); ?>" data-v="list" aria-pressed="<?php echo $isCard ? 'false' : 'true'; ?>" title="แสดงเป็นรายการ"><svg viewBox="0 0 16 16" aria-hidden="true"><path d="M1 2h14v2.5H1zM1 6.75h14v2.5H1zM1 11.5h14V14H1z"/></svg>รายการ</a>
      <a href="<?php echo $this->viewUrl('card'); ?>" data-v="card" aria-pressed="<?php echo $isCard ? 'true' : 'false'; ?>" title="แสดงเป็นการ์ด"><svg viewBox="0 0 16 16" aria-hidden="true"><path d="M1 1h6v6H1zM9 1h6v6H9zM1 9h6v6H1zM9 9h6v6H9z"/></svg>การ์ด</a>
    </div>
    <?php endif; ?>
    </div>
    <?php if (!$this->items) : ?>
      <p>ไม่พบบทความที่เกี่ยวข้อง ลองใช้คำอื่นหรือพิมพ์เป็นประโยค</p>
    <?php else : ?>
      <?php echo $this->loadTemplate($layout); ?>
      <?php echo $this->loadTemplate('pagination'); ?>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php if ($this->canToggle) : ?>
<script>
(function(){document.querySelectorAll('.aisearch .ai-toggle a').forEach(function(a){a.addEventListener('click',function(){try{document.cookie='aisearch_view='+a.getAttribute('data-v')+'; path=/; max-age=31536000; SameSite=Lax';}catch(e){}});});})();
</script>
<?php endif; ?>
<script>
(function(){var r=document.querySelector('.aisearch');if(!r)return;var wait=r.querySelector('.ai-wait');
function bar(){if(document.getElementById('ssb-bar'))return;var b=document.createElement('div');b.id='ssb-bar';b.setAttribute('role','progressbar');b.setAttribute('aria-label','กำลังค้นหา…');b.appendChild(document.createElement('i'));document.body.appendChild(b)}
function busy(){r.classList.add('ai-busy');if(wait)wait.hidden=false;bar()}
function reset(){r.classList.remove('ai-busy');if(wait)wait.hidden=true;var b=document.getElementById('ssb-bar');if(b)b.remove()}
var f=r.querySelector('form');if(f)f.addEventListener('submit',function(){setTimeout(busy,0)});
r.addEventListener('click',function(e){var a=e.target.closest&&e.target.closest('.ai-chips a,.ai-pager a,.uk-pagination a,.page-link,.ai-toggle a,.ai-item');if(a&&a.href&&!e.defaultPrevented&&!e.ctrlKey&&!e.metaKey)setTimeout(busy,0)});
window.addEventListener('pageshow',function(e){if(e.persisted)reset()});})();
</script>
<?php if ($this->q !== '' && $this->settings->clickLearning()) : ?>
<script>
(function(){var q=<?php echo json_encode($this->q, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>,u=<?php echo json_encode(Route::_('index.php?option=com_aisearch&task=suggest.click&format=json', false)); ?>;
function send(e){var a=e.target.closest&&e.target.closest('a[data-aid]');if(!a||!navigator.sendBeacon)return;var d=new FormData();d.append('q',q);d.append('id',a.getAttribute('data-aid'));try{navigator.sendBeacon(u,d)}catch(x){}}
document.addEventListener('click',send,true);document.addEventListener('auxclick',send,true);})();
</script>
<?php endif; ?>
