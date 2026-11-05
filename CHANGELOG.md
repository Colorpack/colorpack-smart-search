# Changelog / บันทึกการเปลี่ยนแปลง

## 0.8.0

- **EN** New site module `mod_smartsearch`: a modern search box with live suggestions (keyword index only, no external service), popular searches on focus, keyboard and screen-reader support. Shape, size, button, accent colour and width are options. Choose the results page or let it find the first Smart Search menu item. Also adds a small JSON endpoint (`task=suggest.show`) used by the module.
- **ไทย** เพิ่มโมดูลฝั่งเว็บ `mod_smartsearch`: ช่องค้นหาทันสมัยพร้อมคำแนะนำขณะพิมพ์ (ใช้เฉพาะดัชนีคำค้นในเว็บ ไม่เรียกบริการภายนอก) คำค้นยอดนิยมเมื่อคลิกช่อง รองรับคีย์บอร์ดและโปรแกรมอ่านหน้าจอ ตั้งรูปทรง ขนาด ปุ่ม สี และความกว้างได้ เลือกหน้าผลลัพธ์เองหรือให้หาเมนู Smart Search แรกให้ และเพิ่ม JSON endpoint เล็ก ๆ (`task=suggest.show`) ที่โมดูลใช้

## 0.7.0

- **EN** First public release as Colorpack Smart Search. Bootstrap 5 markup for Cassiopeia (auto-detected). Choose a Layout on the menu item (List only, Cards only, List + Cards switch) and your own layouts through template overrides. Tested on Joomla 5.4.10 and 6.2.0.
- **ไทย** ปล่อยเวอร์ชันสาธารณะแรกในชื่อ Colorpack Smart Search เพิ่มมาร์กอัป Bootstrap 5 สำหรับ Cassiopeia (ตรวจอัตโนมัติ) เพิ่ม Choose a Layout ในเมนู (รายการอย่างเดียว การ์ดอย่างเดียว รายการ+ปุ่มสลับการ์ด) และเลย์เอาต์ที่สร้างเองผ่าน template override ทดสอบบน Joomla 5.4.10 และ 6.2.0
