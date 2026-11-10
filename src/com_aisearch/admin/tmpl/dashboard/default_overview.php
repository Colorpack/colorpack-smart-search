<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

/** @var \Colorpack\Component\AiSearch\Administrator\View\Dashboard\HtmlView $this */
$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$st = $this->stats;
$cur = $st['cur']; $prev = $st['prev']; $days = (int) $st['days'];
$pct = static fn ($a, $b) => $b > 0 ? round($a / $b * 100, 1) : 0.0;
$zeroRate = $pct($cur['zero'], $cur['searches']); $zeroPrev = $pct($prev['zero'], $prev['searches']);
$ctr = $pct($cur['clicked'], $cur['tracked']);
$ctrReady = $cur['tracked'] >= 20;
/** change vs the period before: text + whether it is good / bad / neutral (null = nothing to compare) */
$delta = static function (float $now, float $before, bool $lowerIsBetter, bool $isRate = false): ?array {
    if ($before <= 0 && $now <= 0) { return null; }
    if ($before <= 0) { return ['ข้อมูลช่วงก่อนหน้ายังไม่มี', 'n']; }
    $d = $isRate ? round($now - $before, 1) : round(($now - $before) / $before * 100);
    if ($d == 0) { return ['เท่ากับช่วงก่อนหน้า', 'n']; }
    $txt = ($d > 0 ? '▲ เพิ่มขึ้น ' : '▼ ลดลง ') . abs($d) . ($isRate ? ' จุด' : '%') . ' จากช่วงก่อนหน้า';
    $good = $lowerIsBetter ? $d < 0 : $d > 0;
    return [$txt, $good ? 'g' : 'b'];
};
$dSearch = $delta((float) $cur['searches'], (float) $prev['searches'], false);
$dZero = $delta($zeroRate, $zeroPrev, true, true);
$h = $st['health'];
$todo = array_filter($h['items'], static fn ($i) => !$i['ok'] && empty($i['optional']));
$series = $st['series']; $max = max(1, max($series)); $n = count($series);
$pts = [];
foreach ($series as $i => $v) { $pts[] = round($i / max(1, $n - 1) * 600, 1) . ',' . round(56 - $v / $max * 52, 1); }
$gap = max(0, $h['published'] - $h['docs']);
$stale = $h['docs'] > 0 && $h['published'] > 0 && $gap > max(3, (int) ($h['published'] * 0.02));
?>
<?php if ($todo) : ?>
<div class="ss-card ss-todo" role="region" aria-label="เริ่มต้นใช้งาน">
  <h3>เริ่มต้นใช้งาน <small>Getting started</small></h3>
  <ul class="ss-check">
    <?php foreach ($h['items'] as $it) : ?>
      <li class="<?php echo $it['ok'] ? 'ok' : (!empty($it['optional']) ? 'opt' : 'todo'); ?>"><span class="ss-mark" aria-hidden="true"><?php echo $it['ok'] ? '✓' : '○'; ?></span>
        <span><strong><?php echo $e($it['label']); ?></strong><?php if (!$it['ok']) : ?><br><span class="ss-sub"><?php echo $e($it['hint']); ?></span><?php endif; ?></span>
        <span class="ss-state"><?php echo $it['ok'] ? 'เรียบร้อย' : (!empty($it['optional']) ? 'ไม่บังคับ' : 'ยังไม่ได้ทำ'); ?></span></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<?php if ($stale) : ?>
<div class="ss-note" role="status">ดัชนียังไม่ตามทัน: มีบทความที่เผยแพร่ <?php echo number_format($h['published']); ?> บทความ แต่อยู่ในดัชนี <?php echo number_format($h['docs']); ?> บทความ
  <a href="#" data-ss-tab="system">ไปอัปเดตดัชนี</a></div>
<?php endif; ?>

<div class="ss-kpis">
  <div class="ss-kpi"><div class="ss-k-l">การค้นทั้งหมด</div><div class="ss-k-v"><?php echo number_format($cur['searches']); ?></div>
    <div class="ss-k-n">ใน <?php echo $days; ?> วันที่ผ่านมา</div>
    <?php if ($dSearch) : ?><div class="ss-d ss-<?php echo $dSearch[1]; ?>"><?php echo $e($dSearch[0]); ?></div><?php endif; ?></div>
  <div class="ss-kpi"><div class="ss-k-l">ค้นแล้วไม่เจอ</div><div class="ss-k-v"><?php echo $cur['searches'] ? $zeroRate . '%' : '–'; ?></div>
    <div class="ss-k-n"><?php echo number_format($cur['zero']); ?> ครั้งที่ไม่เจอบทความเลย · ยิ่งต่ำยิ่งดี</div>
    <?php if ($dZero) : ?><div class="ss-d ss-<?php echo $dZero[1]; ?>"><?php echo $e($dZero[0]); ?></div><?php endif; ?></div>
  <div class="ss-kpi"><div class="ss-k-l">ค้นแล้วมีคนเปิดอ่าน</div>
    <?php if ($ctrReady) : ?><div class="ss-k-v"><?php echo $ctr; ?>%</div>
    <div class="ss-k-n">ของการค้นที่เก็บข้อมูลคลิกได้ (<?php echo number_format($cur['tracked']); ?> ครั้ง)<?php echo $cur['pos'] > 0 ? ' · คนมักเปิดบทความอันดับที่ ' . round($cur['pos'], 1) : ''; ?></div>
    <?php else : ?><div class="ss-k-v">–</div><div class="ss-k-n">กำลังเก็บข้อมูล: ต้องมีการค้นอย่างน้อย 20 ครั้งที่ติดตามคลิกได้ (ตอนนี้ <?php echo (int) $cur['tracked']; ?>)</div><?php endif; ?></div>
</div>

<?php if ($cur['searches'] > 0) : ?>
<div class="ss-card">
  <h3>การค้นรายวัน <small>ใน <?php echo $days; ?> วัน</small></h3>
  <svg class="ss-spark" viewBox="0 0 600 60" preserveAspectRatio="none" role="img" aria-label="กราฟจำนวนการค้นรายวัน สูงสุด <?php echo $max; ?> ครั้งต่อวัน">
    <polyline points="<?php echo implode(' ', $pts); ?>" fill="none" stroke="currentColor" stroke-width="2" vector-effect="non-scaling-stroke"/>
  </svg>
  <div class="ss-axis"><span><?php echo gmdate('j M', time() - ($days - 1) * 86400); ?></span><span>สูงสุด <?php echo number_format($max); ?> ครั้ง/วัน</span><span>วันนี้</span></div>
</div>
<?php endif; ?>

<div class="ss-cols">
  <div class="ss-card">
    <h3>ค้นแล้วไม่เจอ <small>ควรดูก่อน</small></h3>
    <?php if (!$st['zero']) : ?>
      <p class="ss-empty"><?php echo $cur['searches'] ? 'ไม่มีคำค้นที่ไม่เจอผลเลยในช่วงนี้ ดีมาก' : 'ยังไม่มีการค้น พอมีผู้เข้าชมค้นหา คำที่ไม่เจอจะมาแสดงตรงนี้ พร้อมปุ่มเพิ่มคำพ้อง'; ?></p>
    <?php else : ?>
      <p class="ss-sub">คำที่ผู้เข้าชมพิมพ์แล้วไม่เจอบทความเลย เหตุผลที่พบบ่อย: ใช้คำคนละคำกับบทความ (แก้ด้วยคำพ้อง) หรือเว็บยังไม่มีเนื้อหานั้น (ไอเดียบทความใหม่)</p>
      <ul class="ss-list">
      <?php foreach ($st['zero'] as $r) : ?>
        <li><div class="ss-row"><span class="ss-q"><?php echo $e($r->q); ?></span><span class="ss-n"><?php echo (int) $r->n; ?> ครั้ง</span></div>
          <details><summary>เพิ่มคำพ้อง</summary>
            <form action="index.php?option=com_aisearch&amp;task=rules.add" method="post" class="ss-inline">
              <input type="hidden" name="q" value="<?php echo $e($r->q); ?>">
              <label class="visually-hidden" for="alts-<?php echo md5($r->q); ?>">คำที่ต้องการให้ค้นเจอแทน</label>
              <input id="alts-<?php echo md5($r->q); ?>" class="form-control form-control-sm" type="text" name="alts" placeholder="ค้นคำนี้แล้วให้เจอบทความที่ใช้คำว่า… (คั่นด้วย ,)" required>
              <button class="btn btn-primary btn-sm" type="submit">เพิ่ม</button>
              <?php echo HTMLHelper::_('form.token'); ?>
            </form></details></li>
      <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <div class="ss-card">
    <h3>ค้นแล้วไม่มีใครเปิดอ่าน <small>ผลอาจไม่ตรงใจ</small></h3>
    <?php if (!$st['noclick']) : ?>
      <p class="ss-empty"><?php echo $ctrReady ? 'ไม่มีคำค้นที่ถูกค้นซ้ำหลายครั้งแต่ไม่มีใครเปิดอ่านเลย' : 'รายการนี้จะขึ้นเมื่อเก็บข้อมูลคลิกได้พอ (คำที่ค้นตั้งแต่ 3 ครั้งแต่ไม่มีใครเปิดอ่านผลเลย)'; ?></p>
    <?php else : ?>
      <p class="ss-sub">คำที่ถูกค้นตั้งแต่ 3 ครั้งแต่ไม่มีใครเปิดอ่านผลเลย อาจแปลว่าผลที่ได้ไม่ตรงสิ่งที่ผู้ค้นอยากได้</p>
      <ul class="ss-list">
      <?php foreach ($st['noclick'] as $r) : ?>
        <li><div class="ss-row"><span class="ss-q"><?php echo $e($r->q); ?></span><span class="ss-n"><?php echo (int) $r->n; ?> ครั้ง</span>
          <a class="btn btn-outline-secondary btn-sm" href="index.php?option=com_aisearch&amp;view=compare&amp;queries=<?php echo rawurlencode($r->q); ?>">ดูผล</a></div></li>
      <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>
