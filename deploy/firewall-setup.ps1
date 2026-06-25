# ============================================================
# SEPJ Gabès — Windows Firewall Hardening Script
# File: deploy/firewall-setup.ps1
#
# RUN AS ADMINISTRATOR:
#   Right-click PowerShell → "Run as Administrator"
#   Set-ExecutionPolicy Bypass -Scope Process -Force
#   .\deploy\firewall-setup.ps1
#
# BEFORE RUNNING:
#   Set $ADMIN_IP below to your actual office/home IP address.
#   If you do not set this, RDP will be left unrestricted.
# ============================================================

# ── Your admin IP — CHANGE THIS ──────────────────────────────
$ADMIN_IP = "YOUR.ADMIN.IP.HERE"   # e.g. "197.0.12.45"
# ─────────────────────────────────────────────────────────────

Write-Host "=== SEPJ Gabès — Firewall Hardening ===" -ForegroundColor Cyan

# 1. Allow HTTP inbound (Cloudflare → server)
netsh advfirewall firewall add rule `
    name="SEPJ Allow HTTP" `
    protocol=TCP dir=in action=allow localport=80
Write-Host "[OK] HTTP (80) allowed." -ForegroundColor Green

# 2. Allow HTTPS inbound
netsh advfirewall firewall add rule `
    name="SEPJ Allow HTTPS" `
    protocol=TCP dir=in action=allow localport=443
Write-Host "[OK] HTTPS (443) allowed." -ForegroundColor Green

# 3. Block MySQL from external access (only localhost should connect)
netsh advfirewall firewall add rule `
    name="SEPJ Block MySQL External" `
    protocol=TCP dir=in action=block localport=3306
Write-Host "[OK] MySQL (3306) blocked from external." -ForegroundColor Green

# 4. Restrict RDP to admin IP only
if ($ADMIN_IP -ne "YOUR.ADMIN.IP.HERE") {
    netsh advfirewall firewall set rule `
        name="Remote Desktop - User Mode (TCP-In)" `
        new remoteip=$ADMIN_IP
    Write-Host "[OK] RDP restricted to $ADMIN_IP only." -ForegroundColor Green
} else {
    Write-Host "[WARN] ADMIN_IP not set — RDP remains unrestricted. Edit the script and re-run." -ForegroundColor Yellow
}

# 5. Enable firewall on all profiles with default deny inbound
netsh advfirewall set allprofiles firewallpolicy blockinbound,allowoutbound
Write-Host "[OK] Default policy: block inbound, allow outbound." -ForegroundColor Green

# 6. Verify
Write-Host "`n=== Current inbound rules (SEPJ) ===" -ForegroundColor Cyan
netsh advfirewall firewall show rule name=all dir=in | Select-String "SEPJ|Remote Desktop"

Write-Host "`nDone. Verify with: netsh advfirewall firewall show rule name=all dir=in" -ForegroundColor Cyan
