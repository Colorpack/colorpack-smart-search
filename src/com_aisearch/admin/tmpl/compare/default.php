<?php
\defined('_JEXEC') or die;

/** @var \Colorpack\Component\AiSearch\Administrator\View\Compare\HtmlView $this */
$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<div class="mb-3">
  <a href="index.php?option=com_aisearch" class="btn btn-outline-secondary btn-sm">&larr; Dashboard</a>
</div>
<div class="card mb-3"><div class="card-body">
  <p class="mb-2">พิมพ์คำค้นทีละบรรทัด (สูงสุด 30) ระบบจะค้นสองแบบแล้ววางผลเทียบกัน: <strong>แบบปัจจุบัน</strong> กับ <strong>โหมดความแม่นยำ</strong> (แม้ตอนนี้โหมดความแม่นยำจะ<?php echo $this->precisionOn ? 'เปิดอยู่' : 'ปิดอยู่'; ?> ก็ลองดูผลได้ ไม่กระทบผู้เข้าชม)<br>
  <span class="text-muted small">Type one search per line (up to 30). Each is run twice, with the current ranking and with the precision mode, and shown side by side. This does not change what visitors see.</span></p>
  <form action="index.php" method="get">
    <input type="hidden" name="option" value="com_aisearch"><input type="hidden" name="view" value="compare">
    <textarea name="queries" rows="6" class="form-control mb-2" placeholder="เด็ก 1 เดือน&#10;ลูกไม่ยอมกินข้าว&#10;อยากให้ลูกสูง"><?php echo $e($this->queries); ?></textarea>
    <button class="btn btn-primary" type="submit">เปรียบเทียบ / Compare</button>
  </form>
</div></div>
<?php foreach ($this->rows as $r) :
    $cur = $r['current']; $pre = $r['precision']; ?>
<div class="card mb-3"><div class="card-body">
  <h3 class="h6 mb-3"><?php echo $e($r['q']); ?></h3>
  <?php /* AI-START */ ?><?php if (!empty($cur['rewrite'])) : ?><p class="small mb-2"><strong>AI เข้าใจว่า / AI read it as:</strong> <?php echo $e($cur['rewrite']); ?></p><?php endif; ?><?php /* AI-END */ ?>
  <div class="row">
    <div class="col-md-6">
      <p class="small text-muted mb-2"><strong>แบบปัจจุบัน / Current</strong> · พบ <?php echo (int) $cur['total']; ?> เรื่อง · 12 อันดับแรก</p>
      <ol class="small ps-3 mb-0">
        <?php foreach ($cur['items'] as $d) : ?><li><?php echo $e($d->title); ?> <span class="text-muted">[<?php echo $e($d->category); ?>]</span></li><?php endforeach; ?>
        <?php if (!$cur['items']) : ?><li class="text-muted">ไม่พบ / none</li><?php endif; ?>
      </ol>
    </div>
    <div class="col-md-6">
      <p class="small text-muted mb-2"><strong>โหมดความแม่นยำ / Precision</strong> · เหลือ <?php echo (int) $pre['total']; ?> เรื่อง (ซ่อน <?php echo (int) ($pre['hidden'] ?? 0); ?>) · 12 อันดับแรก</p>
      <ol class="small ps-3 mb-0">
        <?php foreach ($pre['items'] as $d) : $inf = $pre['explain'][(int) $d->id] ?? null; ?>
          <li><?php echo $e($d->title); ?> <span class="text-muted">[<?php echo $e($d->category); ?>]</span>
            <?php if ($inf) : ?><span class="badge bg-<?php echo $inf['tier'] <= 2 ? 'success' : ($inf['tier'] == 3 ? 'info' : 'secondary'); ?>" title="<?php echo $e($inf['why']); ?>">tier <?php echo (int) $inf['tier']; ?></span><?php endif; ?></li>
        <?php endforeach; ?>
        <?php if (!$pre['items']) : ?><li class="text-muted">ไม่พบ / none</li><?php endif; ?>
      </ol>
    </div>
  </div>
</div></div>
<?php endforeach; ?>
