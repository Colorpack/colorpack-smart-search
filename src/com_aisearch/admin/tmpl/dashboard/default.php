<?php
\defined('_JEXEC') or die;

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
</style>
<?php if (!$this->intl) : ?>
<div class="alert alert-warning">PHP extension <code>intl</code> is not enabled on this server. Thai word segmentation needs it, so Thai searches will be much less accurate. Ask the hosting provider to enable <code>intl</code>.</div>
<?php endif; ?>
<div class="row">
  <div class="col-md-6"><div class="card mb-3"><div class="card-body">
    <h3 class="h6">Index</h3>
    <p class="display-6 mb-1"><?php echo number_format($this->docs); ?></p>
    <p class="text-muted small mb-3">articles indexed<?php echo $this->updated ? ' · updated ' . HTMLHelper::_('date', $this->updated, 'Y-m-d H:i') : ' · <strong>not built yet</strong>'; ?></p>
    <button id="ai-start" class="btn btn-primary" type="button"><?php echo $this->updated ? 'Rebuild / update index' : 'Build index'; ?></button>
    <noscript><p class="text-danger small mt-2">JavaScript is required for building the index.</p></noscript>
    <?php if ($this->docs) : ?><p class="text-muted small mt-3 mb-0">Pictures found: <?php echo number_format($this->imgStats['article']); ?> from the article · <?php echo number_format($this->imgStats['fc']); ?> from FlexiContent · <strong<?php echo $this->imgStats['none'] > $this->docs / 2 ? ' class="text-danger"' : ''; ?>><?php echo number_format($this->imgStats['none']); ?> without a picture</strong> (placeholder shown). Change the source in Options &gt; Appearance, then rebuild.</p><?php endif; ?>
  </div></div></div>
  <div class="col-md-6"><div class="card mb-3"><div class="card-body">
    <h3 class="h6">Last 30 days</h3>
    <p class="display-6 mb-1"><?php echo number_format($this->searches30); ?></p>
    <p class="text-muted small mb-0">searches · <?php echo number_format($this->zero30); ?> with no result</p>
  </div></div></div>
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

<div class="row">
  <div class="col-md-6"><div class="card mb-3"><div class="card-body">
    <h3 class="h6">Top searches</h3>
    <?php if (!$this->top) : ?><p class="text-muted">No data yet.</p><?php else : ?>
    <table class="table table-sm mb-0"><tbody>
    <?php foreach ($this->top as $r) : ?><tr><td><?php echo $e($r->q); ?></td><td class="text-end"><?php echo (int) $r->n; ?></td></tr><?php endforeach; ?>
    </tbody></table><?php endif; ?>
  </div></div></div>
  <div class="col-md-6"><div class="card mb-3"><div class="card-body">
    <h3 class="h6">Searches with no result</h3>
    <?php if (!$this->zero) : ?><p class="text-muted">None.</p><?php else : ?>
    <table class="table table-sm mb-0"><tbody>
    <?php foreach ($this->zero as $r) : ?><tr><td><?php echo $e($r->q); ?></td><td class="text-end"><?php echo (int) $r->n; ?></td></tr><?php endforeach; ?>
    </tbody></table><?php endif; ?>
  </div></div></div>
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
