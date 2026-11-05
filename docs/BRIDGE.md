# Smart Search bridge / ตัวส่งต่อจาก Smart Search เดิม

**ไทย** · [English](#english)

ปลั๊กอิน **System - Colorpack Smart Search bridge** ส่งการค้นหาที่ผ่าน Smart Search เดิมของ Joomla (`com_finder`) มายังหน้าค้นหาของเรา ใช้เมื่อช่องค้นหาผูกกับ `com_finder` ในตัว เช่น **ช่องค้นหาส่วนหัวของ YOOtheme** (Customizer → Header → Search) ที่ไม่ได้มาจากโมดูล

## ทำอะไรได้

- ผู้เข้าชมพิมพ์ในช่องค้นหาเดิม แล้วระบบพาไปหน้าผลลัพธ์ของเราพร้อมคำค้น
- ลิงก์เก่า `/component/finder/search?q=...` ก็ไปหน้าใหม่ด้วย
- ถ้าช่องเดิมมีคำแนะนำขณะพิมพ์ของ Smart Search ปลั๊กอินตอบด้วยชื่อบทความของเรา (ใช้เฉพาะดัชนีคำค้น ไม่เรียกบริการภายนอก)
- แสดงว่า **กำลังค้นหาอยู่** (แถบด้านบนและข้อความ "กำลังค้นหา…" ใต้ช่อง) ตั้งแต่ส่งฟอร์มค้นหาของเว็บ ปิดได้ที่ตัวเลือก *แสดงว่ากำลังค้นหาอยู่*
- ไม่แตะการโพสต์ฟอร์ม, OpenSearch, ฟีด และหน้าผู้ดูแล

## เปิดใช้

ติดตั้งแล้ว **ปิดอยู่** เพื่อไม่ให้เว็บเปลี่ยนพฤติกรรมเอง

1. ต้องมีเมนู **Colorpack Smart Search → Search page** แล้ว
2. System → Plugins ค้น "Smart Search bridge" → เปิด (Enabled) → ตั้งค่า (หน้าแสดงผลลัพธ์ ปล่อยอัตโนมัติได้)
3. ใช้ *302 ชั่วคราว* ระหว่างทดสอบ เมื่อพอใจเปลี่ยนเป็น *301 ถาวร*
4. ลองค้นจากไอคอนค้นหาของเว็บ ต้องมาที่หน้าค้นหาใหม่
5. ปิดกลับได้ทันทีที่ System → Plugins ถ้าต้องการกลับไปใช้ Smart Search เดิม

> ถ้าช่องค้นหาของเว็บเป็นโมดูล (วางตำแหน่งได้) อาจใช้ [โมดูลช่องค้นหา](MODULE.md) แทนก็ได้

---

<a id="english"></a>

## English

The **System - Colorpack Smart Search bridge** plugin sends searches made through Joomla's own Smart Search (`com_finder`) to our search page. Use it when a search box is hard-wired to `com_finder`, such as the **YOOtheme header search** (Customizer → Header → Search), which is not a module.

### What it does

- A visitor types in the existing search box and lands on our results page with the search term.
- Old links such as `/component/finder/search?q=...` go to the new page too.
- If the existing box has Smart Search's search-as-you-type, the plugin answers with our article titles (keyword index only, no external service).
- It shows that **a search is running** (a bar at the top and "Searching…" under the box) from the moment the site's search form is sent. Switch it off with the option *Show that a search is running*.
- It leaves form posts, OpenSearch, feeds and the administrator alone.

### Turn it on

It is installed **switched off**, so the site does not change by itself.

1. A **Colorpack Smart Search → Search page** menu item must exist.
2. System → Plugins, search for "Smart Search bridge", enable it, and open its settings (the results page can stay on Automatic).
3. Use *302 temporary* while testing, then switch to *301 permanent* when you are happy.
4. Search from the site's search icon: you should land on the new search page.
5. Switch it off again under System → Plugins at any time to go back to the old Smart Search.

> If your search box is a module (you can place it in a position), the [search box module](MODULE.md) is an alternative.
