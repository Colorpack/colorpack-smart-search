<?php
\defined('_JEXEC') or die;

use Colorpack\Component\AiSearch\Administrator\View\Words\HtmlView as V;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;

/** @var V $this */
$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$tok = HTMLHelper::_('form.token');
$base = 'index.php?option=com_aisearch&view=words';
$filters = array_filter(['q' => $this->q, 'source' => $this->source, 'kind' => $this->kind], static fn ($v) => $v !== '');
$pages = (int) ceil($this->total / V::PER_PAGE);
$src = ['mined' => 'จากข้อเสนอ', 'manual' => 'เพิ่มเอง', 'import' => 'นำเข้า', 'tag' => 'จากแท็ก', 'cat' => 'จากหมวด'];
$sn = $this->snapshot;
?>
<style>
.wd-wrap{max-width:1180px}.wd-card{background:var(--bs-body-bg,#fff);border:1px solid var(--bs-border-color,#dee2e6);border-radius:.75rem;padding:1rem 1.15rem;margin:0 0 1rem}
.wd-card h3{font-size:1.02rem;margin:0 0 .6rem;font-weight:700}.wd-sub{font-size:.85rem;color:var(--bs-secondary-color,#6c757d)}
.wd-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1rem}.wd-stats{display:flex;flex-wrap:wrap;gap:.5rem 1.5rem;margin:0 0 1rem}.wd-stats b{font-size:1.3rem}
.wd-tok span{display:inline-block;margin:.15rem;padding:.1rem .5rem;border-radius:.4rem;background:var(--bs-secondary-bg,#e9ecef)}.wd-tok span.j{background:var(--bs-success-bg-subtle,#d1e7dd);font-weight:600}
.wd-badge{display:inline-block;padding:.1rem .55rem;border-radius:99px;font-size:.78rem;background:var(--bs-secondary-bg,#e9ecef)}.wd-off{opacity:.55}
.wd-filter{display:flex;flex-wrap:wrap;gap:.5rem;margin:0 0 .75rem}.wd-filter .form-control,.wd-filter .form-select{width:auto;min-width:9rem}
</style>
<div class="wd-wrap">
<p><a class="btn btn-outline-secondary btn-sm" href="index.php?option=com_aisearch#tools">&larr; แดชบอร์ด</a></p>
<?php if ($this->dirty) : ?><div class="alert alert-warning">แก้ไขคำศัพท์แล้ว แต่ดัชนียังใช้ชุดเดิม กด <a href="index.php?option=com_aisearch#system">อัปเดตดัชนี</a> เพื่อให้มีผล</div><?php endif; ?>
<div class="wd-stats">
  <div><b><?php echo number_format((int) ($sn['total'] ?? 0)); ?></b><br><span class="wd-sub">คำที่ดัชนีใช้อยู่</span></div>
  <div><b><?php echo number_format((int) ($sn['base'] ?? 0)); ?></b><br><span class="wd-sub">คำพื้นฐาน (ไฟล์)</span></div>
  <div><b><?php echo number_format((int) ($sn['starter'] ?? 0)); ?></b><br><span class="wd-sub">ชุดเริ่มต้นเว็บแม่และเด็ก</span></div>
  <div><b><?php echo number_format((int) ($sn['site'] ?? 0)); ?></b><br><span class="wd-sub">จากแท็กและหมวด</span></div>
  <div><b><?php echo number_format((int) ($sn['manual'] ?? 0)); ?></b><br><span class="wd-sub">เพิ่มเอง</span></div>
</div>
<?php if (!$sn) : ?><div class="alert alert-info">ดัชนีนี้สร้างก่อนมีระบบคำศัพท์ จึงยังตัดคำด้วย ICU อย่างเดียว กด "อัปเดตดัชนี" เพื่อเริ่มใช้คำศัพท์</div><?php endif; ?>
<div class="wd-grid">
  <div class="wd-card">
    <h3>ทดสอบการตัดคำ</h3>
    <p class="wd-sub">พิมพ์ข้อความ เพื่อดูว่า ICU ตัดอย่างไร และเมื่อใช้คำศัพท์ (ตามที่เป็นตอนนี้ แม้ยังไม่ได้อัปเดตดัชนี) จะรวมเป็นอะไร</p>
    <form action="index.php" method="get" class="wd-filter">
      <input type="hidden" name="option" value="com_aisearch"><input type="hidden" name="view" value="words">
      <label class="visually-hidden" for="wd-test">ข้อความ</label>
      <input class="form-control" id="wd-test" name="test" type="text" style="min-width:16rem" value="<?php echo $e($this->testText); ?>" placeholder="เช่น ทำไมถึงเป็นเบาหวานตอนท้อง">
      <button class="btn btn-outline-primary" type="submit">ทดสอบ</button>
    </form>
    <?php if ($this->testText !== '') : ?>
      <p class="mb-1"><strong>ICU:</strong> <span class="wd-tok"><?php foreach ($this->icu as $t) { echo '<span>' . $e($t) . '</span>'; } ?></span></p>
      <p class="mb-0"><strong>ใช้คำศัพท์:</strong> <span class="wd-tok"><?php foreach ($this->withLexicon as $t) { echo '<span class="' . (in_array($t, $this->icu, true) ? '' : 'j') . '">' . $e($t) . '</span>'; } ?></span></p>
    <?php endif; ?>
  </div>
  <div class="wd-card">
    <h3>เพิ่มคำ</h3>
    <form action="index.php?option=com_aisearch&amp;task=words.save" method="post">
      <div class="mb-2"><label class="form-label" for="wd-word">คำ (คำเดียว ไม่มีช่องว่าง)</label><input class="form-control" id="wd-word" name="word" type="text" maxlength="100" required></div>
      <div class="form-check"><input class="form-check-input" type="radio" name="kind" id="wd-k1" value="keep" checked><label class="form-check-label" for="wd-k1"><strong>รวมเป็นคำเดียว</strong> <span class="wd-sub">เช่น เบาหวาน (ICU ตัดเป็น เบา + หวาน)</span></label></div>
      <div class="form-check mb-2"><input class="form-check-input" type="radio" name="kind" id="wd-k2" value="split"><label class="form-check-label" for="wd-k2"><strong>ห้ามรวม</strong> <span class="wd-sub">ใช้เมื่อคำพื้นฐานรวมแล้วเพี้ยน</span></label></div>
      <button class="btn btn-primary" type="submit">เพิ่ม</button><?php echo $tok; ?>
    </form>
    <hr>
    <h3>นำเข้า / ส่งออก</h3>
    <p class="wd-sub">หนึ่งคำต่อบรรทัด ขึ้นต้นด้วย <code>!</code> คือห้ามรวม ขึ้นต้นด้วย # คือหมายเหตุ</p>
    <form action="index.php?option=com_aisearch&amp;task=words.import" method="post">
      <textarea class="form-control mb-2" name="text" rows="3" aria-label="คำที่จะนำเข้า"></textarea>
      <button class="btn btn-outline-primary btn-sm" type="submit">นำเข้า</button>
      <a class="btn btn-outline-secondary btn-sm" href="index.php?option=com_aisearch&amp;task=words.export&amp;<?php echo Session::getFormToken(); ?>=1">ส่งออก .txt</a><?php echo $tok; ?>
    </form>
  </div>
</div>
<div class="wd-card">
  <h3>คำที่น่าจะเพิ่ม <span class="wd-sub">(จากบทความของเว็บเอง)</span></h3>
  <p class="wd-sub">หาคู่คำที่อยู่ติดกันบ่อยกว่าที่ควรเป็นเมื่อเทียบกับการบังเอิญ และยังไม่อยู่ในคำศัพท์ ตัวช่วยเดา ไม่ใช่คำตอบ ให้ติ๊กเฉพาะที่เป็นคำจริง</p>
  <?php if (!$this->mined) : ?>
    <a class="btn btn-outline-primary btn-sm" href="<?php echo $base; ?>&amp;mine=1#wd-mined">ค้นหาคำที่น่าจะเพิ่ม (ใช้เวลาไม่กี่วินาที)</a>
  <?php elseif (!$this->candidates) : ?>
    <p class="wd-sub mb-0">ไม่พบคำที่ควรเสนอเพิ่ม</p>
  <?php else : ?>
  <form id="wd-mined" action="index.php?option=com_aisearch&amp;task=words.addMined" method="post">
    <div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th style="width:2rem"></th><th>คำ</th><th>พบติดกัน (ครั้ง)</th><th>ความมั่นใจ</th></tr></thead><tbody>
    <?php foreach ($this->candidates as $c) : ?><tr><td><input type="checkbox" class="form-check-input" name="words[]" value="<?php echo $e($c['word']); ?>" aria-label="เพิ่ม <?php echo $e($c['word']); ?>"></td><td><strong><?php echo $e($c['word']); ?></strong></td><td><?php echo (int) $c['count']; ?></td><td><?php echo $c['score']; ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <button class="btn btn-primary btn-sm" type="submit">เพิ่มคำที่ติ๊ก</button><?php echo $tok; ?>
  </form>
  <?php endif; ?>
</div>
<div class="wd-card">
  <h3>คำของเว็บและที่เพิ่มเอง <span class="wd-sub">(<?php echo number_format($this->total); ?>) — คำพื้นฐานจากไฟล์ไม่อยู่ในรายการนี้</span></h3>
  <form action="index.php" method="get" class="wd-filter">
    <input type="hidden" name="option" value="com_aisearch"><input type="hidden" name="view" value="words">
    <input class="form-control" name="q" type="search" value="<?php echo $e($this->q); ?>" placeholder="ค้นในรายการ" aria-label="ค้นในรายการ">
    <select class="form-select" name="source" aria-label="แหล่งที่มา"><option value="">ทุกแหล่ง</option><?php foreach ($src as $k => $l) { echo '<option value="' . $k . '"' . ($this->source === $k ? ' selected' : '') . '>' . $l . '</option>'; } ?></select>
    <select class="form-select" name="kind" aria-label="ชนิด"><option value="">ทุกชนิด</option><option value="keep"<?php echo $this->kind === 'keep' ? ' selected' : ''; ?>>รวมเป็นคำเดียว</option><option value="split"<?php echo $this->kind === 'split' ? ' selected' : ''; ?>>ห้ามรวม</option></select>
    <button class="btn btn-outline-secondary" type="submit">กรอง</button>
  </form>
  <?php if (!$this->rows) : ?><p class="wd-sub">ยังไม่มีคำ คำจากแท็กและหมวดจะขึ้นที่นี่หลังกด "อัปเดตดัชนี" ครั้งแรก</p><?php else : ?>
  <form action="index.php?option=com_aisearch&amp;task=words.bulk" method="post">
    <div class="table-responsive"><table class="table table-sm align-middle">
      <thead><tr><th style="width:2rem"><input type="checkbox" class="form-check-input" id="wd-all" aria-label="เลือกทั้งหมด"></th><th>คำ</th><th>ชนิด</th><th>แหล่ง</th></tr></thead><tbody>
      <?php foreach ($this->rows as $r) : $on = (int) $r->enabled === 1; ?>
        <tr class="<?php echo $on ? '' : 'wd-off'; ?>"><td><input type="checkbox" class="form-check-input wd-cb" name="cid[]" value="<?php echo (int) $r->id; ?>" aria-label="เลือก <?php echo $e($r->word); ?>"></td>
          <td><strong><?php echo $e($r->word); ?></strong></td><td><?php echo $r->kind === 'split' ? 'ห้ามรวม' : 'รวมเป็นคำเดียว'; ?></td>
          <td><span class="wd-badge"><?php echo $e($src[$r->source] ?? $r->source); ?></span><?php echo $on ? '' : ' <span class="wd-badge">ปิดอยู่</span>'; ?></td></tr>
      <?php endforeach; ?></tbody></table></div>
    <div class="d-flex gap-2 align-items-center">
      <select class="form-select" name="action" style="width:auto" aria-label="ทำกับที่เลือก"><option value="">ทำกับที่เลือก…</option><option value="enable">เปิดใช้</option><option value="disable">ปิดใช้</option><option value="delete">ลบ</option></select>
      <button class="btn btn-outline-secondary btn-sm" type="submit" id="wd-go">ตกลง</button>
    </div><?php echo $tok; ?>
  </form>
  <?php if ($pages > 1) : ?><nav class="mt-2 small"><?php if ($this->page > 1) : ?><a href="<?php echo $base . '&' . http_build_query($filters + ['page' => $this->page - 1]); ?>">&larr; ก่อนหน้า</a> <?php endif; ?>หน้า <?php echo $this->page; ?> / <?php echo $pages; ?><?php if ($this->page < $pages) : ?> <a href="<?php echo $base . '&' . http_build_query($filters + ['page' => $this->page + 1]); ?>">ถัดไป &rarr;</a><?php endif; ?></nav><?php endif; ?>
  <?php endif; ?>
</div>
</div>
<script>
(function () {
  var all = document.getElementById('wd-all');
  if (all) all.addEventListener('change', function () { document.querySelectorAll('.wd-cb').forEach(function (c) { c.checked = all.checked; }); });
  var go = document.getElementById('wd-go');
  if (go) go.addEventListener('click', function (e) { if (document.querySelector('[name=action]').value === 'delete' && !confirm('ลบคำที่เลือกถาวร ต้องการดำเนินการต่อหรือไม่?')) e.preventDefault(); });
})();
</script>
