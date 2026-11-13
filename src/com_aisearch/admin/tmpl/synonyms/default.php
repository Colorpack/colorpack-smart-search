<?php
\defined('_JEXEC') or die;

use Colorpack\Component\AiSearch\Administrator\View\Synonyms\HtmlView as V;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;

/** @var V $this */
$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$tok = HTMLHelper::_('form.token');
$base = 'index.php?option=com_aisearch&view=synonyms';
$filters = array_filter(['q' => $this->q, 'source' => $this->source, 'status' => $this->status], static fn ($v) => $v !== '');
$pageUrl = static fn (int $p) => $base . '&' . http_build_query($filters + ['page' => $p]);
$pages = (int) ceil($this->total / V::PER_PAGE);
$srcLabel = ['manual' => 'เพิ่มเอง', 'ai' => 'AI เสนอ', 'starter' => 'ชุดเริ่มต้น', 'import' => 'นำเข้า', 'dashboard' => 'จากแดชบอร์ด'];
$edit = $this->edit;
?>
<style>
.sy-wrap{max-width:1180px}
.sy-card{background:var(--bs-body-bg,#fff);border:1px solid var(--bs-border-color,#dee2e6);border-radius:.75rem;padding:1rem 1.15rem;margin:0 0 1rem}
.sy-card h3{font-size:1.02rem;margin:0 0 .6rem;font-weight:700}
.sy-sub{font-size:.85rem;color:var(--bs-secondary-color,#6c757d)}
.sy-stats{display:flex;flex-wrap:wrap;gap:.5rem 1.5rem;margin:0 0 1rem}.sy-stats b{font-size:1.4rem}
.sy-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1rem}
.sy-badge{display:inline-block;padding:.1rem .55rem;border-radius:99px;font-size:.78rem;background:var(--bs-secondary-bg,#e9ecef)}
.sy-badge.ai{background:var(--bs-info-bg-subtle,#cff4fc)}.sy-badge.off{background:var(--bs-warning-bg-subtle,#fff3cd)}
.sy-table td{vertical-align:top}.sy-term{font-weight:600;overflow-wrap:anywhere}.sy-alts{overflow-wrap:anywhere}
.sy-off{opacity:.55}
.sy-filter{display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;margin:0 0 .75rem}.sy-filter .form-control,.sy-filter .form-select{width:auto;min-width:9rem}
.sy-bulk{display:flex;gap:.5rem;align-items:center;margin:.5rem 0}
.sy-pager{display:flex;gap:.6rem;align-items:center;margin-top:.75rem;font-size:.9rem}
.sy-hit{background:var(--bs-success-bg-subtle,#d1e7dd);border-radius:.4rem;padding:.35rem .6rem;margin:.3rem 0}
</style>
<div class="sy-wrap">
<p><a class="btn btn-outline-secondary btn-sm" href="index.php?option=com_aisearch#tools">&larr; แดชบอร์ด</a>
</p>
<?php if ($this->moved) : ?><div class="alert alert-info">ย้ายคำพ้องเดิมจากช่องข้อความในตัวเลือก เข้ามาอยู่ในหน้านี้แล้ว ไม่ต้องทำอะไรเพิ่ม</div><?php endif; ?>
<div class="sy-stats"><div><b><?php echo number_format($this->counts['total']); ?></b><br><span class="sy-sub">กฎทั้งหมด</span></div>
  <div><b><?php echo number_format($this->counts['on']); ?></b><br><span class="sy-sub">เปิดใช้อยู่</span></div>
  <?php /* AI-START */ ?><div><b><?php echo number_format($this->counts['ai']); ?></b><br><span class="sy-sub">มาจาก AI</span></div><?php /* AI-END */ ?></div>

<div class="sy-grid">
  <div class="sy-card" id="sy-form">
    <h3><?php echo $edit ? 'แก้ไขกฎ #' . (int) $edit->id : 'เพิ่มกฎคำพ้อง'; ?></h3>
    <form action="index.php?option=com_aisearch&amp;task=synonyms.save" method="post">
      <input type="hidden" name="id" value="<?php echo $edit ? (int) $edit->id : 0; ?>">
      <fieldset class="mb-2"><legend class="visually-hidden">ชนิดของกฎ</legend>
        <div class="form-check"><input class="form-check-input" type="radio" name="kind" id="k-one" value="one"<?php echo (!$edit || $edit->kind === 'one') ? ' checked' : ''; ?>>
          <label class="form-check-label" for="k-one"><strong>คำเดียว &rarr; ให้ค้นเจอคำอื่นด้วย</strong><br><span class="sy-sub">คนพิมพ์ "นมน้อย" ให้หาบทความที่ใช้คำว่า "น้ำนมไม่พอ" ด้วย (ทางเดียว)</span></label></div>
        <div class="form-check mt-1"><input class="form-check-input" type="radio" name="kind" id="k-set" value="set"<?php echo ($edit && $edit->kind === 'set') ? ' checked' : ''; ?>>
          <label class="form-check-label" for="k-set"><strong>กลุ่มคำที่ความหมายเท่ากัน</strong><br><span class="sy-sub">พิมพ์คำไหนก็หาคำอื่นในกลุ่มด้วย (สองทาง) เช่น "ท้องผูก, ถ่ายยาก, อุจจาระแข็ง"</span></label></div>
      </fieldset>
      <div class="mb-2" id="sy-term-row"><label class="form-label" for="sy-term">คำที่คนพิมพ์ (สำหรับแบบคำเดียว)</label>
        <div class="sy-sub mb-1">ใช้ <code>+</code> เพื่อให้กฎทำงานเมื่อมีครบทุกส่วน เช่น <code>นมแม่ + เยอะ</code> = เพิ่มน้ำนม, กระตุ้นน้ำนม (ความหมาย "อยากมีนมเยอะ")</div>
        <input class="form-control" id="sy-term" name="term" type="text" maxlength="200" value="<?php echo $edit ? $e($edit->term) : ''; ?>"></div>
      <div class="mb-2"><label class="form-label" for="sy-alts"><span id="sy-alts-lbl">คำที่ต้องการให้ค้นเจอด้วย</span> <span class="sy-sub">(คั่นด้วยเครื่องหมายจุลภาค ,)</span></label>
        <textarea class="form-control" id="sy-alts" name="alts" rows="3" required><?php echo $edit ? $e($edit->alts) : ''; ?></textarea></div>
      <div class="mb-2"><label class="form-label" for="sy-note">บันทึกช่วยจำ (ไม่บังคับ)</label>
        <input class="form-control" id="sy-note" name="note" type="text" maxlength="255" value="<?php echo $edit ? $e($edit->note) : ''; ?>"></div>
      <div class="form-check mb-3"><input type="hidden" name="enabled" value="0"><input class="form-check-input" type="checkbox" id="sy-en" name="enabled" value="1"<?php echo (!$edit || (int) $edit->enabled === 1) ? ' checked' : ''; ?>><label class="form-check-label" for="sy-en">เปิดใช้งาน</label></div>
      <button class="btn btn-primary" type="submit"><?php echo $edit ? 'บันทึก' : 'เพิ่มกฎ'; ?></button>
      <?php if ($edit) : ?><a class="btn btn-outline-secondary" href="<?php echo $base; ?>">ยกเลิก</a><?php endif; ?>
      <?php echo $tok; ?>
    </form>
  </div>

  <div class="sy-card">
    <h3>ทดสอบคำค้น</h3>
    <p class="sy-sub">พิมพ์คำที่ผู้เข้าชมอาจค้น เพื่อดูว่ากฎข้อไหนทำงาน และระบบจะค้นคำอะไรเพิ่ม</p>
    <form action="index.php" method="get" class="sy-filter">
      <input type="hidden" name="option" value="com_aisearch"><input type="hidden" name="view" value="synonyms">
      <label class="visually-hidden" for="sy-test">คำค้นที่ต้องการทดสอบ</label>
      <input class="form-control" id="sy-test" name="test" type="text" value="<?php echo $e($this->testQ); ?>" placeholder="เช่น ลูกนมน้อย">
      <button class="btn btn-outline-primary" type="submit">ทดสอบ</button>
    </form>
    <?php if ($this->testQ !== '') : ?>
      <?php if (!$this->test['rules']) : ?><p class="sy-sub">ไม่มีกฎที่ตรงกับคำนี้</p><?php else : ?>
        <?php foreach ($this->test['rules'] as $r) : ?>
          <div class="sy-hit<?php echo $r['enabled'] ? '' : ' sy-off'; ?>"><strong><?php echo $e($r['term']); ?></strong> &rarr; <?php echo $e($r['alts']); ?>
            <?php echo $r['enabled'] ? '' : '<span class="sy-badge off">ปิดอยู่</span>'; ?> <a href="<?php echo $base . '&id=' . (int) $r['id'] . '#sy-form'; ?>">แก้ไข</a></div>
        <?php endforeach; ?>
        <p class="mb-0"><strong>คำที่ระบบจะค้นเพิ่ม:</strong> <?php echo $this->test['words'] ? $e(implode(', ', $this->test['words'])) : '–'; ?></p>
      <?php endif; ?>
    <?php endif; ?>
    <hr>
    <h3>นำเข้า / ส่งออก</h3>
    <p class="sy-sub">หนึ่งกฎต่อหนึ่งบรรทัด: <code>คำ = คำพ้อง1, คำพ้อง2</code> (ทางเดียว) หรือ <code>คำ1, คำ2, คำ3</code> (กลุ่มความหมายเท่ากัน) บรรทัดที่ขึ้นต้นด้วย # ถูกข้าม</p>
    <form action="index.php?option=com_aisearch&amp;task=synonyms.import" method="post">
      <label class="visually-hidden" for="sy-imp">ข้อความที่จะนำเข้า</label>
      <textarea class="form-control mb-2" id="sy-imp" name="text" rows="4" placeholder="นมน้อย = น้ำนมไม่พอ, น้ำนมน้อย&#10;ท้องผูก, ถ่ายยาก, อุจจาระแข็ง"></textarea>
      <button class="btn btn-outline-primary btn-sm" type="submit">นำเข้า</button>
      <a class="btn btn-outline-secondary btn-sm" href="index.php?option=com_aisearch&amp;task=synonyms.export&amp;<?php echo Session::getFormToken(); ?>=1">ส่งออกเป็นไฟล์ .txt</a>
      <?php echo $tok; ?>
    </form>
  </div>
</div>

<div class="sy-card">
  <h3>รายการกฎ <span class="sy-sub">(<?php echo number_format($this->total); ?>)</span></h3>
  <form action="index.php" method="get" class="sy-filter">
    <input type="hidden" name="option" value="com_aisearch"><input type="hidden" name="view" value="synonyms">
    <label class="visually-hidden" for="sy-q">ค้นในรายการ</label>
    <input class="form-control" id="sy-q" name="q" type="search" value="<?php echo $e($this->q); ?>" placeholder="ค้นในรายการกฎ">
    <label class="visually-hidden" for="sy-st">สถานะ</label>
    <select class="form-select" id="sy-st" name="status"><option value="">ทุกสถานะ</option><option value="on"<?php echo $this->status === 'on' ? ' selected' : ''; ?>>เปิดใช้</option><option value="off"<?php echo $this->status === 'off' ? ' selected' : ''; ?>>ปิดอยู่</option></select>
    <label class="visually-hidden" for="sy-so">แหล่งที่มา</label>
    <select class="form-select" id="sy-so" name="source"><option value="">ทุกแหล่ง</option>
      <?php foreach ($srcLabel as $k => $l) : ?><option value="<?php echo $k; ?>"<?php echo $this->source === $k ? ' selected' : ''; ?>><?php echo $l; ?></option><?php endforeach; ?></select>
    <button class="btn btn-outline-secondary" type="submit">กรอง</button>
    <?php if ($filters) : ?><a href="<?php echo $base; ?>">ล้างตัวกรอง</a><?php endif; ?>
  </form>
  <?php if (!$this->rows) : ?>
    <p class="sy-sub"><?php echo $this->counts['total'] ? 'ไม่พบกฎที่ตรงกับตัวกรอง' : 'ยังไม่มีกฎ เพิ่มกฎแรกได้ที่ฟอร์มด้านบน'; ?></p>
  <?php else : ?>
  <form action="index.php?option=com_aisearch&amp;task=synonyms.bulk" method="post">
    <div class="table-responsive"><table class="table table-sm sy-table align-middle">
      <thead><tr><th style="width:2rem"><input type="checkbox" class="form-check-input" id="sy-all" aria-label="เลือกทั้งหมดในหน้านี้"></th><th>คำที่คนพิมพ์</th><th>ให้ค้นเจอด้วย</th><th title="จำนวนการค้นใน 30 วันที่มีคำนี้">ใช้ (30 วัน)</th><th>แหล่ง</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($this->rows as $r) : $on = (int) $r->enabled === 1; ?>
        <tr class="<?php echo $on ? '' : 'sy-off'; ?>">
          <td><input type="checkbox" class="form-check-input sy-cb" name="cid[]" value="<?php echo (int) $r->id; ?>" aria-label="เลือกกฎ <?php echo (int) $r->id; ?>"></td>
          <td class="sy-term"><?php echo $r->kind === 'set' ? '<span class="sy-badge">กลุ่มคำ</span>' : $e($r->term); ?></td>
          <td class="sy-alts"><?php echo $e($r->alts); ?><?php echo trim((string) $r->note) !== '' ? '<br><span class="sy-sub">' . $e($r->note) . '</span>' : ''; ?></td>
          <td><?php echo number_format($this->usage[(int) $r->id] ?? 0); ?></td>
          <td><span class="sy-badge<?php echo $r->source === 'ai' ? ' ai' : ''; ?>"><?php echo $e($srcLabel[$r->source] ?? $r->source); ?></span><?php echo $on ? '' : ' <span class="sy-badge off">ปิดอยู่</span>'; ?></td>
          <td class="text-end"><a class="btn btn-outline-secondary btn-sm" href="<?php echo $base . '&id=' . (int) $r->id . '#sy-form'; ?>">แก้ไข</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table></div>
    <div class="sy-bulk">
      <label class="visually-hidden" for="sy-act">ทำกับรายการที่เลือก</label>
      <select class="form-select" id="sy-act" name="action" style="width:auto"><option value="">ทำกับที่เลือก…</option><option value="enable">เปิดใช้</option><option value="disable">ปิดใช้</option><option value="delete">ลบ</option></select>
      <button class="btn btn-outline-secondary btn-sm" type="submit" id="sy-go">ตกลง</button>
    </div>
    <?php echo $tok; ?>
  </form>
  <?php if ($pages > 1) : ?>
  <nav class="sy-pager" aria-label="หน้ารายการ">
    <?php if ($this->page > 1) : ?><a href="<?php echo $pageUrl($this->page - 1); ?>">&larr; ก่อนหน้า</a><?php endif; ?>
    <span>หน้า <?php echo $this->page; ?> / <?php echo $pages; ?></span>
    <?php if ($this->page < $pages) : ?><a href="<?php echo $pageUrl($this->page + 1); ?>">ถัดไป &rarr;</a><?php endif; ?>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</div>
</div>
<script>
(function () {
  var all = document.getElementById('sy-all');
  if (all) all.addEventListener('change', function () { document.querySelectorAll('.sy-cb').forEach(function (c) { c.checked = all.checked; }); });
  var go = document.getElementById('sy-go');
  if (go) go.addEventListener('click', function (e) { if (document.getElementById('sy-act').value === 'delete' && !confirm('ลบกฎที่เลือกถาวร ต้องการดำเนินการต่อหรือไม่?')) e.preventDefault(); });
  function sync() {
    var set = document.getElementById('k-set').checked;
    document.getElementById('sy-term-row').style.display = set ? 'none' : '';
    document.getElementById('sy-alts-lbl').textContent = set ? 'คำทั้งหมดในกลุ่ม (อย่างน้อย 2 คำ)' : 'คำที่ต้องการให้ค้นเจอด้วย';
  }
  document.getElementById('k-one').addEventListener('change', sync); document.getElementById('k-set').addEventListener('change', sync); sync();
})();
</script>
