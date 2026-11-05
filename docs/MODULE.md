# Search box module / โมดูลช่องค้นหา

**ไทย** · [English](#english)

`Colorpack Smart Search (ช่องค้นหา)` คือโมดูลฝั่งเว็บ วางได้ทุกตำแหน่งของเทมเพลต

## เพิ่มโมดูล

Content → Site Modules → New → เลือก **Colorpack Smart Search (ช่องค้นหา)** เลือกตำแหน่ง ตั้งให้แสดงทุกหน้า แล้วบันทึก

## ทำงานอย่างไร

- พิมพ์ตั้งแต่ 2 ตัวอักษร ระบบแสดง **ชื่อบทความที่ตรงกัน** ให้เลือก (เน้นคำที่พิมพ์) เลือกแล้วไปที่บทความทันที
- ท้ายรายการมี "ดูผลลัพธ์ทั้งหมดสำหรับ …" ส่งไปหน้าค้นหาเต็ม
- คลิกช่องที่ยังว่าง จะเห็น **คำค้นยอดนิยม** (ตามค่าของคอมโพเนนต์)
- คำแนะนำมาจากดัชนีคำค้นในฐานข้อมูลของเว็บเท่านั้น **ไม่เรียกบริการภายนอก**
- คีย์บอร์ด: ลูกศรขึ้น/ลง เลือก, Enter ไปที่รายการที่เลือก, Esc ปิดรายการ

## ตัวเลือก

| ตัวเลือก | ความหมาย |
|---|---|
| หน้าแสดงผลลัพธ์ | เลือกเมนู Search page หรือปล่อยอัตโนมัติ (ใช้เมนู Smart Search แรกที่เผยแพร่) |
| รูปทรง / ขนาด | แคปซูลหรือมุมมน ปกติหรือใหญ่ |
| ปุ่มค้นหา | ปุ่มข้อความ ปุ่มไอคอน หรือไม่มีปุ่ม |
| สีหลัก / ความกว้างสูงสุด | สีปุ่มและวงโฟกัส ความกว้างสูงสุดของช่อง (0 = เต็มความกว้าง) |
| คำแนะนำขณะพิมพ์ / คำค้นยอดนิยม | เปิดปิดแต่ละส่วน และจำนวนที่แสดง |

โมดูลใช้ฟอนต์ของเว็บ และมีสีกับรูปทรงในตัว จึงดูเหมือนกันในทุกเทมเพลต ปรับสีหลักให้เข้ากับเว็บได้

## ใช้แทนช่องค้นหาของ YOOtheme

YOOtheme สร้างปุ่มค้นหาในส่วนหัวจาก **โมดูล** ในตำแหน่งค้นหา (ไอคอนแว่นขยายที่เปิด modal หรือ dropdown) ส่วนที่ต้องเปลี่ยนคือโมดูลข้างใน ไม่ใช่ปุ่มของ YOOtheme

1. Content → Site Modules ค้นหาโมดูลชนิด **Smart Search** (ของ Joomla เดิม) จดว่าอยู่ **ตำแหน่ง (Position)** อะไร และตั้ง **Menu Assignment** อย่างไร (เว็บมักมี 2 ตัว คือเดสก์ท็อปและมือถือ)
2. New → **Colorpack Smart Search (ช่องค้นหา)** ใช้ตำแหน่งและ Menu Assignment เดียวกัน ตั้งสถานะ Published
3. ตัวเลือกที่แนะนำ: ปุ่ม = ไอคอน หรือไม่มีปุ่ม, หน้าแสดงผลลัพธ์ = อัตโนมัติ (หรือเลือกเมนู Search page)
4. **Unpublish** โมดูล Smart Search เดิม (ยังไม่ต้องลบ จนกว่าจะตรวจแล้ว)
5. เปิดหน้าเว็บ คลิกไอคอนแว่นขยาย ช่องจะโฟกัสทันที พิมพ์แล้วเห็นคำแนะนำ

ใช้เวอร์ชัน 0.8.1 ขึ้นไป (โมดูลโฟกัสช่องให้เองเมื่อ modal เปิด)

---

<a id="english"></a>

## English

`Colorpack Smart Search (search box)` is a site module you can place in any template position.

### Add the module

Content → Site Modules → New → choose **Colorpack Smart Search (search box)**, pick a position, show it on all pages, and save.

### How it works

- From 2 characters, it shows **matching article titles** (the typed text is highlighted). Choosing one opens the article.
- The last row, "See all results for …", goes to the full search page.
- Clicking the empty box shows **popular searches** (following the component's settings).
- Suggestions come only from the keyword index in your own database. **No external service is called.**
- Keyboard: Up/Down to choose, Enter to open the chosen item, Esc to close the list.

### Options

| Option | Meaning |
|---|---|
| Search results page | Pick a Search page menu item, or leave Automatic (the first published Smart Search menu item) |
| Shape / Size | Pill or rounded corners; normal or large |
| Search button | Text button, icon button, or none |
| Accent colour / Maximum width | Button and focus-ring colour; the box's maximum width (0 = full width) |
| Live suggestions / Popular searches | Turn each part on or off, and how many to show |

The module uses your site's font and carries its own colours and shapes, so it looks the same in every template. Change the accent colour to match your site.

### Replace the YOOtheme header search

YOOtheme builds the header search button from a **module** in the search position (the magnifier icon that opens a modal or dropdown). You replace the module inside it, not YOOtheme's button.

1. Content → Site Modules, find the **Smart Search** module (Joomla's own) and note its **Position** and **Menu Assignment** (sites often have two: desktop and mobile).
2. New → **Colorpack Smart Search (search box)**, using the same position and Menu Assignment, and set it to Published.
3. Suggested options: Button = icon or none, Results page = Automatic (or pick the Search page menu item).
4. **Unpublish** the old Smart Search module (do not delete it until you have checked).
5. Open the site and click the magnifier: the box is focused straight away, and suggestions appear as you type.

Use version 0.8.1 or later (the module focuses the box by itself when the modal opens).
