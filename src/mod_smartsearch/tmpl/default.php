<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/** @var \Joomla\Registry\Registry $params */
/** @var string $action */
/** @var string $suggestUrl */
/** @var int $itemId */
/** @var object $module */

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('mod_smartsearch');
$wa->useStyle('mod_smartsearch.css')->useScript('mod_smartsearch.js');

$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$id = 'ssm-' . (int) $module->id;
$style = $params->get('shape', 'pill') === 'rounded' ? 'rounded' : 'pill';
$size = $params->get('size', 'normal') === 'large' ? 'large' : 'normal';
$button = in_array($params->get('button', 'label'), ['icon', 'none'], true) ? $params->get('button') : 'label';
$accent = preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $params->get('accent', '')) ? $params->get('accent') : '#3b8fe0';
$maxw = (int) $params->get('max_width', 640);
$placeholder = trim((string) $params->get('placeholder', '')) !== '' ? $params->get('placeholder') : Text::_('MOD_SMARTSEARCH_PLACEHOLDER_DEFAULT');
$live = (int) $params->get('live', 1) === 1;
$popular = (int) $params->get('popular', 1) === 1;
$count = max(3, min(10, (int) $params->get('count', 6)));
$q = Factory::getApplication()->getInput()->getString('q', '');
$onResults = Factory::getApplication()->getInput()->getCmd('option') === 'com_aisearch';
$hidden = [];
if (str_contains($action, '?')) {
    parse_str((string) parse_url($action, PHP_URL_QUERY), $hidden);
}
$sfx = htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_COMPAT, 'UTF-8');
?>
<div class="ssm ssm-<?php echo $style; ?> ssm-<?php echo $size; ?> ssm-btn-<?php echo $button; ?><?php echo $sfx !== '' ? ' ' . $sfx : ''; ?>" id="<?php echo $id; ?>" data-ssm
     style="--ssm-accent:<?php echo $e($accent); ?><?php echo $maxw > 0 ? ';--ssm-max:' . $maxw . 'px' : ';--ssm-max:none'; ?>"
     data-suggest="<?php echo $e($suggestUrl); ?>" data-n="<?php echo $count; ?>" data-live="<?php echo $live ? 1 : 0; ?>" data-popular="<?php echo $popular ? 1 : 0; ?>"
     data-t-popular="<?php echo $e(Text::_('MOD_SMARTSEARCH_T_POPULAR')); ?>" data-t-all="<?php echo $e(Text::_('MOD_SMARTSEARCH_T_ALL')); ?>"
     data-t-none="<?php echo $e(Text::_('MOD_SMARTSEARCH_T_NONE')); ?>" data-t-count="<?php echo $e(Text::_('MOD_SMARTSEARCH_T_COUNT')); ?>">
  <form class="ssm-form" role="search" action="<?php echo $e($action); ?>" method="get" autocomplete="off">
    <?php foreach ($hidden as $k => $v) : if (is_string($v)) : ?><input type="hidden" name="<?php echo $e($k); ?>" value="<?php echo $e($v); ?>"><?php endif; endforeach; ?>
    <?php if ($itemId > 0 && !isset($hidden['Itemid'])) : ?><input type="hidden" name="Itemid" value="<?php echo (int) $itemId; ?>"><?php endif; ?>
    <span class="ssm-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/></svg></span>
    <input class="ssm-input" type="search" name="q" value="<?php echo $onResults ? $e($q) : ''; ?>" placeholder="<?php echo $e($placeholder); ?>"
           role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="<?php echo $id; ?>-list" aria-label="<?php echo $e(Text::_('MOD_SMARTSEARCH_ARIA_SEARCH')); ?>" enterkeyhint="search" spellcheck="false">
    <button class="ssm-clear" type="button" hidden aria-label="<?php echo $e(Text::_('MOD_SMARTSEARCH_ARIA_CLEAR')); ?>"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
    <?php if ($button !== 'none') : ?>
    <button class="ssm-go" type="submit" aria-label="<?php echo $e(Text::_('MOD_SMARTSEARCH_BUTTON_TEXT')); ?>">
      <?php if ($button === 'icon') : ?><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/></svg><?php else : ?><?php echo $e(Text::_('MOD_SMARTSEARCH_BUTTON_TEXT')); ?><?php endif; ?>
    </button>
    <?php endif; ?>
  </form>
  <div class="ssm-pop" id="<?php echo $id; ?>-list" role="listbox" hidden></div>
  <div class="ssm-sr" role="status" aria-live="polite"></div>
</div>
