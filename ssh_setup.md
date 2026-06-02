# NAS / VM SSH 連線設定指引

本檔記錄本機（Windows）連到 NAS（QNAP, lohastime.com.tw）與 skywin VM 的設定，提供給任何 AI 助理（Claude Code / OpenCode / 其他）或新機器接手時直接複製設定。

---

## 一、現有連線

| Alias | Host | Port | User | 用途 |
|-------|------|------|------|------|
| `nas` | lohastime.com.tw | 8022 | admin | QNAP NAS 主機（跑 ai_trading 容器） |
| `vm`  | 192.168.0.103    | 22   | skywin | skywin 系統的 NAS VM |

兩者共用同一把 ed25519 私鑰，免密碼。

---

## 二、本機所需檔案

```
~/.ssh/
├── config                          # SSH 連線設定（含 alias）
├── skywin_nas_ed25519              # 私鑰（chmod 600）
├── skywin_nas_ed25519.pub          # 公鑰
└── known_hosts                     # 首次連線後自動寫入
```

Windows 路徑：`C:\Users\<user>\.ssh\`

### `~/.ssh/config` 完整內容

```
Host nas
  HostName lohastime.com.tw
  Port 8022
  User admin
  IdentityFile ~/.ssh/skywin_nas_ed25519
  IdentitiesOnly yes
  StrictHostKeyChecking accept-new

Host vm
  HostName 192.168.0.103
  Port 22
  User skywin
  IdentityFile ~/.ssh/skywin_nas_ed25519
  IdentitiesOnly yes
  StrictHostKeyChecking accept-new
```

---

## 三、常用指令

```bash
# 跑遠端命令
ssh nas "命令"
ssh vm  "命令"

# 推檔
scp 本機檔 nas:/share/CACHEDEV1_DATA/ai_trading_system/路徑/

# 拉檔
scp nas:/share/CACHEDEV1_DATA/ai_trading_system/路徑/檔案 本機路徑

# 子目錄遞迴
scp -r 本機資料夾 nas:/share/.../

# 跳板（少用）
ssh -J nas vm "命令"
```

---

## 四、NAS 容器拓撲（重要）

QNAP Container Station 並存兩個 Docker daemon：

| Daemon | Socket | 跑哪些容器 |
|--------|--------|-----------|
| 普通 docker | `/var/run/docker.sock` | `novnc-skywin` |
| **system-docker** | `/var/run/system-docker.sock` | **ai_trading**、daywin、wordpress、aicore、NotesStation3 |

**`ai_trading` 容器在 system-docker 上**。平常 `ssh nas "docker ps"` 看不到 ai_trading，要指定 socket：

```bash
SDOCKER='/share/CACHEDEV1_DATA/.qpkg/container-station/bin/docker -H unix:///var/run/system-docker.sock'

# 列容器
ssh nas "$SDOCKER ps"

# 進容器執行命令
ssh nas "$SDOCKER exec ai_trading <cmd>"

# 重啟
ssh nas "$SDOCKER restart ai_trading"

# 查 logs
ssh nas "$SDOCKER logs --tail 200 ai_trading"

# 看容器環境變數
ssh nas "$SDOCKER inspect ai_trading --format '{{range .Config.Env}}{{println .}}{{end}}'"
```

### 重要路徑

| 用途 | NAS host 路徑 | 容器內路徑 |
|------|-------------|----------|
| ai_trading 程式 | `/share/CACHEDEV1_DATA/ai_trading_system/` | `/app/` |
| daywin 程式 | `/share/CACHEDEV1_DATA/daywin/` | `/app/`（不同容器） |
| state.json | `/share/CACHEDEV1_DATA/ai_trading_system/data/state.json` | `/app/data/state.json` |
| Container Station docker binary | `/share/CACHEDEV1_DATA/.qpkg/container-station/bin/docker` | — |

---

## 五、若新機器接手（私鑰移轉）

從現有的開發機複製 3 個檔案到新機器的 `~/.ssh/`：

1. `config`
2. `skywin_nas_ed25519`（**私鑰，敏感**）
3. `skywin_nas_ed25519.pub`

**權限**：
- Linux/Mac：`chmod 600 ~/.ssh/skywin_nas_ed25519`
- Windows：右鍵 → 內容 → 安全性 → 只留下目前 User 與 SYSTEM 讀取權；或用 `icacls`：
  ```powershell
  icacls "$env:USERPROFILE\.ssh\skywin_nas_ed25519" /inheritance:r /grant:r "${env:USERNAME}:R"
  ```

**驗證**：
```bash
ssh nas "hostname && date"
# 預期輸出：DrYang464 + 台灣時間
```

若失敗常見原因：
- 私鑰權限太寬（OpenSSH 拒絕載入）→ 鎖權限
- NAS `authorized_keys` 未含對應公鑰 → 用既有機器 ssh 進去把 `.pub` 內容 append 到 `~/.ssh/authorized_keys`
- NAS 防火牆限制 IP → 從新 IP 登入 QNAP Web UI 解封

---

## 六、給 AI 助理的快速指南

如果你是接手這台機器的 AI 助理（Claude Code、OpenCode 或其他），預設這些已經就緒：

✅ 你可以直接跑：
```bash
ssh nas "命令"
scp 檔案 nas:/share/CACHEDEV1_DATA/ai_trading_system/路徑/
```
不需要任何認證設定（key 已配好）。

✅ 要操作 ai_trading 容器務必走 **system-docker socket**（見「四」段）。

❌ 不要：
- 不要在容器內 SSH 進其他機器（容器內沒有 SSH key）
- 不要把 SSH 私鑰 commit 進 git（已在 `.gitignore` 排除 `key/`，但 `~/.ssh/` 在 user home，commit 風險另外注意）
- 不要從本機推 `state.json`、`launcher_heartbeat.txt` 等 NAS 即時狀態檔（會覆寫 NAS 寫入端，見 `scripts/upload_to_qnap.py` 的 EXCLUDE 清單與 CLAUDE.md「upload_to_qnap.py 排除清單」段）

---

## 七、次要連線方式（paramiko + 密碼，不推薦）

`scripts/upload_to_qnap.py` 用 paramiko + `data/nas_config.json` 內的明文密碼登入。

- 缺點：明文密碼曾經洩漏進 git history（已記錄於記憶 `project_security_20260528.md`）
- 建議：終究改用本檔的 SSH key 方式，把 upload_to_qnap.py 改寫成 scp/rsync。目前還未動，但 ssh key 方式（本檔）才是主推路線。

---

## 八、failover 相關

本機四個 PowerShell/Python 腳本（`scripts/failover_*.ps1` + `scripts/nas_sync.py`）會用本檔的 SSH 設定做 NAS 故障切換：

- `nas_sync.py`：每 5 分鐘 scp 拉 NAS 的關鍵狀態到本機（供 failover 用）
- `setup_failover.ps1`：建立排程 + 桌面 3 個快捷方式
- `failover_status.ps1`：查 NAS/VM 連線狀態
- `failover_ai.ps1`：手動啟動本機 watchdog 接管交易

它們全都依賴 `ssh nas` / `scp nas:`，即靠本檔描述的設定運作。
