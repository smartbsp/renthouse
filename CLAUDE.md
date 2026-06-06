# renthouse — PWA 租屋應用 (電費/水費/房客管理)

## 專案概覽
- **網址(production):** `https://www.lohastime.com.tw/renthouse/`
- **網址(dev sandbox):** `https://lohastime.com.tw/renthouse-dev/`
- **單檔架構:** 所有 HTML/CSS/JS 在 `index.html`(production)/ `index-dev.html`(沙盒,~2850 行)
- **後端:** `api.php` 連接 MySQL (march_2011 / lohas2529time*** / lohastime / localhost:3306)
- **SW:** `sw.js` v3, network-first for HTML, register with `?v=3`(dev sandbox 沒推 sw.js 避免迭代時被快取干擾)

## 部署
```
# Production(從本地 → NAS production)
scp index.html api.php sw.js icon-*.png manifest.json nas:/share/CACHEDEV1_DATA/Web/renthouse/
# 或雙擊「上傳NAS.bat」

# Dev sandbox
scp index-dev.html nas:/share/CACHEDEV1_DATA/Web/renthouse-dev/index.html
```
備份:`git tag v*.*` + NAS `/share/CACHEDEV1_DATA/Web/renthouse_backups/v*/`

## Promote 流程(dev → production)
1. 先備份 production 當前版本:`ssh nas "mkdir -p /share/CACHEDEV1_DATA/Web/renthouse_backups/pre_<ver>_promote_<ts> && cp /share/.../renthouse/{index.html,api.php,sw.js,manifest.json} <DEST>/"`
2. 本地 `cp index-dev.html index.html`
3. 升 `sw.js` 的 `CACHE_NAME`(`elec-calc-v<N>` → `v<N+1>`)
4. 升 index.html 的 `register('sw.js?v=<N>')` 對應數字
5. `scp index.html sw.js manifest.json icon-192.png icon-512.png` 到 NAS production
6. `git commit -m "promote V<ver> to production - sw.js cache bumped to v<N+1>"`
7. 拷貝到 Google Drive `G:\我的雲端硬碟\renthouse_backups\production_v<ver>_promoted_<date>/`
8. `curl https://www.lohastime.com.tw/renthouse/` 驗證 production 跟本地 byte-for-byte 一致

歷史 promote 紀錄:
- 2026-06-06 上午:V3.0 promote(commit `8f344c5`,sw cache v4→v5)
- 2026-06-06 晚上:V3.1 promote(commit `ddaff1e`,sw cache v5→v6)

---

## 資料庫 (MySQL: lohastime)

| 表 | 用途 |
|----|------|
| `renthouse_records` | 電費 |
| `renthouse_water_records` | 水費(2026-06 大幅擴充,見下面 schema) |
| `renthouse_tenants` | 房客(JSON blob,亦存 `waterBillingMode` 全域設定) |
| `renthouse_tenant_log` | 房客異動紀錄(目前 JS 沒寫入,搜尋功能空 — 待修) |
| `renthouse_drafts` | 電費草稿(自動暫存) |

### renthouse_water_records 擴充欄位(v3.0)
原本 11 欄,2026-06 加 9 欄:
```sql
ALTER TABLE renthouse_water_records ADD COLUMN
  wMeterPrev DECIMAL(12,3),    -- 主水表上期指針
  wMeterCurr DECIMAL(12,3),    -- 主水表本期指針
  wSubMeter  DECIMAL(12,3),    -- 公共分攤度數
  wWaterFee  DECIMAL(12,2),    -- 用水費(獨立於 baseFee)
  wExtraFee  DECIMAL(12,2),    -- 代徵費用
  readDateNext VARCHAR(20),    -- 下期抄表日
  mode VARCHAR(8),             -- 'head' 或 'meter'(per-record 凍結)
  costA DECIMAL(12,2),         -- 凍結的 A 戶應繳
  costB DECIMAL(12,2),         -- 凍結的 B 戶應繳
  paid TINYINT(1) DEFAULT 0;   -- 已收款 checkbox
```
此後新存的紀錄會帶完整輸入 + 凍結 cost,翻歷史不受目前模式切換影響。

---

## 頁面結構 (4 個頁籤)
1. **房客** — A戶(藍)/B戶(綠) 雙戶獨立管理
2. **電費** — 台電帳單 + 統合分電表(已不再有條列/雙欄切換)
3. **水費** — 兩種模式,有獨立子表(B 戶實測,A 戶推算)
4. **合計** — 財務彙總(production 還空,sandbox 有 `summary-dev.html` 雛形)

手機 scroll-snap 左右滑動切換,`localStorage.activeTab` 記憶頁籤。

---

## 房客系統(變動不大)
姓名*,身份證,電話,LineID,通訊地址,戶籍地址,簽約人☑,申請補貼☑,租約起訖日,搬入搬離日
- **鎖定機制:** `tenantLocked{A,B}`,編輯解鎖儲存後鎖回
- **日期同步:** 住戶一→同代表簽約人;住戶二+→同住戶一
- **空白名:** 儲存時自動清除
- **`waterBillingMode`** 也存在 tenants JSON blob 裡(全域水費模式預設)

---

## 電費系統(v3.0 統合)

### 欄位
帳單月份(唯讀,民國年月)、起訖日、總電費、公電費、底度費、計費度數、本/下次抄表日、A/B 分表上期/本期

### UI 已改:統合分電表(不再有條列/雙欄切換)
單張表格,4 列 4 欄(包含合計欄):
```
              | 戶A    | 戶B    | 合計
上期分表      | input  | input  | 自動加總
本期分表      | input  | input  | 自動加總
用電度數      | auto   | auto   | 自動加總 (綠底)
對帳⇨估算公電 | 帳單 - 分表 = 差 (橘底)
```
然後 📋 歷史總覽永遠可見(max-height 180px,顯示 ~6 期可捲動)、上一期/下一期 nav、暫存帳單 btn-row、試算結果。

### 計算邏輯
- 總用電度數 = 戶A + 戶B
- 每度單價 = (總電費 − 公電 − 底度) / 總度數
- 各戶電費 = (用電度數 × 單價) + 公電分攤(÷2)
- 本次抄表日 = 訖日+1,下次抄表日 = 本次抄表日+2月+1

### 試算結果有 📐 計算方式 黃色 banner
顯示完整推算式(公電+底度 → 每戶分攤 → 扣除公電 → 每度單價 → A/B 應繳)

### 上一期/下一期 nav
- 點 → 切換紀錄但**不捲動**畫面(`noScroll=true` 傳進 `editRecord`)
- 點歷史 ✎ 才捲到表單頂端(`noScroll=false`)
- 點歷史總覽列 → 載入紀錄 **不捲動** + 該列加紅底線高亮(`tr.editing-row` class)

### 已**移除**的按鈕(v3.0 簡化)
- ❌ 📄 匯入PDF(瀏覽器內 Tesseract.js 中文 OCR 不準)
- ❌ 📋 貼上(regex 對台電版型敏感,效果不穩)
- ❌ 💾 備份 / 📥 還原(資料在 NAS MySQL,沒必要再前端下載 JSON)
- ❌ 清除全部紀錄(破壞性按鈕誤觸風險)
- 對應的 JS 函式 + pdf.js / tesseract.js CDN 載入也全清

---

## 水費系統(v3.0 完整改寫)

### 兩種模式(per-record 凍結 + 同月可各存一筆)
| Icon | 模式名 | 值 |
|------|--------|-----|
| 👫 | **依戶別人數** | `head`(預設) |
| 🔢 | **依用水度數** | `meter`(分表計算) |

- 全域預設模式存在 `renthouse_tenants` JSON 的 `waterBillingMode`
- 編輯舊紀錄時,select 顯示該筆的 `mode`(隔離全域)
- 同月份**同模式** 才視為重複(觸發自製 modal);**不同模式** 可以共存
- 歷史可在同月份開兩筆並排(👫 + 🔢)比較金額,決定哪筆 ✓ 已收

### 預設值(v3.0)
- 基本費 `71.4`(台北市自來水基本月費)
- 代徵費用 `24`
- 兩個都在「🆕 清除」後會還原預設

### 自動帶值
從歷史最新一筆推算下一期:
- 計費起日 = 上一期訖日 + 1 日
- 上期指針(主表 + A/B 子表)= 上一期的本期讀數

### 自動連動 cascade
```
起日 → 訖日 (+2月 −2天)
訖日 → 帳單月份 (= 訖日月 + 1)  ← v3.0 新邏輯
訖日 → 本期抄表日 (= 訖日,不 +1)
本期抄表日 → 下期抄表日 (+2月 +3日)
```

### 計算公式(度數模式)
B 有獨立子表;A 用扣除法。基本費/代徵兩戶平分,公共分攤度數平分:

```
M  = 主表本期 − 主表上期      (兩戶共用)
S  = 公共分攤度數
Bu = B本期 − B上期            (子表直讀)
Au = M − Bu                   (A 推算)

A 計費度數 = Au + S/2
B 計費度數 = Bu + S/2

A 應繳 = 基本費/2 + 代徵/2 + 用水費 × (A度 / (A度+B度))
B 應繳 = 基本費/2 + 代徵/2 + 用水費 × (B度 / (A度+B度))
```
試算結果黃色 📐 計算方式 banner 顯示完整推算式。

### 計算公式(人頭模式)
```
總水費 × hA/(hA+hB)  (hA, hB = 各戶有姓名的住戶數)
```
代簽人不計。

### 用水度數異常警告
若 `本期 < 上期`(度數異常),欄位變紅底白字:`⚠ 本期<上期 差 X.X`

### 歷史過濾(月曆 widget)
- 民國年下拉(`民國 X 年`)+ ◀▶ 微調(wrap-around)+「全部」清空
- 12 月份按鈕 grid(選中藍底)
- 點 tab / refresh / 回到水費 tab → **篩選自動清空**回預設
- 排序:billMonth desc → readDate desc
- 抄表日顯示民國格式(`fmtMinguo`:`115/6/1`)

### 已收款 checkbox 欄
- 每筆有 ☐ checkbox → 勾選後整列變淡綠底
- API `?action=water_set_paid {id, paid}` 單欄位輕量更新
- 編輯時 paid 狀態保留

### 編輯狀態的按鈕組
```
[✏️ 修改] [➕ 另存新筆] [🗑️ 刪除] [🆕 清除]
```
另存新筆:取消 editing → 走「新增」路徑(重複偵測會檢查同月+同模式)

### 表單可儲存條件
不只看 totalBill > 0(預設 71.4+24=95 會自動觸發)→ 改成:
用水費 > 0 **或** 主表本期 > 0 **或** A本期 > 0 **或** B本期 > 0
避免純預設值不小心存進去。

---

## ⚠️ 編輯此專案注意事項

### DOM 定位
- **禁止用多重 `</div>` 當錨點!** 檔案有大量相似結構,誤配會破壞整個頁面
- **必須用獨特 id**(如 `editBtnB` / `mainCard` / `meterTable`)定位插入點
- 編輯前先 `grep` 確認 oldString 只有一處匹配

### HTML div 平衡 (v3.0 新增準則)
- **DOM 不平衡會讓 tab content 提早關閉,造成兄弟 tab 互相溢出**
- 大改 markup 後一定要 `awk + grep '<div\\|</div>' | uniq -c` 檢查平衡
- 例:本 session 抓到 batchCard 內有多一個 `</div>`,造成電費歷史溢出到水費 tab

### var 宣告位置 (v3.0 新增準則)
- 早期 init(switchTab / scroll handler)用到的 state,**一定要在 script 頂端宣告 var**
- 否則 `var x = []` 雖被 hoist 但值是 `undefined`,被先用就 `undefined.length` → 整個 script eval 中斷
- 本 session 抓到 `waterRecords` 在 switchTab 初次被 resetWaterFilters 用到時還沒 init

### scrollIntoView (v3.0 新增準則)
- 多個函式各自會 scrollIntoView,組合會「奇怪地一路捲到頁底」
- 改成接受 `noScroll` 參數的明確控制
- `calculate()` 內部的 result 捲動也要 forward 這個參數

### 修改策略
- 一次只改一個功能,測試後再改下一個
- 避免同時改 HTML + CSS + JS(無法定位問題)
- 大改前先存 git tag,DB 大改前 mysqldump

### SW 快取
- 上傳後瀏覽器可能顯示舊版 = SW 快取,非程式碼錯誤
- 診斷:換瀏覽器 / curl / 比對 MD5
- Dev sandbox 不推 sw.js(避免迭代時 cache 干擾)
- Production `sw.js` 版本號更新於 `register('sw.js?v=N')` + 檔內 `CACHE_NAME`(目前 `elec-calc-v4`)

### 自製 modal / toast(不用原生 alert/confirm)
- **`showToast(msg)`** 取代 alert
- **`customConfirm({title, message, okText, onConfirm})`** 取代 confirm
- 6 處原本 `confirm()` 都已換掉(電費刪除/清除批次/還原備份/水費刪除/月份重複)

### MySQL 操作
- **NAS 上有 credentials file**:`/share/CACHEDEV1_DATA/Web/.renthouse_db.cnf`(permission 600,user 是 march_2011),所有 mysql/mysqldump 操作都用 `--defaults-file=<path>`,**不要**再把密碼寫在 command line(避免 shell history / ps aux 洩漏)
  ```bash
  # 推薦:
  ssh nas "/usr/local/mariadb/bin/mysql --defaults-file=/share/CACHEDEV1_DATA/Web/.renthouse_db.cnf lohastime -e 'SELECT ...'"
  ssh nas "/usr/local/mariadb/bin/mysqldump --defaults-file=/share/CACHEDEV1_DATA/Web/.renthouse_db.cnf lohastime renthouse_water_records > backup.sql"
  ```
- 變更 schema 前先 `mysqldump` 備份到 NAS `renthouse_backups/`
- 重要紀錄前用 ON DUPLICATE KEY UPDATE 避免 race
- 字串欄位(billMonth、mode、readDate 等)在 water_list 不要被 `is_numeric` 誤轉 float(api.php 有 strFields whitelist)
- `api.php` 自己連 DB 還是用硬編碼密碼(legacy,獨立 binding 沒走 cnf)— 那邊只 PHP 內部用,不是 shell 操作
