# Changelog / บันทึกการเปลี่ยนแปลง

## 0.9.2

- **EN** Fixed: the "Searching…" indicator did not appear on the YOOtheme header search, because Joomla's own Smart Search script stops the form's submit event from reaching other scripts. The bridge plugin now listens earlier, and also covers choosing one of Smart Search's suggestions.
- **ไทย** แก้: ข้อความ "กำลังค้นหา…" ไม่ขึ้นที่ช่องค้นหาส่วนหัวของ YOOtheme เพราะสคริปต์ Smart Search ของ Joomla หยุดเหตุการณ์ส่งฟอร์มไม่ให้ถึงสคริปต์อื่น ตอนนี้ปลั๊กอิน bridge ฟังก่อนหน้านั้น และครอบคลุมกรณีเลือกคำแนะนำของ Smart Search ด้วย

## 0.9.1

- **EN** Searches now show that they are working: a bar at the top of the page and "Searching…" under the search box from the moment a search form is sent (the search results page, the search box module, and, through the bridge plugin, the YOOtheme header search and any other search form). Switch it off in the bridge plugin if you do not want it.
- **EN** Faster: the keyword index is stored in a compact form, so every search loads it in a few milliseconds instead of about 400 ms, and uses about a fifth of the memory (10 MB instead of 53 MB). Results are identical. An existing index is converted automatically on its first use.
- **ไทย** การค้นหาแสดงสถานะว่ากำลังทำงาน: มีแถบด้านบนของหน้าและข้อความ "กำลังค้นหา…" ใต้ช่องค้นหา ตั้งแต่ส่งฟอร์ม (หน้าผลลัพธ์ โมดูลช่องค้นหา และผ่านปลั๊กอิน bridge ยังครอบคลุมช่องค้นหาส่วนหัวของ YOOtheme และฟอร์มค้นหาอื่น ๆ) ปิดได้ในปลั๊กอิน bridge
- **ไทย** เร็วขึ้น: เก็บดัชนีคำค้นในรูปแบบกะทัดรัด การโหลดดัชนีต่อการค้นเหลือไม่กี่มิลลิวินาที จากเดิมราว 400 มิลลิวินาที และใช้หน่วยความจำประมาณหนึ่งในห้า (10 MB จากเดิม 53 MB) ผลลัพธ์เหมือนเดิมทุกประการ ดัชนีเดิมจะถูกแปลงอัตโนมัติเมื่อใช้ครั้งแรก

## 0.9.0

- **EN** New system plugin "Colorpack Smart Search bridge" (installed off): sends Joomla Smart Search (`com_finder`) searches, including the YOOtheme header search and old search links, to the Colorpack Smart Search page, and answers search-as-you-type with our titles. See docs/BRIDGE.md.
- **ไทย** เพิ่มปลั๊กอิน system "Colorpack Smart Search bridge" (ติดตั้งแล้วปิดไว้) ส่งการค้นหาของ Smart Search เดิม (`com_finder`) รวมถึงช่องค้นหาส่วนหัวของ YOOtheme และลิงก์เก่า มายังหน้าค้นหาของ Colorpack Smart Search และตอบคำแนะนำขณะพิมพ์ด้วยชื่อบทความของเรา ดู docs/BRIDGE.md

## 0.8.2

- **EN** Fixed "Invalid field: Layout" when saving the search box module on sites with template overrides (YOOtheme). The Layout field now uses Joomla's own validation.
- **ไทย** แก้ error "Invalid field: Layout" ตอนบันทึกโมดูลช่องค้นหาในเว็บที่มี template override (เช่น YOOtheme) ช่อง Layout ใช้การตรวจสอบมาตรฐานของ Joomla แล้ว

## 0.8.1

- **EN** The search box module focuses itself when a YOOtheme / UIkit modal or dropdown opens, so it can replace the YOOtheme header search. New guide: replacing the header search.
- **ไทย** โมดูลช่องค้นหาโฟกัสช่องให้เองเมื่อ modal หรือ dropdown ของ YOOtheme / UIkit เปิด จึงใช้แทนช่องค้นหาในส่วนหัวของ YOOtheme ได้ เพิ่มคู่มือการแทนที่ช่องค้นหาในส่วนหัว

## 0.8.0

- **EN** New site module `mod_smartsearch`: a modern search box with live suggestions (keyword index only, no external service), popular searches on focus, keyboard and screen-reader support. Shape, size, button, accent colour and width are options. Choose the results page or let it find the first Smart Search menu item. Also adds a small JSON endpoint (`task=suggest.show`) used by the module.
- **ไทย** เพิ่มโมดูลฝั่งเว็บ `mod_smartsearch`: ช่องค้นหาทันสมัยพร้อมคำแนะนำขณะพิมพ์ (ใช้เฉพาะดัชนีคำค้นในเว็บ ไม่เรียกบริการภายนอก) คำค้นยอดนิยมเมื่อคลิกช่อง รองรับคีย์บอร์ดและโปรแกรมอ่านหน้าจอ ตั้งรูปทรง ขนาด ปุ่ม สี และความกว้างได้ เลือกหน้าผลลัพธ์เองหรือให้หาเมนู Smart Search แรกให้ และเพิ่ม JSON endpoint เล็ก ๆ (`task=suggest.show`) ที่โมดูลใช้

## 0.7.0

- **EN** First public release as Colorpack Smart Search. Bootstrap 5 markup for Cassiopeia (auto-detected). Choose a Layout on the menu item (List only, Cards only, List + Cards switch) and your own layouts through template overrides. Tested on Joomla 5.4.10 and 6.2.0.
- **ไทย** ปล่อยเวอร์ชันสาธารณะแรกในชื่อ Colorpack Smart Search เพิ่มมาร์กอัป Bootstrap 5 สำหรับ Cassiopeia (ตรวจอัตโนมัติ) เพิ่ม Choose a Layout ในเมนู (รายการอย่างเดียว การ์ดอย่างเดียว รายการ+ปุ่มสลับการ์ด) และเลย์เอาต์ที่สร้างเองผ่าน template override ทดสอบบน Joomla 5.4.10 และ 6.2.0
