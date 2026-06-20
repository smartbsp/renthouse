// renthouse V3.4 wallet 邏輯 cross-test
// 不啟動 browser,純邏輯驗證,Node 直跑:  node test_wallet_logic.js
//
// 涵蓋:
//  1. balance event-sourcing 正確
//  2. 結算流程(自動分配 + 結算後 wallet)
//  3. 預繳 / 提款 行為
//  4. 編輯歷史(replay balance)
//  5. 刪除歷史(replay balance)
//  6. 折抵邏輯(本期實際應付)
//  7. 調整 (adjust) 號正確性
//  8. 邊界 case(空 history、缺繳累積、跨日期)

// ─────────────────────────────────────────────────────────
// 核心 helper(從 index-dev.html 抽出來,維持一致)
// ─────────────────────────────────────────────────────────

// 同日按 settledAt 時間排序(支援同日多次 💾)
function computeBalance(history, unit, beforeDate, excludeSettledAt) {
  return history.reduce(function(s, h) {
    if (h.unit !== unit) return s;
    if (excludeSettledAt && h.settledAt === excludeSettledAt) return s;
    if (beforeDate && h.accountDate) {
      if (h.accountDate > beforeDate) return s;
      if (h.accountDate === beforeDate && excludeSettledAt && h.settledAt > excludeSettledAt) return s;
    }
    var r = parseFloat(h.received) || 0;
    var a = parseFloat(h.adjust) || 0;
    var d = parseFloat(h.totalPaidDue) || 0;
    return s + r - d - a;
  }, 0);
}

// 帳目卡計算:given 當期 state,算出 actualPay (本期實際應付) + balanceAfter (結算後 wallet)
function calcPeriod(history, unit, accountDate, sumDue, adjust, received) {
  var balanceBefore = computeBalance(history, unit, accountDate, null);
  var dueThis = sumDue + adjust;
  var actualPay = Math.max(0, dueThis - balanceBefore);
  var balanceAfter = balanceBefore + received - dueThis;
  return { balanceBefore: balanceBefore, dueThis: dueThis, actualPay: actualPay, balanceAfter: balanceAfter };
}

// 模擬 💾 結算:寫 history record + 不再保留 wallet 狀態(state in event-sourced)
function saveSettle(history, unit, accountDate, picker, received, adjust, payDate) {
  var totalPaidDue = picker.reduce(function(s, p) { return s + (p.due || 0); }, 0);
  var carryOut = received - totalPaidDue - adjust;
  history.push({
    unit: unit,
    type: 'settle',
    settledAt: 'sa-' + history.length + '-' + Date.now(),
    accountDate: accountDate,
    payDate: payDate || '',
    received: received,
    adjust: adjust,
    totalPaidDue: totalPaidDue,
    carryOut: carryOut,
    paidItems: picker
  });
}

// 模擬 💰 預繳
function savePrepay(history, unit, accountDate, amount, note) {
  history.push({
    unit: unit,
    type: 'prepay',
    settledAt: 'sa-' + history.length + '-' + Date.now(),
    accountDate: accountDate,
    received: amount,
    adjust: 0,
    totalPaidDue: 0,
    paidItems: [],
    note: note || ''
  });
}

// 模擬 💸 提款
function saveWithdraw(history, unit, accountDate, amount, note) {
  history.push({
    unit: unit,
    type: 'withdraw',
    settledAt: 'sa-' + history.length + '-' + Date.now(),
    accountDate: accountDate,
    received: -amount,
    adjust: 0,
    totalPaidDue: 0,
    paidItems: [],
    note: note || ''
  });
}

// 模擬編輯 history record(預繳/提款/結算 都走這條;只改 received/adjust/accountDate)
function editRecord(history, settledAt, updates) {
  var rec = history.find(function(h) { return h.settledAt === settledAt; });
  if (!rec) throw new Error('record not found: ' + settledAt);
  Object.keys(updates).forEach(function(k) { rec[k] = updates[k]; });
}

// 模擬刪除 history record
function deleteRecord(history, settledAt) {
  var idx = history.findIndex(function(h) { return h.settledAt === settledAt; });
  if (idx < 0) throw new Error('record not found');
  history.splice(idx, 1);
}

// ─────────────────────────────────────────────────────────
// Test framework
// ─────────────────────────────────────────────────────────

var _pass = 0, _fail = 0, _failures = [];
function assert(name, actual, expected) {
  var ok = Math.abs(actual - expected) < 0.5;
  if (ok) { _pass++; console.log('  ✓ ' + name + ' = ' + actual); }
  else    { _fail++; _failures.push(name + ': expected ' + expected + ', got ' + actual); console.log('  ✗ ' + name + ' = ' + actual + ' (expected ' + expected + ')'); }
}
function section(title) { console.log('\n━━━ ' + title + ' ━━━'); }

// ─────────────────────────────────────────────────────────
// Scenarios
// ─────────────────────────────────────────────────────────

section('Test 1: 房客按時繳費(每月應繳 = 實收,balance 維持 0)');
{
  var h = [];
  saveSettle(h, 'A', '2026-01-15', [{ type: '⚡電費', billMonth: '115/01', due: 1000 }], 1000, 0, '2026-01-20');
  saveSettle(h, 'A', '2026-02-15', [{ type: '⚡電費', billMonth: '115/02', due: 1000 }], 1000, 0, '2026-02-20');
  saveSettle(h, 'A', '2026-03-15', [{ type: '⚡電費', billMonth: '115/03', due: 1000 }], 1000, 0, '2026-03-20');
  assert('全 3 期繳清後 balance', computeBalance(h, 'A'), 0);
  assert('2 月時的 balance(看 2 月之前)', computeBalance(h, 'A', '2026-02-15'), 0);
}

section('Test 2: 預繳一次,後續扣完');
{
  var h = [];
  savePrepay(h, 'A', '2026-01-01', 3000, '預繳 3 期');
  assert('預繳後 balance', computeBalance(h, 'A'), 3000);
  saveSettle(h, 'A', '2026-01-15', [{ type: '⚡電費', billMonth: '115/01', due: 1000 }], 0, 0);
  assert('1 月 settled (用 wallet) 後 balance', computeBalance(h, 'A'), 2000);
  saveSettle(h, 'A', '2026-02-15', [{ type: '⚡電費', billMonth: '115/02', due: 1000 }], 0, 0);
  assert('2 月 settled 後 balance', computeBalance(h, 'A'), 1000);
  saveSettle(h, 'A', '2026-03-15', [{ type: '⚡電費', billMonth: '115/03', due: 1000 }], 0, 0);
  assert('3 月 settled 後 balance', computeBalance(h, 'A'), 0);
  saveSettle(h, 'A', '2026-04-15', [{ type: '⚡電費', billMonth: '115/04', due: 1000 }], 0, 0);
  assert('4 月 settled(預繳用完)後 balance 變負', computeBalance(h, 'A'), -1000);
}

section('Test 3: 缺繳後補繳');
{
  var h = [];
  saveSettle(h, 'A', '2026-01-15', [{ type: '⚡電費', billMonth: '115/01', due: 1000 }], 500, 0);
  assert('1 月缺繳 500 後 balance', computeBalance(h, 'A'), -500);
  saveSettle(h, 'A', '2026-02-15', [{ type: '⚡電費', billMonth: '115/02', due: 1000 }], 1500, 0);
  assert('2 月補繳 + 本期 (1500) 後 balance', computeBalance(h, 'A'), 0);
}

section('Test 4: 預繳 + 提款');
{
  var h = [];
  savePrepay(h, 'A', '2026-01-01', 1000);
  saveWithdraw(h, 'A', '2026-01-05', 300, '退押金');
  assert('預繳 1000 後提款 300', computeBalance(h, 'A'), 700);
}

section('Test 5: 編輯歷史 → balance replay 自動重算');
{
  var h = [];
  saveSettle(h, 'A', '2026-01-15', [{ type: '⚡電費', billMonth: '115/01', due: 1000 }], 1000, 0);
  saveSettle(h, 'A', '2026-02-15', [{ type: '⚡電費', billMonth: '115/02', due: 1000 }], 1000, 0);
  assert('編輯前 balance', computeBalance(h, 'A'), 0);
  editRecord(h, h[0].settledAt, { received: 500 });
  assert('編輯 1 月實收 1000→500 後 balance', computeBalance(h, 'A'), -500);
  editRecord(h, h[0].settledAt, { received: 1200 });
  assert('再編輯 1 月實收 →1200 後 balance', computeBalance(h, 'A'), 200);
}

section('Test 6: 刪除歷史 → balance replay');
{
  var h = [];
  savePrepay(h, 'A', '2026-01-01', 2000);  // +2000 → bal 2000
  saveSettle(h, 'A', '2026-02-15', [{ type: '⚡電費', due: 1000 }], 0, 0);  // -1000 → bal 1000
  assert('預繳 2000 + 結算 1000 後 balance', computeBalance(h, 'A'), 1000);
  deleteRecord(h, h[0].settledAt);  // 刪掉預繳
  assert('刪掉預繳 → balance 變負', computeBalance(h, 'A'), -1000);
  // restore 用 push 又 push 回去(模擬還原)
  savePrepay(h, 'A', '2026-01-01', 2000);
  assert('重新 push 預繳 → balance 回 1000', computeBalance(h, 'A'), 1000);
}

section('Test 7: 折抵計算(本期實際應付)');
{
  var h = [];
  savePrepay(h, 'A', '2026-01-01', 1500);
  var r1 = calcPeriod(h, 'A', '2026-01-15', /*sumDue*/ 1000, /*adjust*/ 0, /*received*/ 0);
  assert('case1 balanceBefore', r1.balanceBefore, 1500);
  assert('case1 dueThis', r1.dueThis, 1000);
  assert('case1 actualPay (預繳夠抵)', r1.actualPay, 0);
  assert('case1 balanceAfter (剩 500)', r1.balanceAfter, 500);

  var h2 = [];
  savePrepay(h2, 'A', '2026-01-01', 500);
  var r2 = calcPeriod(h2, 'A', '2026-01-15', 1000, 0, 0);
  assert('case2 actualPay (預繳半抵)', r2.actualPay, 500);
  assert('case2 balanceAfter (扣完 -500)', r2.balanceAfter, -500);

  var h3 = [];
  saveSettle(h3, 'A', '2026-01-15', [{ type: '⚡電費', due: 1000 }], 700, 0);  // 缺 300
  var r3 = calcPeriod(h3, 'A', '2026-02-15', 1000, 0, 0);
  assert('case3 balanceBefore (上期缺 -300)', r3.balanceBefore, -300);
  assert('case3 actualPay (要補 1000+300)', r3.actualPay, 1300);
}

section('Test 8: adjust 符號正確性');
{
  var h = [];
  // adjust > 0 = 補繳(房客要多付) → balance 應減少
  saveSettle(h, 'A', '2026-01-15', [{ type: '⚡電費', due: 1000 }], 1000, /*adjust*/ 100);
  assert('adjust +100 (補繳) → balance 減 100', computeBalance(h, 'A'), -100);

  var h2 = [];
  // adjust < 0 = 折讓(房客少付) → balance 應增加
  saveSettle(h2, 'A', '2026-01-15', [{ type: '⚡電費', due: 1000 }], 1000, -100);
  assert('adjust -100 (折讓) → balance 增 100', computeBalance(h2, 'A'), 100);
}

section('Test 9: 邊界 - 空 history');
{
  var h = [];
  assert('空 history 的 balance', computeBalance(h, 'A'), 0);
  var r = calcPeriod(h, 'A', '2026-01-15', 1000, 0, 0);
  assert('空 history 下 actualPay = dueThis', r.actualPay, 1000);
  assert('空 history 下 balanceAfter = -dueThis', r.balanceAfter, -1000);
}

section('Test 10: excludeSettledAt 排除自己(編輯歷史用)');
{
  var h = [];
  saveSettle(h, 'A', '2026-01-15', [{ type: '⚡電費', due: 1000 }], 1500, 0);
  // 編輯 modal 計算 balanceBefore 時應該排除自己
  var beforeAll = computeBalance(h, 'A', '2026-02-01');  // 含 1 月
  var beforeSelf = computeBalance(h, 'A', '2026-02-01', h[0].settledAt);  // 排除 1 月
  assert('含自己的 balance', beforeAll, 500);
  assert('排除自己的 balance', beforeSelf, 0);
}

section('Test 11: 戶 A / B 完全獨立');
{
  var h = [];
  savePrepay(h, 'A', '2026-01-01', 1000);
  savePrepay(h, 'B', '2026-01-01', 2000);
  assert('A balance', computeBalance(h, 'A'), 1000);
  assert('B balance', computeBalance(h, 'B'), 2000);
}

// ─────────────────────────────────────────────────────────
// picker / BALANCE 對帳 helpers(從 index-dev.html 抽出)
// ─────────────────────────────────────────────────────────

// _itemDueA / _itemDueB:電費直讀 costA/costB,水費要 computeWaterShare(此 test 用 head 模式簡化:總 / 戶數)
function itemDueA(r) {
  if (r.kind === 'electric') return parseFloat(r.costA) || 0;
  // 水費 head 模式:總 / 2 (假設兩戶都有人)
  return (parseFloat(r.totalBill) || 0) / 2;
}
function itemDueB(r) {
  if (r.kind === 'electric') return parseFloat(r.costB) || 0;
  return (parseFloat(r.totalBill) || 0) / 2;
}

// _defaultUnpaidCheckedIds:scan paid=1 records,任一戶 partial 也算未繳清
function defaultUnpaidCheckedIds(records) {
  var ids = [];
  records.filter(function(r) { return parseInt(r.paid, 10) === 1; }).forEach(function(r) {
    var dueA = itemDueA(r);
    var dueB = itemDueB(r);
    var pA = parseFloat(r.paidA) || 0;
    var pB = parseFloat(r.paidB) || 0;
    if ((dueA > 0 && pA < dueA - 0.5) || (dueB > 0 && pB < dueB - 0.5)) {
      ids.push({ id: r.id });
    }
  });
  return ids;
}

// BALANCE 對帳 aggregate:given checkedIds + records → {dueA, dueB, dueA_gross, dueB_gross, billSum}
function aggregateBalance(records, checkedIds) {
  var billSum = 0, dueA = 0, dueB = 0, dueA_gross = 0, dueB_gross = 0;
  records.filter(function(r) {
    return checkedIds.some(function(c) { return String(c.id) === String(r.id); });
  }).forEach(function(r) {
    var a = itemDueA(r);
    var b = itemDueB(r);
    var pA = parseFloat(r.paidA) || 0;
    var pB = parseFloat(r.paidB) || 0;
    var t = parseFloat(r.totalBill) || 0;
    dueA += Math.max(0, a - pA);
    dueB += Math.max(0, b - pB);
    dueA_gross += a;
    dueB_gross += b;
    billSum += t;
  });
  return { dueA: dueA, dueB: dueB, dueA_gross: dueA_gross, dueB_gross: dueB_gross, billSum: billSum };
}

section('Test 13: picker 預設 = 所有未繳清(partial 也算)');
{
  var records = [
    // 全付清(paidA = costA, paidB = costB)→ 不應勾
    { id: '1', kind: 'electric', billMonth: '115/01', costA: 100, costB: 200, paidA: 100, paidB: 200, paid: 1 },
    // A 部分繳(50/100)→ 應勾
    { id: '2', kind: 'electric', billMonth: '115/02', costA: 100, costB: 200, paidA: 50, paidB: 200, paid: 1 },
    // 都沒付 → 應勾
    { id: '3', kind: 'electric', billMonth: '115/03', costA: 100, costB: 200, paidA: 0, paidB: 0, paid: 1 },
    // B 部分繳 → 應勾
    { id: '4', kind: 'electric', billMonth: '115/04', costA: 100, costB: 200, paidA: 100, paidB: 50, paid: 1 },
    // 未過帳(paid != 1)→ 不應勾
    { id: '5', kind: 'electric', billMonth: '115/05', costA: 100, costB: 200, paidA: 0, paidB: 0, paid: 0 }
  ];
  var ids = defaultUnpaidCheckedIds(records);
  assert('未繳清筆數', ids.length, 3);
  var idSet = ids.map(function(x) { return x.id; }).sort().join(',');
  if (idSet === '2,3,4') { _pass++; console.log('  ✓ ids = ["2","3","4"]'); }
  else { _fail++; _failures.push('picker ids 錯誤: 預期 2,3,4 實得 ' + idSet); console.log('  ✗ ids = ' + idSet + ' (預期 2,3,4)'); }
}

section('Test 14: BALANCE 對帳 aggregate (gross / net 拆分)');
{
  var records = [
    { id: '1', kind: 'electric', costA: 200, costB: 300, paidA: 0, paidB: 0, totalBill: 500, paid: 1 },
    { id: '2', kind: 'electric', costA: 100, costB: 400, paidA: 50, paidB: 400, totalBill: 500, paid: 1 }
  ];
  var checkedIds = [{ id: '1' }, { id: '2' }];
  var agg = aggregateBalance(records, checkedIds);
  assert('dueA_gross (全 A 應收)', agg.dueA_gross, 300);  // 200 + 100
  assert('dueB_gross (全 B 應收)', agg.dueB_gross, 700);  // 300 + 400
  assert('dueA (扣已收後)', agg.dueA, 250);                // (200-0) + (100-50)
  assert('dueB (扣已收後)', agg.dueB, 300);                // (300-0) + (400-400)
  assert('billSum', agg.billSum, 1000);                    // 500 + 500
  // 拆分檢核:dueA_gross + dueB_gross 應 = billSum
  assert('拆分檢核 (gross 對 billSum)', agg.dueA_gross + agg.dueB_gross, agg.billSum);
}

section('Test 15: BALANCE 對帳 整套(picker + wallet + 折抵)');
{
  // 場景:戶 A 有預繳 1000,本期 picker 選 2 筆,paidA = 300/500、paidB = 0/700
  var records = [
    { id: '1', kind: 'electric', costA: 500, costB: 700, paidA: 300, paidB: 0, totalBill: 1200, paid: 1 }
  ];
  var history = [];
  savePrepay(history, 'A', '2026-01-01', 1000);
  var checkedIds = defaultUnpaidCheckedIds(records);
  assert('未繳清筆數 (A 跟 B 都未付清)', checkedIds.length, 1);
  var agg = aggregateBalance(records, checkedIds);
  // A 戶 算 wallet + actualPay
  var balBeforeA = computeBalance(history, 'A', '2026-02-15');
  assert('A balanceBefore (預繳 1000)', balBeforeA, 1000);
  var dueA = agg.dueA;  // 應 = 500-300 = 200(扣已收後)
  assert('dueA net', dueA, 200);
  var adjA = 0;
  var dueThisA = dueA + adjA;  // 200
  var actualPayA = Math.max(0, dueThisA - balBeforeA);  // max(0, 200-1000) = 0
  assert('A 本期實際應付 (預繳夠抵)', actualPayA, 0);
  // 假設 A 不繳(received = 0),結算後 wallet = 1000 + 0 - 200 = 800
  var balAfterA = balBeforeA + 0 - dueThisA;
  assert('A balanceAfter (扣 200)', balAfterA, 800);

  // B 戶 沒有預繳,應收全付
  var balBeforeB = computeBalance(history, 'B', '2026-02-15');
  assert('B balanceBefore (沒預繳)', balBeforeB, 0);
  var dueB = agg.dueB;  // 700
  assert('dueB net', dueB, 700);
  var actualPayB = Math.max(0, dueB - balBeforeB);
  assert('B 本期實際應付', actualPayB, 700);
}

section('Test 16: 編輯歷史時 balanceBefore 排除自己');
{
  var h = [];
  saveSettle(h, 'A', '2026-01-15', [{ type: '⚡電費', due: 1000 }], 1200, 0);  // bal +200
  saveSettle(h, 'A', '2026-02-15', [{ type: '⚡電費', due: 1000 }], 1000, 0);  // bal 0
  // 編輯 2 月時的 balanceBefore(排除 2 月)
  var balBefore = computeBalance(h, 'A', '2026-02-15', h[1].settledAt);
  assert('編輯 2 月時 balanceBefore', balBefore, 200);
  // 確認 1 月不影響 編輯 2 月(因 1 月在更早)
  // 改 2 月 received → balance 應 replay
  editRecord(h, h[1].settledAt, { received: 1500 });
  assert('編輯 2 月實收 1000→1500 後 balance', computeBalance(h, 'A'), 700);
}

section('Test 17: 跨期 cell paidA 不影響 picker 對歷史的判讀');
{
  // 場景:cell paidA 已被某筆 auto-distribute 改了,但歷史紀錄該保留 sumDue 不變
  var records = [
    { id: '1', kind: 'electric', costA: 500, costB: 700, paidA: 500, paidB: 700, paid: 1 }
  ];
  // 歷史 record 凍結 sumDue,即使 cell 已改 paidA/B 也不該影響
  var historyRecord = {
    unit: 'A',
    settledAt: 'sa-frozen',
    accountDate: '2026-01-15',
    received: 500,
    adjust: 0,
    totalPaidDue: 500,  // 凍結
    paidItems: [{ type: '⚡電費', billMonth: '115/01', due: 500 }]
  };
  var balance = computeBalance([historyRecord], 'A');
  assert('歷史紀錄 frozen sumDue,balance 計算正確', balance, 0);  // 500 - 500 = 0
  // picker 顯示「已全收」(cell paidA = 500 = costA)
  var ids = defaultUnpaidCheckedIds(records);
  assert('picker 對應 cell 已全收 → 不勾', ids.length, 0);
}

section('Test 18: 結算後預繳 == 💾 後 top bar(預測 vs 實際 一致)');
{
  // 場景:A 有預繳 1000,本期 picker 應收 600;B 有缺繳 -300,本期應收 800
  var history = [];
  savePrepay(history, 'A', '2026-01-01', 1000);
  saveSettle(history, 'B', '2025-12-15', [{ type: '⚡電費', due: 1000 }], 700, 0);  // bal B = -300
  // 模擬當期(尚未 💾)
  var accountDateA = '2026-06-20', accountDateB = '2026-06-20';
  var balBeforeA = computeBalance(history, 'A', accountDateA);
  var balBeforeB = computeBalance(history, 'B', accountDateB);
  assert('A balanceBefore (預繳 1000)', balBeforeA, 1000);
  assert('B balanceBefore (缺繳 -300)', balBeforeB, -300);

  // BALANCE 對帳 表 預測:
  var dueA = 600, adjA = 0, paidA = 600;   // A 房客付剛好抵掉 (應繳 600,預繳 1000,實際應付 0,但他付了 600 → balance 多 600)
  var dueB = 800, adjB = 0, paidB = 1100;  // B 補上期 300 + 本期 800 = 1100
  var actualA = dueA + adjA;
  var actualB = dueB + adjB;
  var balAfterA_predicted = balBeforeA + paidA - actualA;  // 1000+600-600 = 1000
  var balAfterB_predicted = balBeforeB + paidB - actualB;  // -300+1100-800 = 0
  assert('預測 balAfterA', balAfterA_predicted, 1000);
  assert('預測 balAfterB', balAfterB_predicted, 0);
  var predictedSum = balAfterA_predicted + balAfterB_predicted;
  assert('預測 A+B', predictedSum, 1000);

  // 模擬 💾(寫 history)
  saveSettle(history, 'A', accountDateA, [{ type: '⚡電費', due: 600 }], paidA, adjA);
  saveSettle(history, 'B', accountDateB, [{ type: '⚡電費', due: 800 }], paidB, adjB);

  // 💾 後 top bar(no beforeDate filter)
  var topA = computeBalance(history, 'A');
  var topB = computeBalance(history, 'B');
  assert('💾 後 top bar A', topA, 1000);
  assert('💾 後 top bar B', topB, 0);
  var topSum = topA + topB;
  assert('💾 後 top bar A+B', topSum, 1000);

  // 關鍵:預測 == 實際
  assert('結算後預繳預測 = top bar 實際 (A)', balAfterA_predicted, topA);
  assert('結算後預繳預測 = top bar 實際 (B)', balAfterB_predicted, topB);
  assert('結算後預繳預測 = top bar 實際 (A+B)', predictedSum, topSum);
}

section('Test 19: 同 accountDate 下 balanceBefore convention(同日 inclusive,按 settledAt 排序)');
{
  var history = [];
  // 09:00 存 1 筆
  saveSettle(history, 'A', '2026-06-20', [{ type: '⚡電費', due: 1000 }], 1500, 0);  // bal +500
  var rec1 = history[0];
  // 14:00 再開新期 → balanceBefore 應該包含 09:00 那筆(同日 inclusive)
  var balBefore = computeBalance(history, 'A', '2026-06-20');
  assert('同日 record 已存(no exclude)→ 含進 balanceBefore', balBefore, 500);
  // top bar
  assert('top bar 一致', computeBalance(history, 'A'), 500);
  // ★ 預測 == top bar 一致 ✓

  // 模擬 14:00 存第 2 筆
  saveSettle(history, 'A', '2026-06-20', [{ type: '💧水費', due: 200 }], 0, 0);  // bal -200
  var rec2 = history[1];
  // 編輯 第 2 筆 → balanceBefore 應只含 第 1 筆(09:00),不含自己,不含 14:00 之後
  var balBeforeRec2 = computeBalance(history, 'A', '2026-06-20', rec2.settledAt);
  assert('編輯 rec2 時 balanceBefore (= rec1 net)', balBeforeRec2, 500);
  // 編輯 第 1 筆 → balanceBefore 不應含 rec2(同日但時間晚)
  var balBeforeRec1 = computeBalance(history, 'A', '2026-06-20', rec1.settledAt);
  assert('編輯 rec1 時 balanceBefore (rec2 同日但晚 → 排除)', balBeforeRec1, 0);
}
{
  var h = [];
  savePrepay(h, 'A', '2026-01-01', 5000);                                                   // +5000 → bal 5000
  saveSettle(h, 'A', '2026-01-15', [{ type: '⚡電費', due: 1000 }], 0, 0);                  // -1000 → bal 4000
  saveWithdraw(h, 'A', '2026-01-20', 500, '退一部分');                                       // -500 → bal 3500
  saveSettle(h, 'A', '2026-02-15', [{ type: '⚡電費', due: 1200 }], 0, 200);                // -1200 -200 → bal 2100
  saveSettle(h, 'A', '2026-03-15', [{ type: '💧水費', due: 800 }], 500, 0);                 // +500 -800 → bal 1800
  saveSettle(h, 'A', '2026-04-15', [{ type: '⚡電費', due: 2000 }], 0, -100);               // -2000 +100 → bal -100
  assert('複雜混合後 balance', computeBalance(h, 'A'), -100);
}

// ─────────────────────────────────────────────────────────
// Report
// ─────────────────────────────────────────────────────────

console.log('\n══════════════════════════════════════');
console.log('Total: ' + (_pass + _fail) + ' assertions');
console.log('  ✓ Pass:  ' + _pass);
console.log('  ✗ Fail:  ' + _fail);
if (_fail > 0) {
  console.log('\nFailures:');
  _failures.forEach(function(f) { console.log('  · ' + f); });
  process.exit(1);
}
console.log('\nAll tests passed ✓');
process.exit(0);
