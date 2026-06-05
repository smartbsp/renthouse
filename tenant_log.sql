CREATE TABLE IF NOT EXISTS renthouse_tenant_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  unit CHAR(1),
  tenant_index INT,
  tenant_name VARCHAR(100),
  data LONGTEXT,
  log_month VARCHAR(7),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_unit (unit),
  INDEX idx_name (tenant_name),
  INDEX idx_month (log_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
