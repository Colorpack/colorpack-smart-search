# Choose a Layout / เลือกเลย์เอาต์

**ไทย** · [English](#english)

สร้างเมนูชนิด **Colorpack Smart Search → Search page** ช่อง **Choose a Layout** จะมีให้เลือก:

| เลย์เอาต์ | ทำอะไร |
|---|---|
| Search page | ใช้ค่า *วิธีแสดงผลลัพธ์* ใน Options |
| List only / Cards only | แบบเดียวตายตัว ไม่มีปุ่มสลับให้ผู้เข้าชม |
| List + Cards switch (List first / Cards first) | ผู้เข้าชมสลับได้ |

ค่าที่เลือกในเมนูมีผลเหนือ Options จึงทำคนละเมนูคนละแบบได้

## สร้างเลย์เอาต์เอง (แบบ Joomla: template override)

1. สร้างโฟลเดอร์ `templates/<เทมเพลตของคุณ>/html/com_aisearch/search/`
2. วางไฟล์สองไฟล์ชื่อเดียวกัน เช่น `mylayout.php` และ `mylayout.xml`
3. `mylayout.xml` กำหนดชื่อที่แสดงในรายการ:

   ```xml
   <?xml version="1.0" encoding="utf-8"?>
   <metadata>
       <layout title="My layout">
           <message>คำอธิบายสั้น ๆ</message>
       </layout>
   </metadata>
   ```
4. เปิดเมนู กด **Select** ในช่อง *Choose a Layout* จะเห็น "My layout" (ถ้าไม่ขึ้นให้ล้างแคช)

`mylayout.php` คือหน้าเต็มหนึ่งหน้า ตัวแปรที่ใช้ได้:

* `$this->q`, `$this->items`, `$this->total`, `$this->start`, `$this->perPage`
* `$this->settings` (ค่าใน Options), `$this->link($item)`, `$this->imageUrl($item)`, `$this->excerpt($item)`, `$this->tagList($item)`
* เรียกส่วนมาตรฐานโดยไม่ต้องก๊อป: `echo $this->loadTemplate('list');` หรือ `'card'` หรือ `'pagination'`

ถ้าอยากแก้หน้าตาของส่วนที่มีอยู่ ให้ก๊อป `default_list.php`, `default_card.php`, `default_pagination.php` (หรือ `default.php`) จาก `components/com_aisearch/tmpl/search/` ไปไว้ในโฟลเดอร์ override เดียวกันแล้วแก้ที่ไฟล์ก๊อป การอัปเดตส่วนขยายจะไม่ทับไฟล์ของคุณ

---

<a id="english"></a>

## English

Create a menu item of type **Colorpack Smart Search → Search page**. The **Choose a Layout** list offers:

| Layout | What it does |
|---|---|
| Search page | Uses *How results are displayed* from Options |
| List only / Cards only | A fixed layout, no switch for visitors |
| List + Cards switch (List first / Cards first) | Visitors can switch |

The menu item choice wins over Options, so different menu items can show different layouts.

### Make your own layout (the Joomla way: a template override)

1. Create the folder `templates/<your-template>/html/com_aisearch/search/`.
2. Put two files there with the same name, e.g. `mylayout.php` and `mylayout.xml`.
3. `mylayout.xml` gives the name shown in the list (see the example above).
4. Open the menu item, press **Select** in *Choose a Layout*; "My layout" appears (clear the cache if it does not).

`mylayout.php` is a whole page. Available in it:

* `$this->q`, `$this->items`, `$this->total`, `$this->start`, `$this->perPage`
* `$this->settings` (Options), `$this->link($item)`, `$this->imageUrl($item)`, `$this->excerpt($item)`, `$this->tagList($item)`
* Re-use the standard parts without copying them: `echo $this->loadTemplate('list');`, `'card'`, `'pagination'`

To change only the look of an existing part, copy `default_list.php`, `default_card.php`, `default_pagination.php` (or `default.php`) from `components/com_aisearch/tmpl/search/` into the same override folder and edit the copy. Extension updates will not overwrite it.
