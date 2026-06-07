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
- 2026-06-07 下午:**V3.2 promote**(commit `8a8de67`,sw cache v6→v7)— 記帳系統最終定型

---

## 記帳系統(V3.2 — 2026-06-07 下午定型,集 30+ 輪迭代後)

### 整體版面(記帳 tab)
```
┌─ 💧 水費 list ──┐ ┌─ 戶 A 帳目卡 ──┐
│ 月份 / 戶A/戶B │ │ 房客面 + 房東面 │
│ (checkbox cell)│ │ 含 🖨 列印     │
└────────────────┘ └─────────────────┘
┌─ ⚡ 電費 list ──┐ ┌─ 戶 B 帳目卡 ──┐
│                │ │                 │
└────────────────┘ └─────────────────┘
```
- 寬 ≥ 540px 並排;手機堆疊單欄(`flex:1 1 280px;min-width:280px;flex-wrap:wrap`)

### 核心公式(全 unified sign convention)
```
本期應繳金額 = 應繳款 − 上期溢(缺)繳 + 調整
本期溢(缺)繳 = 實收款項 − 本期應繳金額
```
**符號統一:正 = 溢繳 / 多繳 / 綠;負 = 缺繳 / 少繳 / 紅**(全專案一致)

### 帳目卡分區(輸入欄分區,但列印時兩塊都給房客)
- 🔵 **應繳區(實心邊框)**
  - 📋 應繳明細(只列勾選未繳的)+ 🖨 列印按鈕
  - 應繳款 → 上期折抵(動態 ± 符號)→ 調整(動態 ± 符號)→ **本期應繳金額(大字 28px)**
- ⚪ **收款結算區(灰底虛線)**
  - 🧾 收款結算(列印時也會給房客)
  - 實收款項 input → 本期溢(缺)繳
- 🖨 **列印**:應繳區明細 + 收款結算(實收 + 本期溢/缺繳)全部出在通知單上,讓房客知道扣抵後還欠多少 / 或這期多匯了會折抵下期

### 動態加減符號顯示
- 上期 > 0(溢繳)→ 顯示「**−** 上期溢繳 X」(綠色,減少本期應繳)
- 上期 < 0(缺繳)→ 顯示「**+** 上期缺繳 |X|」(紅色,增加本期應繳)
- 調整 > 0 → 「**+** 調整」(補繳)
- 調整 < 0 → 「**−** 調整」(折讓)
- 0 → 隱藏值(只顯示 placeholder)

### 鎖定 / 編輯 機制(V3.2 加強)
- 💾 **儲存(寫入歷史)** 按鈕:直接寫入歷史 + 鎖定所有 input(不彈 confirm — z-index 衝突 + UX 直接)
- ✎ **編輯** 按鈕:解鎖
- 🆕 **新期** 按鈕(綠色,標題右上):一鍵清空 picker + 重置所有 input + 解鎖,從零開始新期
- **鎖定時擋什麼**:
  - 所有 input(上期 / 調整 / 實收 / 記帳日期 / 繳款日期)→ disabled + 灰底
  - 記帳 list 的 cell 切換 → 加 🔒 + 淡化 + cursor:not-allowed + onclick 改 toast 警告
  - 不擋:📋 歷史 / 🖨 列印 / picker 勾選(這些不影響該戶帳目)
- 狀態:`renthouseLocked{A,B}` localStorage(`'1'` = 鎖定)

### 套入(loadPeriodToCard)— 逐欄位比對版
1. 找歷史該筆(by settledAt)
2. 對 pickerSelection 每筆 reconcile:
   - 優先用 `billMonth` 找現在的 record(billMonth 是 user 信賴的 anchor)
   - billMonth 找不到 → fallback 用 id
   - 都找不到 → 警告「紀錄已刪」
3. 找到 record 後,**逐欄位 diff**:
   - 摘要四欄:月份 / 總額 / A額 / B額
   - 完整 record snapshot 逐 key diff(跳過 paid/paidA/paidB/id)
4. 寫回 localStorage:received / adjust / accountDate / payDate / unlock
5. 重建 picker(fixedPicker)寫入 `renthouseBalanceCheckedIds`
6. 有差異 → alert 列出每筆每欄差異 + toast 警告;全對 → toast 「X 筆對齊」

### Stale-While-Revalidate(SWR)載入
- `initApp` 重構成兩段:
  - **Phase 1**(同步,從 localStorage):立刻 render History / Overview / Tenants / Summary / restoreForm
  - **Phase 2**(背景,Promise.all 平行):fetch list / tenant_list / draft_load → 有差異才 re-render + toast「🔄 NAS 同步完成 (Xms)」
- **防止 cache 被清空**:API 回空陣列 + 本地有資料時不覆蓋,反過來 `dbSave(localData)` 把本地推回 DB
- 同樣模式套用在 `loadWaterRecords`

### localStorage keys(V3.2 完整版)
```
renthouseCarryover{A,B}      DEPRECATED (上期改從歷史抓,這個 key 留著只是 backward compat)
renthouseReceivedThis{A,B}   本期實收       number(Math.round)
renthouseAdjust{A,B}         調整          number,正=補/負=折(Math.round)
renthouseAccountDate{A,B}    記帳日期       'YYYY-MM-DD'(預設今天,寫入 localStorage 確保 rolloverPeriod 不卡)
renthousePayDate{A,B}        繳款日期       'YYYY-MM-DD'(可空)
renthouseLocked{A,B}         鎖定狀態       '1'=鎖,其他=可編輯
renthouseBalanceCheckedIds   BALANCE picker 選擇 [{table, id}]  ※區分 null(從未設定→fallback 最新) vs '[]'(刻意清空→尊重)
renthouseBalancePickerOpen   '0'/'1' 上方選擇面板摺疊狀態
renthousePaymentHistory      結算歷史 array
```

`renthousePaymentHistory` 每筆 schema:
```js
{
  unit: 'A' | 'B',
  settledAt: ISO timestamp (stable ID, never changes after first save),
  updatedAt: ISO timestamp (每次 update 都改),
  accountDate: 'YYYY-MM-DD' (期別 key,跟 unit 組合判斷 same period upsert),
  payDate: 'YYYY-MM-DD' | '',
  carryIn, received, adjust, carryOut, totalPaidDue,  // 全 Math.round
  paidItems: [{ type: '⚡電費'|'💧水費', billMonth, due }],
  pickerSelection: [{                                  // V3.2 加,套入時逐欄位比對用
    table, id, billMonth, totalBill, costA, costB,
    snapshot: { ...整筆 record deep-clone... }
  }]
}
```

### 核心 helper(全局,不再 inline)
```js
_getPrevFromHistory(unit, beforeDate)
  // 從 renthousePaymentHistory 找該戶 accountDate < beforeDate 的最近一筆 carryOut
  // 主卡 prevVal / rolloverPeriod carryIn / printInvoice 三處統一用

_snapshotPaidItems(unit)
  // 用 picker 選擇 + 該戶 cell 未結 過濾 = 該期該戶的應繳明細
  // 條件:due > 0 AND paidA/paidB < due (未結)
```

### 核心邏輯鏈(必須對齊,不然 history / print 會亂)
1. **picker = 本期單據定義**(BALANCE 區的 checkbox 選擇)
2. **cell 已結 = 該戶該筆已付**(記帳 list 裡的 A/B 切換 → 寫 paidA/paidB)
3. **三條鏈完全對齊**(picker + cell 已結 過濾):
   - 主卡 應繳明細 (`_unitAccount` `unpaidEntries = entries.filter(!done)`)
   - 儲存歷史 snapshot (`_snapshotPaidItems` 過濾 `unitPaid < due`)
   - 列印 invoice (`printInvoice` 過濾 `unitPaid < due`)
4. **歷史 snapshot 含 picker deep-clone**(套入時逐欄位比對 + 警告 mismatch)

### 列印房客通知(printInvoice)
- `window.open('', '_blank')` 開新分頁
- 生成 standalone HTML(含內嵌 CSS,字型 Microsoft JhengHei)
- 內容:標題 + 記帳日期 + 明細表(類型/月份/日期/應繳)+ 折抵/調整 + 大字本期應繳
- 自動 `window.print()`
- 只導出房客面內容(房東記帳數字不洩漏)

### 繳費歷史(可編輯)
- `viewPaymentHistory(unit)` → overlay modal
- 每筆 carryIn / received / adjust / carryOut / accountDate 全 inline input
- onchange 即觸發 `updatePaymentHistoryField(settledAt, field, val, asString?)`
- 刪除:🗑️ → `deletePaymentHistory(settledAt, unit)`(用 settledAt 而非 idx,避免錯位)

### 色彩識別(全專案統一)
| 角色 | 顏色 | hex | 用在 |
|-----|------|-----|------|
| 戶 A | 藍 | `#4A6FA5` | A 戶 帳目卡、checkbox label、cell 邊框 |
| 戶 B | 綠 | `#2E7D32` | B 戶 帳目卡、checkbox label、cell 邊框 |
| 水費 | 藍 | `#0284c7` | 水費區塊邊框/標題 |
| 電費 | 橘 | `#ea580c` | 電費區塊邊框/標題 |
| 未繳/缺繳 | 紅 | `#dc2626` | 缺繳 label、未繳 emphasis |
| 溢繳/完成 | 綠 | `#16a34a` | 溢繳 label、已收 input 邊框 |

> A 戶藍跟水費藍是不同色階(A 較深、水費較鮮),不會混淆。

### 記帳 cell 風格(水費/電費 list)
- 表頭:月份 | 戶 A | 戶 B(checkbox 完全 `display:none`,整個 cell 是 click target)
- **未繳**:識別色淡底 + 識別色實邊 + 微陰影(`box-shadow`,搶眼)
- **已繳**:白底 + 灰細邊 + 識別色字 + `text-decoration:underline` + ✓
- 月份欄底加日期副標(電費=`r.period`,水費=`fmtMinguo(r.readDate)`)

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
