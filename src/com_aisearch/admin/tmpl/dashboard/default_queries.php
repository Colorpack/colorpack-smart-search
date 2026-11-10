<?php
\defined('_JEXEC') or die;

/** @var \Colorpack\Component\AiSearch\Administrator\View\Dashboard\HtmlView $this */
$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$st = $this->stats;
?>
<div class="ss-card">
  <h3>คำค้นยอดนิยม <small>ใน <?php echo (int) $st['days']; ?> วันที่ผ่านมา</small></h3>
  <?php if (!$st['top']) : ?>
    <p class="ss-empty">ยังไม่มีการค้น คำที่ผู้เข้าชมค้นบ่อยจะแสดงที่นี่</p>
  <?php else : ?>
  <div class="ss-table-wrap"><table class="table table-sm align-middle mb-0">
    <thead><tr><th scope="col">คำค้น</th><th scope="col" class="text-end">ครั้ง</th><th scope="col" class="text-end" title="จำนวนบทความที่พบเฉลี่ย">พบเฉลี่ย</th><th scope="col" class="text-end" title="สัดส่วนการค้นที่มีคนเปิดอ่านผล">เปิดอ่าน</th></tr></thead>
    <tbody>
    <?php foreach ($st['top'] as $r) : $r->t = (int) $r->t; ?>
      <tr><td><?php echo $e($r->q); ?></td><td class="text-end"><?php echo (int) $r->n; ?></td>
        <td class="text-end"><?php echo (float) $r->r < 0.5 ? '<span class="ss-b">ไม่เจอ</span>' : number_format((float) $r->r, 0); ?></td>
        <td class="text-end"><?php echo $r->t >= 3 ? round((int) $r->c / $r->t * 100) . '%' : '–'; ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <p class="ss-sub mt-2">"เปิดอ่าน" แสดงเมื่อมีข้อมูลคลิกอย่างน้อย 3 ครั้งของคำนั้น</p>
  <?php endif; ?>
</div>
