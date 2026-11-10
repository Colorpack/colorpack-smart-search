# Changelog / บันทึกการเปลี่ยนแปลง

## 0.11.1

- **EN** The article's **Meta Keywords** are now used by the search. They count as much as tags (more than the body text) and the precision mode treats a match in them like a match in the tags. Press **Rebuild / update index** once after upgrading; articles saved afterwards are updated automatically. The upgrade adds one column to the text table.
- **ไทย** ระบบค้นหาใช้ **Meta Keywords** ของบทความแล้ว นับน้ำหนักเท่าแท็ก (มากกว่าเนื้อหา) และโหมดความแม่นยำนับว่าเจอในคีย์เวิร์ดเหมือนเจอในแท็ก หลังอัปเกรดให้กด **Rebuild / update index** หนึ่งครั้ง บทความที่บันทึกหลังจากนั้นจะอัปเดตอัตโนมัติ การอัปเกรดเพิ่ม 1 คอลัมน์ในตารางข้อความ

## 0.11.0

- **EN** New **Learn from clicks** (Options > Search, on by default). When several visitors who searched for the same words open the same article, that article is moved up for that search. A count needs 3 clicks by default (changeable), and each visitor session counts a given search and article once. Only the search text, the article number and a count are stored, with no visitor information. The upgrade adds one small table.
- **ไทย** เพิ่ม **เรียนรู้จากการคลิก** (ตัวเลือก > Search เปิดไว้เป็นค่าเริ่มต้น) เมื่อผู้เข้าชมหลายคนที่ค้นด้วยคำเดียวกันเปิดบทความเดียวกัน บทความนั้นจะถูกดันขึ้นสำหรับคำค้นนั้น ต้องมีคลิก 3 ครั้งขึ้นไปโดยค่าเริ่มต้น (ปรับได้) และนับหนึ่งครั้งต่อคำค้นและบทความต่อหนึ่งเซสชันของผู้เข้าชม เก็บเฉพาะข้อความที่ค้น เลขบทความ และจำนวนครั้ง ไม่เก็บข้อมูลผู้เข้าชม การอัปเกรดเพิ่มตารางเล็ก ๆ หนึ่งตาราง
- **EN** New **dashboard**, with four tabs: Overview, Queries, Improve results and System and index. The Overview shows a getting-started checklist (until the site is set up), the number of searches, the share of searches that found nothing, and the share that led to a click, each compared with the period before, a daily chart, the searches that found nothing (with a button to add a synonym right there) and the searches nobody opened. Choose 7, 30 or 90 days. The Queries tab lists the most popular searches with how many articles they found and how often they were opened. The click figures need a few weeks of visits to be meaningful. A search is now stored with a flag for whether it led to a click and at which position; the upgrade adds three columns to the search log. The search log is deleted after 180 days (Options > Search, "Keep the search log for").
- **ไทย** **แดชบอร์ดใหม่** มี 4 แท็บ: ภาพรวม, คำค้น, ปรับปรุงผลการค้น, ระบบและดัชนี หน้าภาพรวมมีเช็กลิสต์เริ่มต้นใช้งาน (จนกว่าจะตั้งค่าครบ) จำนวนการค้น สัดส่วนการค้นที่ไม่เจอผล และสัดส่วนที่มีคนเปิดอ่าน เทียบกับช่วงก่อนหน้า กราฟรายวัน คำที่ค้นแล้วไม่เจอ (มีปุ่มเพิ่มคำพ้องได้ทันที) และคำที่ค้นแล้วไม่มีใครเปิดอ่าน เลือกดู 7, 30 หรือ 90 วัน แท็บคำค้นแสดงคำยอดนิยม พร้อมจำนวนบทความที่พบและสัดส่วนที่ถูกเปิดอ่าน ตัวเลขการคลิกต้องใช้เวลาสะสมสักระยะจึงมีความหมาย ตอนนี้ระบบบันทึกว่าการค้นแต่ละครั้งมีคนคลิกหรือไม่และคลิกอันดับที่เท่าไร การอัปเกรดเพิ่ม 3 คอลัมน์ในบันทึกการค้น และบันทึกการค้นจะถูกลบหลัง 180 วัน (ตัวเลือก > Search "เก็บบันทึกการค้นไว้")

## 0.10.0

- **EN** New, optional **Precision mode** (off by default). Instead of listing every article that contains one of the words, it checks each candidate and keeps only articles that are really about the search: the phrase or all the key words are in the title (best), in the tags or category, or discussed in the text (not just mentioned once). Quantities with units are treated as a unit, so "เด็ก 1 เดือน" no longer returns "6 เดือน" articles. Results are ordered by this match level first, then by the usual ranking. Articles that only mention the words are hidden (Options > Search: how strict, and how common a word must be to count as context). Turn it on in Options > Search. After upgrading, press **Rebuild / update index** once so the text needed for the check is stored.
- **EN** New admin page **Compare search modes** (button on the dashboard): type up to 30 searches and see the current results beside the Precision results, with the match level of each article. Try it before switching the mode on. Administrators can also add `?explain=1` to a results page to see why each article is listed.
- **ไทย** เพิ่ม **โหมดความแม่นยำ (Precision mode)** แบบเลือกเปิดเอง (ปิดไว้เป็นค่าเริ่มต้น) แทนที่จะแสดงทุกบทความที่มีคำใดคำหนึ่ง ระบบจะตรวจแต่ละบทความและเก็บเฉพาะที่เกี่ยวกับคำค้นจริง คือมีวลีหรือคำสำคัญครบในชื่อเรื่อง (ดีที่สุด) ในแท็กหรือหมวด หรือกล่าวถึงเป็นเรื่องเป็นราวในเนื้อหา (ไม่ใช่แค่เอ่ยถึงครั้งเดียว) ตัวเลขกับหน่วยถูกมองเป็นหน่วยเดียว เช่น "เด็ก 1 เดือน" จะไม่ได้บทความ "6 เดือน" ผลลัพธ์เรียงตามระดับความตรงก่อน แล้วจึงเรียงตามคะแนนปกติ บทความที่แค่เอ่ยถึงคำจะถูกซ่อน (ตั้งความเข้มงวดและเกณฑ์คำทั่วไปได้ที่ ตัวเลือก > Search) เปิดได้ที่ ตัวเลือก > Search หลังอัปเกรดให้กด **Rebuild / update index** หนึ่งครั้งเพื่อเก็บข้อความที่ใช้ตรวจ
- **ไทย** เพิ่มหน้าผู้ดูแล **เปรียบเทียบโหมดการค้น** (ปุ่มในแดชบอร์ด) พิมพ์คำค้นได้สูงสุด 30 คำ แล้วดูผลปัจจุบันเทียบกับผลโหมดความแม่นยำ พร้อมระดับความตรงของแต่ละบทความ ควรลองก่อนเปิดใช้ ผู้ดูแลระบบเติม `?explain=1` ท้าย URL หน้าผลลัพธ์เพื่อดูเหตุผลที่แต่ละบทความถูกแสดงได้

## 0.9.4

- **EN** Fixed: the pagination buttons (Next, Previous, page numbers) on the search results page did nothing, because the page number in the link was never read. Page 2, 3 and so on now show the right results, in the List and Cards views and with Bootstrap, UIkit (YOOtheme) and plain markup. A link past the last page shows the last page.
- **ไทย** แก้: ปุ่มแบ่งหน้า (ถัดไป ก่อนหน้า และเลขหน้า) ในหน้าผลลัพธ์ไม่ทำงาน เพราะระบบไม่เคยอ่านเลขหน้าจากลิงก์ ตอนนี้หน้า 2, 3 และถัดไปแสดงผลถูกต้อง ทั้งมุมมองรายการและการ์ด และทั้งมาร์กอัป Bootstrap, UIkit (YOOtheme) และแบบธรรมดา ลิงก์ที่เกินหน้าสุดท้ายจะแสดงหน้าสุดท้าย

## 0.9.3

- **EN** Scheduled and restricted articles are handled correctly when searching. An article with a future *Start publishing* date no longer shows up before that date, an article whose *Finish publishing* date has passed stops showing up, and articles in an access level the visitor does not have (for example Registered) are hidden. This is checked at search time, so nothing has to run when a date arrives. The upgrade adds three columns to the document table; articles indexed before the upgrade are treated as public and always visible until the index is rebuilt once.
- **ไทย** จัดการบทความที่ตั้งเวลาเผยแพร่และจำกัดสิทธิ์ได้ถูกต้องเมื่อค้นหา บทความที่ตั้งวันเริ่มเผยแพร่ล่วงหน้าจะไม่โผล่ก่อนถึงวัน บทความที่ผ่านวันสิ้นสุดการเผยแพร่แล้วจะไม่โผล่อีก และบทความในระดับการเข้าถึงที่ผู้เข้าชมไม่มีสิทธิ์ (เช่น Registered) จะถูกซ่อน ตรวจตอนค้น จึงไม่ต้องมีงานเบื้องหลังเมื่อถึงวันที่กำหนด การอัปเกรดเพิ่ม 3 คอลัมน์ในตารางเอกสาร บทความที่ทำดัชนีไว้ก่อนอัปเกรดจะถูกมองเป็นสาธารณะและแสดงเสมอ จนกว่าจะ Rebuild ดัชนีหนึ่งครั้ง

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
