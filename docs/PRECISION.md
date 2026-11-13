# Precision mode / โหมดความแม่นยำ

## English

Precision mode (Options > Search, off by default) keeps only articles that are really about the search.

**Match levels** (best first)

1. The phrase, or all key words close together, is in the **title**.
2. It is in the **tags or category**, or the title has the words but far apart.
3. It is **discussed in the text** (more than once, or near the beginning).
4. It is only **mentioned**. Hidden unless you set "Show up to level" to 4.

Quantities with units (1 เดือน, 3 ขวบ, 2 years) are one unit, and the number must match. Very common words (in 40% or more of articles by default) count only as context, not as the topic. Synonyms count as the word.

**Use**

1. Upgrade, then press *Rebuild / update index* once.
2. Open *Compare search modes* on the dashboard and try your real searches.
3. When happy, switch Precision mode on in Options > Search.
4. Administrators can add `?explain=1` to a results URL (log in on the site itself, not only in the administrator) to see, in Thai, what the search did with the typed text and why each article is where it is: its level and reason, which of the five fields hold the whole search, and how many times each word appears in the article.

Not yet done (waiting for site-owner feedback): per-article topic and age data, a reranker, a "related articles" section.

## ไทย

โหมดความแม่นยำ (ตัวเลือก > Search, ปิดไว้เป็นค่าเริ่มต้น) เก็บเฉพาะบทความที่เกี่ยวกับคำค้นจริง

**ระดับความตรง** (ดีที่สุดก่อน)

1. วลี หรือคำสำคัญครบและอยู่ใกล้กัน อยู่ใน**ชื่อเรื่อง**
2. อยู่ใน**แท็กหรือหมวด** หรือชื่อเรื่องมีคำแต่อยู่ห่างกัน
3. **กล่าวถึงเป็นเรื่องเป็นราว**ในเนื้อหา (มากกว่าหนึ่งครั้ง หรืออยู่ต้นเรื่อง)
4. แค่**เอ่ยถึง** จะถูกซ่อน เว้นแต่ตั้ง "แสดงถึงระดับ" เป็น 4

ตัวเลขกับหน่วย (1 เดือน, 3 ขวบ, 2 ปี) ถือเป็นหน่วยเดียว และตัวเลขต้องตรง คำที่พบบ่อยมาก (ตั้งแต่ 40% ของบทความขึ้นไป) นับเป็นบริบท ไม่ใช่หัวข้อ คำพ้องความหมายนับเหมือนคำนั้น

**วิธีใช้**

1. อัปเกรด แล้วกด *Rebuild / update index* หนึ่งครั้ง
2. เปิด *เปรียบเทียบโหมดการค้น* ในแดชบอร์ด ลองคำค้นจริงของเว็บ
3. พอใจแล้วเปิดโหมดความแม่นยำที่ ตัวเลือก > Search
4. ผู้ดูแลเติม `?explain=1` ท้าย URL ผลลัพธ์ (ต้องล็อกอินที่หน้าเว็บ ไม่ใช่แค่หลังบ้าน) จะเห็นคำอธิบายภาษาไทยว่าระบบทำอะไรกับคำที่พิมพ์ และแต่ละบทความอยู่ตรงนั้นเพราะอะไร ทั้งระดับ เหตุผล ช่องที่เจอคำค้นครบ และจำนวนครั้งที่พบแต่ละคำ

ยังไม่ทำ (รอคำตอบจากเจ้าของเว็บ): ข้อมูลหัวข้อและช่วงอายุของแต่ละบทความ, reranker, ส่วน "บทความที่เกี่ยวข้อง"
