<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;

$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$token = Session::getFormToken();
?>
<style>
.aisearch-steps{list-style:none;margin:0 0 1rem;padding:0}
.aisearch-steps li{display:flex;align-items:center;gap:.75rem;padding:.4rem 0;color:var(--bs-secondary-color,#6c757d)}
.aisearch-steps li.run{color:inherit;font-weight:600}
.aisearch-steps li.ok{color:var(--bs-success,#198754)}
.aisearch-steps li.err{color:var(--bs-danger,#dc3545)}
.aisearch-steps li.skip{opacity:.55}
.aisearch-ico{width:1.4rem;height:1.4rem;flex:none;display:inline-flex;align-items:center;justify-content:center;border-radius:50%;border:2px solid currentColor;font-size:.8rem;line-height:1}
.aisearch-steps li.run .aisearch-ico{border-color:rgba(128,128,128,.3);border-top-color:var(--bs-primary,#0d6efd);animation:aisearch-spin .8s linear infinite}
@keyframes aisearch-spin{to{transform:rotate(360deg)}}
.aisearch-steps small{font-weight:400;color:var(--bs-secondary-color,#6c757d)}
@media (prefers-reduced-motion:reduce){.aisearch-steps li.run .aisearch-ico{animation:none}}

.ss-wrap{max-width:1180px}
.ss-top{display:flex;flex-wrap:wrap;gap:.75rem 1.25rem;align-items:center;justify-content:space-between;margin:0 0 1rem}
.ss-top p{margin:0;color:var(--bs-secondary-color,#6c757d)}
.ss-period{display:inline-flex;border:1px solid var(--bs-border-color,#dee2e6);border-radius:.5rem;overflow:hidden}
.ss-period a{padding:.3rem .85rem;text-decoration:none;color:inherit;font-size:.9rem}
.ss-period a[aria-current="true"]{background:var(--bs-primary,#0d6efd);color:#fff}
.ss-tabs{display:flex;gap:.25rem;border-bottom:1px solid var(--bs-border-color,#dee2e6);margin:0 0 1.25rem;overflow-x:auto}
.ss-tabs button{background:none;border:0;border-bottom:3px solid transparent;padding:.6rem 1rem;font:inherit;font-weight:600;color:var(--bs-secondary-color,#6c757d);cursor:pointer;white-space:nowrap}
.ss-tabs button[aria-selected="true"]{color:var(--bs-primary,#0d6efd);border-bottom-color:var(--bs-primary,#0d6efd)}
.ss-tabs button:focus-visible{outline:2px solid var(--bs-primary,#0d6efd);outline-offset:-2px}
.ss-pane[hidden]{display:none}
.ss-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(215px,1fr));gap:1rem;margin:0 0 1rem}
.ss-kpi,.ss-card{background:var(--bs-body-bg,#fff);border:1px solid var(--bs-border-color,#dee2e6);border-radius:.75rem;padding:1rem 1.15rem}
.ss-card{margin:0 0 1rem}
.ss-card h3{font-size:1.02rem;margin:0 0 .6rem;font-weight:700}
.ss-card h3 small{font-weight:400;color:var(--bs-secondary-color,#6c757d);margin-left:.4rem}
.ss-k-l{font-size:.88rem;color:var(--bs-secondary-color,#6c757d);margin:0 0 .15rem}
.ss-k-v{font-size:2rem;font-weight:700;line-height:1.2}
.ss-k-n,.ss-sub{font-size:.85rem;color:var(--bs-secondary-color,#6c757d)}
.ss-d{font-size:.85rem;margin-top:.3rem;font-weight:600}
.ss-d.ss-g{color:var(--bs-success,#198754)}.ss-d.ss-b{color:var(--bs-danger,#dc3545)}.ss-d.ss-n{color:var(--bs-secondary-color,#6c757d);font-weight:400}
.ss-b{color:var(--bs-danger,#dc3545);font-weight:600}
.ss-bar{height:.5rem;border-radius:99px;background:var(--bs-secondary-bg,#e9ecef);overflow:hidden;margin-top:.5rem}
.ss-bar span{display:block;height:100%;background:var(--bs-success,#198754)}.ss-bar span.warn{background:var(--bs-warning,#ffc107)}.ss-bar span.bad{background:var(--bs-danger,#dc3545)}
.ss-spark{width:100%;height:64px;color:var(--bs-primary,#0d6efd)}
.ss-axis{display:flex;justify-content:space-between;font-size:.78rem;color:var(--bs-secondary-color,#6c757d)}
.ss-cols{display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:1rem}
.ss-list{list-style:none;margin:0;padding:0}.ss-list li{padding:.5rem 0;border-top:1px solid var(--bs-border-color,#dee2e6)}.ss-list li:first-child{border-top:0}
.ss-row{display:flex;gap:.6rem;align-items:center}.ss-q{flex:1;min-width:0;overflow-wrap:anywhere;font-weight:600}.ss-n{color:var(--bs-secondary-color,#6c757d);font-size:.88rem;white-space:nowrap}
.ss-list details{margin-top:.35rem;font-size:.9rem}.ss-list summary{cursor:pointer;color:var(--bs-primary,#0d6efd)}
.ss-inline{display:flex;gap:.4rem;margin-top:.4rem}.ss-inline .form-control{flex:1}
.ss-empty{color:var(--bs-secondary-color,#6c757d);margin:.25rem 0}
.ss-note{background:var(--bs-warning-bg-subtle,#fff3cd);border:1px solid var(--bs-warning-border-subtle,#ffecb5);border-radius:.6rem;padding:.6rem .9rem;margin:0 0 1rem}
.ss-todo{border-color:var(--bs-primary,#0d6efd)}
.ss-check{list-style:none;margin:0;padding:0}.ss-check li{display:flex;gap:.7rem;align-items:flex-start;padding:.45rem 0}
.ss-mark{flex:none;width:1.4rem;height:1.4rem;border-radius:50%;border:2px solid currentColor;display:inline-flex;align-items:center;justify-content:center;font-size:.8rem}
.ss-check li.ok{color:var(--bs-success,#198754)}.ss-check li.ok strong{color:inherit}.ss-check li.todo .ss-mark{color:var(--bs-warning,#b58105)}.ss-check li.opt{color:var(--bs-secondary-color,#6c757d)}
.ss-check .ss-state{margin-left:auto;font-size:.8rem;color:var(--bs-secondary-color,#6c757d);white-space:nowrap}
.ss-tools{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem}
.ss-tool{display:flex;flex-direction:column;gap:.5rem}.ss-tool p{margin:0;flex:1}
.ss-table-wrap{overflow-x:auto}
@media (prefers-reduced-motion:reduce){*{animation:none!important}}
</style>
<div class="ss-wrap">
<div class="ss-top">
  <p>ภาพรวมการค้นหาในเว็บ ดูได้ว่าคนค้นอะไร เจอหรือไม่เจอ และควรปรับอะไร</p>
  <nav class="ss-period" aria-label="ช่วงเวลา">
    <?php foreach ([7 => '7 วัน', 30 => '30 วัน', 90 => '90 วัน'] as $d => $lbl) : ?>
      <a href="index.php?option=com_aisearch&amp;days=<?php echo $d; ?>"<?php echo $this->days === $d ? ' aria-current="true"' : ''; ?>><?php echo $lbl; ?></a>
    <?php endforeach; ?>
  </nav>
</div>
<div class="ss-tabs" role="tablist" aria-label="หมวดของแดชบอร์ด">
  <button type="button" role="tab" id="tab-overview" aria-controls="ss-overview" aria-selected="true" data-ss-tab="overview">ภาพรวม</button>
  <button type="button" role="tab" id="tab-queries" aria-controls="ss-queries" aria-selected="false" data-ss-tab="queries">คำค้น</button>
  <button type="button" role="tab" id="tab-tools" aria-controls="ss-tools" aria-selected="false" data-ss-tab="tools">ปรับปรุงผลการค้น</button>
  <button type="button" role="tab" id="tab-system" aria-controls="ss-system" aria-selected="false" data-ss-tab="system">ระบบและดัชนี</button>
</div>
<section class="ss-pane" id="ss-overview" role="tabpanel" aria-labelledby="tab-overview"><?php echo $this->loadTemplate('overview'); ?></section>
<section class="ss-pane" id="ss-queries" role="tabpanel" aria-labelledby="tab-queries"><?php echo $this->loadTemplate('queries'); ?></section>
<section class="ss-pane" id="ss-tools" role="tabpanel" aria-labelledby="tab-tools">
  <div class="ss-tools">
    <div class="ss-card ss-tool"><h3>เปรียบเทียบโหมดการค้น</h3><p class="ss-sub">พิมพ์คำค้นหลายคำ แล้วดูผลปัจจุบันเทียบกับโหมดความแม่นยำ โดยไม่กระทบผู้เข้าชม ควรลองก่อนเปิดโหมดความแม่นยำ</p>
      <a class="btn btn-primary btn-sm align-self-start" href="index.php?option=com_aisearch&amp;view=compare">เปิดหน้าเปรียบเทียบ</a></div>
    <div class="ss-card ss-tool"><h3>คำพ้อง</h3><p class="ss-sub">จัดการคำที่มีความหมายเหมือนกัน เช่น "นมน้อย" = "น้ำนมไม่พอ" เพิ่ม แก้ ปิดใช้ ค้นหา นำเข้า ส่งออก และทดสอบคำค้นได้</p>
      <a class="btn btn-primary btn-sm align-self-start" href="index.php?option=com_aisearch&amp;view=synonyms">จัดการคำพ้อง</a></div>
    <?php if (Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_aisearch')) : ?>
    <div class="ss-card ss-tool"><h3>ตัวเลือกของระบบ</h3><p class="ss-sub">ตั้งค่าโหมดความแม่นยำ เรียนรู้จากการคลิก คำพ้อง รูปแบบการแสดงผล และอื่น ๆ</p>
      <a class="btn btn-outline-secondary btn-sm align-self-start" href="index.php?option=com_config&amp;view=component&amp;component=com_aisearch">เปิดตัวเลือก</a></div>
    <?php endif; ?>
  </div>
</section>
<section class="ss-pane" id="ss-system" role="tabpanel" aria-labelledby="tab-system">
<?php if (!$this->intl) : ?>
<div class="alert alert-warning">PHP extension <code>intl</code> is not enabled on this server. Thai word segmentation needs it, so Thai searches will be much less accurate. Ask the hosting provider to enable <code>intl</code>.</div>
<?php endif; ?>

<div class="ss-card">
<h3>ดัชนีค้นหา <small>Index</small></h3>
    <p class="display-6 mb-1"><?php echo number_format($this->docs); ?></p>
    <p class="text-muted small mb-3">บทความในดัชนี<?php echo $this->updated ? ' · อัปเดตล่าสุด ' . HTMLHelper::_('date', $this->updated, 'Y-m-d H:i') : ' · <strong>ยังไม่ได้สร้างดัชนี</strong>'; ?></p>
    <button id="ai-start" class="btn btn-primary" type="button"><?php echo $this->updated ? 'อัปเดตดัชนี' : 'สร้างดัชนี'; ?></button>
    <noscript><p class="text-danger small mt-2">ต้องเปิด JavaScript จึงจะสร้างดัชนีได้</p></noscript>
    <?php if ($this->docs) : ?><p class="text-muted small mt-3 mb-0">พบรูป: <?php echo number_format($this->imgStats['article']); ?> จากตัวบทความ · <?php echo number_format($this->imgStats['fc']); ?> จาก FlexiContent · <strong<?php echo $this->imgStats['none'] > $this->docs / 2 ? ' class="text-danger"' : ''; ?>><?php echo number_format($this->imgStats['none']); ?> ไม่มีรูป</strong> (ใช้รูปแทน) เปลี่ยนแหล่งรูปได้ที่ ตัวเลือก &gt; Appearance แล้วอัปเดตดัชนี</p><?php endif; ?>
  </div>
<div id="ai-progress" class="card mb-3" style="display:none"><div class="card-body" aria-live="polite">
  <h3 class="h6 mb-3">กำลังสร้างดัชนีค้นหา</h3>
  <ol class="aisearch-steps">
    <li data-step="1"><span class="aisearch-ico"></span><span>อ่านบทความและสร้างดัชนีคำค้น <small class="ai-note"></small></span></li>
    <li data-step="2"><span class="aisearch-ico"></span><span>เสร็จสิ้น <small class="ai-note"></small></span></li>
  </ol>
  <div class="progress" style="height:1.25rem" role="progressbar" aria-label="ความคืบหน้า" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
    <div id="ai-bar" class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%"></div>
  </div>
  <div class="small text-muted mt-2" id="ai-status"></div>
  <div class="mt-3"><button id="ai-reload" class="btn btn-primary btn-sm" type="button" style="display:none">รีเฟรชหน้า</button></div>
</div></div>

</section>
</div>
<script>
(function () {
  var base = 'index.php?option=com_aisearch&format=json&<?php echo $e($token); ?>=1&task=';
  var $ = function (id) { return document.getElementById(id); };
  var panel = $('ai-progress'), bar = $('ai-bar'), status = $('ai-status'), startBtn = $('ai-start'), reloadBtn = $('ai-reload');
  var running = false;
  var fmt = function (n) { return Number(n).toLocaleString('th-TH'); };
  function step(n, state, note) {
    var li = panel.querySelector('li[data-step="' + n + '"]');
    li.className = state;
    li.querySelector('.aisearch-ico').textContent = state === 'ok' ? '✓' : state === 'err' ? '✕' : '';
    li.querySelector('.ai-note').textContent = note ? '· ' + note : '';
  }
  startBtn.addEventListener('click', function () {
    if (running) return;
    running = true; startBtn.disabled = true; panel.style.display = ''; reloadBtn.style.display = 'none';
    step(1, 'run'); step(2, '');
    bar.style.width = '100%'; bar.className = 'progress-bar progress-bar-striped progress-bar-animated'; bar.textContent = '';
    status.textContent = 'กำลังอ่านบทความทั้งหมดและตัดคำ — ปกติใช้เวลาไม่กี่วินาที ถึงครึ่งนาที';
    fetch(base + 'index.ajaxRebuild', { method: 'POST', credentials: 'same-origin' }).then(function (r) {
      return r.text().then(function (t) {
        var j; try { j = JSON.parse(t); } catch (e) { throw new Error('เซิร์ฟเวอร์ตอบกลับผิดรูปแบบ (HTTP ' + r.status + ') — ลองรีเฟรชหน้าแล้วทำใหม่'); }
        if (!j.ok) throw new Error(j.error || 'เกิดข้อผิดพลาด');
        return j;
      });
    }).then(function (r) {
      step(1, 'ok', fmt(r.docs) + ' บทความ · ' + r.seconds + ' วินาที'); step(2, 'ok');
      bar.className = 'progress-bar bg-success'; bar.textContent = '100%'; status.textContent = 'เสร็จแล้ว';
    }).catch(function (err) {
      step(1, 'err', 'ผิดพลาด'); bar.className = 'progress-bar bg-danger'; status.textContent = 'ผิดพลาด: ' + err.message;
    }).then(function () { running = false; reloadBtn.style.display = ''; });
  });
  reloadBtn.addEventListener('click', function () { window.location.href = 'index.php?option=com_aisearch'; });
})();
</script>
<script>
(function () {
  var tabs = document.querySelectorAll('.ss-tabs [data-ss-tab]'), panes = document.querySelectorAll('.ss-pane');
  function show(name, focus) {
    if (!document.getElementById('ss-' + name)) name = 'overview';
    panes.forEach(function (p) { p.hidden = p.id !== 'ss-' + name; });
    tabs.forEach(function (t) { var on = t.getAttribute('data-ss-tab') === name; t.setAttribute('aria-selected', on ? 'true' : 'false'); t.tabIndex = on ? 0 : -1; if (on && focus) t.focus(); });
    try { history.replaceState(null, '', '#' + name); } catch (e) {}
  }
  document.querySelectorAll('[data-ss-tab]').forEach(function (el) { el.addEventListener('click', function (ev) { ev.preventDefault(); show(el.getAttribute('data-ss-tab')); }); });
  document.querySelector('.ss-tabs').addEventListener('keydown', function (ev) {
    var i = Array.prototype.findIndex.call(tabs, function (t) { return t.getAttribute('aria-selected') === 'true'; });
    if (ev.key === 'ArrowRight') { ev.preventDefault(); show(tabs[(i + 1) % tabs.length].getAttribute('data-ss-tab'), true); }
    if (ev.key === 'ArrowLeft') { ev.preventDefault(); show(tabs[(i + tabs.length - 1) % tabs.length].getAttribute('data-ss-tab'), true); }
  });
  show((location.hash || '#overview').slice(1));
})();
</script>
