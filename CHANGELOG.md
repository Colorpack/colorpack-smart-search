# Changelog / บันทึกการเปลี่ยนแปลง

## 0.15.0

- **EN** **Rules that say what a wish means.** On the Synonyms page, write a rule with `+` between its parts, for example `นมแม่ + เยอะ = เพิ่มน้ำนม, กระตุ้นน้ำนม`. It applies only when the search contains every part. The first part is the topic; the other parts are the wording of the wish, and they are replaced by the words on the right. So "อยากมีนมแม่เยอะ ๆ" is searched as "breast milk" + "increase the milk supply", and an article is no longer ranked first just because its title happens to contain "นมแม่" and "เยอะๆ". The Synonyms page's tester shows which rules fire.
- **EN** Only the rules that apply to the search are used when the precision mode checks an article, instead of every rule that merely contains one of its words.
- **ไทย** **กฎที่บอกว่าความต้องการหมายถึงอะไร** ในหน้าคำพ้อง เขียนกฎโดยคั่นส่วนต่าง ๆ ด้วย `+` เช่น `นมแม่ + เยอะ = เพิ่มน้ำนม, กระตุ้นน้ำนม` กฎจะทำงานเมื่อคำค้นมีครบทุกส่วน ส่วนแรกคือหัวข้อ ส่วนที่เหลือคือถ้อยคำของความต้องการ ซึ่งจะถูกแทนด้วยคำทางขวา "อยากมีนมแม่เยอะ ๆ" จึงถูกค้นเป็น "นมแม่" + "เพิ่มน้ำนม" และบทความจะไม่ขึ้นที่หนึ่งเพียงเพราะชื่อบังเอิญมีคำว่า "นมแม่" กับ "เยอะๆ" ช่องทดสอบในหน้าคำพ้องแสดงว่ากฎข้อไหนทำงาน
- **ไทย** โหมดความแม่นยำใช้เฉพาะกฎที่ตรงกับคำค้นนั้น ไม่ใช่ทุกกฎที่บังเอิญมีคำใดคำหนึ่งอยู่ในนั้น

## 0.14.0

- **EN** **Compound words are now recognised.** Thai word splitting cuts many everyday words into pieces (เบาหวาน becomes เบา + หวาน, อาบน้ำ becomes อา + บน้ำ), so an article about desserts could be found for "diabetes". The index now also keeps every pair of neighbouring words, and a search treats words typed together as one unit when the articles keep them together too. This is judged from your own articles, so no word list has to be kept by hand. **Press Rebuild / update index once after upgrading**; until then the search behaves as before.
- **EN** **Question wording is ignored**: "ทำไมถึง", "แบบไหน", "ยี่ห้อไหน", "ทำยังไง" and the like no longer count as topic words, so a long question such as "ทำไมถึงเป็นเบาหวานตอนท้อง" is searched by its topic (เบาหวาน, ท้อง). A search made only of such words finds nothing instead of a hundred random articles. In the precision mode, a question with four or more topic words now needs most of them, not all.
- **EN** New options **Categories left out of search** and **Articles left out of search** (Options > Search) for pages that are not content, such as Cookies, Privacy Policy, Contact or the Homepage. They are checked at search time and also hide those pages from the search-box suggestions.
- **EN** Fixed: adding a second group of equal words on the Synonyms page, or importing one, could stop with an error since 0.12.0. A group that already exists in another order is now refused with a message.
- **ไทย** **รู้จักคำประสมแล้ว** การตัดคำภาษาไทยตัดคำที่ใช้บ่อยออกเป็นชิ้น (เบาหวาน กลายเป็น เบา + หวาน, อาบน้ำ กลายเป็น อา + บน้ำ) ทำให้บทความเรื่องของหวานอาจโผล่เมื่อค้น "เบาหวาน" ตอนนี้ดัชนีเก็บคู่คำที่อยู่ติดกันด้วย และการค้นมองคำที่พิมพ์ติดกันเป็นหน่วยเดียวเมื่อบทความก็ใช้คำนั้นติดกัน ตัดสินจากบทความของเว็บเอง ไม่ต้องดูแลรายการคำเอง **หลังอัปเกรดให้กด Rebuild / update index หนึ่งครั้ง** ก่อนหน้านั้นการค้นทำงานเหมือนเดิม
- **ไทย** **ไม่นับคำถามเป็นหัวข้อ** คำอย่าง "ทำไมถึง" "แบบไหน" "ยี่ห้อไหน" "ทำยังไง" ไม่ถูกนับเป็นคำค้นหลัก ประโยคยาว เช่น "ทำไมถึงเป็นเบาหวานตอนท้อง" จึงค้นด้วยหัวข้อ (เบาหวาน ท้อง) คำค้นที่มีแต่คำแบบนี้จะไม่พบอะไร แทนที่จะได้บทความสุ่มร้อยเรื่อง ในโหมดความแม่นยำ คำถามที่มีคำหัวข้อตั้งแต่ 4 คำขึ้นไปต้องมีคำส่วนใหญ่ ไม่ต้องครบ
- **ไทย** เพิ่มตัวเลือก **หมวดที่ไม่นำมาค้น** และ **บทความที่ไม่นำมาค้น** (ตัวเลือก > Search) สำหรับหน้าที่ไม่ใช่เนื้อหา เช่น Cookies, Privacy Policy, ติดต่อ หรือ Homepage ตรวจตอนค้น และซ่อนหน้าเหล่านั้นจากคำแนะนำในช่องค้นหาด้วย
- **ไทย** แก้: ตั้งแต่ 0.12.0 การเพิ่มกลุ่มคำความหมายเท่ากันกลุ่มที่สองในหน้าคำพ้อง หรือนำเข้า อาจหยุดด้วยข้อผิดพลาด ตอนนี้กลุ่มที่มีอยู่แล้ว (แม้เรียงต่างกัน) จะถูกปฏิเสธพร้อมข้อความ

## 0.13.0

- **EN** **Protection** (new Options tab). A visitor who searches more than 90 times a minute sees a short "please wait" notice instead of results (the limit is changeable, 0 turns it off); the search-box suggestions and the click counter have their own limits too. Counters use a hash of the visitor's address, never the address itself, and any failure lets the visitor through, so the protection cannot take the search down. If the site is behind a proxy such as Cloudflare, name the header that carries the real address (Options > Protection). The upgrade adds one small table.
- **EN** Fixed: an article whose **category** was unpublished, trashed or restricted (or sat under such a category) could still appear in results. It is now hidden at search time, so nothing has to run when a category changes. On a multilingual site, only articles in the visitor's language (or in all languages) are shown; press Rebuild / update index once so each article's language is stored.
- **EN** The click counter ignores single clicks older than 90 days. 
- **ไทย** **การป้องกัน** (แท็บใหม่ในตัวเลือก) ผู้เข้าชมที่ค้นเกิน 90 ครั้งต่อนาทีจะเห็นข้อความขอให้รอสักครู่แทนผลลัพธ์ (ปรับได้ ใส่ 0 เพื่อปิด) ช่องแนะนำคำค้นและตัวนับคลิกก็มีเพดานของตัวเอง ตัวนับใช้แฮชของที่อยู่ผู้เข้าชม ไม่เก็บที่อยู่จริง และถ้าระบบป้องกันผิดพลาดจะปล่อยผ่าน จึงไม่ทำให้การค้นล่ม ถ้าเว็บอยู่หลังพร็อกซีอย่าง Cloudflare ให้ระบุส่วนหัวที่มีที่อยู่จริง (ตัวเลือก > การป้องกัน) การอัปเกรดเพิ่ม 1 ตารางเล็ก ๆ
- **ไทย** แก้: บทความที่อยู่ใน**หมวดที่ปิดเผยแพร่ ถูกลบ หรือจำกัดสิทธิ์** (หรืออยู่ใต้หมวดแบบนั้น) ยังโผล่ในผลลัพธ์ได้ ตอนนี้ถูกซ่อนตอนค้น ไม่ต้องรอให้มีงานรันเมื่อหมวดเปลี่ยน บนเว็บหลายภาษา จะแสดงเฉพาะบทความในภาษาของผู้เข้าชมหรือบทความที่ใช้ได้ทุกภาษา ให้กด Rebuild / update index หนึ่งครั้งเพื่อบันทึกภาษาของแต่ละบทความ
- **ไทย** ตัวนับคลิกข้ามคลิกครั้งเดียวที่เก่ากว่า 90 วัน 

## 0.12.0

- **EN** New **Synonyms** management page (Dashboard > Improve results > Synonyms), replacing the single text box in the options. Each rule is a row you can add, edit, switch off without deleting, search, filter by status or source, delete in bulk, import and export as text. Two kinds of rule: one-way ("when someone types X, also find Y and Z") and groups of words that mean the same. A tester shows which rules fire for a search and the words they add, and each rule shows how many searches of the last 30 days it applied to. Adding a rule for a phrase that already has one merges them. The dashboard's "add a synonym" for searches that found nothing writes here too. Your existing synonym text is moved into the table the first time you open the page; until then the search keeps using it, so an upgrade changes nothing by itself. The upgrade adds one table.
- **ไทย** เพิ่มหน้า **คำพ้อง** สำหรับจัดการโดยเฉพาะ (แดชบอร์ด > ปรับปรุงผลการค้น > คำพ้อง) แทนช่องข้อความช่องเดียวในตัวเลือก แต่ละกฎเป็นหนึ่งแถว เพิ่ม แก้ไข ปิดใช้โดยไม่ต้องลบ ค้นหา กรองตามสถานะหรือแหล่งที่มา ลบทีละหลายรายการ นำเข้าและส่งออกเป็นข้อความได้ กฎมี 2 แบบ คือทางเดียว ("เมื่อมีคนพิมพ์ X ให้หา Y และ Z ด้วย") และกลุ่มคำที่ความหมายเท่ากัน มีช่องทดสอบว่าคำค้นหนึ่งใช้กฎข้อไหนและเพิ่มคำอะไร และแสดงว่าแต่ละกฎถูกใช้กับการค้นกี่ครั้งใน 30 วัน ถ้าเพิ่มกฎของคำที่มีกฎอยู่แล้วจะรวมเข้าด้วยกัน ปุ่ม "เพิ่มคำพ้อง" ในแดชบอร์ดสำหรับคำที่ค้นแล้วไม่เจอก็เขียนมาที่นี่ คำพ้องเดิมจากช่องข้อความจะถูกย้ายเข้าตารางเมื่อเปิดหน้านี้ครั้งแรก ก่อนหน้านั้นระบบยังใช้ช่องข้อความเดิม การอัปเกรดจึงไม่เปลี่ยนอะไรเอง การอัปเกรดเพิ่ม 1 ตาราง

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
